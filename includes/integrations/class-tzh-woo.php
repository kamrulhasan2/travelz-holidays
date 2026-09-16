<?php
/**
 * WooCommerce integration.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Takes a booking request through WooCommerce checkout.
 *
 * Every package is mirrored by one hidden WooCommerce product. The product is
 * never browsed, searched or bought on its own — it exists so that a booking
 * becomes a normal WooCommerce order, with the reporting, emails, taxes and
 * payment gateways a shop already has. The price on the product is a
 * placeholder; the payable amount always comes from TZH_Booking::quote(), which
 * is the one place in the plugin that does arithmetic on a party.
 */
class TZH_Woo {

	/**
	 * Meta on the product pointing back at its package.
	 */
	public const PRODUCT_LINK = '_tzh_package';

	/**
	 * Meta flag marking an order as containing a holiday package.
	 */
	public const ORDER_FLAG = '_tzh_booking';

	/**
	 * Key the booking travels under, in cart item data and order item meta.
	 */
	public const ITEM_KEY = '_tzh_booking';

	/**
	 * Whether WooCommerce is available.
	 */
	public static function active(): bool {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' );
	}

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		if ( ! self::active() ) {
			return;
		}

		add_filter( 'tzh_booking_redirect', array( $this, 'to_checkout' ), 10, 4 );

		add_action( 'save_post_' . TZH_Package::POST_TYPE, array( $this, 'sync' ), 20, 2 );
		add_action( 'trashed_post', array( $this, 'retire' ) );
		add_action( 'untrashed_post', array( $this, 'restore_product' ) );

		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'from_session' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_price' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'cart_details' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'cart_name' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_permalink', array( $this, 'cart_permalink' ), 10, 3 );

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'order_item' ), 10, 4 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'flag_order' ), 10, 2 );
	}

	/**
	 * Send a valid booking request to checkout.
	 *
	 * @param string               $url     Destination so far.
	 * @param TZH_Package          $package Package being booked.
	 * @param array<string, mixed> $party   Party being booked.
	 * @param array<string, mixed> $quote   Priced breakdown.
	 */
	public function to_checkout( string $url, TZH_Package $package, array $party, array $quote ): string {
		if ( '' !== $url || ! TZH_Settings::get( 'woo_checkout', true ) ) {
			return $url;
		}

		$product_id = $this->product_id( $package, true );
		$cart       = WC()->cart;

		if ( $product_id <= 0 || ! $cart ) {
			return $url;
		}

		/*
		 * A holiday is not a basket item: arriving at checkout with last
		 * month's abandoned package still in the cart would be quoted, taxed
		 * and charged alongside this one.
		 */
		$this->remove_bookings( $cart );

		$added = $cart->add_to_cart(
			$product_id,
			1,
			0,
			array(),
			array(
				self::ITEM_KEY => array(
					'package' => $package->id(),
					'party'   => $party,
					'quote'   => $quote,
				),
			)
		);

		if ( ! $added ) {
			return $url;
		}

		return wc_get_checkout_url();
	}

	/**
	 * Product ID mirroring a package, optionally creating it.
	 *
	 * @param TZH_Package $package Package to mirror.
	 * @param bool        $create  Whether to create the product when missing.
	 */
	public function product_id( TZH_Package $package, bool $create = false ): int {
		$stored  = (int) get_post_meta( $package->id(), TZH_Package::META_WC_PRODUCT, true );
		$product = $stored > 0 ? wc_get_product( $stored ) : null;

		if ( $product instanceof WC_Product ) {
			return $stored;
		}

		if ( ! $create ) {
			return 0;
		}

		return $this->sync( $package->id(), $package->post() );
	}

	/**
	 * Create or refresh the product behind a package.
	 *
	 * @param int     $post_id Package post ID.
	 * @param WP_Post $post    Package post.
	 *
	 * @return int Product ID, or 0 when nothing was written.
	 */
	public function sync( int $post_id, WP_Post $post ): int {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status ) {
			return 0;
		}

		$package = TZH_Package::from( $post );

		if ( ! $package ) {
			return 0;
		}

		$stored  = (int) get_post_meta( $post_id, TZH_Package::META_WC_PRODUCT, true );
		$product = $stored > 0 ? wc_get_product( $stored ) : null;

		if ( ! $product instanceof WC_Product ) {
			$product = new WC_Product_Simple();
		}

		$product->set_name( $package->full_title() );
		$product->set_status( 'publish' === $post->post_status ? 'publish' : 'private' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_virtual( true );
		$product->set_sold_individually( true );
		$product->set_short_description( $package->short_description() );
		$product->set_regular_price( (string) $package->price() );
		$product->set_price( (string) $package->price() );

		if ( $package->hero_id() > 0 ) {
			$product->set_image_id( $package->hero_id() );
		}

		$this->set_sku( $product, $package );

		$product->update_meta_data( self::PRODUCT_LINK, $post_id );

		$product_id = (int) $product->save();

		if ( $product_id > 0 ) {
			update_post_meta( $post_id, TZH_Package::META_WC_PRODUCT, $product_id );
		}

		return $product_id;
	}

	/**
	 * Hide the product when its package goes to the bin.
	 *
	 * @param int $post_id Post being trashed.
	 */
	public function retire( int $post_id ): void {
		$product = $this->product_for_post( $post_id );

		if ( $product ) {
			$product->set_status( 'draft' );
			$product->save();
		}
	}

	/**
	 * Bring the product back with its package.
	 *
	 * @param int $post_id Post being restored.
	 */
	public function restore_product( int $post_id ): void {
		$product = $this->product_for_post( $post_id );

		if ( $product ) {
			$product->set_status( 'publish' );
			$product->save();
		}
	}

	/**
	 * Put the booking back on the cart item after a page load.
	 *
	 * @param array<string, mixed> $item    Cart item.
	 * @param array<string, mixed> $session Stored cart item.
	 *
	 * @return array<string, mixed>
	 */
	public function from_session( $item, $session = array() ) {
		if ( is_array( $item ) && is_array( $session ) && isset( $session[ self::ITEM_KEY ] ) ) {
			$item[ self::ITEM_KEY ] = $session[ self::ITEM_KEY ];
		}

		return $item;
	}

	/**
	 * Price each booking line from its own quote.
	 *
	 * @param WC_Cart $cart Cart being totalled.
	 */
	public function apply_price( $cart ): void {
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		foreach ( $cart->get_cart() as $item ) {
			$booking = $item[ self::ITEM_KEY ] ?? null;

			if ( ! is_array( $booking ) || ! isset( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
				continue;
			}

			$total = (int) ( $booking['quote']['total'] ?? 0 );

			// The line is always one package for one party, so the whole quote
			// is the unit price; quantity is pinned to 1 on the product.
			$item['data']->set_price( (string) $total );
		}
	}

	/**
	 * Show the travel date and the party in the cart and at checkout.
	 *
	 * @param array<int, array{key: string, value: string}> $data Existing rows.
	 * @param array<string, mixed>                          $item Cart item.
	 *
	 * @return array<int, array{key: string, value: string}>
	 */
	public function cart_details( $data, $item = array() ) {
		$booking = is_array( $item ) ? ( $item[ self::ITEM_KEY ] ?? null ) : null;

		if ( ! is_array( $data ) || ! is_array( $booking ) ) {
			return $data;
		}

		foreach ( self::summary( (array) $booking ) as $label => $value ) {
			$data[] = array(
				'key'   => $label,
				'value' => $value,
			);
		}

		return $data;
	}

	/**
	 * Name the cart line after the package, linked back to it.
	 *
	 * @param string               $name Current name markup.
	 * @param array<string, mixed> $item Cart item.
	 * @param string               $key  Cart item key.
	 */
	public function cart_name( $name, $item = array(), $key = '' ) {
		unset( $key );

		$package = is_array( $item ) ? $this->package_of( $item ) : null;

		if ( ! $package ) {
			return $name;
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( (string) get_permalink( $package->id() ) ),
			esc_html( $package->full_title() )
		);
	}

	/**
	 * Point the cart thumbnail at the package, not the hidden product.
	 *
	 * @param string               $permalink Current link.
	 * @param array<string, mixed> $item      Cart item.
	 * @param string               $key       Cart item key.
	 */
	public function cart_permalink( $permalink, $item = array(), $key = '' ) {
		unset( $key );

		$package = is_array( $item ) ? $this->package_of( $item ) : null;

		return $package ? (string) get_permalink( $package->id() ) : $permalink;
	}

	/**
	 * Copy the booking onto the order line.
	 *
	 * @param WC_Order_Item_Product $item    Order line being created.
	 * @param string                $key     Cart item key.
	 * @param array<string, mixed>  $values  Cart item.
	 * @param WC_Order              $order   Order being created.
	 */
	public function order_item( $item, $key, $values, $order ): void {
		unset( $key, $order );

		$booking = $values[ self::ITEM_KEY ] ?? null;

		if ( ! is_array( $booking ) || ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		// Readable rows first: these are what a shop manager reads on the order
		// screen and what the customer sees on the confirmation email.
		foreach ( self::summary( $booking ) as $label => $value ) {
			$item->add_meta_data( $label, $value, true );
		}

		// Then the machine-readable copy, hidden by its leading underscore.
		$item->add_meta_data( self::ITEM_KEY, $booking, true );

		$package = TZH_Package::from( (int) ( $booking['package'] ?? 0 ) );

		if ( $package ) {
			$item->set_name( $package->full_title() );
		}
	}

	/**
	 * Mark an order that carries a package, so the Bookings screen can find it.
	 *
	 * @param WC_Order             $order Order being created.
	 * @param array<string, mixed> $data  Posted checkout data.
	 */
	public function flag_order( $order, $data = array() ): void {
		unset( $data );

		if ( ! $order instanceof WC_Order || ! WC()->cart ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( isset( $item[ self::ITEM_KEY ] ) ) {
				$order->update_meta_data( self::ORDER_FLAG, 'yes' );

				return;
			}
		}
	}

	/**
	 * The rows describing a booking, label => value.
	 *
	 * @param array<string, mixed> $booking Stored booking.
	 *
	 * @return array<string, string>
	 */
	public static function summary( array $booking ): array {
		$party = (array) ( $booking['party'] ?? array() );
		$quote = (array) ( $booking['quote'] ?? array() );
		$rows  = array();

		$date = TZH_Booking::date_label( (string) ( $party['date'] ?? '' ) );

		if ( '' !== $date ) {
			$rows[ __( 'Travel date', 'travelz-holidays' ) ] = $date;
		}

		$people = array();

		foreach ( (array) ( $quote['lines'] ?? array() ) as $line ) {
			if ( (int) ( $line['count'] ?? 0 ) > 0 ) {
				$people[] = (string) $line['label'];
			}
		}

		if ( $people ) {
			$rows[ __( 'Travelers', 'travelz-holidays' ) ] = implode( ', ', $people );
		}

		$package = TZH_Package::from( (int) ( $booking['package'] ?? 0 ) );

		if ( $package && '' !== $package->code() ) {
			$rows[ __( 'Package code', 'travelz-holidays' ) ] = $package->code();
		}

		return $rows;
	}

	/**
	 * Package behind a cart item, when there is one.
	 *
	 * @param array<string, mixed> $item Cart item.
	 */
	private function package_of( array $item ): ?TZH_Package {
		$booking = $item[ self::ITEM_KEY ] ?? null;

		if ( ! is_array( $booking ) ) {
			return null;
		}

		return TZH_Package::from( (int) ( $booking['package'] ?? 0 ) );
	}

	/**
	 * Drop any package lines already in the cart.
	 *
	 * @param WC_Cart $cart Cart to clean.
	 */
	private function remove_bookings( $cart ): void {
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		foreach ( $cart->get_cart() as $key => $item ) {
			if ( isset( $item[ self::ITEM_KEY ] ) ) {
				$cart->remove_cart_item( $key );
			}
		}
	}

	/**
	 * Product mirroring a package post, when one exists.
	 *
	 * @param int $post_id Package post ID.
	 */
	private function product_for_post( int $post_id ): ?WC_Product {
		if ( TZH_Package::POST_TYPE !== get_post_type( $post_id ) ) {
			return null;
		}

		$product = wc_get_product( (int) get_post_meta( $post_id, TZH_Package::META_WC_PRODUCT, true ) );

		return $product instanceof WC_Product ? $product : null;
	}

	/**
	 * Give the product a stable SKU without colliding with the shop's own.
	 *
	 * @param WC_Product  $product Product being saved.
	 * @param TZH_Package $package Package it mirrors.
	 */
	private function set_sku( WC_Product $product, TZH_Package $package ): void {
		$code = str_replace( ' ', '-', trim( $package->code() ) );
		$sku  = '' === $code ? 'TZH-' . $package->id() : $code;

		if ( $sku === $product->get_sku() ) {
			return;
		}

		try {
			$product->set_sku( $sku );
		} catch ( Exception $error ) {
			unset( $error );

			// Another product already answers to this code. The package ID is
			// unique by construction, so it is the safe second choice.
			try {
				$product->set_sku( 'TZH-' . $package->id() );
			} catch ( Exception $ignored ) {
				unset( $ignored );
			}
		}
	}
}

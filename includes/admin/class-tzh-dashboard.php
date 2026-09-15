<?php
/**
 * Dashboard data.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gathers what a travel desk actually needs to see first thing in the morning.
 *
 * Which departures are coming up, what was booked recently, and which packages
 * are not fit to sell yet. Everything here is either a number someone acts on
 * or a link to the screen where they act on it; a green tick beside a line that
 * is always green is not information.
 *
 * The WooCommerce side is scanned once and cached for a few minutes, because a
 * dashboard is refreshed far more often than an order is placed.
 */
class TZH_Dashboard {

	/**
	 * How far ahead a departure counts as upcoming.
	 */
	private const HORIZON_DAYS = 60;

	/**
	 * How many orders back the booking scan looks.
	 */
	private const SCAN_ORDERS = 200;

	/**
	 * How long the booking scan is reused.
	 */
	private const CACHE_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Catalogue scan, cached for this request.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $catalogue = null;

	/**
	 * Render the screen.
	 */
	public function render(): void {
		tzh_admin_view(
			'dashboard',
			array(
				'tiles'        => $this->tiles(),
				'warnings'     => $this->warnings(),
				'departures'   => $this->departures(),
				'recent'       => $this->recent_bookings(),
				'attention'    => $this->attention(),
				'destinations' => $this->destinations(),
				'woo'          => TZH_Woo::active(),
			)
		);
	}

	/**
	 * The numbers along the top.
	 *
	 * @return array<int, array{label: string, value: string, url: string, note: string}>
	 */
	public function tiles(): array {
		$counts   = wp_count_posts( TZH_Package::POST_TYPE );
		$bookings = $this->bookings();

		$tiles = array(
			array(
				'label' => __( 'Published packages', 'travelz-holidays' ),
				'value' => number_format_i18n( (int) ( $counts->publish ?? 0 ) ),
				'url'   => admin_url( 'edit.php?post_type=' . TZH_Package::POST_TYPE ),
				'note'  => sprintf(
					/* translators: %s: number of drafts */
					_n( '%s draft waiting', '%s drafts waiting', (int) ( $counts->draft ?? 0 ), 'travelz-holidays' ),
					number_format_i18n( (int) ( $counts->draft ?? 0 ) )
				),
			),
			array(
				'label' => __( 'Destinations', 'travelz-holidays' ),
				'value' => number_format_i18n(
					(int) wp_count_terms(
						array(
							'taxonomy'   => TZH_Package::TAX_DESTINATION,
							'hide_empty' => false,
						)
					)
				),
				'url'   => admin_url( 'edit-tags.php?taxonomy=' . TZH_Package::TAX_DESTINATION . '&post_type=' . TZH_Package::POST_TYPE ),
				'note'  => sprintf(
					/* translators: %s: number of tour groups */
					_n( '%s tour group', '%s tour groups', $this->group_count(), 'travelz-holidays' ),
					number_format_i18n( $this->group_count() )
				),
			),
		);

		if ( ! TZH_Woo::active() ) {
			return $tiles;
		}

		$tiles[] = array(
			'label' => __( 'Bookings (30 days)', 'travelz-holidays' ),
			'value' => number_format_i18n( $bookings['recent_count'] ),
			'url'   => tzh_admin_url( TZH_Bookings_Page::SLUG ),
			'note'  => sprintf(
				/* translators: %s: number of travellers */
				_n( '%s traveler', '%s travelers', $bookings['recent_travelers'], 'travelz-holidays' ),
				number_format_i18n( $bookings['recent_travelers'] )
			),
		);

		$tiles[] = array(
			'label' => __( 'Booked value (30 days)', 'travelz-holidays' ),
			'value' => tzh_price( $bookings['recent_value'] ),
			'url'   => tzh_admin_url( TZH_Bookings_Page::SLUG ),
			'note'  => sprintf(
				/* translators: %s: number of departures */
				_n( '%s departure ahead', '%s departures ahead', count( $bookings['departures'] ), 'travelz-holidays' ),
				number_format_i18n( count( $bookings['departures'] ) )
			),
		);

		return $tiles;
	}

	/**
	 * Things that are actually wrong, and nothing else.
	 *
	 * @return array<int, array{text: string, url: string, label: string}>
	 */
	public function warnings(): array {
		$warnings = array();

		if ( ! get_option( 'permalink_structure' ) ) {
			$warnings[] = array(
				'text'  => __( 'Plain permalinks are switched on. Package and destination URLs will not work until pretty permalinks are enabled.', 'travelz-holidays' ),
				'url'   => admin_url( 'options-permalink.php' ),
				'label' => __( 'Open Permalinks', 'travelz-holidays' ),
			);
		}

		if ( '' === trim( (string) TZH_Settings::get( 'whatsapp', '' ) ) ) {
			$warnings[] = array(
				'text'  => __( 'No WhatsApp number is set, so the enquiry button is hidden on every package.', 'travelz-holidays' ),
				'url'   => tzh_admin_url( 'travelz-holidays-settings' ),
				'label' => __( 'Add a number', 'travelz-holidays' ),
			);
		}

		if ( TZH_Settings::get( 'woo_checkout', true ) && ! TZH_Woo::active() ) {
			$warnings[] = array(
				'text'  => __( 'Bookings are set to go through WooCommerce checkout, but WooCommerce is not active. Travelers are being given a reference to follow up instead.', 'travelz-holidays' ),
				'url'   => admin_url( 'plugins.php' ),
				'label' => __( 'Open Plugins', 'travelz-holidays' ),
			);
		}

		return $warnings;
	}

	/**
	 * Departures coming up, soonest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function departures(): array {
		return array_slice( $this->bookings()['departures'], 0, 8 );
	}

	/**
	 * The latest bookings, newest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function recent_bookings(): array {
		return array_slice( $this->bookings()['recent'], 0, 5 );
	}

	/**
	 * Packages that are not ready to sell.
	 *
	 * @return array<int, array{title: string, url: string, issues: string[]}>
	 */
	public function attention(): array {
		return array_slice( $this->catalogue()['attention'], 0, 8 );
	}

	/**
	 * Destinations with how much they hold and what they start at.
	 *
	 * @return array<int, array{name: string, count: int, from: int, url: string}>
	 */
	public function destinations(): array {
		return $this->catalogue()['destinations'];
	}

	/**
	 * How many tour groups exist.
	 */
	private function group_count(): int {
		return (int) wp_count_terms(
			array(
				'taxonomy'   => TZH_Package::TAX_FAMILY,
				'hide_empty' => false,
			)
		);
	}

	/**
	 * Walk the catalogue once and answer everything that depends on it.
	 *
	 * @return array{attention: array<int, array<string, mixed>>, destinations: array<int, array<string, mixed>>}
	 */
	private function catalogue(): array {
		if ( null !== $this->catalogue ) {
			return $this->catalogue;
		}

		$ids = get_posts(
			array(
				'post_type'        => TZH_Package::POST_TYPE,
				'post_status'      => array( 'publish', 'draft', 'pending' ),
				'numberposts'      => 300,
				'fields'           => 'ids',
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		// One round trip for every package's meta, instead of one per field.
		update_meta_cache( 'post', $ids );

		$attention = array();
		$by_term   = array();

		foreach ( $ids as $id ) {
			$package = TZH_Package::from( $id );

			if ( ! $package ) {
				continue;
			}

			$issues = $this->issues( $package );

			if ( $issues ) {
				$attention[] = array(
					'title'  => get_the_title( $id ),
					'url'    => (string) get_edit_post_link( $id ),
					'issues' => $issues,
				);
			}

			$term = $package->destination();

			if ( ! $term instanceof WP_Term || 'publish' !== get_post_status( $id ) ) {
				continue;
			}

			if ( ! isset( $by_term[ $term->term_id ] ) ) {
				$by_term[ $term->term_id ] = array(
					'name'  => $term->name,
					'count' => 0,
					'from'  => 0,
					'url'   => admin_url( 'edit.php?post_type=' . TZH_Package::POST_TYPE . '&' . TZH_Package::TAX_DESTINATION . '=' . $term->slug ),
				);
			}

			++$by_term[ $term->term_id ]['count'];

			$price = $package->price();

			if ( $price > 0 && ( 0 === $by_term[ $term->term_id ]['from'] || $price < $by_term[ $term->term_id ]['from'] ) ) {
				$by_term[ $term->term_id ]['from'] = $price;
			}
		}

		usort(
			$by_term,
			static function ( array $a, array $b ): int {
				return $b['count'] <=> $a['count'];
			}
		);

		$this->catalogue = array(
			'attention'    => $attention,
			'destinations' => array_values( $by_term ),
		);

		return $this->catalogue;
	}

	/**
	 * What is missing from a package, in plain words.
	 *
	 * @param TZH_Package $package Package to check.
	 *
	 * @return string[]
	 */
	private function issues( TZH_Package $package ): array {
		$issues = array();

		if ( $package->price() <= 0 ) {
			$issues[] = __( 'no price', 'travelz-holidays' );
		}

		if ( ! $package->destination() ) {
			$issues[] = __( 'no destination', 'travelz-holidays' );
		}

		if ( ! $package->tier() ) {
			$issues[] = __( 'no category', 'travelz-holidays' );
		}

		if ( '' === trim( $package->code() ) ) {
			$issues[] = __( 'no package code', 'travelz-holidays' );
		}

		if ( $package->days() <= 0 ) {
			$issues[] = __( 'no duration', 'travelz-holidays' );
		}

		if ( ! $package->itinerary() ) {
			$issues[] = __( 'no itinerary', 'travelz-holidays' );
		}

		if ( $package->hero_id() <= 0 ) {
			$issues[] = __( 'no banner image', 'travelz-holidays' );
		}

		return $issues;
	}

	/**
	 * Read the booking orders, once per five minutes.
	 *
	 * @return array{departures: array<int, array<string, mixed>>, recent: array<int, array<string, mixed>>, recent_count: int, recent_travelers: int, recent_value: int}
	 */
	private function bookings(): array {
		$empty = array(
			'departures'       => array(),
			'recent'           => array(),
			'recent_count'     => 0,
			'recent_travelers' => 0,
			'recent_value'     => 0,
		);

		if ( ! TZH_Woo::active() ) {
			return $empty;
		}

		$cached = get_transient( 'tzh_dashboard_bookings' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => self::SCAN_ORDERS,
				'orderby'    => 'date',
				'order'      => 'DESC',
				'status'     => array_diff(
					array_keys( wc_get_order_statuses() ),
					array( 'wc-cancelled', 'wc-refunded', 'wc-failed' )
				),
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => TZH_Woo::ORDER_FLAG,
						'value' => 'yes',
					),
				),
			)
		);

		$result  = $empty;
		$today   = wp_date( 'Y-m-d' );
		$horizon = wp_date( 'Y-m-d', time() + ( self::HORIZON_DAYS * DAY_IN_SECONDS ) );
		$cutoff  = time() - ( 30 * DAY_IN_SECONDS );

		foreach ( (array) $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$created = $order->get_date_created();
			$is_new  = $created && $created->getTimestamp() >= $cutoff;

			foreach ( $order->get_items() as $item ) {
				$booking = $item->get_meta( TZH_Woo::ITEM_KEY, true );

				if ( ! is_array( $booking ) ) {
					continue;
				}

				$package   = TZH_Package::from( (int) ( $booking['package'] ?? 0 ) );
				$party     = (array) ( $booking['party'] ?? array() );
				$quote     = (array) ( $booking['quote'] ?? array() );
				$travelers = (int) ( $quote['travelers'] ?? 0 );

				$row = array(
					'order'     => $order->get_order_number(),
					'url'       => $order->get_edit_order_url(),
					'status'    => wc_get_order_status_name( $order->get_status() ),
					'customer'  => trim( $order->get_formatted_billing_full_name() ),
					'package'   => $package ? $package->full_title() : $item->get_name(),
					'date'      => (string) ( $party['date'] ?? '' ),
					'travelers' => $travelers,
					'total'     => (int) round( (float) $item->get_total() ),
					'placed'    => $created ? $created->date_i18n( (string) get_option( 'date_format' ) ) : '',
				);

				if ( $is_new ) {
					$result['recent'][]        = $row;
					++$result['recent_count'];
					$result['recent_travelers'] += $travelers;
					$result['recent_value']     += $row['total'];
				}

				if ( '' !== $row['date'] && $row['date'] >= $today && $row['date'] <= $horizon ) {
					$result['departures'][] = $row;
				}
			}
		}

		usort(
			$result['departures'],
			static function ( array $a, array $b ): int {
				return strcmp( $a['date'], $b['date'] );
			}
		);

		set_transient( 'tzh_dashboard_bookings', $result, self::CACHE_TTL );

		return $result;
	}
}

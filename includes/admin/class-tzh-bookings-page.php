<?php
/**
 * Bookings screen.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lists the WooCommerce orders that contain a holiday package.
 *
 * Bookings are ordinary orders, so this screen owns no data of its own: it
 * reads the orders back out of WooCommerce and links to them. The one thing it
 * does change is the order status, because that is what a travel desk touches
 * all day — confirming a booking, marking one cancelled — and walking to the
 * WooCommerce order screen for each is the slow way round. The change itself
 * still goes through WC_Order::update_status(), so the order note, the customer
 * email and every other hook fire exactly as they would there.
 */
class TZH_Bookings_Page {

	/**
	 * Page slug.
	 */
	public const SLUG = 'travelz-holidays-bookings';

	/**
	 * admin-post action behind the status form.
	 */
	public const ACTION = 'tzh_booking_status';

	/**
	 * Bookings shown per page.
	 */
	private const PER_PAGE = 20;

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save_status' ) );
	}

	/**
	 * Whether the current user may change an order's status.
	 */
	public static function may_edit(): bool {
		return current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Apply a status change submitted from the table.
	 */
	public function save_status(): void {
		$order_id = isset( $_POST['order'] ) ? (int) $_POST['order'] : 0;

		check_admin_referer( self::ACTION . '-' . $order_id );

		if ( ! self::may_edit() ) {
			wp_die(
				esc_html__( 'You are not allowed to change booking statuses.', 'travelz-holidays' ),
				'',
				array( 'response' => 403 )
			);
		}

		// The select submits WooCommerce's own keys ("wc-completed"), while
		// update_status() wants them without the prefix.
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$status = str_starts_with( $status, 'wc-' ) ? substr( $status, 3 ) : $status;

		$back  = wp_get_referer();
		$back  = is_string( $back ) && '' !== $back ? $back : self_admin_url( 'admin.php?page=' . self::SLUG );
		$order = TZH_Woo::active() ? wc_get_order( $order_id ) : null;

		if ( ! $order instanceof WC_Order || ! array_key_exists( 'wc-' . $status, wc_get_order_statuses() ) ) {
			wp_safe_redirect( add_query_arg( 'tzh-status', 'failed', remove_query_arg( array( 'tzh-status', 'tzh-order' ), $back ) ) );
			exit;
		}

		if ( $order->get_status() !== $status ) {
			$order->update_status(
				$status,
				__( 'Status changed from the TravelZ Bookings screen.', 'travelz-holidays' ),
				true
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'tzh-status' => rawurlencode( $status ),
					'tzh-order'  => $order->get_order_number(),
				),
				remove_query_arg( array( 'tzh-status', 'tzh-order' ), $back )
			)
		);
		exit;
	}

	/**
	 * Render the screen.
	 */
	public function render(): void {
		if ( ! TZH_Woo::active() ) {
			tzh_admin_view( 'bookings', array( 'inactive' => true ) );

			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only paging.
		$paged  = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$result = wc_get_orders(
			array(
				'limit'      => self::PER_PAGE,
				'paged'      => $paged,
				'orderby'    => 'date',
				'order'      => 'DESC',
				'paginate'   => true,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => TZH_Woo::ORDER_FLAG,
						'value' => 'yes',
					),
				),
			)
		);

		$orders = is_object( $result ) ? (array) $result->orders : (array) $result;
		$pages  = is_object( $result ) ? (int) $result->max_num_pages : 1;
		$total  = is_object( $result ) ? (int) $result->total : count( $orders );

		tzh_admin_view(
			'bookings',
			array(
				'inactive' => false,
				'rows'     => $this->rows( $orders ),
				'paged'    => $paged,
				'pages'    => $pages,
				'total'    => $total,
				'statuses' => wc_get_order_statuses(),
				'editable' => self::may_edit(),
			)
		);
	}

	/**
	 * Flatten orders into one row per booked package.
	 *
	 * @param array<int, mixed> $orders Orders to read.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function rows( array $orders ): array {
		$rows = array();
		$seen = array();

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			foreach ( $order->get_items() as $item ) {
				$booking = $item->get_meta( TZH_Woo::ITEM_KEY, true );

				if ( ! is_array( $booking ) ) {
					continue;
				}

				$package = TZH_Package::from( (int) ( $booking['package'] ?? 0 ) );
				$party   = (array) ( $booking['party'] ?? array() );
				$quote   = (array) ( $booking['quote'] ?? array() );

				// An order holding two packages produces two rows; only the
				// first carries the status control, so the same order is never
				// offered twice on one screen.
				$id    = $order->get_id();
				$first = ! isset( $seen[ $id ] );

				$seen[ $id ] = true;

				$rows[] = array(
					'order'     => $id,
					'number'    => $order->get_order_number(),
					'url'       => $order->get_edit_order_url(),
					'first'     => $first,
					'state'     => 'wc-' . $order->get_status(),
					'status'    => wc_get_order_status_name( $order->get_status() ),
					'placed'    => $order->get_date_created() ? $order->get_date_created()->date_i18n( (string) get_option( 'date_format' ) ) : '',
					'customer'  => trim( $order->get_formatted_billing_full_name() ),
					'email'     => $order->get_billing_email(),
					'package'   => $package ? $package->full_title() : $item->get_name(),
					'link'      => $package ? get_edit_post_link( $package->id() ) : '',
					'code'      => $package ? $package->code() : '',
					'travel'    => TZH_Booking::date_label( (string) ( $party['date'] ?? '' ) ),
					'travelers' => (int) ( $quote['travelers'] ?? 0 ),
					'party'     => $this->party_label( $quote ),
					'total'     => (float) $item->get_total(),
				);
			}
		}

		return $rows;
	}

	/**
	 * "2 Adult, 1 Child" from a stored quote.
	 *
	 * @param array<string, mixed> $quote Stored quote.
	 */
	private function party_label( array $quote ): string {
		$parts = array();

		foreach ( (array) ( $quote['lines'] ?? array() ) as $line ) {
			if ( (int) ( $line['count'] ?? 0 ) > 0 ) {
				$parts[] = (string) $line['label'];
			}
		}

		return implode( ', ', $parts );
	}
}

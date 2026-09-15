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
 * Bookings are ordinary orders, so this screen deliberately owns no data of its
 * own: it reads the orders back out of WooCommerce and links to them. Anything
 * a shop manager needs to change — status, refunds, notes — happens on the
 * order itself, where the rest of the shop already looks.
 */
class TZH_Bookings_Page {

	/**
	 * Page slug.
	 */
	public const SLUG = 'travelz-holidays-bookings';

	/**
	 * Bookings shown per page.
	 */
	private const PER_PAGE = 20;

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

				$rows[] = array(
					'order'     => $order->get_id(),
					'number'    => $order->get_order_number(),
					'url'       => $order->get_edit_order_url(),
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

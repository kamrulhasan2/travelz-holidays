<?php
/**
 * Bookings screen.
 *
 * @package TravelZ_Holidays
 *
 * @var bool                                  $inactive Whether WooCommerce is missing.
 * @var array<int, array<string, mixed>>|null $rows     One row per booked package.
 * @var int|null                              $paged    Current page.
 * @var int|null                              $pages    Total pages.
 * @var int|null                              $total    Total orders.
 * @var array<string, string>|null            $statuses WooCommerce order statuses.
 * @var bool|null                             $editable Whether statuses may be changed.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only confirmation message.
$tzh_changed = isset( $_GET['tzh-status'] ) ? sanitize_key( wp_unslash( $_GET['tzh-status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only confirmation message.
$tzh_order = isset( $_GET['tzh-order'] ) ? sanitize_text_field( wp_unslash( $_GET['tzh-order'] ) ) : '';
?>
<div class="wrap tzh-wrap">
	<h1><?php esc_html_e( 'Bookings', 'travelz-holidays' ); ?></h1>

	<?php if ( ! empty( $inactive ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'WooCommerce is not active, so bookings are not being taken through checkout. Travelers who book are shown a reference and asked to contact a consultant instead.', 'travelz-holidays' ); ?>
			</p>
		</div>

		<?php return; ?>
	<?php endif; ?>

	<?php if ( '' !== $tzh_changed ) : ?>
		<?php if ( 'failed' === $tzh_changed ) : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'That status could not be applied. The order may have been deleted.', 'travelz-holidays' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					printf(
						/* translators: 1: order number, 2: status name */
						esc_html__( 'Order #%1$s is now %2$s.', 'travelz-holidays' ),
						esc_html( $tzh_order ),
						esc_html( wc_get_order_status_name( $tzh_changed ) )
					);
					?>
				</p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<p class="tzh-lede">
		<?php
		printf(
			/* translators: %s: number of orders */
			esc_html( _n( '%s order contains a holiday package.', '%s orders contain a holiday package.', (int) $total, 'travelz-holidays' ) ),
			esc_html( number_format_i18n( (int) $total ) )
		);
		?>
	</p>

	<?php if ( empty( $rows ) ) : ?>
		<div class="tzh-panel">
			<p><?php esc_html_e( 'No bookings yet. They appear here the moment a traveler completes checkout.', 'travelz-holidays' ); ?></p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped tzh-bookings">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Order', 'travelz-holidays' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Traveler', 'travelz-holidays' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Package', 'travelz-holidays' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Travel date', 'travelz-holidays' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Party', 'travelz-holidays' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Total', 'travelz-holidays' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'travelz-holidays' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( (array) $rows as $row ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( (string) $row['url'] ); ?>">
								<strong>#<?php echo esc_html( (string) $row['number'] ); ?></strong>
							</a>
							<div class="tzh-muted"><?php echo esc_html( (string) $row['placed'] ); ?></div>
						</td>
						<td>
							<?php echo esc_html( '' !== $row['customer'] ? (string) $row['customer'] : __( 'Guest', 'travelz-holidays' ) ); ?>
							<div class="tzh-muted"><?php echo esc_html( (string) $row['email'] ); ?></div>
						</td>
						<td>
							<?php if ( '' !== (string) $row['link'] ) : ?>
								<a href="<?php echo esc_url( (string) $row['link'] ); ?>"><?php echo esc_html( (string) $row['package'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( (string) $row['package'] ); ?>
							<?php endif; ?>

							<?php if ( '' !== (string) $row['code'] ) : ?>
								<div class="tzh-muted"><?php echo esc_html( (string) $row['code'] ); ?></div>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( (string) $row['travel'] ); ?></td>
						<td>
							<?php echo esc_html( (string) $row['party'] ); ?>
							<div class="tzh-muted">
								<?php
								printf(
									/* translators: %s: number of travellers */
									esc_html( _n( '%s traveler', '%s travelers', (int) $row['travelers'], 'travelz-holidays' ) ),
									esc_html( number_format_i18n( (int) $row['travelers'] ) )
								);
								?>
							</div>
						</td>
						<td><?php echo wp_kses_post( wc_price( (float) $row['total'] ) ); ?></td>
						<td>
							<?php if ( empty( $editable ) || empty( $row['first'] ) ) : ?>
								<span class="tzh-state tzh-state--<?php echo esc_attr( str_replace( 'wc-', '', (string) $row['state'] ) ); ?>">
									<?php echo esc_html( (string) $row['status'] ); ?>
								</span>
							<?php else : ?>
								<form class="tzh-status" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( TZH_Bookings_Page::ACTION . '-' . (int) $row['order'] ); ?>
									<input type="hidden" name="action" value="<?php echo esc_attr( TZH_Bookings_Page::ACTION ); ?>" />
									<input type="hidden" name="order" value="<?php echo esc_attr( (string) $row['order'] ); ?>" />

									<label class="screen-reader-text" for="tzh-status-<?php echo esc_attr( (string) $row['order'] ); ?>">
										<?php esc_html_e( 'Booking status', 'travelz-holidays' ); ?>
									</label>
									<select class="tzh-status__select" name="status"
										id="tzh-status-<?php echo esc_attr( (string) $row['order'] ); ?>"
										data-initial="<?php echo esc_attr( (string) $row['state'] ); ?>">
										<?php foreach ( (array) $statuses as $tzh_key => $tzh_label ) : ?>
											<option value="<?php echo esc_attr( (string) $tzh_key ); ?>"
												<?php selected( (string) $tzh_key, (string) $row['state'] ); ?>>
												<?php echo esc_html( (string) $tzh_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>

									<button type="submit" class="button button-small tzh-status__go">
										<?php esc_html_e( 'Update', 'travelz-holidays' ); ?>
									</button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( (int) $pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => (int) $paged,
								'total'     => (int) $pages,
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>

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
 */

defined( 'ABSPATH' ) || exit;
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
						<td><?php echo esc_html( (string) $row['status'] ); ?></td>
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

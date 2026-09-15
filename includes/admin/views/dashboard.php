<?php
/**
 * Plugin dashboard screen.
 *
 * @package TravelZ_Holidays
 *
 * @var array<int, array{label: string, value: string, url: string, note: string}> $tiles
 * @var array<int, array{text: string, url: string, label: string}>                $warnings
 * @var array<int, array<string, mixed>>                                           $departures
 * @var array<int, array<string, mixed>>                                           $recent
 * @var array<int, array{title: string, url: string, issues: string[]}>            $attention
 * @var array<int, array{name: string, count: int, from: int, url: string}>        $destinations
 * @var bool                                                                       $woo
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap tzh-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'TravelZ Holidays', 'travelz-holidays' ); ?></h1>

	<a class="page-title-action" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . TZH_Package::POST_TYPE ) ); ?>">
		<?php esc_html_e( 'Add Package', 'travelz-holidays' ); ?>
	</a>

	<a class="page-title-action" href="<?php echo esc_url( TZH_Rewrites::grid_url() ); ?>" target="_blank" rel="noreferrer">
		<?php esc_html_e( 'View catalogue', 'travelz-holidays' ); ?>
	</a>

	<hr class="wp-header-end" />

	<?php foreach ( $warnings as $tzh_warning ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php echo esc_html( $tzh_warning['text'] ); ?>
				<a href="<?php echo esc_url( $tzh_warning['url'] ); ?>"><?php echo esc_html( $tzh_warning['label'] ); ?></a>
			</p>
		</div>
	<?php endforeach; ?>

	<div class="tzh-tiles">
		<?php foreach ( $tiles as $tzh_tile ) : ?>
			<a class="tzh-tile" href="<?php echo esc_url( $tzh_tile['url'] ); ?>">
				<span class="tzh-tile__count"><?php echo esc_html( $tzh_tile['value'] ); ?></span>
				<span class="tzh-tile__label"><?php echo esc_html( $tzh_tile['label'] ); ?></span>
				<span class="tzh-tile__note"><?php echo esc_html( $tzh_tile['note'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="tzh-panels">
		<section class="tzh-panel">
			<h2><?php esc_html_e( 'Upcoming departures', 'travelz-holidays' ); ?></h2>

			<?php if ( ! $woo ) : ?>
				<p class="tzh-note">
					<?php esc_html_e( 'Departures are read from paid orders. Activate WooCommerce to take bookings through checkout.', 'travelz-holidays' ); ?>
				</p>
			<?php elseif ( ! $departures ) : ?>
				<p class="tzh-note">
					<?php esc_html_e( 'Nothing departing in the next 60 days.', 'travelz-holidays' ); ?>
				</p>
			<?php else : ?>
				<table class="tzh-list">
					<tbody>
					<?php foreach ( $departures as $tzh_row ) : ?>
						<tr>
							<td class="tzh-list__when">
								<b><?php echo esc_html( TZH_Booking::date_label( (string) $tzh_row['date'] ) ); ?></b>
								<span class="tzh-muted"><?php echo esc_html( tzh_days_away( (string) $tzh_row['date'] ) ); ?></span>
							</td>
							<td>
								<?php echo esc_html( (string) $tzh_row['package'] ); ?>
								<span class="tzh-muted">
									<?php
									printf(
										/* translators: 1: number of travellers, 2: customer name */
										esc_html__( '%1$s · %2$s', 'travelz-holidays' ),
										esc_html(
											sprintf(
												/* translators: %s: number of travellers */
												_n( '%s traveler', '%s travelers', (int) $tzh_row['travelers'], 'travelz-holidays' ),
												number_format_i18n( (int) $tzh_row['travelers'] )
											)
										),
										esc_html( '' !== $tzh_row['customer'] ? (string) $tzh_row['customer'] : __( 'Guest', 'travelz-holidays' ) )
									);
									?>
								</span>
							</td>
							<td class="tzh-list__end">
								<a href="<?php echo esc_url( (string) $tzh_row['url'] ); ?>">#<?php echo esc_html( (string) $tzh_row['order'] ); ?></a>
								<span class="tzh-muted"><?php echo esc_html( (string) $tzh_row['status'] ); ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p class="tzh-panel__more">
					<a href="<?php echo esc_url( tzh_admin_url( TZH_Bookings_Page::SLUG ) ); ?>">
						<?php esc_html_e( 'All bookings', 'travelz-holidays' ); ?> &rarr;
					</a>
				</p>
			<?php endif; ?>
		</section>

		<section class="tzh-panel">
			<h2><?php esc_html_e( 'Needs attention', 'travelz-holidays' ); ?></h2>

			<?php if ( ! $attention ) : ?>
				<p class="tzh-note">
					<?php esc_html_e( 'Every package has a price, a destination, a duration, an itinerary and a banner. Nothing to fix.', 'travelz-holidays' ); ?>
				</p>
			<?php else : ?>
				<p class="tzh-note">
					<?php esc_html_e( 'These packages are missing something travelers will notice.', 'travelz-holidays' ); ?>
				</p>

				<table class="tzh-list">
					<tbody>
					<?php foreach ( $attention as $tzh_row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( (string) $tzh_row['url'] ); ?>"><?php echo esc_html( (string) $tzh_row['title'] ); ?></a>
								<span class="tzh-muted"><?php echo esc_html( implode( ', ', (array) $tzh_row['issues'] ) ); ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="tzh-panel">
			<h2><?php esc_html_e( 'Destinations at a glance', 'travelz-holidays' ); ?></h2>

			<?php if ( ! $destinations ) : ?>
				<p class="tzh-note">
					<?php esc_html_e( 'No published package has a destination yet.', 'travelz-holidays' ); ?>
				</p>
			<?php else : ?>
				<table class="tzh-list">
					<tbody>
					<?php foreach ( $destinations as $tzh_row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( (string) $tzh_row['url'] ); ?>"><?php echo esc_html( (string) $tzh_row['name'] ); ?></a>
							</td>
							<td class="tzh-list__end">
								<?php
								printf(
									/* translators: %s: number of packages */
									esc_html( _n( '%s package', '%s packages', (int) $tzh_row['count'], 'travelz-holidays' ) ),
									esc_html( number_format_i18n( (int) $tzh_row['count'] ) )
								);
								?>
								<?php if ( (int) $tzh_row['from'] > 0 ) : ?>
									<span class="tzh-muted">
										<?php
										printf(
											/* translators: %s: lowest price */
											esc_html__( 'from %s', 'travelz-holidays' ),
											esc_html( tzh_price( (int) $tzh_row['from'] ) )
										);
										?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="tzh-panel">
			<h2><?php esc_html_e( 'Recent bookings', 'travelz-holidays' ); ?></h2>

			<?php if ( ! $woo ) : ?>
				<p class="tzh-note">
					<?php esc_html_e( 'Booking requests are being collected with a reference number instead. Activate WooCommerce to take payment at checkout.', 'travelz-holidays' ); ?>
				</p>
			<?php elseif ( ! $recent ) : ?>
				<p class="tzh-note">
					<?php esc_html_e( 'No bookings in the last 30 days.', 'travelz-holidays' ); ?>
				</p>
			<?php else : ?>
				<table class="tzh-list">
					<tbody>
					<?php foreach ( $recent as $tzh_row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( (string) $tzh_row['url'] ); ?>">#<?php echo esc_html( (string) $tzh_row['order'] ); ?></a>
								<span class="tzh-muted"><?php echo esc_html( (string) $tzh_row['placed'] ); ?></span>
							</td>
							<td>
								<?php echo esc_html( (string) $tzh_row['package'] ); ?>
								<span class="tzh-muted"><?php echo esc_html( (string) $tzh_row['status'] ); ?></span>
							</td>
							<td class="tzh-list__end"><b><?php echo esc_html( tzh_price( (int) $tzh_row['total'] ) ); ?></b></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p class="tzh-panel__more">
					<a href="<?php echo esc_url( tzh_admin_url( TZH_Bookings_Page::SLUG ) ); ?>">
						<?php esc_html_e( 'All bookings', 'travelz-holidays' ); ?> &rarr;
					</a>
				</p>
			<?php endif; ?>
		</section>
	</div>
</div>

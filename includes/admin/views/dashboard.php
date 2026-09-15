<?php
/**
 * Plugin dashboard screen.
 *
 * @package TravelZ_Holidays
 *
 * @var array<int, array{label: string, value: string, status: string}> $checks
 * @var array<int, array{label: string, count: int, url: string}>       $catalogue
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap tzh-wrap">
	<h1><?php esc_html_e( 'TravelZ Holidays', 'travelz-holidays' ); ?></h1>
	<p class="tzh-lede">
		<?php esc_html_e( 'Destinations, tour packages, itineraries and pricing tiers for TravelZ.', 'travelz-holidays' ); ?>
	</p>

	<div class="tzh-tiles">
		<?php foreach ( $catalogue as $tile ) : ?>
			<a class="tzh-tile" href="<?php echo esc_url( $tile['url'] ); ?>">
				<span class="tzh-tile__count"><?php echo esc_html( number_format_i18n( $tile['count'] ) ); ?></span>
				<span class="tzh-tile__label"><?php echo esc_html( $tile['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="tzh-panels">
		<section class="tzh-panel">
			<h2><?php esc_html_e( 'System status', 'travelz-holidays' ); ?></h2>
			<table class="tzh-status">
				<tbody>
				<?php foreach ( $checks as $check ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $check['label'] ); ?></th>
						<td>
							<span class="tzh-dot tzh-dot--<?php echo esc_attr( $check['status'] ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $check['value'] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="tzh-panel">
			<h2><?php esc_html_e( 'Build progress', 'travelz-holidays' ); ?></h2>
			<p class="tzh-note">
				<?php esc_html_e( 'This plugin is being built in phases. Completed phases are listed below; the rest of the menu fills in as they land.', 'travelz-holidays' ); ?>
			</p>
			<ol class="tzh-phases">
				<li class="is-done"><?php esc_html_e( 'Plugin skeleton and admin menu', 'travelz-holidays' ); ?></li>
				<li class="is-done"><?php esc_html_e( 'Packages, destinations and categories', 'travelz-holidays' ); ?></li>
				<li class="is-done"><?php esc_html_e( 'Package editor — overview and pricing', 'travelz-holidays' ); ?></li>
				<li class="is-done"><?php esc_html_e( 'Package editor — itinerary', 'travelz-holidays' ); ?></li>
				<li class="is-done"><?php esc_html_e( 'Package editor — inclusions, terms, visa', 'travelz-holidays' ); ?></li>
				<li><?php esc_html_e( 'Destination images and tier variants', 'travelz-holidays' ); ?></li>
			</ol>
		</section>
	</div>
</div>

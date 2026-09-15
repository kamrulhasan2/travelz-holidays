<?php
/**
 * Inclusion and Exclusion tab.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package being shown.
 */

defined( 'ABSPATH' ) || exit;

$tzh_lists = array(
	'yes' => array(
		'title' => __( 'Inclusion', 'travelz-holidays' ),
		'icon'  => 'check',
		'items' => $package->inclusion(),
	),
	'no'  => array(
		'title' => __( 'Exclusion', 'travelz-holidays' ),
		'icon'  => 'x',
		'items' => $package->exclusion(),
	),
);
?>
<div class="tz-panel tz-panel--pair">
	<?php foreach ( $tzh_lists as $tzh_kind => $tzh_list ) : ?>
		<?php if ( $tzh_list['items'] ) : ?>
			<div class="tz-card">
				<div class="tz-card__body">
					<h2 class="tz-heading tz-heading--<?php echo esc_attr( $tzh_kind ); ?>">
						<?php echo esc_html( $tzh_list['title'] ); ?>
					</h2>
					<ul class="tz-ticks tz-ticks--<?php echo esc_attr( $tzh_kind ); ?>">
						<?php foreach ( $tzh_list['items'] as $tzh_item ) : ?>
							<li>
								<span class="tz-ticks__mark">
									<?php echo tzh_icon( $tzh_list['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
								</span>
								<span><?php echo esc_html( $tzh_item ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
</div>

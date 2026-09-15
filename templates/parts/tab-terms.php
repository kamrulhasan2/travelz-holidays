<?php
/**
 * Terms & Conditions tab.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package being shown.
 */

defined( 'ABSPATH' ) || exit;

$tzh_terms = $package->terms();
?>
<div class="tz-panel">
	<div class="tz-card">
		<div class="tz-card__body">
			<?php if ( $tzh_terms ) : ?>
				<ul class="tz-bullets">
					<?php foreach ( $tzh_terms as $tzh_term ) : ?>
						<li><?php echo esc_html( $tzh_term ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="tz-muted"><?php esc_html_e( 'Booking terms will be confirmed by your travel consultant.', 'travelz-holidays' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
/**
 * Package Price tab.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package being shown.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="tz-panel">
	<div class="tz-card">
		<div class="tz-card__body">
			<h2 class="tz-meta"><?php esc_html_e( 'Per Person Price (Adult)', 'travelz-holidays' ); ?></h2>
			<p class="tz-bigprice tz-sunset-text"><?php echo esc_html( tzh_price( $package->price() ) ); ?></p>
			<p class="tz-muted tz-price-note">
				<?php esc_html_e( 'Prices for Child and Infant will be calculated at checkout.', 'travelz-holidays' ); ?>
			</p>

			<div class="tz-pricegrid">
				<?php
				$tzh_rows = array(
					__( 'Adult (12+)', 'travelz-holidays' )  => $package->price(),
					__( 'Child (2–11)', 'travelz-holidays' ) => $package->child_price(),
					__( 'Infant (0–1)', 'travelz-holidays' ) => $package->infant_price(),
				);

				foreach ( $tzh_rows as $tzh_label => $tzh_amount ) :
					?>
					<div class="tz-pricegrid__cell">
						<span class="tz-meta"><?php echo esc_html( $tzh_label ); ?></span>
						<b><?php echo esc_html( tzh_price( $tzh_amount ) ); ?></b>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<?php tzh_template( 'parts/variant-switch', array( 'package' => $package ) ); ?>
</div>

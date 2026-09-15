<?php
/**
 * Destination grid — the front door of the package catalogue.
 *
 * Copy this file into a theme's travelz-holidays/ folder to customise it.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

get_header();

$tzh_terms = TZH_Term_Meta::ordered( TZH_Package::TAX_DESTINATION, true );

$tzh_eyebrow  = (string) TZH_Settings::get( 'archive_eyebrow', __( 'Curated for Bangladeshi travelers', 'travelz-holidays' ) );
$tzh_title    = (string) TZH_Settings::get( 'archive_title', __( 'Explore Holiday Packages', 'travelz-holidays' ) );
$tzh_subtitle = (string) TZH_Settings::get( 'archive_subtitle', __( 'Handpicked destinations, unforgettable journeys.', 'travelz-holidays' ) );
?>
<div id="tz-app">
	<section class="tz-section tz-wrap tz-fade-in">
		<header class="tz-archive-head">
			<?php if ( '' !== $tzh_eyebrow ) : ?>
				<span class="tz-badge tz-badge--soft">
					<?php echo tzh_icon( 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from a fixed sprite id. ?>
					<?php echo esc_html( $tzh_eyebrow ); ?>
				</span>
			<?php endif; ?>

			<h1 class="tz-title"><?php echo esc_html( $tzh_title ); ?></h1>

			<?php if ( '' !== $tzh_subtitle ) : ?>
				<p class="tz-lede"><?php echo esc_html( $tzh_subtitle ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( $tzh_terms ) : ?>
			<div class="tz-dest-grid">
				<?php foreach ( $tzh_terms as $tzh_term ) : ?>
					<?php tzh_template( 'parts/card-destination', array( 'term' => $tzh_term ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="tz-empty">
				<?php esc_html_e( 'No destinations have packages yet.', 'travelz-holidays' ); ?>
			</p>
		<?php endif; ?>
	</section>
</div>
<?php
get_footer();

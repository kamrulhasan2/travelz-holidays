<?php
/**
 * Packages in one destination, with the filter panel.
 *
 * Copy this file into a theme's travelz-holidays/ folder to customise it.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

get_header();

$tzh_term = get_queried_object();

if ( ! $tzh_term instanceof WP_Term ) {
	get_footer();

	return;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters from the URL.
$tzh_filters = TZH_Query::filters( wp_unslash( $_GET ), $tzh_term->slug );
$tzh_action  = get_term_link( $tzh_term );
$tzh_blurb   = TZH_Term_Meta::blurb( $tzh_term->term_id );

?>
<div id="tz-app">
	<?php
	tzh_template(
		'parts/breadcrumb',
		array(
			'crumbs' => array(
				array(
					'label' => __( 'Home', 'travelz-holidays' ),
					'url'   => home_url( '/' ),
				),
				array(
					'label' => (string) TZH_Settings::get( 'archive_title', __( 'Holiday Packages', 'travelz-holidays' ) ),
					'url'   => TZH_Rewrites::grid_url(),
				),
				array(
					'label' => $tzh_term->name,
					'url'   => '',
				),
			),
		)
	);
	?>

	<section class="tz-wrap tz-section tz-fade-in">
		<header class="tz-list-head">
			<h1 class="tz-title--sm"><?php echo esc_html( $tzh_term->name ); ?></h1>
			<?php if ( '' !== $tzh_blurb ) : ?>
				<p class="tz-lede"><?php echo esc_html( $tzh_blurb ); ?></p>
			<?php endif; ?>
		</header>

		<div class="tz-list-layout">
			<aside class="tz-list-aside">
				<?php
				tzh_template(
					'parts/filters',
					array(
						'filters' => $tzh_filters,
						'action'  => $tzh_action,
					)
				);
				?>
			</aside>

			<div class="tz-list-main">
				<button type="button" class="tz-btn tz-btn--outline tz-btn--block tz-filters-toggle" data-tz-filters-open aria-expanded="false">
					<?php echo tzh_icon( 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
					<?php esc_html_e( 'Filters', 'travelz-holidays' ); ?>
				</button>

				<?php tzh_template( 'parts/results', array( 'query' => $GLOBALS['wp_query'] ) ); ?>
			</div>
		</div>
	</section>
</div>
<?php
get_footer();

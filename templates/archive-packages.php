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

$tzh_object = get_queried_object();
$tzh_term   = $tzh_object instanceof WP_Term ? $tzh_object : null;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters from the URL.
$tzh_filters = TZH_Query::filters( wp_unslash( $_GET ), $tzh_term ? $tzh_term->slug : '' );

/*
 * The form always posts to the catalogue, never to a country's own page: a
 * visitor who ticks two countries needs a page that belongs to neither, and
 * this is it. The country they started on travels with them as a ticked box.
 */
$tzh_action = TZH_Rewrites::grid_url();
$tzh_blurb  = $tzh_term ? TZH_Term_Meta::blurb( $tzh_term->term_id ) : (string) TZH_Settings::get( 'archive_subtitle', '' );
$tzh_title  = $tzh_term ? $tzh_term->name : (string) TZH_Settings::get( 'archive_title', __( 'Holiday Packages', 'travelz-holidays' ) );

/*
 * "Clear Filters" undoes the filtering, not the visitor's whole journey: it
 * returns to the country page they came in through rather than to the top of
 * the catalogue. On the catalogue itself a single ticked country still has a
 * page of its own, and that is the page to go back to.
 */
$tzh_reset = TZH_Rewrites::destination_url(
	$tzh_term ? $tzh_term->slug : ( 1 === count( $tzh_filters['dest'] ) ? $tzh_filters['dest'][0] : '' )
);

?>
<div id="tz-app">
	<?php
	tzh_template(
		'parts/breadcrumb',
		array(
			'crumbs' => array_values( array_filter( array(
				array(
					'label' => __( 'Home', 'travelz-holidays' ),
					'url'   => home_url( '/' ),
				),
				array(
					'label' => (string) TZH_Settings::get( 'archive_title', __( 'Holiday Packages', 'travelz-holidays' ) ),
					'url'   => $tzh_term ? TZH_Rewrites::grid_url() : '',
				),
				$tzh_term ? array(
					'label' => $tzh_term->name,
					'url'   => '',
				) : null,
			) ) ),
		)
	);
	?>

	<section class="tz-wrap tz-section tz-fade-in">
		<header class="tz-list-head">
			<h1 class="tz-title--sm"><?php echo esc_html( $tzh_title ); ?></h1>
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
						'reset'   => $tzh_reset,
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

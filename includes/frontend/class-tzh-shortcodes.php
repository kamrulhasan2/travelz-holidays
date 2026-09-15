<?php
/**
 * Shortcodes.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lets an existing page — a home page built in a page builder, say — show the
 * destination grid without becoming the catalogue itself.
 *
 * The cards still link into the plugin's own URLs, so every package keeps a
 * page of its own.
 */
class TZH_Shortcodes {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_shortcode( 'travelz_destinations', array( $this, 'destinations' ) );
		add_shortcode( 'travelz_packages', array( $this, 'packages' ) );
	}

	/**
	 * [travelz_packages destination="maldives" limit="3" bestseller="yes"]
	 *
	 * A row of package cards for a home page or a landing page. The cards are
	 * the same ones the catalogue uses, so a package never has two looks.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function packages( $atts ): string {
		$atts = shortcode_atts(
			array(
				'destination' => '',
				'category'    => '',
				'limit'       => 3,
				'bestseller'  => 'no',
				'orderby'     => 'serial',
			),
			(array) $atts,
			'travelz_packages'
		);

		$query = array(
			'post_type'        => TZH_Package::POST_TYPE,
			'post_status'      => 'publish',
			'posts_per_page'   => max( 1, min( 24, (int) $atts['limit'] ) ),
			'suppress_filters' => false,
			'no_found_rows'    => true,
		);

		$tax = array();

		foreach ( array(
			TZH_Package::TAX_DESTINATION => $atts['destination'],
			TZH_Package::TAX_TIER        => $atts['category'],
		) as $taxonomy => $slug ) {
			$slug = sanitize_title( (string) $slug );

			if ( '' !== $slug ) {
				$tax[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $slug,
				);
			}
		}

		if ( $tax ) {
			$query['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		if ( 'yes' === $atts['bestseller'] ) {
			$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => TZH_Package::META_BESTSELLER,
					'value' => '1',
				),
			);
		}

		if ( 'price' === $atts['orderby'] ) {
			$query['meta_key'] = TZH_Package::META_PRICE; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query['orderby']  = 'meta_value_num';
			$query['order']    = 'ASC';
		}

		$posts = get_posts( $query );

		if ( ! $posts ) {
			return '';
		}

		if ( 'serial' === $atts['orderby'] ) {
			usort(
				$posts,
				static function ( WP_Post $a, WP_Post $b ): int {
					return TZH_Package::serial_from_code( (string) get_post_meta( $a->ID, TZH_Package::META_CODE, true ) )
						<=> TZH_Package::serial_from_code( (string) get_post_meta( $b->ID, TZH_Package::META_CODE, true ) );
				}
			);
		}

		TZH_Assets::need();

		ob_start();
		?>
		<div id="tz-app">
			<div class="tz-results">
				<?php foreach ( $posts as $post ) : ?>
					<?php
					$package = TZH_Package::from( $post );

					if ( $package ) {
						tzh_template( 'parts/card-package', array( 'package' => $package ) );
					}
					?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * [travelz_destinations limit="8" columns="4" empty="hide"]
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function destinations( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit'   => 0,
				'columns' => 4,
				'empty'   => 'hide',
			),
			(array) $atts,
			'travelz_destinations'
		);

		$terms = TZH_Term_Meta::ordered(
			TZH_Package::TAX_DESTINATION,
			'show' !== $atts['empty']
		);

		$limit = (int) $atts['limit'];

		if ( $limit > 0 ) {
			$terms = array_slice( $terms, 0, $limit );
		}

		if ( ! $terms ) {
			return '';
		}

		TZH_Assets::need();

		$columns = max( 1, min( 6, (int) $atts['columns'] ) );

		ob_start();
		?>
		<div id="tz-app">
			<div class="tz-dest-grid" style="--tz-dest-columns:<?php echo (int) $columns; ?>">
				<?php foreach ( $terms as $term ) : ?>
					<?php tzh_template( 'parts/card-destination', array( 'term' => $term ) ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

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

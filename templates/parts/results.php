<?php
/**
 * Package results — the part the filter panel swaps out.
 *
 * @package TravelZ_Holidays
 *
 * @var WP_Query $query Query holding the matching packages.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $query ) || ! $query instanceof WP_Query ) {
	return;
}

$tzh_total = (int) $query->found_posts;
?>
<div class="tz-results" data-tz-results>
	<p class="tz-meta tz-results__count" data-tz-results-count>
		<?php
		printf(
			/* translators: %s: number of packages found */
			esc_html( _n( '%s package found', '%s packages found', $tzh_total, 'travelz-holidays' ) ),
			esc_html( number_format_i18n( $tzh_total ) )
		);
		?>
	</p>

	<?php if ( $query->have_posts() ) : ?>
		<div class="tz-stack">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();

				$tzh_package = TZH_Package::from( get_post() );

				if ( $tzh_package ) {
					tzh_template( 'parts/card-package', array( 'package' => $tzh_package ) );
				}
			endwhile;

			wp_reset_postdata();
			?>
		</div>

		<?php
		$tzh_links = paginate_links(
			array(
				'total'     => (int) $query->max_num_pages,
				'current'   => max( 1, (int) $query->get( 'paged' ) ),
				'type'      => 'array',
				'prev_text' => esc_html__( 'Previous', 'travelz-holidays' ),
				'next_text' => esc_html__( 'Next', 'travelz-holidays' ),
			)
		);

		if ( is_array( $tzh_links ) && count( $tzh_links ) > 1 ) :
			?>
			<nav class="tz-pagination" aria-label="<?php esc_attr_e( 'Package pages', 'travelz-holidays' ); ?>">
				<?php foreach ( $tzh_links as $tzh_link ) : ?>
					<?php echo wp_kses_post( $tzh_link ); ?>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<p class="tz-empty"><?php esc_html_e( 'No packages match your filters.', 'travelz-holidays' ); ?></p>
	<?php endif; ?>
</div>

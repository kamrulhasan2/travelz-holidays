<?php
/**
 * Destination card.
 *
 * @package TravelZ_Holidays
 *
 * @var WP_Term $term Destination term.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $term ) || ! $term instanceof WP_Term ) {
	return;
}

$tzh_image = TZH_Term_Meta::image_id( $term->term_id );
$tzh_blurb = TZH_Term_Meta::blurb( $term->term_id );
$tzh_count = (int) $term->count;
?>
<a class="tz-dest" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
	<?php if ( $tzh_image ) : ?>
		<?php
		echo wp_get_attachment_image(
			$tzh_image,
			'large',
			false,
			array(
				'class'   => 'tz-dest__img',
				'alt'     => esc_attr( $term->name ),
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
		?>
	<?php else : ?>
		<span class="tz-dest__img tz-dest__img--empty" aria-hidden="true"></span>
	<?php endif; ?>

	<span class="tz-dest__shade" aria-hidden="true"></span>

	<?php if ( $tzh_count > 0 ) : ?>
		<span class="tz-dest__count">
			<span class="tz-badge">
				<?php
				printf(
					/* translators: %s: number of packages */
					esc_html( _n( '%s Package', '%s Packages', $tzh_count, 'travelz-holidays' ) ),
					esc_html( number_format_i18n( $tzh_count ) )
				);
				?>
			</span>
		</span>
	<?php endif; ?>

	<span class="tz-dest__foot">
		<span class="tz-dest__name"><?php echo esc_html( $term->name ); ?></span>
		<?php if ( '' !== $tzh_blurb ) : ?>
			<span class="tz-dest__blurb"><?php echo esc_html( $tzh_blurb ); ?></span>
		<?php endif; ?>
	</span>
</a>

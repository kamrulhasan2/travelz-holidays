<?php
/**
 * Package card, as listed on a destination page.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package to render.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $package ) || ! $package instanceof TZH_Package ) {
	return;
}

$tzh_destination = $package->destination();
$tzh_tier        = $package->tier();
$tzh_permalink   = get_permalink( $package->id() );
?>
<article class="tz-pkg">
	<div class="tz-pkg__media">
		<?php if ( has_post_thumbnail( $package->id() ) ) : ?>
			<a href="<?php echo esc_url( $tzh_permalink ); ?>" tabindex="-1" aria-hidden="true">
				<?php
				echo get_the_post_thumbnail(
					$package->id(),
					'large',
					array(
						'alt'     => esc_attr( get_the_title( $package->id() ) ),
						'loading'  => 'lazy',
				'decoding' => 'async',
					)
				);
				?>
			</a>
		<?php endif; ?>

		<span class="tz-pkg__tag">
			<span class="tz-badge">
				<?php
				printf(
					/* translators: %s: tour type, e.g. Private */
					esc_html__( '%s Tour', 'travelz-holidays' ),
					esc_html( $package->tour_type_label() )
				);
				?>
			</span>
		</span>
	</div>

	<div class="tz-pkg__body">
		<div>
			<span class="tz-meta tz-pkg__where">
				<?php echo tzh_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
				<?php
				$tzh_where = array();

				if ( $tzh_destination ) {
					$tzh_where[] = $tzh_destination->name;
				}

				if ( '' !== $package->code() ) {
					/* translators: %s: package code, e.g. TZ 001 */
					$tzh_where[] = sprintf( __( 'Code %s', 'travelz-holidays' ), $package->code() );
				}

				echo esc_html( implode( ' · ', $tzh_where ) );
				?>
			</span>

			<h3 class="tz-pkg__name">
				<a href="<?php echo esc_url( $tzh_permalink ); ?>"><?php echo esc_html( get_the_title( $package->id() ) ); ?></a>
			</h3>

			<?php if ( '' !== $package->short_description() ) : ?>
				<p class="tz-pkg__short"><?php echo esc_html( $package->short_description() ); ?></p>
			<?php endif; ?>

			<div class="tz-chips tz-pkg__chips">
				<?php if ( $package->days() ) : ?>
					<span class="tz-chip">
						<?php echo tzh_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
						<?php echo esc_html( $package->duration_label() ); ?>
					</span>
				<?php endif; ?>

				<span class="tz-chip">
					<?php echo tzh_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
					<?php
					printf(
						/* translators: %s: minimum number of travellers */
						esc_html__( 'Min %s pax', 'travelz-holidays' ),
						esc_html( number_format_i18n( $package->min_pax() ) )
					);
					?>
				</span>

				<?php if ( $tzh_tier ) : ?>
					<span class="tz-chip tz-chip--accent">
						<?php echo tzh_icon( 'award' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
						<?php echo esc_html( $tzh_tier->name ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<div class="tz-pkg__foot">
			<div>
				<span class="tz-meta"><?php esc_html_e( 'Starting from', 'travelz-holidays' ); ?></span>
				<span class="tz-price-tag">
					<?php echo esc_html( tzh_price( $package->price() ) ); ?>
					<span><?php esc_html_e( '/person', 'travelz-holidays' ); ?></span>
				</span>
			</div>

			<a class="tz-btn" href="<?php echo esc_url( $tzh_permalink ); ?>">
				<?php esc_html_e( 'View Details', 'travelz-holidays' ); ?>
			</a>
		</div>
	</div>
</article>

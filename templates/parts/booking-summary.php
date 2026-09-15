<?php
/**
 * Package summary and travel date on the booking screen.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package           $package Package being booked.
 * @var array<string, mixed>  $party   Party being priced.
 * @var array<string, string> $errors  Validation errors, keyed by field.
 */

defined( 'ABSPATH' ) || exit;

$tzh_thumb = tzh_image_url( $package->hero_id(), 'medium' );
?>
<div class="tz-summary">
	<?php if ( '' !== $tzh_thumb ) : ?>
		<img src="<?php echo esc_url( $tzh_thumb ); ?>" alt="" loading="lazy" />
	<?php endif; ?>

	<div class="tz-summary__body">
		<div>
			<span class="tz-badge tz-badge--teal">
				<?php
				printf(
					/* translators: %s: tour type, e.g. Private */
					esc_html__( '%s Tour', 'travelz-holidays' ),
					esc_html( $package->tour_type_label() )
				);
				?>
			</span>

			<p class="tz-summary__name"><?php echo esc_html( $package->full_title() ); ?></p>

			<p class="tz-meta">
				<?php
				echo esc_html(
					implode(
						' · ',
						array_filter(
							array(
								$package->days() ? $package->duration_label() : '',
								'' !== $package->code()
									/* translators: %s: package code, e.g. TZ 001 */
									? sprintf( __( 'Code %s', 'travelz-holidays' ), $package->code() )
									: '',
							),
							'strlen'
						)
					)
				);
				?>
			</p>
		</div>

		<div class="tz-field">
			<label class="tz-field__label" for="tz-date">
				<?php echo tzh_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
				<?php esc_html_e( 'Travel date', 'travelz-holidays' ); ?>
			</label>

			<input class="tz-date" type="date" id="tz-date" name="tzh_date" required
				value="<?php echo esc_attr( (string) $party['date'] ); ?>"
				min="<?php echo esc_attr( TZH_Booking::earliest() ); ?>"
				<?php echo isset( $errors['date'] ) ? 'aria-describedby="tz-date-error" aria-invalid="true"' : ''; ?> />

			<?php if ( isset( $errors['date'] ) ) : ?>
				<span class="tz-error" id="tz-date-error"><?php echo esc_html( $errors['date'] ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</div>

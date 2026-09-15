<?php
/**
 * Traveller steppers on the booking screen.
 *
 * Each stepper is a real number input with two buttons beside it. Without
 * JavaScript the input is typed into and the totals are worked out on submit;
 * with it, the buttons take over and the breakdown updates as you go.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package           $package Package being booked.
 * @var array<string, mixed>  $party   Party being priced.
 * @var array<string, string> $errors  Validation errors, keyed by field.
 */

defined( 'ABSPATH' ) || exit;

$tzh_rows = array(
	array(
		'key'   => 'adults',
		'name'  => 'tzh_adults',
		'label' => __( 'Adult', 'travelz-holidays' ),
		/* translators: %s: price per adult */
		'sub'   => sprintf( __( '%s per person', 'travelz-holidays' ), tzh_price( $package->price() ) ),
		'min'   => 1,
		'max'   => 30,
	),
	array(
		'key'   => 'children',
		'name'  => 'tzh_children',
		'label' => __( 'Child', 'travelz-holidays' ),
		/* translators: %s: price per child */
		'sub'   => sprintf( __( '%s per person · Age 2–11', 'travelz-holidays' ), tzh_price( $package->child_price() ) ),
		'min'   => 0,
		'max'   => 30,
	),
	array(
		'key'   => 'infants',
		'name'  => 'tzh_infants',
		'label' => __( 'Infant', 'travelz-holidays' ),
		/* translators: %s: price per infant */
		'sub'   => sprintf( __( '%s per person · Age 0–1', 'travelz-holidays' ), tzh_price( $package->infant_price() ) ),
		'min'   => 0,
		'max'   => 10,
	),
);
?>
<div class="tz-card">
	<div class="tz-card__body">
		<h2 class="tz-heading"><?php esc_html_e( 'Travelers', 'travelz-holidays' ); ?></h2>
		<p class="tz-muted tz-book-hint"><?php esc_html_e( 'Adjust the number of travelers for accurate pricing.', 'travelz-holidays' ); ?></p>

		<?php if ( isset( $errors['travelers'] ) ) : ?>
			<p class="tz-error"><?php echo esc_html( $errors['travelers'] ); ?></p>
		<?php endif; ?>

		<div class="tz-steppers">
			<?php foreach ( $tzh_rows as $tzh_row ) : ?>
				<?php $tzh_id = 'tz-count-' . $tzh_row['key']; ?>
				<div class="tz-stepper">
					<div>
						<label class="tz-stepper__label" for="<?php echo esc_attr( $tzh_id ); ?>">
							<?php echo esc_html( $tzh_row['label'] ); ?>
						</label>
						<span class="tz-stepper__sub"><?php echo esc_html( $tzh_row['sub'] ); ?></span>
					</div>

					<div class="tz-stepper__controls">
						<button type="button" class="tz-round" data-tz-step="-1"
							data-tz-target="<?php echo esc_attr( $tzh_id ); ?>"
							aria-label="<?php
								/* translators: %s: traveller type */
								echo esc_attr( sprintf( __( 'Decrease %s', 'travelz-holidays' ), $tzh_row['label'] ) );
							?>">
							<?php echo tzh_icon( 'minus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
						</button>

						<input class="tz-count" type="number" inputmode="numeric"
							id="<?php echo esc_attr( $tzh_id ); ?>"
							name="<?php echo esc_attr( $tzh_row['name'] ); ?>"
							data-tz-count="<?php echo esc_attr( $tzh_row['key'] ); ?>"
							data-tz-unit="<?php echo esc_attr( (string) ( 'adults' === $tzh_row['key'] ? $package->price() : ( 'children' === $tzh_row['key'] ? $package->child_price() : $package->infant_price() ) ) ); ?>"
							value="<?php echo esc_attr( (string) (int) $party[ $tzh_row['key'] ] ); ?>"
							min="<?php echo esc_attr( (string) $tzh_row['min'] ); ?>"
							max="<?php echo esc_attr( (string) $tzh_row['max'] ); ?>"
							step="1" />

						<button type="button" class="tz-round tz-round--solid" data-tz-step="1"
							data-tz-target="<?php echo esc_attr( $tzh_id ); ?>"
							aria-label="<?php
								/* translators: %s: traveller type */
								echo esc_attr( sprintf( __( 'Increase %s', 'travelz-holidays' ), $tzh_row['label'] ) );
							?>">
							<?php echo tzh_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

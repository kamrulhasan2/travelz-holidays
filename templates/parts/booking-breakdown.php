<?php
/**
 * Price breakdown beside the booking form.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package          $package Package being booked.
 * @var array<string, mixed> $quote   Priced breakdown from TZH_Booking::quote().
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="tz-card tz-booking__panel" data-tz-breakdown>
	<div class="tz-card__body">
		<h2 class="tz-heading"><?php esc_html_e( 'Price breakdown', 'travelz-holidays' ); ?></h2>

		<div class="tz-breakdown">
			<?php foreach ( (array) $quote['lines'] as $tzh_line ) : ?>
				<div class="tz-breakdown__row<?php echo 0 === (int) $tzh_line['count'] ? ' is-zero' : ''; ?>"
					data-tz-line="<?php echo esc_attr( $tzh_line['key'] ); ?>">
					<span data-tz-line-label="<?php echo esc_attr( $tzh_line['type'] ); ?>"><?php echo esc_html( $tzh_line['label'] ); ?></span>
					<b data-tz-line-total><?php echo esc_html( tzh_price( $tzh_line['total'] ) ); ?></b>
				</div>
			<?php endforeach; ?>

			<div class="tz-breakdown__rule" aria-hidden="true"></div>

			<div class="tz-breakdown__row tz-muted">
				<span><?php esc_html_e( 'Subtotal', 'travelz-holidays' ); ?></span>
				<span data-tz-subtotal><?php echo esc_html( tzh_price( (int) $quote['subtotal'] ) ); ?></span>
			</div>

			<div class="tz-breakdown__row tz-muted">
				<span><?php esc_html_e( 'Taxes &amp; fees', 'travelz-holidays' ); ?></span>
				<span><?php esc_html_e( 'Included', 'travelz-holidays' ); ?></span>
			</div>

			<div class="tz-breakdown__total">
				<span class="tz-breakdown__label"><?php esc_html_e( 'Total', 'travelz-holidays' ); ?></span>
				<b data-tz-total><?php echo esc_html( tzh_price( (int) $quote['total'] ) ); ?></b>
			</div>
		</div>

		<noscript>
			<button type="submit" class="tz-btn tz-btn--teal-outline tz-btn--block tz-book-recalc" name="tzh_recalculate" value="1">
				<?php esc_html_e( 'Update total', 'travelz-holidays' ); ?>
			</button>
		</noscript>

		<button type="submit" class="tz-btn tz-btn--teal tz-btn--lg tz-btn--block tz-book-submit" name="tzh_book" value="1">
			<?php esc_html_e( 'Proceed to Payment', 'travelz-holidays' ); ?>
		</button>

		<p class="tz-meta tz-book-terms">
			<?php esc_html_e( 'By continuing you agree to our booking terms.', 'travelz-holidays' ); ?>
		</p>
	</div>
</div>

<?php
/**
 * Confirmation shown after a booking request is accepted.
 *
 * This is the screen a visitor lands on while the plugin has no payment
 * gateway wired up: the request is priced, recorded under a reference and
 * handed to a consultant. Once WooCommerce is connected, the
 * tzh_booking_redirect filter sends the visitor to checkout instead and this
 * part is never reached.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package          $package   Package being booked.
 * @var array<string, mixed> $party     Party that was booked.
 * @var array<string, mixed> $quote     Priced breakdown.
 * @var string               $reference Booking reference.
 */

defined( 'ABSPATH' ) || exit;

$tzh_whatsapp = (string) TZH_Settings::get( 'whatsapp', '' );
$tzh_date     = TZH_Booking::date_label( (string) $party['date'] );

$tzh_message = sprintf(
	/* translators: 1: booking reference, 2: package title, 3: travel date, 4: number of travellers */
	__( 'Hi, I would like to confirm booking %1$s — %2$s, travelling %3$s for %4$s traveler(s).', 'travelz-holidays' ),
	$reference,
	$package->full_title(),
	$tzh_date,
	number_format_i18n( (int) $quote['travelers'] )
);
?>
<div class="tz-done">
	<span class="tz-done__mark" aria-hidden="true">
		<?php echo tzh_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
	</span>

	<h1 class="tz-title tz-title--sm"><?php esc_html_e( 'Your request is in', 'travelz-holidays' ); ?></h1>

	<p class="tz-lede">
		<?php esc_html_e( 'A booking consultant will contact you shortly to confirm availability and take payment.', 'travelz-holidays' ); ?>
	</p>

	<div class="tz-card tz-done__card">
		<div class="tz-card__body">
			<div class="tz-breakdown__row">
				<span class="tz-meta"><?php esc_html_e( 'Reference', 'travelz-holidays' ); ?></span>
				<b class="tz-done__ref"><?php echo esc_html( $reference ); ?></b>
			</div>

			<div class="tz-breakdown__rule" aria-hidden="true"></div>

			<div class="tz-breakdown">
				<div class="tz-breakdown__row">
					<span><?php esc_html_e( 'Package', 'travelz-holidays' ); ?></span>
					<b><?php echo esc_html( $package->full_title() ); ?></b>
				</div>

				<div class="tz-breakdown__row">
					<span><?php esc_html_e( 'Travel date', 'travelz-holidays' ); ?></span>
					<b><?php echo esc_html( $tzh_date ); ?></b>
				</div>

				<?php foreach ( (array) $quote['lines'] as $tzh_line ) : ?>
					<?php if ( 0 === (int) $tzh_line['count'] ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<div class="tz-breakdown__row">
						<span><?php echo esc_html( $tzh_line['label'] ); ?></span>
						<b><?php echo esc_html( tzh_price( $tzh_line['total'] ) ); ?></b>
					</div>
				<?php endforeach; ?>

				<div class="tz-breakdown__rule" aria-hidden="true"></div>

				<div class="tz-breakdown__total">
					<span class="tz-breakdown__label"><?php esc_html_e( 'Total', 'travelz-holidays' ); ?></span>
					<b><?php echo esc_html( tzh_price( (int) $quote['total'] ) ); ?></b>
				</div>
			</div>
		</div>
	</div>

	<div class="tz-done__actions">
		<?php if ( '' !== $tzh_whatsapp ) : ?>
			<a class="tz-btn tz-btn--teal tz-btn--lg"
				href="<?php echo esc_url( 'https://wa.me/' . rawurlencode( $tzh_whatsapp ) . '?text=' . rawurlencode( $tzh_message ) ); ?>"
				target="_blank" rel="noopener noreferrer">
				<?php echo tzh_icon( 'whatsapp', array( 'class' => 'tz-icon--fill' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
				<?php esc_html_e( 'Confirm on WhatsApp', 'travelz-holidays' ); ?>
			</a>
		<?php endif; ?>

		<a class="tz-btn tz-btn--teal-outline tz-btn--lg" href="<?php echo esc_url( TZH_Rewrites::grid_url() ); ?>">
			<?php esc_html_e( 'Browse more packages', 'travelz-holidays' ); ?>
		</a>
	</div>

	<p class="tz-meta"><?php esc_html_e( 'Keep this reference — quote it when a consultant calls.', 'travelz-holidays' ); ?></p>
</div>

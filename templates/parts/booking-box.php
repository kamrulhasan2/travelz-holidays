<?php
/**
 * Booking card beside the package details.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package being shown.
 */

defined( 'ABSPATH' ) || exit;

$tzh_whatsapp = (string) TZH_Settings::get( 'whatsapp', '' );
?>
<div class="tz-booking">
	<span class="tz-meta"><?php esc_html_e( 'Starting from', 'travelz-holidays' ); ?></span>

	<div class="tz-booking__from">
		<span class="tz-booking__amount tz-sunset-text"><?php echo esc_html( tzh_price( $package->price() ) ); ?></span>

		<?php if ( $package->with_airfare() ) : ?>
			<span class="tz-badge tz-badge--sunset tz-booking__air">
				<?php echo tzh_icon( 'plane' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
				<?php esc_html_e( 'With air fare', 'travelz-holidays' ); ?>
			</span>
		<?php endif; ?>
	</div>

	<span class="tz-meta"><?php esc_html_e( 'per person', 'travelz-holidays' ); ?></span>

	<div class="tz-booking__actions">
		<?php if ( '' !== $tzh_whatsapp ) : ?>
			<a class="tz-btn tz-btn--teal-outline tz-btn--icon"
				href="<?php echo esc_url( 'https://wa.me/' . rawurlencode( $tzh_whatsapp ) . '?text=' . rawurlencode( sprintf( /* translators: %s: package title */ __( 'Hi, I would like to know more about %s.', 'travelz-holidays' ), get_the_title( $package->id() ) ) ) ); ?>"
				target="_blank" rel="noopener noreferrer"
				aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'travelz-holidays' ); ?>">
				<?php echo tzh_icon( 'whatsapp', array( 'class' => 'tz-icon--fill' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
			</a>
		<?php endif; ?>

		<a class="tz-btn tz-btn--sunset tz-btn--lg tz-booking__book" href="<?php echo esc_url( $package->action_url( 'book' ) ); ?>">
			<?php esc_html_e( 'Book Now', 'travelz-holidays' ); ?>
		</a>
	</div>

	<a class="tz-btn tz-btn--teal-outline tz-btn--block tz-booking__pdf"
		href="<?php echo esc_url( add_query_arg( 'print', '1', $package->action_url( 'pdf' ) ) ); ?>"
		target="_blank" rel="noopener">
		<?php echo tzh_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
		<?php esc_html_e( 'Download Tour Product Details PDF', 'travelz-holidays' ); ?>
	</a>

	<p class="tz-note tz-note--urgency">
		<?php echo tzh_icon( 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
		<span>
			<?php
			printf(
				/* translators: %s: emphasised "Limited slots" */
				esc_html__( '%s for this month. Book to lock your price.', 'travelz-holidays' ),
				'<strong>' . esc_html__( 'Limited slots', 'travelz-holidays' ) . '</strong>'
			);
			?>
		</span>
	</p>

	<ul class="tz-assurances">
		<li><?php echo tzh_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?> <?php esc_html_e( 'Secure booking', 'travelz-holidays' ); ?></li>
		<li><?php echo tzh_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?> <?php esc_html_e( 'Free itinerary tweaks', 'travelz-holidays' ); ?></li>
		<li><?php echo tzh_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?> <?php esc_html_e( '24/7 traveler support', 'travelz-holidays' ); ?></li>
	</ul>
</div>

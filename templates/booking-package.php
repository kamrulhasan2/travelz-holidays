<?php
/**
 * Booking screen, served at /holiday-packages/…/book/.
 *
 * Copy this file into a theme's travelz-holidays/ folder to customise it.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

get_header();

$tzh_package = TZH_Package::from( get_queried_object() );
$tzh_booking = TZH_Plugin::instance()->module( 'booking' );

if ( ! $tzh_package || ! $tzh_booking instanceof TZH_Booking ) {
	get_footer();

	return;
}

$tzh_destination = $tzh_package->destination();
$tzh_confirmed   = $tzh_booking->confirmed();
$tzh_party       = $tzh_booking->party();
$tzh_quote       = $tzh_confirmed
	? (array) $tzh_confirmed['quote']
	: TZH_Booking::quote( $tzh_package, $tzh_party );
?>
<div id="tz-app">
	<?php
	tzh_template(
		'parts/breadcrumb',
		array(
			'crumbs' => array_values(
				array_filter(
					array(
						array(
							'label' => __( 'Home', 'travelz-holidays' ),
							'url'   => home_url( '/' ),
						),
						array(
							'label' => (string) TZH_Settings::get( 'archive_title', __( 'Holiday Packages', 'travelz-holidays' ) ),
							'url'   => TZH_Rewrites::grid_url(),
						),
						$tzh_destination ? array(
							'label' => $tzh_destination->name,
							'url'   => (string) get_term_link( $tzh_destination ),
						) : null,
						array(
							'label' => get_the_title( $tzh_package->id() ),
							'url'   => (string) get_permalink( $tzh_package->id() ),
						),
						array(
							'label' => __( 'Booking', 'travelz-holidays' ),
							'url'   => '',
						),
					)
				)
			),
		)
	);
	?>

	<div class="tz-wrap tz-section">
		<a class="tz-back" href="<?php echo esc_url( (string) get_permalink( $tzh_package->id() ) ); ?>">
			<?php echo tzh_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
			<?php esc_html_e( 'Back to package', 'travelz-holidays' ); ?>
		</a>

		<?php if ( $tzh_confirmed ) : ?>
			<?php
			tzh_template(
				'parts/booking-done',
				array(
					'package'   => $tzh_package,
					'party'     => $tzh_party,
					'quote'     => $tzh_quote,
					'reference' => (string) ( $tzh_confirmed['reference'] ?? '' ),
				)
			);
			?>
		<?php else : ?>
			<h1 class="tz-title tz-title--sm"><?php esc_html_e( 'Complete Your Booking', 'travelz-holidays' ); ?></h1>
			<p class="tz-muted tz-book-sub"><?php echo esc_html( $tzh_package->full_title() ); ?></p>

			<form class="tz-book-layout" method="post" data-tz-book
				data-tz-min-pax="<?php echo esc_attr( (string) $tzh_package->min_pax() ); ?>"
				action="<?php echo esc_url( $tzh_package->action_url( 'book' ) ); ?>">
				<?php wp_nonce_field( TZH_Booking::NONCE, TZH_Booking::NONCE ); ?>

				<div class="tz-stack">
					<?php
					tzh_template(
						'parts/booking-summary',
						array(
							'package' => $tzh_package,
							'party'   => $tzh_party,
							'errors'  => $tzh_booking->errors(),
						)
					);

					tzh_template(
						'parts/booking-travelers',
						array(
							'package' => $tzh_package,
							'party'   => $tzh_party,
							'errors'  => $tzh_booking->errors(),
						)
					);
					?>

					<p class="tz-note tz-note--soft">
						<?php echo tzh_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
						<span><?php esc_html_e( 'You’ll complete secure payment on the next step.', 'travelz-holidays' ); ?></span>
					</p>
				</div>

				<aside>
					<?php
					tzh_template(
						'parts/booking-breakdown',
						array(
							'package' => $tzh_package,
							'quote'   => $tzh_quote,
						)
					);
					?>
				</aside>
			</form>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();

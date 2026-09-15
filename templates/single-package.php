<?php
/**
 * A single holiday package.
 *
 * Copy this file into a theme's travelz-holidays/ folder to customise it.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

get_header();

$tzh_package = TZH_Package::from( get_queried_object() );

if ( ! $tzh_package ) {
	get_footer();

	return;
}

$tzh_destination = $tzh_package->destination();
$tzh_hero        = $tzh_package->hero_url();

/*
 * A tab only appears when it has something to say. An empty "Visa
 * Requirements" tab is worse than no tab at all — it reads as a broken page
 * rather than as a package that does not need one.
 */
$tzh_tabs = array(
	'plan'  => array(
		'label' => __( 'Tour Plan', 'travelz-holidays' ),
		'part'  => 'parts/tab-plan',
		'show'  => true,
	),
	'price' => array(
		'label' => __( 'Package Price', 'travelz-holidays' ),
		'part'  => 'parts/tab-price',
		'show'  => true,
	),
	'incl'  => array(
		'label' => __( 'Inclusion and Exclusion', 'travelz-holidays' ),
		'part'  => 'parts/tab-incl',
		'show'  => (bool) ( $tzh_package->inclusion() || $tzh_package->exclusion() ),
	),
	'terms' => array(
		'label' => __( 'Terms & Conditions', 'travelz-holidays' ),
		'part'  => 'parts/tab-terms',
		'show'  => (bool) $tzh_package->terms(),
	),
	'other' => array(
		'label' => __( 'Other Details', 'travelz-holidays' ),
		'part'  => 'parts/tab-other',
		'show'  => (bool) $tzh_package->other_details(),
	),
	'visa'  => array(
		'label' => __( 'Visa Requirements', 'travelz-holidays' ),
		'part'  => 'parts/tab-visa',
		'show'  => (bool) $tzh_package->visa_doc_id(),
	),
);

$tzh_tabs  = array_filter(
	$tzh_tabs,
	static function ( array $tab ): bool {
		return $tab['show'];
	}
);
$tzh_first = (string) array_key_first( $tzh_tabs );
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
							'url'   => '',
						),
					)
				)
			),
		)
	);
	?>

	<section class="tz-hero<?php echo '' === $tzh_hero ? ' tz-hero--empty' : ''; ?>">
		<?php if ( '' !== $tzh_hero ) : ?>
			<img class="tz-hero__img" src="<?php echo esc_url( $tzh_hero ); ?>"
				alt="<?php echo esc_attr( $tzh_destination ? $tzh_destination->name : get_the_title( $tzh_package->id() ) ); ?>" />
		<?php endif; ?>

		<span class="tz-hero__shade" aria-hidden="true"></span>

		<div class="tz-wrap tz-hero__inner">
			<div>
				<?php if ( $tzh_package->is_bestseller() ) : ?>
					<span class="tz-badge tz-badge--sunset tz-hero__badge"><?php esc_html_e( 'Bestseller', 'travelz-holidays' ); ?></span>
				<?php endif; ?>

				<h1 class="tz-hero__title"><?php echo esc_html( $tzh_package->full_title() ); ?></h1>

				<?php if ( $tzh_package->rating() > 0 || $tzh_package->booked_count() > 0 ) : ?>
					<p class="tz-hero__rating">
						<?php echo tzh_icon( 'star', array( 'class' => 'tz-icon--fill' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
						<span>
							<?php
							$tzh_bits = array();

							if ( $tzh_package->rating() > 0 ) {
								$tzh_bits[] = number_format_i18n( $tzh_package->rating(), 1 );
							}

							if ( $tzh_package->booked_count() > 0 ) {
								$tzh_bits[] = sprintf(
									/* translators: %s: number of travellers who booked */
									_n( '%s traveler booked', '%s travelers booked', $tzh_package->booked_count(), 'travelz-holidays' ),
									number_format_i18n( $tzh_package->booked_count() )
								);
							}

							echo esc_html( implode( ' · ', $tzh_bits ) );
							?>
						</span>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="tz-quickinfo">
		<div class="tz-wrap tz-quickinfo__inner">
			<?php foreach ( $tzh_package->quick_facts() as $tzh_fact ) : ?>
				<span class="tz-chip tz-chip--bordered">
					<?php echo tzh_icon( $tzh_fact['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
					<?php echo esc_html( $tzh_fact['label'] ); ?>
				</span>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="tz-wrap">
		<div class="tz-detail">
			<div class="tz-detail__main" data-tz-tabs>
				<div class="tz-tabbar">
					<div class="tz-tablist" role="tablist" aria-label="<?php esc_attr_e( 'Package details', 'travelz-holidays' ); ?>">
						<?php foreach ( $tzh_tabs as $tzh_slug => $tzh_tab ) : ?>
							<button type="button" class="tz-tab" role="tab"
								id="tz-tab-<?php echo esc_attr( $tzh_slug ); ?>"
								data-tz-tab="<?php echo esc_attr( $tzh_slug ); ?>"
								aria-controls="tz-panel-<?php echo esc_attr( $tzh_slug ); ?>"
								aria-selected="<?php echo $tzh_slug === $tzh_first ? 'true' : 'false'; ?>">
								<?php echo esc_html( $tzh_tab['label'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>

				<?php foreach ( $tzh_tabs as $tzh_slug => $tzh_tab ) : ?>
					<section class="tz-panel-wrap" role="tabpanel"
						id="tz-panel-<?php echo esc_attr( $tzh_slug ); ?>"
						aria-labelledby="tz-tab-<?php echo esc_attr( $tzh_slug ); ?>"
						data-tz-panel="<?php echo esc_attr( $tzh_slug ); ?>"
						<?php echo $tzh_slug === $tzh_first ? '' : 'hidden'; ?>>
						<?php tzh_template( $tzh_tab['part'], array( 'package' => $tzh_package ) ); ?>
					</section>
				<?php endforeach; ?>
			</div>

			<aside class="tz-detail__aside tz-only-desktop">
				<?php tzh_template( 'parts/booking-box', array( 'package' => $tzh_package ) ); ?>
			</aside>
		</div>
	</div>

	<div class="tz-stickybar">
		<div class="tz-wrap tz-stickybar__inner">
			<div>
				<span class="tz-meta"><?php esc_html_e( 'Starting from', 'travelz-holidays' ); ?></span>
				<span class="tz-stickybar__price">
					<b class="tz-sunset-text"><?php echo esc_html( tzh_price( $tzh_package->price() ) ); ?></b>
					<?php if ( $tzh_package->with_airfare() ) : ?>
						<span class="tz-badge tz-badge--sunset tz-stickybar__air"><?php esc_html_e( 'With air fare', 'travelz-holidays' ); ?></span>
					<?php endif; ?>
				</span>
			</div>

			<a class="tz-btn tz-btn--sunset tz-btn--lg tz-stickybar__book" href="<?php echo esc_url( $tzh_package->action_url( 'book' ) ); ?>">
				<?php esc_html_e( 'Book Now', 'travelz-holidays' ); ?>
			</a>
		</div>
	</div>
</div>
<?php
get_footer();

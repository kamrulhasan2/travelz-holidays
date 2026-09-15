<?php
/**
 * Printable tour details sheet, served at /holiday-packages/…/details/.
 *
 * A standalone document: no get_header(), no theme stylesheet, no plugin
 * script. Everything it needs is inlined, so the page prints identically
 * whether it is opened from the site, saved and reopened, or reached through a
 * CDN that serves assets from another host.
 *
 * Copy this file into a theme's travelz-holidays/ folder to customise it.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

$tzh_package = TZH_Package::from( get_queried_object() );

if ( ! $tzh_package ) {
	wp_die( esc_html__( 'This package could not be found.', 'travelz-holidays' ), '', array( 'response' => 404 ) );
}

$tzh_destination = $tzh_package->destination();
$tzh_tier        = $tzh_package->tier();
$tzh_logo_id     = (int) get_theme_mod( 'custom_logo' );
$tzh_logo        = $tzh_logo_id ? tzh_image_url( $tzh_logo_id, 'medium' ) : '';
$tzh_whatsapp    = (string) TZH_Settings::get( 'whatsapp', '' );
$tzh_itinerary   = $tzh_package->itinerary();
$tzh_highlights  = $tzh_package->highlights();
$tzh_inclusion   = $tzh_package->inclusion();
$tzh_exclusion   = $tzh_package->exclusion();
$tzh_terms       = $tzh_package->terms();
$tzh_other       = $tzh_package->other_details();
$tzh_visa_note   = $tzh_package->visa_note();

/* Facts are built here rather than reused from quick_facts() because a sheet
   that may be filed or forwarded needs the label and the value apart. */
$tzh_facts = array_values(
	array_filter(
		array(
			'' !== $tzh_package->code() ? array( __( 'Package Code', 'travelz-holidays' ), $tzh_package->code() ) : null,
			$tzh_package->days() ? array( __( 'Duration', 'travelz-holidays' ), $tzh_package->duration_label() ) : null,
			array( __( 'Minimum Person', 'travelz-holidays' ), number_format_i18n( $tzh_package->min_pax() ) ),
			array( __( 'Tour Type', 'travelz-holidays' ), $tzh_package->tour_type_label() ),
			$tzh_tier instanceof WP_Term ? array( __( 'Category', 'travelz-holidays' ), $tzh_tier->name ) : null,
			$tzh_destination ? array( __( 'Destination', 'travelz-holidays' ), $tzh_destination->name ) : null,
			array(
				__( 'Air Fare', 'travelz-holidays' ),
				$tzh_package->with_airfare()
					? __( 'Included', 'travelz-holidays' )
					: __( 'Not included', 'travelz-holidays' ),
			),
		)
	)
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( $tzh_package->full_title() . ' — ' . get_bloginfo( 'name' ) ); ?></title>
	<?php
	/*
	 * The one external request on this page. The sheet is legible without it —
	 * every rule falls back to a system stack — but a PDF a traveler forwards
	 * to a friend should still look like the site it came from, and the print
	 * dialog waits for the load event before it opens.
	 */
	?>
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&#038;display=swap" />

	<style><?php TZH_Assets::inline( 'assets/css/print.css' ); ?></style>
</head>
<body>
<?php TZH_Assets::sprite(); ?>

<div class="tz-toolbar tz-no-print">
	<button type="button" class="tz-btn tz-btn--primary" data-tz-print>
		<?php echo tzh_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
		<?php esc_html_e( 'Save as PDF / Print', 'travelz-holidays' ); ?>
	</button>

	<a class="tz-btn tz-btn--ghost" href="<?php echo esc_url( (string) get_permalink( $tzh_package->id() ) ); ?>">
		<?php echo tzh_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
		<?php esc_html_e( 'Back to package', 'travelz-holidays' ); ?>
	</a>

	<p class="tz-toolbar__hint">
		<?php esc_html_e( 'In the print window choose “Save as PDF” as the destination.', 'travelz-holidays' ); ?>
	</p>
</div>

<main class="tz-sheet">
	<header class="tz-head">
		<div>
			<?php if ( '' !== $tzh_logo ) : ?>
				<img class="tz-head__logo" src="<?php echo esc_url( $tzh_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
			<?php else : ?>
				<p class="tz-head__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<?php endif; ?>

			<p class="tz-head__tag"><?php echo esc_html( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></p>
		</div>

		<div class="tz-head__right">
			<p class="tz-head__kind"><?php esc_html_e( 'Tour Product Details', 'travelz-holidays' ); ?></p>
			<p class="tz-head__meta">
				<?php
				printf(
					/* translators: %s: date the sheet was generated */
					esc_html__( 'Issued %s', 'travelz-holidays' ),
					esc_html( date_i18n( (string) get_option( 'date_format' ) ) )
				);
				?>
			</p>
			<?php if ( '' !== $tzh_whatsapp ) : ?>
				<p class="tz-head__meta"><?php echo esc_html( $tzh_whatsapp ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<h1 class="tz-title"><?php echo esc_html( $tzh_package->full_title() ); ?></h1>

	<?php if ( '' !== $tzh_package->short_description() ) : ?>
		<p class="tz-tagline"><?php echo esc_html( $tzh_package->short_description() ); ?></p>
	<?php endif; ?>

	<ul class="tz-badges">
		<?php if ( $tzh_package->is_bestseller() ) : ?>
			<li class="tz-badge tz-badge--sunset"><?php esc_html_e( 'Bestseller', 'travelz-holidays' ); ?></li>
		<?php endif; ?>

		<?php if ( $tzh_package->with_airfare() ) : ?>
			<li class="tz-badge tz-badge--sunset">
				<?php echo tzh_icon( 'plane' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
				<?php esc_html_e( 'With air fare', 'travelz-holidays' ); ?>
			</li>
		<?php endif; ?>

		<?php if ( $tzh_tier instanceof WP_Term ) : ?>
			<li class="tz-badge"><?php echo esc_html( $tzh_tier->name ); ?></li>
		<?php endif; ?>

		<?php if ( $tzh_package->rating() > 0 ) : ?>
			<li class="tz-badge">
				<?php echo tzh_icon( 'star', array( 'class' => 'tz-icon--fill' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
				<?php echo esc_html( number_format_i18n( $tzh_package->rating(), 1 ) ); ?>
			</li>
		<?php endif; ?>
	</ul>

	<section class="tz-section">
		<h2 class="tz-section__title"><?php esc_html_e( 'At a glance', 'travelz-holidays' ); ?></h2>

		<ul class="tz-facts">
			<?php foreach ( $tzh_facts as $tzh_fact ) : ?>
				<li>
					<span><?php echo esc_html( $tzh_fact[0] ); ?></span>
					<b><?php echo esc_html( $tzh_fact[1] ); ?></b>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<section class="tz-section">
		<h2 class="tz-section__title"><?php esc_html_e( 'Package Price', 'travelz-holidays' ); ?></h2>

		<div class="tz-price">
			<div>
				<span class="tz-price__label"><?php esc_html_e( 'Per person (Adult)', 'travelz-holidays' ); ?></span>
				<b class="tz-price__amount"><?php echo esc_html( tzh_price( $tzh_package->price() ) ); ?></b>
			</div>

			<div>
				<span class="tz-price__label"><?php esc_html_e( 'Minimum booking', 'travelz-holidays' ); ?></span>
				<b>
					<?php
					printf(
						/* translators: %s: smallest number of travellers */
						esc_html( _n( '%s person', '%s persons', $tzh_package->min_pax(), 'travelz-holidays' ) ),
						esc_html( number_format_i18n( $tzh_package->min_pax() ) )
					);
					?>
				</b>
			</div>
		</div>

		<table class="tz-table" style="margin-top:.7rem">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Traveler', 'travelz-holidays' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Price per person', 'travelz-holidays' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$tzh_rows = array(
					__( 'Adult (12+)', 'travelz-holidays' )   => $tzh_package->price(),
					__( 'Child (2–11)', 'travelz-holidays' )  => $tzh_package->child_price(),
					__( 'Infant (0–1)', 'travelz-holidays' )  => $tzh_package->infant_price(),
				);

				foreach ( $tzh_rows as $tzh_label => $tzh_amount ) :
					?>
					<tr>
						<td><?php echo esc_html( $tzh_label ); ?></td>
						<td><?php echo esc_html( tzh_price( $tzh_amount ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="tz-note"><?php esc_html_e( 'Child and infant fares are confirmed at checkout. Prices are subject to change until the booking is confirmed.', 'travelz-holidays' ); ?></p>
	</section>

	<?php if ( $tzh_highlights ) : ?>
		<section class="tz-section">
			<h2 class="tz-section__title"><?php esc_html_e( 'Tour Product Highlights', 'travelz-holidays' ); ?></h2>

			<ul class="tz-list tz-list--dot">
				<?php foreach ( $tzh_highlights as $tzh_item ) : ?>
					<li><?php echo esc_html( $tzh_item ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( $tzh_itinerary ) : ?>
		<section class="tz-section">
			<h2 class="tz-section__title"><?php esc_html_e( 'Tour Plan', 'travelz-holidays' ); ?></h2>

			<?php foreach ( $tzh_itinerary as $tzh_index => $tzh_day ) : ?>
				<article class="tz-day">
					<span class="tz-day__no">
						<?php
						printf(
							/* translators: %s: day number */
							esc_html__( 'Day %s', 'travelz-holidays' ),
							esc_html( number_format_i18n( $tzh_index + 1 ) )
						);
						?>
					</span>

					<div class="tz-day__head">
						<h3 class="tz-day__title"><?php echo esc_html( $tzh_day['title'] ); ?></h3>

						<?php if ( '' !== $tzh_day['meals'] ) : ?>
							<span class="tz-day__meals">
								<?php
								printf(
									/* translators: %s: meals included that day */
									esc_html__( 'Meals: %s', 'travelz-holidays' ),
									esc_html( $tzh_day['meals'] )
								);
								?>
							</span>
						<?php endif; ?>
					</div>

					<?php if ( '' !== $tzh_day['body'] ) : ?>
						<p class="tz-day__body"><?php echo esc_html( $tzh_day['body'] ); ?></p>
					<?php endif; ?>

					<?php if ( $tzh_day['hotels'] ) : ?>
						<ul class="tz-hotels">
							<?php foreach ( $tzh_day['hotels'] as $tzh_hotel ) : ?>
								<li>
									<?php echo tzh_icon( 'hotel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
									<b><?php echo esc_html( $tzh_hotel['name'] ); ?></b>
									<span>
										<?php
										echo esc_html(
											implode(
												' · ',
												array_filter(
													array(
														$tzh_hotel['city'],
														$tzh_hotel['class'],
														$tzh_hotel['stay'],
													),
													'strlen'
												)
											)
										);
										?>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

	<?php if ( $tzh_inclusion || $tzh_exclusion ) : ?>
		<section class="tz-section">
			<h2 class="tz-section__title"><?php esc_html_e( 'Inclusion and Exclusion', 'travelz-holidays' ); ?></h2>

			<div class="tz-cols">
				<?php if ( $tzh_inclusion ) : ?>
					<div>
						<h3 class="tz-day__title"><?php esc_html_e( 'What is included', 'travelz-holidays' ); ?></h3>
						<ul class="tz-list tz-list--yes">
							<?php foreach ( $tzh_inclusion as $tzh_item ) : ?>
								<li>
									<?php echo tzh_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
									<span><?php echo esc_html( $tzh_item ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $tzh_exclusion ) : ?>
					<div>
						<h3 class="tz-day__title"><?php esc_html_e( 'What is not included', 'travelz-holidays' ); ?></h3>
						<ul class="tz-list tz-list--no">
							<?php foreach ( $tzh_exclusion as $tzh_item ) : ?>
								<li>
									<?php echo tzh_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
									<span><?php echo esc_html( $tzh_item ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $tzh_package->visa_doc_id() && '' !== $tzh_visa_note ) : ?>
		<section class="tz-section">
			<h2 class="tz-section__title"><?php esc_html_e( 'Visa Requirements', 'travelz-holidays' ); ?></h2>
			<div class="tz-prose"><p><?php echo esc_html( $tzh_visa_note ); ?></p></div>
		</section>
	<?php endif; ?>

	<?php if ( $tzh_terms ) : ?>
		<section class="tz-section">
			<h2 class="tz-section__title"><?php esc_html_e( 'Terms &amp; Conditions', 'travelz-holidays' ); ?></h2>

			<ol class="tz-terms">
				<?php foreach ( $tzh_terms as $tzh_item ) : ?>
					<li><?php echo esc_html( $tzh_item ); ?></li>
				<?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>

	<?php if ( $tzh_other ) : ?>
		<section class="tz-section">
			<h2 class="tz-section__title"><?php esc_html_e( 'Other Details', 'travelz-holidays' ); ?></h2>

			<div class="tz-prose">
				<?php foreach ( $tzh_other as $tzh_paragraph ) : ?>
					<p><?php echo esc_html( $tzh_paragraph ); ?></p>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<footer class="tz-foot">
		<div>
			<b><?php echo esc_html( get_bloginfo( 'name' ) ); ?></b><br />
			<?php if ( '' !== $tzh_whatsapp ) : ?>
				<?php
				printf(
					/* translators: %s: WhatsApp number */
					esc_html__( 'WhatsApp: %s', 'travelz-holidays' ),
					esc_html( $tzh_whatsapp )
				);
				?>
				<br />
			<?php endif; ?>
			<?php echo esc_html( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ); ?>
		</div>

		<div class="tz-foot__right">
			<?php echo esc_html( untrailingslashit( (string) get_permalink( $tzh_package->id() ) ) ); ?><br />
			<?php esc_html_e( 'This sheet is for information only and is not a confirmed booking.', 'travelz-holidays' ); ?>
		</div>
	</footer>
</main>

<script>
( function () {
	var button = document.querySelector( '[data-tz-print]' );

	if ( button ) {
		button.addEventListener( 'click', function () {
			window.print();
		} );
	}

	// Opening the sheet from the package page is a request to save it, so the
	// print dialog comes up on its own — but only once the images that go on
	// the page have settled, or the first page prints with a gap.
	if ( window.location.search.indexOf( 'print=1' ) !== -1 ) {
		window.addEventListener( 'load', function () {
			window.setTimeout( function () {
				window.print();
			}, 300 );
		} );
	}
}() );
</script>
</body>
</html>

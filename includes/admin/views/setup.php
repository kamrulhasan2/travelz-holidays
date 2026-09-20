<?php
/**
 * Setup guide screen.
 *
 * @package TravelZ_Holidays
 *
 * @var array<int, array<string, mixed>> $steps
 * @var array<int, array<string, mixed>> $shortcodes
 * @var int                              $done
 */

defined( 'ABSPATH' ) || exit;

$total = count( $steps );

$tzh_updater   = new TZH_Updater();
$tzh_repo      = $tzh_updater->repo_url();
$tzh_check_url = $tzh_updater->check_url();
?>
<div class="wrap tzh-wrap tzh-setup">
	<h1><?php esc_html_e( 'Setup Guide', 'travelz-holidays' ); ?></h1>

	<p class="tzh-lede">
		<?php esc_html_e( 'Everything needed to get TravelZ Holidays live, in the order it should be done. Each step checks itself — a tick means the site already has it.', 'travelz-holidays' ); ?>
	</p>

	<div class="tzh-progress">
		<div class="tzh-progress__bar">
			<span style="width:<?php echo esc_attr( (string) round( $done / max( 1, $total ) * 100 ) ); ?>%"></span>
		</div>
		<p class="tzh-progress__count">
			<?php
			printf(
				/* translators: 1: steps done, 2: steps in total */
				esc_html__( '%1$s of %2$s done', 'travelz-holidays' ),
				esc_html( number_format_i18n( $done ) ),
				esc_html( number_format_i18n( $total ) )
			);
			?>
		</p>
	</div>

	<ol class="tzh-steps">
		<?php foreach ( $steps as $tzh_index => $tzh_step ) : ?>
			<li class="tzh-step<?php echo $tzh_step['done'] ? ' is-done' : ''; ?>">
				<span class="tzh-step__mark" aria-hidden="true">
					<?php if ( $tzh_step['done'] ) : ?>
						<span class="dashicons dashicons-yes"></span>
					<?php else : ?>
						<?php echo esc_html( number_format_i18n( $tzh_index + 1 ) ); ?>
					<?php endif; ?>
				</span>

				<div class="tzh-step__body">
					<h2 class="tzh-step__title"><?php echo esc_html( (string) $tzh_step['title'] ); ?></h2>
					<p class="tzh-step__text"><?php echo esc_html( (string) $tzh_step['body'] ); ?></p>
				</div>

				<a class="button<?php echo $tzh_step['done'] ? '' : ' button-primary'; ?> tzh-step__action"
					href="<?php echo esc_url( (string) $tzh_step['action'] ); ?>"
					<?php echo $tzh_step['blank'] ? 'target="_blank" rel="noreferrer"' : ''; ?>>
					<?php echo esc_html( (string) $tzh_step['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ol>

	<div class="tzh-panels">
		<section class="tzh-panel">
			<h2><?php esc_html_e( 'Shortcodes', 'travelz-holidays' ); ?></h2>
			<p class="tzh-note">
				<?php esc_html_e( 'Paste one of these into any page or widget. The catalogue itself needs no shortcode — it has its own address.', 'travelz-holidays' ); ?>
			</p>

			<?php foreach ( $shortcodes as $tzh_code ) : ?>
				<div class="tzh-snippet">
					<h3><?php echo esc_html( (string) $tzh_code['title'] ); ?></h3>
					<div class="tzh-snippet__row">
						<code class="tzh-snippet__code"><?php echo esc_html( (string) $tzh_code['code'] ); ?></code>
						<button type="button" class="button tzh-copy" data-tzh-copy-text="<?php echo esc_attr( (string) $tzh_code['code'] ); ?>">
							<?php esc_html_e( 'Copy', 'travelz-holidays' ); ?>
						</button>
					</div>
					<p class="tzh-snippet__help"><?php echo esc_html( (string) $tzh_code['body'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</section>

		<section class="tzh-panel">
			<h2><?php esc_html_e( 'Worth knowing', 'travelz-holidays' ); ?></h2>

			<ul class="tzh-tips">
				<li>
					<b><?php esc_html_e( 'The package code sets the order.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( 'TZ 001 comes before TZ 010 because the number inside the code is what sorts the list — not the title, not the date.', 'travelz-holidays' ); ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Settings are fallbacks, not overrides.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( 'A package that fills in its own inclusion list or terms ignores the site-wide ones completely.', 'travelz-holidays' ); ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Child price is a percentage.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( '70 means 70% of the adult price, recalculated whenever the adult price changes. Infant price is a flat amount.', 'travelz-holidays' ); ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Empty tabs disappear.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( 'A package with no visa document simply has no Visa tab. Leave a section blank rather than filling it with filler text.', 'travelz-holidays' ); ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Templates can be overridden.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( 'Copy any file from the plugin\'s templates folder into a travelz-holidays folder inside your theme and edit it there; updates will not overwrite it.', 'travelz-holidays' ); ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Exclude the booking page from caching.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( 'The plugin already tells LiteSpeed, WP Rocket and W3 Total Cache to skip it. If you use another cache plugin, exclude any URL ending in /book/ by hand.', 'travelz-holidays' ); ?>
				</li>
			</ul>
		</section>

		<section class="tzh-panel">
			<h2><?php esc_html_e( 'Updates', 'travelz-holidays' ); ?></h2>

			<p class="description">
				<?php
				printf(
					/* translators: %s: repository link. */
					esc_html__( 'The plugin checks %s for new versions, so an update arrives on the Plugins screen the same way it would for any plugin from the directory.', 'travelz-holidays' ),
					'<a href="' . esc_url( $tzh_repo ) . '" target="_blank" rel="noopener">GitHub</a>'
				);
				?>
			</p>

			<ol class="tzh-tips">
				<li>
					<b><?php esc_html_e( 'Bump the version.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( 'Edit travelz-holidays.php: the Version line in the header and the TZH_VERSION constant must match. This number is what every site compares against.', 'travelz-holidays' ); ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Push the code.', 'travelz-holidays' ); ?></b>
					<code>git push origin main</code>
				</li>
				<li>
					<b><?php esc_html_e( 'Publish a release.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( 'Tag it with the same version — v0.1.2 — and write the release notes; they show up in the plugin\'s "View details" window.', 'travelz-holidays' ); ?>
					<code>git tag v0.1.2 &amp;&amp; git push origin v0.1.2</code>
				</li>
				<li>
					<b><?php esc_html_e( 'The site picks it up.', 'travelz-holidays' ); ?></b>
					<?php esc_html_e( 'Within six hours, or immediately via "Check for updates" on the Plugins screen. Turn on auto-updates there and the site installs it by itself.', 'travelz-holidays' ); ?>
				</li>
			</ol>

			<p class="description">
				<?php esc_html_e( 'With no release published yet, the version in the plugin header on the main branch is used instead, and the branch is downloaded as the update. A private repository needs a token in Settings → Updates.', 'travelz-holidays' ); ?>
			</p>

			<p>
				<a class="button" href="<?php echo esc_url( $tzh_check_url ); ?>">
					<?php esc_html_e( 'Check for updates now', 'travelz-holidays' ); ?>
				</a>
			</p>
		</section>

	</div>
</div>

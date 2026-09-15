<?php
/**
 * Setup guide screen.
 *
 * @package TravelZ_Holidays
 *
 * @var array<int, array<string, mixed>> $steps
 * @var array<int, array<string, mixed>> $shortcodes
 * @var array<int, array<string, mixed>> $urls
 * @var int                              $done
 */

defined( 'ABSPATH' ) || exit;

$total = count( $steps );
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
			<h2><?php esc_html_e( 'Addresses on your site', 'travelz-holidays' ); ?></h2>
			<p class="tzh-note">
				<?php esc_html_e( 'The plugin builds these itself — no page has to be created for any of them.', 'travelz-holidays' ); ?>
			</p>

			<table class="tzh-list">
				<tbody>
				<?php foreach ( $urls as $tzh_url ) : ?>
					<tr>
						<td>
							<b><?php echo esc_html( (string) $tzh_url['title'] ); ?></b>
							<code class="tzh-url"><?php echo wp_kses( (string) $tzh_url['url'], array() ); ?></code>
							<span class="tzh-muted"><?php echo esc_html( (string) $tzh_url['body'] ); ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
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
			<h2><?php esc_html_e( 'Going live', 'travelz-holidays' ); ?></h2>

			<ol class="tzh-tips tzh-tips--ordered">
				<li><?php esc_html_e( 'Copy the plugin folder to the live site and activate it.', 'travelz-holidays' ); ?></li>
				<li><?php esc_html_e( 'Open Settings → Permalinks and press Save once, so the package URLs register.', 'travelz-holidays' ); ?></li>
				<li><?php esc_html_e( 'Re-enter the WhatsApp number, currency and default lists — settings do not travel with the plugin files.', 'travelz-holidays' ); ?></li>
				<li><?php esc_html_e( 'Clear the site cache and open one package to confirm the styling loaded.', 'travelz-holidays' ); ?></li>
				<li><?php esc_html_e( 'Place one test booking, then refund or cancel it.', 'travelz-holidays' ); ?></li>
			</ol>
		</section>
	</div>
</div>

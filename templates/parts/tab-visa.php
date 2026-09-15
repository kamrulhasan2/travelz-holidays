<?php
/**
 * Visa Requirements tab.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package being shown.
 */

defined( 'ABSPATH' ) || exit;

$tzh_doc  = $package->visa_doc_id();
$tzh_note = $package->visa_note();
$tzh_full = $tzh_doc ? tzh_image_url( $tzh_doc, 'full' ) : '';
?>
<div class="tz-panel">
	<div class="tz-card">
		<div class="tz-card__body">
			<div class="tz-visa__head">
				<h2 class="tz-heading"><?php esc_html_e( 'Visa Requirements', 'travelz-holidays' ); ?></h2>
				<?php if ( $tzh_doc ) : ?>
					<span class="tz-badge tz-badge--outline"><?php esc_html_e( 'Uploaded by admin', 'travelz-holidays' ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $tzh_note ) : ?>
				<p class="tz-muted tz-visa__note"><?php echo esc_html( $tzh_note ); ?></p>
			<?php endif; ?>

			<?php if ( $tzh_doc ) : ?>
				<div class="tz-visa__doc">
					<?php
					echo wp_get_attachment_image(
						$tzh_doc,
						'large',
						false,
						array(
							'alt'     => esc_attr__( 'Visa requirements document', 'travelz-holidays' ),
							'loading' => 'lazy',
						)
					);
					?>
				</div>

				<div class="tz-visa__actions">
					<a class="tz-btn tz-btn--outline" href="<?php echo esc_url( $tzh_full ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View full document', 'travelz-holidays' ); ?>
					</a>
					<a class="tz-btn" href="<?php echo esc_url( $tzh_full ); ?>" download>
						<?php echo tzh_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
						<?php esc_html_e( 'Download', 'travelz-holidays' ); ?>
					</a>
				</div>
			<?php else : ?>
				<p class="tz-muted"><?php esc_html_e( 'Visa paperwork for this destination is handled by your booking consultant.', 'travelz-holidays' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

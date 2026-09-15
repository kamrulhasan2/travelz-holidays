<?php
/**
 * Other Details tab.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package being shown.
 */

defined( 'ABSPATH' ) || exit;

$tzh_paragraphs = $package->other_details();
?>
<div class="tz-panel">
	<div class="tz-card">
		<div class="tz-card__body">
			<h2 class="tz-heading"><?php esc_html_e( 'Other Details', 'travelz-holidays' ); ?></h2>
			<?php if ( $tzh_paragraphs ) : ?>
				<div class="tz-prose">
					<?php foreach ( $tzh_paragraphs as $tzh_paragraph ) : ?>
						<p><?php echo esc_html( $tzh_paragraph ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="tz-muted"><?php esc_html_e( 'Nothing further to note for this package.', 'travelz-holidays' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

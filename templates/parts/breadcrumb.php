<?php
/**
 * Breadcrumb trail.
 *
 * @package TravelZ_Holidays
 *
 * @var array<int, array{label: string, url: string}> $crumbs Trail, last entry is the current page.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $crumbs ) ) {
	return;
}

$tzh_last = count( $crumbs ) - 1;
?>
<nav class="tz-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'travelz-holidays' ); ?>">
	<div class="tz-wrap tz-crumbs__inner">
		<?php foreach ( $crumbs as $tzh_i => $tzh_crumb ) : ?>
			<?php if ( $tzh_i > 0 ) : ?>
				<?php echo tzh_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
			<?php endif; ?>

			<?php if ( $tzh_i === $tzh_last || '' === $tzh_crumb['url'] ) : ?>
				<span class="tz-crumbs__current" aria-current="page"><?php echo esc_html( $tzh_crumb['label'] ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $tzh_crumb['url'] ); ?>"><?php echo esc_html( $tzh_crumb['label'] ); ?></a>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</nav>

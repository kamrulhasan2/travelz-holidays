<?php
/**
 * Admin notices.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Warns about site settings the plugin depends on.
 *
 * Only the ones an administrator has to fix themselves are surfaced here;
 * everything informational stays on the plugin dashboard.
 */
class TZH_Admin_Notices {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_notices', array( $this, 'permalink_notice' ) );
	}

	/**
	 * Package URLs cannot work while permalinks are set to Plain.
	 */
	public function permalink_notice(): void {
		if ( get_option( 'permalink_structure' ) ) {
			return;
		}

		if ( ! current_user_can( tzh_capability() ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( $screen && 'options-permalink' === $screen->id ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			wp_kses_post(
				sprintf(
					/* translators: %s: link to the Permalinks settings screen */
					__( 'TravelZ Holidays needs pretty permalinks for package URLs. Pick any option other than Plain in %s.', 'travelz-holidays' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( admin_url( 'options-permalink.php' ) ),
						esc_html__( 'Settings → Permalinks', 'travelz-holidays' )
					)
				)
			)
		);
	}
}

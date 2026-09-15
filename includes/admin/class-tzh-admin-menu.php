<?php
/**
 * Top-level admin menu.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Holiday Packages" menu and its screens.
 *
 * The post type and taxonomy screens added in phase 2 attach themselves to this
 * menu via show_in_menu, so their position is controlled from here.
 */
class TZH_Admin_Menu {

	/**
	 * Screen hook suffixes registered by this class, keyed by page slug.
	 *
	 * @var array<string, string>
	 */
	private array $screens = array();

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . TZH_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add the top-level menu and its own pages.
	 */
	public function register(): void {
		$capability = tzh_capability();

		add_menu_page(
			__( 'Holiday Packages', 'travelz-holidays' ),
			__( 'Holiday Packages', 'travelz-holidays' ),
			$capability,
			tzh_menu_slug(),
			array( $this, 'render_dashboard' ),
			'dashicons-palmtree',
			26
		);

		$this->screens['dashboard'] = (string) add_submenu_page(
			tzh_menu_slug(),
			__( 'TravelZ Holidays', 'travelz-holidays' ),
			__( 'Dashboard', 'travelz-holidays' ),
			$capability,
			tzh_menu_slug(),
			array( $this, 'render_dashboard' )
		);

		$this->screens['settings'] = (string) add_submenu_page(
			tzh_menu_slug(),
			__( 'TravelZ Holidays Settings', 'travelz-holidays' ),
			__( 'Settings', 'travelz-holidays' ),
			$capability,
			'travelz-holidays-settings',
			array( $this, 'render_settings' ),
			90
		);
	}

	/**
	 * Load admin styles only on this plugin's screens.
	 *
	 * @param string $hook_suffix Current screen hook.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! $this->is_plugin_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'tzh-admin',
			TZH_URL . 'assets/css/admin.css',
			array(),
			TZH_VERSION
		);
	}

	/**
	 * Add a Settings shortcut on the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 *
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( tzh_admin_url( 'travelz-holidays-settings' ) ),
				esc_html__( 'Settings', 'travelz-holidays' )
			)
		);

		return $links;
	}

	/**
	 * Dashboard screen.
	 */
	public function render_dashboard(): void {
		tzh_admin_view(
			'dashboard',
			array(
				'checks' => $this->system_checks(),
			)
		);
	}

	/**
	 * Settings screen placeholder until phase 5.
	 */
	public function render_settings(): void {
		tzh_admin_view( 'settings-placeholder' );
	}

	/**
	 * Whether the given hook suffix belongs to this plugin.
	 *
	 * @param string $hook_suffix Current screen hook.
	 */
	private function is_plugin_screen( string $hook_suffix ): bool {
		return in_array( $hook_suffix, $this->screens, true );
	}

	/**
	 * Environment checks shown on the dashboard.
	 *
	 * Each entry is [ label, value, status ] where status is ok | warn | fail.
	 *
	 * @return array<int, array{label: string, value: string, status: string}>
	 */
	private function system_checks(): array {
		$permalinks = get_option( 'permalink_structure' );

		return array(
			array(
				'label'  => __( 'Plugin version', 'travelz-holidays' ),
				'value'  => TZH_VERSION,
				'status' => 'ok',
			),
			array(
				'label'  => __( 'WordPress', 'travelz-holidays' ),
				'value'  => get_bloginfo( 'version' ),
				'status' => 'ok',
			),
			array(
				'label'  => __( 'PHP', 'travelz-holidays' ),
				'value'  => PHP_VERSION,
				'status' => version_compare( PHP_VERSION, '8.1', '<' ) ? 'warn' : 'ok',
			),
			array(
				'label'  => __( 'Pretty permalinks', 'travelz-holidays' ),
				'value'  => $permalinks
					? __( 'Enabled', 'travelz-holidays' )
					: __( 'Plain — package URLs need this', 'travelz-holidays' ),
				'status' => $permalinks ? 'ok' : 'fail',
			),
			array(
				'label'  => __( 'WooCommerce', 'travelz-holidays' ),
				'value'  => class_exists( 'WooCommerce' )
					? __( 'Active', 'travelz-holidays' )
					: __( 'Not active — needed from phase 13', 'travelz-holidays' ),
				'status' => class_exists( 'WooCommerce' ) ? 'ok' : 'warn',
			),
			array(
				'label'  => __( 'Active theme', 'travelz-holidays' ),
				'value'  => wp_get_theme()->get( 'Name' ),
				'status' => 'ok',
			),
		);
	}
}

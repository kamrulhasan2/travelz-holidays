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
		add_action( 'admin_menu', array( $this, 'arrange_submenu' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . TZH_BASENAME, array( $this, 'action_links' ) );
		add_filter( 'parent_file', array( $this, 'keep_menu_open' ) );
		add_filter( 'submenu_file', array( $this, 'highlight_submenu' ) );
	}

	/**
	 * Submenu slugs in the order they should appear.
	 *
	 * @return string[]
	 */
	private function submenu_order(): array {
		$type = TZH_Package::POST_TYPE;

		return array(
			tzh_menu_slug(),
			'edit.php?post_type=' . $type,
			'post-new.php?post_type=' . $type,
			'edit-tags.php?taxonomy=' . TZH_Package::TAX_DESTINATION . '&post_type=' . $type,
			'edit-tags.php?taxonomy=' . TZH_Package::TAX_TIER . '&post_type=' . $type,
			'edit-tags.php?taxonomy=' . TZH_Package::TAX_FAMILY . '&post_type=' . $type,
			'travelz-holidays-settings',
		);
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

		$this->register_taxonomy_pages();

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
	 * Add the taxonomy screens to this menu.
	 *
	 * WordPress only wires taxonomy submenus to a post type's own menu; because
	 * packages live under a custom parent, the three term screens have to be
	 * attached by hand or they become unreachable.
	 */
	private function register_taxonomy_pages(): void {
		$taxonomies = array(
			TZH_Package::TAX_DESTINATION,
			TZH_Package::TAX_TIER,
			TZH_Package::TAX_FAMILY,
		);

		foreach ( $taxonomies as $taxonomy ) {
			$object = get_taxonomy( $taxonomy );

			if ( ! $object ) {
				continue;
			}

			add_submenu_page(
				tzh_menu_slug(),
				$object->labels->name,
				$object->labels->menu_name,
				$object->cap->manage_terms,
				'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=' . TZH_Package::POST_TYPE
			);
		}
	}

	/**
	 * Put the submenu in a sensible reading order.
	 *
	 * Entries WordPress adds on its own (All Packages) land wherever they land,
	 * so the whole list is re-sorted once every item is registered.
	 */
	public function arrange_submenu(): void {
		global $submenu;

		$parent = tzh_menu_slug();

		if ( empty( $submenu[ $parent ] ) ) {
			return;
		}

		$order = array_flip( $this->submenu_order() );
		$items = $submenu[ $parent ];

		usort(
			$items,
			static function ( $a, $b ) use ( $order ) {
				$rank_a = $order[ $a[2] ] ?? PHP_INT_MAX;
				$rank_b = $order[ $b[2] ] ?? PHP_INT_MAX;

				return $rank_a <=> $rank_b;
			}
		);

		$submenu[ $parent ] = array_values( $items ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Keep the Holiday Packages menu open on package and term screens.
	 *
	 * @param string $parent_file Current parent menu slug.
	 */
	public function keep_menu_open( string $parent_file ): string {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return $parent_file;
		}

		if ( TZH_Package::POST_TYPE === $screen->post_type ) {
			return tzh_menu_slug();
		}

		return $parent_file;
	}

	/**
	 * Highlight the right term screen in the submenu.
	 *
	 * @param string|null $submenu_file Current submenu slug.
	 */
	public function highlight_submenu( ?string $submenu_file ): ?string {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-tags' !== $screen->base ) {
			return $submenu_file;
		}

		$taxonomies = array(
			TZH_Package::TAX_DESTINATION,
			TZH_Package::TAX_TIER,
			TZH_Package::TAX_FAMILY,
		);

		if ( ! in_array( $screen->taxonomy, $taxonomies, true ) ) {
			return $submenu_file;
		}

		return 'edit-tags.php?taxonomy=' . $screen->taxonomy . '&post_type=' . TZH_Package::POST_TYPE;
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
				'checks'    => $this->system_checks(),
				'catalogue' => $this->catalogue_counts(),
			)
		);
	}

	/**
	 * Settings screen.
	 */
	public function render_settings(): void {
		$page = TZH_Plugin::instance()->module( 'settings_page' );

		if ( $page instanceof TZH_Settings_Page ) {
			$page->render();
		}
	}

	/**
	 * Whether the current screen belongs to this plugin.
	 *
	 * Covers the plugin's own pages plus every package and taxonomy screen, so
	 * the stylesheet loads exactly where it is needed and nowhere else.
	 *
	 * @param string $hook_suffix Current screen hook.
	 */
	private function is_plugin_screen( string $hook_suffix ): bool {
		if ( in_array( $hook_suffix, $this->screens, true ) ) {
			return true;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		if ( TZH_Package::POST_TYPE === $screen->post_type ) {
			return true;
		}

		return in_array(
			$screen->taxonomy,
			array( TZH_Package::TAX_DESTINATION, TZH_Package::TAX_TIER, TZH_Package::TAX_FAMILY ),
			true
		);
	}

	/**
	 * Catalogue tallies shown on the dashboard.
	 *
	 * @return array<int, array{label: string, count: int, url: string}>
	 */
	private function catalogue_counts(): array {
		$counts = wp_count_posts( TZH_Package::POST_TYPE );

		return array(
			array(
				'label' => __( 'Published packages', 'travelz-holidays' ),
				'count' => (int) ( $counts->publish ?? 0 ),
				'url'   => admin_url( 'edit.php?post_type=' . TZH_Package::POST_TYPE ),
			),
			array(
				'label' => __( 'Drafts', 'travelz-holidays' ),
				'count' => (int) ( $counts->draft ?? 0 ),
				'url'   => admin_url( 'edit.php?post_status=draft&post_type=' . TZH_Package::POST_TYPE ),
			),
			array(
				'label' => __( 'Destinations', 'travelz-holidays' ),
				'count' => (int) wp_count_terms(
					array(
						'taxonomy'   => TZH_Package::TAX_DESTINATION,
						'hide_empty' => false,
					)
				),
				'url'   => admin_url( 'edit-tags.php?taxonomy=' . TZH_Package::TAX_DESTINATION . '&post_type=' . TZH_Package::POST_TYPE ),
			),
			array(
				'label' => __( 'Tour groups', 'travelz-holidays' ),
				'count' => (int) wp_count_terms(
					array(
						'taxonomy'   => TZH_Package::TAX_FAMILY,
						'hide_empty' => false,
					)
				),
				'url'   => admin_url( 'edit-tags.php?taxonomy=' . TZH_Package::TAX_FAMILY . '&post_type=' . TZH_Package::POST_TYPE ),
			),
		);
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

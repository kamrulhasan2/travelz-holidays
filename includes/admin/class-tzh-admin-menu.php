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
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 3 );
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
			TZH_Bookings_Page::SLUG,
			TZH_Setup_Page::SLUG,
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

		$this->screens['bookings'] = (string) add_submenu_page(
			tzh_menu_slug(),
			__( 'Bookings', 'travelz-holidays' ),
			__( 'Bookings', 'travelz-holidays' ),
			$capability,
			TZH_Bookings_Page::SLUG,
			array( $this, 'render_bookings' ),
			80
		);

		$this->screens['setup'] = (string) add_submenu_page(
			tzh_menu_slug(),
			__( 'Setup Guide', 'travelz-holidays' ),
			__( 'Setup Guide', 'travelz-holidays' ),
			$capability,
			TZH_Setup_Page::SLUG,
			array( $this, 'render_setup' ),
			85
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
	/*
	 * Hook callbacks take their arguments untyped on purpose. WordPress lets
	 * any plugin filter a value before this one sees it, and a plugin that
	 * hands back null where a string is documented would turn a declared
	 * parameter into a fatal TypeError on somebody else's screen. Values are
	 * checked here instead, where a surprise is a no-op rather than a crash.
	 */

	public function keep_menu_open( $parent_file ) {
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
	public function highlight_submenu( $submenu_file = null ) {
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
	public function enqueue( $hook_suffix = '' ) {
		if ( ! $this->is_plugin_screen( (string) $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'tzh-admin',
			TZH_URL . 'assets/css/admin.css',
			array(),
			TZH_Assets::version( 'assets/css/admin.css' )
		);

		if ( ( $this->screens['setup'] ?? '' ) === $hook_suffix ) {
			wp_enqueue_script(
				'tzh-setup',
				TZH_URL . 'assets/js/setup.js',
				array(),
				TZH_Assets::version( 'assets/js/setup.js' ),
				true
			);

			wp_localize_script(
				'tzh-setup',
				'tzhSetup',
				array(
					'copied' => __( 'Copied', 'travelz-holidays' ),
					'copy'   => __( 'Copy', 'travelz-holidays' ),
				)
			);
		}
	}

	/**
	 * Add a Settings shortcut on the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 *
	 * @return string[]
	 */
	public function action_links( $links ) {
		if ( ! is_array( $links ) ) {
			return $links;
		}

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
	 * Say who developed the plugin, rather than WordPress's bare "By".
	 *
	 * The row is rebuilt rather than string-replaced: "By %s" is a translated
	 * core string, so looking for the English word would quietly do nothing on
	 * a site running in another language.
	 *
	 * @param string[]             $meta   Row meta links.
	 * @param string               $file   Plugin file the row belongs to.
	 * @param array<string, mixed> $data   Plugin header data.
	 *
	 * @return string[]
	 */
	public function row_meta( $meta, $file = '', $data = array() ) {
		if ( ! is_array( $meta ) || ! is_array( $data ) || TZH_BASENAME !== $file ) {
			return $meta;
		}

		$author = (string) ( $data['Author'] ?? '' );

		if ( '' === $author ) {
			return $meta;
		}

		if ( ! empty( $data['AuthorURI'] ) ) {
			$author = sprintf(
				'<a href="%s">%s</a>',
				esc_url( (string) $data['AuthorURI'] ),
				esc_html( wp_strip_all_tags( $author ) )
			);
		}

		// phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Deliberately the core string, to find the row core built.
		$core = sprintf( __( 'By %s' ), $author );

		foreach ( $meta as $index => $entry ) {
			if ( $entry !== $core ) {
				continue;
			}

			$meta[ $index ] = sprintf(
				/* translators: %s: plugin author, linked */
				__( 'Developed by %s', 'travelz-holidays' ),
				$author
			);

			break;
		}

		return $meta;
	}

	/**
	 * Dashboard screen.
	 */
	public function render_dashboard(): void {
		$page = TZH_Plugin::instance()->module( 'dashboard' );

		if ( $page instanceof TZH_Dashboard ) {
			$page->render();
		}
	}

	/**
	 * Bookings screen.
	 */
	public function render_bookings(): void {
		$page = TZH_Plugin::instance()->module( 'bookings_page' );

		if ( $page instanceof TZH_Bookings_Page ) {
			$page->render();
		}
	}

	/**
	 * Setup guide screen.
	 */
	public function render_setup(): void {
		$page = TZH_Plugin::instance()->module( 'setup_page' );

		if ( $page instanceof TZH_Setup_Page ) {
			$page->render();
		}
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
}

<?php
/**
 * Plugin container.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads every module the plugin is made of.
 *
 * Each phase of the build adds one line to modules(); nothing else in the
 * plugin needs to know what else exists.
 */
final class TZH_Plugin {

	/**
	 * Singleton instance.
	 */
	private static ?TZH_Plugin $instance = null;

	/**
	 * Booted modules, keyed by short name.
	 *
	 * @var array<string, object>
	 */
	private array $modules = array();

	/**
	 * Guard so run() is idempotent.
	 */
	private bool $booted = false;

	/**
	 * Private: use instance().
	 */
	private function __construct() {}

	/**
	 * Shared instance.
	 */
	public static function instance(): TZH_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot every module once.
	 */
	public function run(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->load_textdomain();

		foreach ( $this->module_classes() as $key => $class_name ) {
			if ( ! class_exists( $class_name ) ) {
				continue;
			}

			$module = new $class_name();

			if ( method_exists( $module, 'hooks' ) ) {
				$module->hooks();
			}

			$this->modules[ $key ] = $module;
		}

		/**
		 * Fires once every module has registered its hooks.
		 *
		 * @param TZH_Plugin $plugin Plugin container.
		 */
		do_action( 'tzh_loaded', $this );
	}

	/**
	 * Fetch a booted module by its short name.
	 */
	public function module( string $key ): ?object {
		return $this->modules[ $key ] ?? null;
	}

	/**
	 * Module map. Later phases append to this list.
	 *
	 * @return array<string, string>
	 */
	private function module_classes(): array {
		$modules = array(
			'taxonomies' => 'TZH_Taxonomies',
			'post_types' => 'TZH_Post_Types',
			'install'    => 'TZH_Install',
		);

		if ( is_admin() ) {
			$modules['admin_menu']      = 'TZH_Admin_Menu';
			$modules['admin_notices']   = 'TZH_Admin_Notices';
			$modules['package_columns'] = 'TZH_Package_Columns';
			$modules['meta_box']        = 'TZH_Meta_Box';
			$modules['settings_page']   = 'TZH_Settings_Page';
			$modules['term_fields']     = 'TZH_Term_Fields';
			$modules['variant']         = 'TZH_Variant';
		}

		/**
		 * Filter the modules the plugin boots.
		 *
		 * @param array<string, string> $modules Class names keyed by short name.
		 */
		return apply_filters( 'tzh_modules', $modules );
	}

	/**
	 * Load translations from /languages.
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'travelz-holidays',
			false,
			dirname( TZH_BASENAME ) . '/languages'
		);
	}
}

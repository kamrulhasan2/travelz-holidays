<?php
/**
 * Class autoloader.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Maps TZH_* class names to WordPress-style file names.
 *
 * TZH_Admin_Menu is looked for as class-tzh-admin-menu.php inside each
 * registered directory, so classes can move between includes/ and admin/
 * without touching any require statement.
 */
class TZH_Autoloader {

	/**
	 * Directories searched for class files, in order.
	 *
	 * @var string[]
	 */
	private static array $paths = array(
		'includes/',
		'includes/admin/',
		'includes/frontend/',
		'includes/integrations/',
	);

	/**
	 * Hook the autoloader onto the SPL stack.
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Resolve and include the file for a class name.
	 *
	 * @param string $class_name Fully qualified class name.
	 */
	public static function load( string $class_name ): void {
		if ( ! str_starts_with( $class_name, 'TZH_' ) ) {
			return;
		}

		$file = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		foreach ( self::$paths as $path ) {
			$candidate = TZH_DIR . $path . $file;

			if ( is_readable( $candidate ) ) {
				require_once $candidate;

				return;
			}
		}
	}
}

<?php
/**
 * Template loading.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Finds a template, letting the theme override it.
 *
 * A copy of any template placed in a (child) theme's travelz-holidays/ folder
 * wins over the plugin's own, so a site can restyle a single card without
 * forking the plugin — and without losing the change on the next update.
 */
class TZH_Template {

	/**
	 * Folder a theme puts its overrides in.
	 */
	public const THEME_DIR = 'travelz-holidays';

	/**
	 * Locate a template file.
	 *
	 * @param string $name Template path relative to templates/, without .php.
	 */
	public static function locate( string $name ): string {
		$file = ltrim( $name, '/' ) . '.php';

		$theme = locate_template(
			array(
				self::THEME_DIR . '/' . $file,
			)
		);

		if ( $theme ) {
			return $theme;
		}

		$plugin = TZH_DIR . 'templates/' . $file;

		/**
		 * Filter the resolved template path.
		 *
		 * @param string $plugin Path to the template that will be loaded.
		 * @param string $name   Template name that was requested.
		 */
		return (string) apply_filters( 'tzh_template_path', $plugin, $name );
	}

	/**
	 * Render a template.
	 *
	 * @param string               $name Template path relative to templates/.
	 * @param array<string, mixed> $data Variables made available to it.
	 */
	public static function render( string $name, array $data = array() ): void {
		$file = self::locate( $name );

		if ( ! is_readable( $file ) ) {
			return;
		}

		TZH_Assets::need();

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Controlled template data.
		extract( $data, EXTR_SKIP );

		include $file;
	}

	/**
	 * Render a template and return it as a string.
	 *
	 * @param string               $name Template path relative to templates/.
	 * @param array<string, mixed> $data Variables made available to it.
	 */
	public static function capture( string $name, array $data = array() ): string {
		ob_start();
		self::render( $name, $data );

		return (string) ob_get_clean();
	}
}

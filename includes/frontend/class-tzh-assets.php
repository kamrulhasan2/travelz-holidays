<?php
/**
 * Front-end assets.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the stylesheet, the fonts and the icon sprite — and only where needed.
 *
 * A travel plugin has no business slowing down the rest of a site, so nothing
 * is enqueued until a page actually renders package markup. Templates call
 * TZH_Assets::need() to say so.
 */
class TZH_Assets {

	/**
	 * Whether this request renders plugin markup.
	 */
	private static bool $needed = false;

	/**
	 * Guard so the sprite is printed at most once.
	 */
	private static bool $sprite_printed = false;

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ), 20 );
		add_action( 'wp_footer', array( $this, 'maybe_enqueue' ), 1 );
		add_action( 'wp_footer', array( $this, 'print_sprite' ), 5 );
	}

	/**
	 * Mark this request as needing the plugin's assets.
	 *
	 * Safe to call from a shortcode, which runs after wp_enqueue_scripts —
	 * the footer pass picks those up.
	 */
	public static function need(): void {
		self::$needed = true;

		if ( did_action( 'wp_enqueue_scripts' ) && ! wp_style_is( 'tzh-front', 'enqueued' ) ) {
			wp_enqueue_style( 'tzh-front' );
			wp_enqueue_script( 'tzh-front' );
		}
	}

	/**
	 * Whether the assets have been asked for.
	 */
	public static function is_needed(): bool {
		return self::$needed;
	}


	/**
	 * Cache-busting version for a bundled asset.
	 *
	 * In production this is the plugin version, which changes once per release
	 * and keeps the file cacheable. On a local or development site the file's
	 * own timestamp is appended, so an edit shows up on the next reload instead
	 * of hiding behind a stale stylesheet.
	 *
	 * @param string $relative Path under the plugin folder.
	 */
	public static function version( string $relative ): string {
		if ( ! self::is_development() ) {
			return TZH_VERSION;
		}

		$file = TZH_DIR . ltrim( $relative, '/' );

		return is_readable( $file )
			? TZH_VERSION . '.' . (string) filemtime( $file )
			: TZH_VERSION;
	}

	/**
	 * Whether this install is somebody's working copy.
	 *
	 * wp_get_environment_type() answers "production" unless a site has been
	 * told otherwise in wp-config.php, which a local XAMPP or MAMP install
	 * almost never has been — and the one place a stale stylesheet costs real
	 * time is exactly there. So the host name is consulted too.
	 */
	public static function is_development(): bool {
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		if ( in_array( $environment, array( 'local', 'development' ), true ) ) {
			return true;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

		if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		foreach ( array( '.local', '.test', '.localhost', '.dev' ) as $suffix ) {
			if ( str_ends_with( $host, $suffix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Print a file shipped with the plugin straight into the page.
	 *
	 * Used for the icon sprite and for the print sheet's stylesheet, both of
	 * which have to survive a CDN that rewrites asset URLs to another host.
	 *
	 * @param string $relative Path under the plugin folder.
	 */
	public static function inline( string $relative ): void {
		$file = TZH_DIR . ltrim( $relative, '/' );

		if ( ! is_readable( $file ) ) {
			return;
		}

		// Plugin-owned files, never user input. wp_kses would lowercase the
		// SVG's viewBox attribute and drop the symbols the icons depend on, so
		// they are printed as authored.
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile, WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-owned asset.
	}

	/**
	 * Print the icon sprite, at most once per request.
	 */
	public static function sprite(): void {
		if ( self::$sprite_printed ) {
			return;
		}

		self::$sprite_printed = true;

		self::inline( 'assets/icons/tz-icons.svg' );
	}

	/**
	 * Register everything without enqueueing it.
	 */
	public function register(): void {
		wp_register_style(
			'tzh-fonts',
			'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URLs carry their own cache key.
		);

		wp_register_style(
			'tzh-front',
			TZH_URL . 'assets/css/travelz.css',
			array( 'tzh-fonts' ),
			self::version( 'assets/css/travelz.css' )
		);

		wp_register_script(
			'tzh-front',
			TZH_URL . 'assets/js/travelz.js',
			array(),
			self::version( 'assets/js/travelz.js' ),
			true
		);

		// Nothing on these screens needs the script before first paint: the
		// tabs, the steppers and the filters all enhance markup that already
		// works without them.
		wp_script_add_data( 'tzh-front', 'strategy', 'defer' );

		wp_localize_script(
			'tzh-front',
			'tzhFront',
			array(
				'currency' => (string) TZH_Settings::get( 'currency_symbol', '৳' ),
				'rest'     => esc_url_raw( rest_url( 'tzh/v1/packages' ) ),
				'i18n'     => array(
					'filters'     => __( 'Filters', 'travelz-holidays' ),
					'close'       => __( 'Close', 'travelz-holidays' ),
					'loading'     => __( 'Loading packages…', 'travelz-holidays' ),
					'pickDate'    => __( 'Please pick a travel date.', 'travelz-holidays' ),
					'minPax'      => __( 'Please add more travelers for this package.', 'travelz-holidays' ),
					'redirecting' => __( 'Sending you to secure checkout…', 'travelz-holidays' ),
				),
			)
		);
	}

	/**
	 * Enqueue on package screens, or wherever a template asked.
	 */
	public function maybe_enqueue(): void {
		if ( ! self::$needed && $this->is_package_screen() ) {
			self::$needed = true;
		}

		if ( ! self::$needed ) {
			return;
		}

		wp_enqueue_style( 'tzh-front' );
		wp_enqueue_script( 'tzh-front' );
	}

	/**
	 * Drop the icon sprite into the page once.
	 *
	 * Inlined rather than linked: an external <use href="file.svg#id"> only
	 * resolves same-origin, so the moment a cache plugin or CDN serves assets
	 * from another host every icon would silently vanish. This costs a few
	 * kilobytes and cannot break.
	 */
	public function print_sprite(): void {
		if ( ! self::$needed ) {
			return;
		}

		self::sprite();
	}

	/**
	 * Whether the current query is one of the plugin's own screens.
	 */
	private function is_package_screen(): bool {
		return is_singular( TZH_Package::POST_TYPE )
			|| is_post_type_archive( TZH_Package::POST_TYPE )
			|| is_tax(
				array(
					TZH_Package::TAX_DESTINATION,
					TZH_Package::TAX_TIER,
				)
			);
	}
}

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
			TZH_VERSION
		);

		wp_register_script(
			'tzh-front',
			TZH_URL . 'assets/js/travelz.js',
			array(),
			TZH_VERSION,
			true
		);

		wp_localize_script(
			'tzh-front',
			'tzhFront',
			array(
				'currency' => (string) TZH_Settings::get( 'currency_symbol', '৳' ),
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
		if ( ! self::$needed || self::$sprite_printed ) {
			return;
		}

		$file = TZH_DIR . 'assets/icons/tz-icons.svg';

		if ( ! is_readable( $file ) ) {
			return;
		}

		self::$sprite_printed = true;

		// A static file shipped with the plugin, never user input. Running it
		// through wp_kses would lowercase viewBox and drop the symbol elements
		// the icons depend on, so it is printed as authored.
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile, WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-owned SVG sprite.
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

<?php
/**
 * Theme compatibility.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asks the active theme to get out of the way on the plugin's own screens.
 *
 * The catalogue is designed edge to edge, with its own container and spacing.
 * A theme sidebar or a second content wrapper would fight it, so on these
 * screens — and only these — the layout is pushed to full width.
 */
class TZH_Theme_Compat {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'template_redirect', array( $this, 'apply' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Whether the plugin is rendering this request.
	 */
	public static function is_plugin_screen(): bool {
		return is_post_type_archive( TZH_Package::POST_TYPE )
			|| is_singular( TZH_Package::POST_TYPE )
			|| is_tax( TZH_Package::TAX_DESTINATION );
	}

	/**
	 * Hook the theme's layout filters once we know the screen.
	 */
	public function apply(): void {
		if ( ! self::is_plugin_screen() ) {
			return;
		}

		/**
		 * Filter whether to force a full-width, sidebar-free layout.
		 *
		 * Return false to keep the theme's own layout for package screens.
		 *
		 * @param bool $full_width Whether to take over the layout.
		 */
		if ( ! apply_filters( 'tzh_force_full_width', true ) ) {
			return;
		}

		// Astra: drop the sidebar and the inner content container.
		add_filter( 'astra_page_layout', array( $this, 'no_sidebar' ), 99 );
		add_filter( 'astra_get_content_layout', array( $this, 'page_builder' ), 99 );

		// Generic themes that respect the core sidebar filters.
		add_filter( 'is_active_sidebar', array( $this, 'hide_sidebar' ), 99, 2 );
	}

	/**
	 * Astra layout: no sidebar.
	 */
	public function no_sidebar(): string {
		return 'no-sidebar';
	}

	/**
	 * Astra content layout: let the page provide its own container.
	 */
	public function page_builder(): string {
		return 'page-builder';
	}

	/**
	 * Report the main sidebar as empty on these screens.
	 *
	 * @param bool   $is_active Whether the sidebar has widgets.
	 * @param string $index     Sidebar id.
	 */
	public function hide_sidebar( $is_active, $index = '' ) {
		if ( in_array( (string) $index, array( 'sidebar-1', 'sidebar-2' ), true ) ) {
			return false;
		}

		return $is_active;
	}

	/**
	 * Mark the page so a theme or site CSS can target it.
	 *
	 * @param string[] $classes Body classes.
	 *
	 * @return string[]
	 */
	public function body_class( $classes ) {
		if ( ! is_array( $classes ) ) {
			return $classes;
		}

		if ( self::is_plugin_screen() ) {
			$classes[] = 'tzh-screen';
		}

		return $classes;
	}
}

<?php
/**
 * Template routing.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hands each of the plugin's screens to its own template.
 *
 * The templates call get_header() and get_footer() themselves, so the site
 * keeps its own header, menu and footer — only the content area belongs to the
 * plugin.
 */
class TZH_Router {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_filter( 'template_include', array( $this, 'route' ), 20 );
		add_action( 'pre_get_posts', array( $this, 'filter_destination_archive' ) );
		add_filter( 'document_title_parts', array( $this, 'title' ) );
	}

	/**
	 * Pick the template for the current request.
	 *
	 * @param string $template Template WordPress settled on.
	 */
	public function route( string $template ): string {
		$name = $this->template_name();

		if ( '' === $name ) {
			return $template;
		}

		$file = TZH_Template::locate( $name );

		if ( ! is_readable( $file ) ) {
			return $template;
		}

		// The print sheet is a standalone document: it inlines its own styles
		// and its own copy of the sprite, so the site's bundle would only be
		// dead weight in the page it produces.
		if ( 'print-package' !== $name ) {
			TZH_Assets::need();
		}

		return $file;
	}

	/**
	 * Give the destination grid a sensible browser title.
	 *
	 * @param array<string, string> $parts Title parts.
	 *
	 * @return array<string, string>
	 */
	public function title( array $parts ): array {
		if ( is_post_type_archive( TZH_Package::POST_TYPE ) ) {
			$parts['title'] = (string) TZH_Settings::get(
				'archive_title',
				__( 'Explore Holiday Packages', 'travelz-holidays' )
			);
		}

		return $parts;
	}

	/**
	 * Apply the URL's filters to the destination archive.
	 *
	 * Done on the main query rather than in a second one, so pagination,
	 * canonical URLs and the found-posts count all stay WordPress's own.
	 *
	 * @param WP_Query $query Query about to run.
	 */
	public function filter_destination_archive( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters from the URL.
		$source = wp_unslash( $_GET );

		if ( $query->is_tax( TZH_Package::TAX_DESTINATION ) ) {
			$term = (string) $query->get( TZH_Package::TAX_DESTINATION );
		} elseif ( $query->is_post_type_archive( TZH_Package::POST_TYPE ) && TZH_Query::requested( $source ) ) {
			// The catalogue is not tied to one country, which is the only place
			// a visitor can pick several at once.
			$term = '';
		} else {
			return;
		}

		TZH_Query::apply( $query, TZH_Query::filters( $source, $term ) );
	}

	/**
	 * Which template this request needs, or '' for none of ours.
	 */
	private function template_name(): string {
		if ( is_post_type_archive( TZH_Package::POST_TYPE ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters from the URL.
			return TZH_Query::requested( wp_unslash( $_GET ) ) ? 'archive-packages' : 'archive-destinations';
		}

		if ( is_tax( TZH_Package::TAX_DESTINATION ) ) {
			return 'archive-packages';
		}

		if ( ! is_singular( TZH_Package::POST_TYPE ) ) {
			return '';
		}

		switch ( TZH_Rewrites::current_action() ) {
			case 'pdf':
				return 'print-package';

			case 'book':
				return 'booking-package';

			case '':
				return 'single-package';
		}

		return '';
	}
}

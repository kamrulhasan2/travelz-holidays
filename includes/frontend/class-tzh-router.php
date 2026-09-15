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

		TZH_Assets::need();

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
	 * Which template this request needs, or '' for none of ours.
	 */
	private function template_name(): string {
		if ( is_post_type_archive( TZH_Package::POST_TYPE ) ) {
			return 'archive-destinations';
		}

		return '';
	}
}

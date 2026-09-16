<?php
/**
 * URL structure.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Owns every URL the plugin answers on.
 *
 * WordPress can generate these rules itself from a post type slug, but only if
 * the post type and the taxonomy live on different bases. Here they share one —
 * /holiday-packages/nepal/ is a destination and /holiday-packages/nepal/tz-001/
 * is a package — so the rules are written out by hand, most specific first,
 * which also makes the match order readable instead of emergent.
 */
class TZH_Rewrites {

	/**
	 * Base path for everything this plugin serves.
	 */
	public const BASE = 'holiday-packages';

	/**
	 * Query var naming the sub-screen of a package: book or pdf.
	 */
	public const ACTION_VAR = 'tzh_action';

	/**
	 * Destination slug used when a package has none.
	 */
	private const ORPHAN = 'other';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'add_rules' ), 10 );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'post_type_link', array( $this, 'package_link' ), 10, 3 );
		add_filter( 'term_link', array( $this, 'destination_link' ), 10, 3 );
		add_filter( 'post_type_archive_link', array( $this, 'archive_link' ), 10, 2 );
		add_filter( 'redirect_canonical', array( $this, 'keep_sub_screens' ) );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
	}

	/**
	 * Stop WordPress redirecting a sub-screen back to the package.
	 *
	 * /…/tz-001/details/ is a singular package query with a different URL, and
	 * redirect_canonical() exists to bounce exactly that back to the permalink.
	 * Without this the print sheet could never be reached.
	 *
	 * @param string|false $redirect URL WordPress wants to send the visitor to.
	 *
	 * @return string|false
	 */
	public function keep_sub_screens( $redirect ) {
		return '' === self::current_action() ? $redirect : false;
	}

	/**
	 * Keep the sub-screens out of search results.
	 *
	 * The print sheet repeats the package page word for word, so indexing it
	 * would put two of the same page in front of Google.
	 *
	 * @param array<string, mixed> $robots Robots directives.
	 *
	 * @return array<string, mixed>
	 */
	public function robots( $robots ) {
		if ( ! is_array( $robots ) || '' === self::current_action() ) {
			return $robots;
		}

		return wp_robots_no_robots( $robots );
	}

	/**
	 * The base path, filterable so a site can rename it.
	 */
	public static function base(): string {
		/**
		 * Filter the base path for package URLs.
		 *
		 * @param string $base Path segment, without slashes.
		 */
		return trim( (string) apply_filters( 'tzh_url_base', self::BASE ), '/' );
	}

	/**
	 * URL of the destination grid.
	 */
	public static function grid_url(): string {
		return user_trailingslashit( home_url( '/' . self::base() . '/' ) );
	}

	/**
	 * Add the rules, most specific first.
	 */
	public function add_rules(): void {
		$base = preg_quote( self::base(), '#' );

		$rules = array(
			// Package sub-screens.
			"^{$base}/([^/]+)/([^/]+)/details/?$"  => 'index.php?' . TZH_Package::POST_TYPE . '=$matches[2]&' . self::ACTION_VAR . '=pdf',
			"^{$base}/([^/]+)/([^/]+)/book/?$"     => 'index.php?' . TZH_Package::POST_TYPE . '=$matches[2]&' . self::ACTION_VAR . '=book',

			// A package itself.
			"^{$base}/([^/]+)/([^/]+)/?$"          => 'index.php?' . TZH_Package::POST_TYPE . '=$matches[2]',

			// The catalogue's own pages, before a destination can swallow "page".
			"^{$base}/page/([0-9]{1,})/?$"         => 'index.php?post_type=' . TZH_Package::POST_TYPE . '&paged=$matches[1]',

			// A destination, paged and not.
			"^{$base}/([^/]+)/page/([0-9]{1,})/?$" => 'index.php?' . TZH_Package::TAX_DESTINATION . '=$matches[1]&paged=$matches[2]',
			"^{$base}/([^/]+)/?$"                  => 'index.php?' . TZH_Package::TAX_DESTINATION . '=$matches[1]',

			// The grid.
			"^{$base}/?$"                          => 'index.php?post_type=' . TZH_Package::POST_TYPE,
		);

		foreach ( $rules as $regex => $query ) {
			add_rewrite_rule( $regex, $query, 'top' );
		}
	}

	/**
	 * Let WordPress keep the action query var.
	 *
	 * @param string[] $vars Registered query vars.
	 *
	 * @return string[]
	 */
	public function query_vars( $vars ) {
		if ( ! is_array( $vars ) ) {
			return $vars;
		}

		$vars[] = self::ACTION_VAR;

		return $vars;
	}

	/**
	 * Build a package permalink: base / destination / package.
	 *
	 * @param string  $permalink Current permalink.
	 * @param WP_Post $post      Post being linked.
	 * @param bool    $leavename Whether to leave the %postname% placeholder in place.
	 */
	public function package_link( $permalink, $post = null, $leavename = false ) {
		if ( ! $post instanceof WP_Post || TZH_Package::POST_TYPE !== $post->post_type ) {
			return $permalink;
		}

		// Drafts have no slug yet; WordPress's own preview URL is the right answer.
		if ( '' === $post->post_name || in_array( $post->post_status, array( 'auto-draft', 'draft', 'pending' ), true ) ) {
			return $permalink;
		}

		$terms       = get_the_terms( $post, TZH_Package::TAX_DESTINATION );
		$destination = ( is_array( $terms ) && $terms ) ? $terms[0]->slug : self::ORPHAN;
		$name        = $leavename ? '%postname%' : $post->post_name;

		return user_trailingslashit(
			home_url( '/' . self::base() . '/' . $destination . '/' . $name . '/' )
		);
	}

	/**
	 * Build a destination archive link.
	 *
	 * @param string  $link     Current term link.
	 * @param WP_Term $term     Term being linked.
	 * @param string  $taxonomy Taxonomy name.
	 */
	public function destination_link( $link, $term = null, $taxonomy = '' ) {
		if ( ! $term instanceof WP_Term || TZH_Package::TAX_DESTINATION !== $taxonomy ) {
			return $link;
		}

		return user_trailingslashit( home_url( '/' . self::base() . '/' . $term->slug . '/' ) );
	}

	/**
	 * Point the post type archive at the grid.
	 *
	 * @param string $link      Current archive link.
	 * @param string $post_type Post type name.
	 */
	public function archive_link( $link, $post_type = '' ) {
		if ( TZH_Package::POST_TYPE !== $post_type ) {
			return $link;
		}

		return self::grid_url();
	}

	/**
	 * Sub-screen requested for the current package, if any.
	 */
	public static function current_action(): string {
		$action = get_query_var( self::ACTION_VAR );

		return in_array( $action, array( 'book', 'pdf' ), true ) ? (string) $action : '';
	}
}

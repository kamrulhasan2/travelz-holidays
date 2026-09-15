<?php
/**
 * Caching and cache exclusions.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Two opposite jobs, both about caching.
 *
 * It caches the one query that runs on every catalogue page — the destination
 * list, which joins terms to their images, blurbs and order — and it tells page
 * caches to leave the booking screen alone, because a cached page carrying
 * someone else's nonce is a form that fails for everyone who sees it.
 *
 * Invalidation is deliberately blunt: one version number, bumped whenever any
 * package or destination changes. A slightly over-eager flush costs one query;
 * a stale catalogue costs a sale.
 */
class TZH_Cache {

	/**
	 * Option holding the current cache generation.
	 */
	public const VERSION_OPTION = 'tzh_cache_version';

	/**
	 * How long a cached list survives without being invalidated.
	 */
	private const TTL = DAY_IN_SECONDS;

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'template_redirect', array( $this, 'exclude_dynamic_screens' ), 1 );

		add_action( 'save_post_' . TZH_Package::POST_TYPE, array( $this, 'bump' ) );
		add_action( 'deleted_post', array( $this, 'bump' ) );
		add_action( 'trashed_post', array( $this, 'bump' ) );
		add_action( 'untrashed_post', array( $this, 'bump' ) );
		add_action( 'edited_' . TZH_Package::TAX_DESTINATION, array( $this, 'bump' ) );
		add_action( 'created_' . TZH_Package::TAX_DESTINATION, array( $this, 'bump' ) );
		add_action( 'delete_' . TZH_Package::TAX_DESTINATION, array( $this, 'bump' ) );
		add_action( 'update_option_' . TZH_Settings::OPTION, array( $this, 'bump' ) );
	}

	/**
	 * Current cache generation.
	 */
	public static function version(): int {
		return (int) get_option( self::VERSION_OPTION, 1 );
	}

	/**
	 * Invalidate everything this class has cached.
	 */
	public function bump(): void {
		update_option( self::VERSION_OPTION, self::version() + 1, false );
	}

	/**
	 * Read a cached value, computing it when it is missing.
	 *
	 * @param string   $key     Cache key, unique within the plugin.
	 * @param callable $compute Producer for a cache miss.
	 *
	 * @return mixed
	 */
	public static function remember( string $key, callable $compute ) {
		$name   = 'tzh_c' . self::version() . '_' . $key;
		$cached = get_transient( $name );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = $compute();

		set_transient( $name, $value, self::TTL );

		return $value;
	}

	/**
	 * Tell page caches not to store the screens that carry a nonce.
	 *
	 * The booking form and its confirmation are per-visitor by definition. A
	 * page cache that keeps either one hands the next visitor a nonce minted
	 * for somebody else, and the form then fails validation for everyone until
	 * the cache expires.
	 */
	public function exclude_dynamic_screens(): void {
		if ( ! $this->is_dynamic_screen() ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// LiteSpeed, WP Rocket and W3 Total Cache each listen for their own
		// signal; none of them is an error to fire when the plugin is absent.
		do_action( 'litespeed_control_set_nocache', 'TravelZ Holidays booking screen' );
		do_action( 'wp_rocket_no_cache_page' );

		nocache_headers();
	}

	/**
	 * Whether the current request must never be served from a page cache.
	 */
	private function is_dynamic_screen(): bool {
		if ( ! is_singular( TZH_Package::POST_TYPE ) ) {
			return false;
		}

		return 'book' === TZH_Rewrites::current_action();
	}
}

<?php
/**
 * Install and upgrade routine.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs one-time setup whenever the stored schema version falls behind.
 *
 * Doing this on init rather than in the activation hook means the work happens
 * after post types and taxonomies exist, and it also runs for sites upgraded by
 * dropping in new files without re-activating.
 */
class TZH_Install {

	/**
	 * Bump when a new step is added below.
	 */
	private const DB_VERSION = 2;

	/**
	 * Option holding the schema version already applied.
	 */
	private const OPTION = 'tzh_db_version';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'maybe_install' ), 20 );
	}

	/**
	 * Apply pending setup steps.
	 */
	public function maybe_install(): void {
		$installed = (int) get_option( self::OPTION, 0 );

		if ( $installed >= self::DB_VERSION ) {
			return;
		}

		if ( $installed < 2 ) {
			$this->seed_tiers();
		}

		update_option( self::OPTION, self::DB_VERSION, false );
		update_option( 'tzh_version', TZH_VERSION, false );

		flush_rewrite_rules();
	}

	/**
	 * Create Standard, Deluxe and Premium in display order.
	 *
	 * Existing terms are left alone, so re-running this never disturbs tiers an
	 * administrator has renamed or reordered.
	 */
	private function seed_tiers(): void {
		$order = 0;

		foreach ( TZH_Package::default_tiers() as $slug => $name ) {
			++$order;

			$term = get_term_by( 'slug', $slug, TZH_Package::TAX_TIER );

			if ( $term instanceof WP_Term ) {
				continue;
			}

			$created = wp_insert_term(
				$name,
				TZH_Package::TAX_TIER,
				array( 'slug' => $slug )
			);

			if ( is_wp_error( $created ) ) {
				continue;
			}

			update_term_meta( $created['term_id'], '_tzh_order', $order );
		}
	}
}

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
	private const DB_VERSION = 6;

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

		if ( $installed < 3 ) {
			$this->seed_settings();
		}

		update_option( self::OPTION, self::DB_VERSION, false );
		update_option( 'tzh_version', TZH_VERSION, false );

		flush_rewrite_rules();
	}

	/**
	 * Give a fresh install the company-wide terms to start from.
	 *
	 * Only runs when nothing has been saved yet, so an administrator who
	 * cleared the list on purpose does not get it back.
	 */
	private function seed_settings(): void {
		$saved = get_option( TZH_Settings::OPTION, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		if ( ! empty( $saved['default_terms'] ) ) {
			return;
		}

		$saved['default_terms'] = TZH_Settings::starter_terms();

		update_option( TZH_Settings::OPTION, wp_parse_args( $saved, TZH_Settings::defaults() ), false );
		TZH_Settings::flush();
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

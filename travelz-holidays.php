<?php
/**
 * Plugin Name:       TravelZ Holidays
 * Plugin URI:        https://tech.travelzbd.com/
 * Description:       Holiday package management for TravelZ — destinations, tour packages, itineraries, pricing tiers and bookings.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            E-GUIDER
 * Author URI:        https://tech.travelzbd.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       travelz-holidays
 * Domain Path:       /languages
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

define( 'TZH_VERSION', '0.1.0' );
define( 'TZH_FILE', __FILE__ );
define( 'TZH_DIR', plugin_dir_path( __FILE__ ) );
define( 'TZH_URL', plugin_dir_url( __FILE__ ) );
define( 'TZH_BASENAME', plugin_basename( __FILE__ ) );
define( 'TZH_MIN_PHP', '8.0' );
define( 'TZH_MIN_WP', '6.4' );

require_once TZH_DIR . 'includes/class-tzh-requirements.php';

/**
 * Boot the plugin once the environment is known to be good.
 *
 * Requirements are checked before anything else loads, so a site running an
 * unsupported PHP or WordPress version shows an admin notice instead of a
 * fatal error.
 */
function tzh_boot(): void {
	$requirements = new TZH_Requirements(
		array(
			'php' => TZH_MIN_PHP,
			'wp'  => TZH_MIN_WP,
		)
	);

	if ( ! $requirements->met() ) {
		$requirements->show_notice();

		return;
	}

	require_once TZH_DIR . 'includes/class-tzh-autoloader.php';
	TZH_Autoloader::register();

	require_once TZH_DIR . 'includes/functions-helpers.php';

	TZH_Plugin::instance()->run();
}

add_action( 'plugins_loaded', 'tzh_boot' );

/**
 * Activation: verify requirements, then record version and install timestamp.
 */
function tzh_activate(): void {
	$requirements = new TZH_Requirements(
		array(
			'php' => TZH_MIN_PHP,
			'wp'  => TZH_MIN_WP,
		)
	);

	if ( ! $requirements->met() ) {
		deactivate_plugins( TZH_BASENAME );
		wp_die(
			esc_html( $requirements->message() ),
			esc_html__( 'TravelZ Holidays — activation failed', 'travelz-holidays' ),
			array( 'back_link' => true )
		);
	}

	if ( ! get_option( 'tzh_installed_at' ) ) {
		add_option( 'tzh_installed_at', time(), '', false );
	}

	update_option( 'tzh_version', TZH_VERSION, false );

	// Post types and rewrite rules arrive in phase 2; flushing here keeps the
	// activation contract stable so later phases need no migration step.
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'tzh_activate' );

/**
 * Deactivation: drop the rewrite rules this plugin added.
 */
function tzh_deactivate(): void {
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'tzh_deactivate' );

<?php
/**
 * Uninstall routine.
 *
 * Runs only when the plugin is deleted from the Plugins screen.
 *
 * Package content (packages, destinations, media) is deliberately left in the
 * database — deleting a plugin should never wipe a travel agency's catalogue.
 * Only the plugin's own bookkeeping options are removed.
 *
 * @package TravelZ_Holidays
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'tzh_version' );
delete_option( 'tzh_installed_at' );

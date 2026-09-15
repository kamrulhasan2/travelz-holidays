<?php
/**
 * Shared helper functions.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Capability required to manage holiday packages.
 *
 * Kept behind a filter so an agency role can be granted access later without
 * touching every add_menu_page() call.
 */
function tzh_capability(): string {
	/**
	 * Filter the capability that guards every TravelZ Holidays admin screen.
	 *
	 * @param string $capability Capability name.
	 */
	return (string) apply_filters( 'tzh_capability', 'manage_options' );
}

/**
 * Menu slug of the plugin's top-level admin menu.
 */
function tzh_menu_slug(): string {
	return 'travelz-holidays';
}

/**
 * Admin URL for one of the plugin's screens.
 *
 * @param string               $page Page slug, or '' for the top-level screen.
 * @param array<string, mixed> $args Extra query arguments.
 */
function tzh_admin_url( string $page = '', array $args = array() ): string {
	$args['page'] = '' === $page ? tzh_menu_slug() : $page;

	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

/**
 * Format an amount as Bangladeshi Taka.
 *
 * Mirrors the BDT() helper from the React source: a taka sign followed by the
 * number in Indian digit grouping (25,900 — not 25900).
 *
 * @param int|float $amount Amount in taka.
 */
function tzh_price( $amount ): string {
	$amount = (float) $amount;

	/**
	 * Filter the currency symbol used across the plugin.
	 *
	 * @param string $symbol Currency symbol.
	 */
	$symbol = (string) apply_filters( 'tzh_currency_symbol', '৳' );

	return $symbol . tzh_group_digits( (int) round( $amount ) );
}

/**
 * Group digits the Indian way: last three, then pairs (12,34,567).
 *
 * PHP's number_format() only does thousands groups, and the React source used
 * toLocaleString("en-IN"), so this keeps the two outputs identical.
 *
 * @param int $number Whole number to format.
 */
function tzh_group_digits( int $number ): string {
	$sign   = $number < 0 ? '-' : '';
	$digits = (string) abs( $number );

	if ( strlen( $digits ) <= 3 ) {
		return $sign . $digits;
	}

	$last_three = substr( $digits, -3 );
	$rest       = substr( $digits, 0, -3 );
	$rest       = preg_replace( '/\B(?=(\d{2})+(?!\d))/', ',', $rest );

	return $sign . $rest . ',' . $last_three;
}

/**
 * Render an admin view file from includes/admin/views/.
 *
 * @param string              $view View file name without extension.
 * @param array<string, mixed> $data Variables extracted into the view's scope.
 */
function tzh_admin_view( string $view, array $data = array() ): void {
	$file = TZH_DIR . 'includes/admin/views/' . $view . '.php';

	if ( ! is_readable( $file ) ) {
		return;
	}

	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Controlled, internal view data.
	extract( $data, EXTR_SKIP );

	require $file;
}

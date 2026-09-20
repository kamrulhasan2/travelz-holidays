<?php
/**
 * Plugin settings store.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site-wide defaults, kept in one option.
 *
 * Anything a travel desk would otherwise retype on every package — the terms,
 * the standard inclusions, the WhatsApp number — lives here, and a package
 * falls back to it whenever its own field is left empty.
 */
class TZH_Settings {

	public const OPTION = 'tzh_settings';

	/**
	 * Cached option contents for this request.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $cache = null;

	/**
	 * Shipped defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'whatsapp'          => '',
			'currency_symbol'   => '৳',
			'woo_checkout'      => true,
			'price_min'         => 0,
			'price_max'         => 0,
			'price_step'        => 1000,
			'child_rate'        => 70,
			'infant_price'      => 1500,
			'default_highlights' => array(),
			'default_inclusion' => array(),
			'default_exclusion' => array(),
			'default_terms'     => array(),
			'archive_eyebrow'   => __( 'Curated for Bangladeshi travelers', 'travelz-holidays' ),
			'archive_title'     => __( 'Explore Holiday Packages', 'travelz-holidays' ),
			'archive_subtitle'  => __( 'Handpicked destinations, unforgettable journeys.', 'travelz-holidays' ),
			'visa_note'         => __( 'Please review the visa document below carefully. Requirements may change; contact your booking consultant for the latest updates.', 'travelz-holidays' ),
			'github_token'      => '',
		);
	}

	/**
	 * Terms seeded on first install.
	 *
	 * These are the company-wide ones from the original site — generic enough
	 * to apply to every package, which is exactly what a default is for.
	 *
	 * @return string[]
	 */
	public static function starter_terms(): array {
		return array(
			__( 'Minimum 2 adults required to confirm the package.', 'travelz-holidays' ),
			__( 'Hotel standard check-in 14:00, check-out 12:00.', 'travelz-holidays' ),
			__( 'Rates are subject to change without prior notice.', 'travelz-holidays' ),
			__( 'Blackout dates apply during Durga Puja, Eid, New Year, and Christmas.', 'travelz-holidays' ),
			__( 'Cancellation charges apply as per TravelZ policy.', 'travelz-holidays' ),
			__( 'All disputes are subject to Dhaka jurisdiction.', 'travelz-holidays' ),
		);
	}

	/**
	 * Every setting, defaults filled in.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		if ( null === self::$cache ) {
			$saved = get_option( self::OPTION, array() );

			self::$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}

		return self::$cache;
	}

	/**
	 * One setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Value when the setting is missing or empty.
	 *
	 * @return mixed
	 */
	public static function get( string $key, $fallback = null ) {
		$all = self::all();

		if ( ! array_key_exists( $key, $all ) ) {
			return $fallback;
		}

		$value = $all[ $key ];

		if ( '' === $value || array() === $value ) {
			return null === $fallback ? $value : $fallback;
		}

		return $value;
	}

	/**
	 * Drop the request cache after a write.
	 */
	public static function flush(): void {
		self::$cache = null;
	}
}

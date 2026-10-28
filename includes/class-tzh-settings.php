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
			'whatsapp_enabled'  => true,
			'whatsapp_message'  => __( 'Hi, I would like to know more about {package}.', 'travelz-holidays' ),
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
	 * WhatsApp number in the digits-only form wa.me expects.
	 *
	 * Accepts what people actually type — "+88 01711-258451", "01711258451" —
	 * and turns a local Bangladeshi mobile number into its international form.
	 */
	public static function whatsapp_number(): string {
		$digits = preg_replace( '/\D+/', '', (string) self::get( 'whatsapp', '' ) );

		if ( 11 === strlen( $digits ) && 0 === strpos( $digits, '01' ) ) {
			$digits = '88' . $digits;
		}

		return $digits;
	}

	/**
	 * Whether the WhatsApp buttons should be shown at all.
	 */
	public static function whatsapp_active(): bool {
		return ! empty( self::all()['whatsapp_enabled'] ) && '' !== self::whatsapp_number();
	}

	/**
	 * Chat link with a pre-filled message, or '' when WhatsApp is off.
	 *
	 * @param string $message Text to pre-fill.
	 */
	public static function whatsapp_url( string $message = '' ): string {
		if ( ! self::whatsapp_active() ) {
			return '';
		}

		$url = 'https://wa.me/' . self::whatsapp_number();

		return '' === $message ? $url : $url . '?text=' . rawurlencode( $message );
	}

	/**
	 * The enquiry message for one package, from the saved template.
	 *
	 * @param string $package_title Package title.
	 */
	public static function whatsapp_enquiry( string $package_title ): string {
		$template = trim( (string) self::get( 'whatsapp_message', '' ) );

		if ( '' === $template ) {
			$template = (string) self::defaults()['whatsapp_message'];
		}

		// Titles arrive texturised (&#8211; and friends); WhatsApp wants plain text.
		$package_title = html_entity_decode( $package_title, ENT_QUOTES, 'UTF-8' );

		return str_replace( '{package}', $package_title, $template );
	}

	/**
	 * Drop the request cache after a write.
	 */
	public static function flush(): void {
		self::$cache = null;
	}
}

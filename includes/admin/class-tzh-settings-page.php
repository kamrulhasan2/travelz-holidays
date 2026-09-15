<?php
/**
 * Settings screen.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Settings form behind Holiday Packages → Settings.
 *
 * Everything here is a fallback: a package that fills in its own inclusion list
 * or terms ignores these entirely. They exist so the common case — the same
 * terms on every package — is typed once.
 */
class TZH_Settings_Page {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	/**
	 * Register the option and its sanitizer.
	 */
	public function register(): void {
		register_setting(
			'tzh_settings_group',
			TZH_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => TZH_Settings::defaults(),
			)
		);
	}

	/**
	 * Render the form.
	 */
	public function render(): void {
		$values = TZH_Settings::all();

		tzh_admin_view( 'settings', array( 'values' => $values ) );
	}

	/**
	 * Clean the submitted settings.
	 *
	 * @param mixed $input Raw submitted values.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input  = is_array( $input ) ? $input : array();
		$clean  = TZH_Settings::defaults();

		$clean['whatsapp'] = preg_replace( '/[^0-9+]/', '', (string) ( $input['whatsapp'] ?? '' ) );

		$symbol                   = trim( (string) ( $input['currency_symbol'] ?? '' ) );
		$clean['currency_symbol'] = '' === $symbol ? '৳' : sanitize_text_field( $symbol );

		$clean['child_rate']   = min( 100, max( 0, (int) ( $input['child_rate'] ?? 70 ) ) );
		$clean['infant_price'] = max( 0, (int) ( $input['infant_price'] ?? 0 ) );

		foreach ( array( 'default_highlights', 'default_inclusion', 'default_exclusion', 'default_terms' ) as $key ) {
			$clean[ $key ] = $this->lines( $input[ $key ] ?? '' );
		}

		foreach ( array( 'archive_eyebrow', 'archive_title', 'archive_subtitle' ) as $key ) {
			$clean[ $key ] = sanitize_text_field( (string) ( $input[ $key ] ?? '' ) );
		}

		$clean['visa_note'] = sanitize_textarea_field( (string) ( $input['visa_note'] ?? '' ) );

		TZH_Settings::flush();

		return $clean;
	}

	/**
	 * Split a textarea into a clean list.
	 *
	 * @param mixed $raw Submitted value.
	 *
	 * @return string[]
	 */
	private function lines( $raw ): array {
		$lines = is_array( $raw ) ? $raw : preg_split( '/\r\n|\r|\n/', (string) $raw );
		$clean = array();

		foreach ( (array) $lines as $line ) {
			$line = sanitize_text_field( trim( (string) $line ) );

			if ( '' !== $line ) {
				$clean[] = $line;
			}
		}

		return $clean;
	}
}

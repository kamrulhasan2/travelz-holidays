<?php
/**
 * Environment requirement checks.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Compares the running environment against the plugin's minimums.
 *
 * Deliberately has no dependency on the autoloader — it is loaded by hand from
 * the main plugin file before anything else, so that an unsupported PHP version
 * can never reach code using newer syntax.
 */
class TZH_Requirements {

	/**
	 * Minimum versions keyed by 'php' and 'wp'.
	 *
	 * @var array<string, string>
	 */
	private array $minimums;

	/**
	 * Failures collected by met(), keyed by 'php' and 'wp'.
	 *
	 * @var array<string, string>
	 */
	private array $failures = array();

	/**
	 * @param array<string, string> $minimums Minimum versions keyed by 'php' and 'wp'.
	 */
	public function __construct( array $minimums ) {
		$this->minimums = $minimums;
	}

	/**
	 * Whether the environment satisfies every minimum.
	 */
	public function met(): bool {
		$this->failures = array();

		if ( isset( $this->minimums['php'] ) && version_compare( PHP_VERSION, $this->minimums['php'], '<' ) ) {
			$this->failures['php'] = PHP_VERSION;
		}

		if ( isset( $this->minimums['wp'] ) && version_compare( get_bloginfo( 'version' ), $this->minimums['wp'], '<' ) ) {
			$this->failures['wp'] = get_bloginfo( 'version' );
		}

		return empty( $this->failures );
	}

	/**
	 * Human-readable explanation of what is missing.
	 */
	public function message(): string {
		$parts = array();

		if ( isset( $this->failures['php'] ) ) {
			$parts[] = sprintf(
				/* translators: 1: required PHP version, 2: running PHP version */
				__( 'PHP %1$s or newer (this server runs %2$s)', 'travelz-holidays' ),
				$this->minimums['php'],
				$this->failures['php']
			);
		}

		if ( isset( $this->failures['wp'] ) ) {
			$parts[] = sprintf(
				/* translators: 1: required WordPress version, 2: running WordPress version */
				__( 'WordPress %1$s or newer (this site runs %2$s)', 'travelz-holidays' ),
				$this->minimums['wp'],
				$this->failures['wp']
			);
		}

		return sprintf(
			/* translators: %s: comma-separated list of unmet requirements */
			__( 'TravelZ Holidays needs %s.', 'travelz-holidays' ),
			implode( __( ' and ', 'travelz-holidays' ), $parts )
		);
	}

	/**
	 * Queue an admin notice describing the unmet requirements.
	 */
	public function show_notice(): void {
		$message = $this->message();

		add_action(
			'admin_notices',
			static function () use ( $message ) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html( $message )
				);
			}
		);
	}
}

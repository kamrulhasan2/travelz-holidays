<?php
/**
 * Booking screen.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads, validates and prices a booking request.
 *
 * Everything the screen needs is worked out here, before a single line of
 * markup is printed, so the template stays a template and the arithmetic can be
 * tested on its own. The same numbers are produced whether the visitor has
 * JavaScript or not: the script in the page is a faster echo of this class, not
 * a second implementation of it.
 */
class TZH_Booking {

	/**
	 * Nonce action and field name.
	 */
	public const NONCE = 'tzh_booking';

	/**
	 * Query var carrying a confirmed booking reference.
	 */
	public const REF_VAR = 'tzh_ref';

	/**
	 * Party currently being priced.
	 *
	 * @var array{date: string, adults: int, children: int, infants: int}
	 */
	private array $party;

	/**
	 * Validation errors, keyed by field.
	 *
	 * @var array<string, string>
	 */
	private array $errors = array();

	/**
	 * Confirmed request, once one has been made.
	 *
	 * @var array{party: array<string, mixed>, quote: array<string, mixed>, package: int}|null
	 */
	private ?array $confirmed = null;

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'template_redirect', array( $this, 'handle' ), 5 );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	/**
	 * Keep the reference out of WordPress's way.
	 *
	 * @param string[] $vars Registered query vars.
	 *
	 * @return string[]
	 */
	public function query_vars( array $vars ): array {
		$vars[] = self::REF_VAR;

		return $vars;
	}

	/**
	 * Default party for a package: the smallest one it can be booked for.
	 *
	 * @param TZH_Package $package Package being booked.
	 *
	 * @return array{date: string, adults: int, children: int, infants: int}
	 */
	public static function defaults( TZH_Package $package ): array {
		return array(
			'date'     => '',
			'adults'   => max( 1, $package->min_pax() ),
			'children' => 0,
			'infants'  => 0,
		);
	}

	/**
	 * Price a party.
	 *
	 * Returns one row per traveller type — including the empty ones, which the
	 * design shows greyed out rather than hiding, so the total never appears to
	 * come from nowhere.
	 *
	 * @param TZH_Package          $package Package being booked.
	 * @param array<string, mixed> $party   Party to price.
	 *
	 * @return array{lines: array<int, array{key: string, type: string, label: string, count: int, unit: int, total: int}>, subtotal: int, total: int, travelers: int}
	 */
	public static function quote( TZH_Package $package, array $party ): array {
		$counts = array(
			'adults'   => max( 0, (int) ( $party['adults'] ?? 0 ) ),
			'children' => max( 0, (int) ( $party['children'] ?? 0 ) ),
			'infants'  => max( 0, (int) ( $party['infants'] ?? 0 ) ),
		);

		$unit = array(
			'adults'   => $package->price(),
			'children' => $package->child_price(),
			'infants'  => $package->infant_price(),
		);

		$labels = array(
			'adults'   => __( 'Adult', 'travelz-holidays' ),
			'children' => __( 'Child', 'travelz-holidays' ),
			'infants'  => __( 'Infant', 'travelz-holidays' ),
		);

		$lines    = array();
		$subtotal = 0;

		foreach ( $counts as $key => $count ) {
			$total     = $count * $unit[ $key ];
			$subtotal += $total;

			$lines[] = array(
				'key'   => $key,
				'type'  => $labels[ $key ],
				'label' => sprintf(
					/* translators: 1: traveller type, 2: how many of them */
					__( '%1$s × %2$s', 'travelz-holidays' ),
					$labels[ $key ],
					number_format_i18n( $count )
				),
				'count' => $count,
				'unit'  => $unit[ $key ],
				'total' => $total,
			);
		}

		return array(
			'lines'     => $lines,
			'subtotal'  => $subtotal,
			/**
			 * Filter the payable total.
			 *
			 * Taxes and fees are quoted as included, so subtotal and total are
			 * the same number until a site says otherwise.
			 *
			 * @param int         $total    Payable total.
			 * @param int         $subtotal Sum of the traveller lines.
			 * @param TZH_Package $package  Package being booked.
			 */
			'total'     => (int) apply_filters( 'tzh_booking_total', $subtotal, $subtotal, $package ),
			'travelers' => array_sum( $counts ),
		);
	}

	/**
	 * Read, validate and act on the booking screen's request.
	 */
	public function handle(): void {
		$package = $this->current_package();

		if ( ! $package ) {
			return;
		}

		nocache_headers();

		$this->party = self::defaults( $package );

		$this->restore_confirmation( $package );

		if ( ! $this->is_post() ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			$this->errors['form'] = __( 'This form expired. Please check your details and try again.', 'travelz-holidays' );

			return;
		}

		$this->party = $this->read_party();

		// The no-script fallback button only asks for the numbers to be totted
		// up again, so it stops here with the form repopulated and no errors.
		if ( isset( $_POST['tzh_recalculate'] ) ) {
			return;
		}

		$this->errors = $this->validate( $package, $this->party );

		if ( $this->errors ) {
			return;
		}

		$this->confirm( $package, $this->party );
	}

	/**
	 * Party currently being shown, whether default, submitted or confirmed.
	 *
	 * @return array{date: string, adults: int, children: int, infants: int}
	 */
	public function party(): array {
		return $this->party ?? array(
			'date'     => '',
			'adults'   => 1,
			'children' => 0,
			'infants'  => 0,
		);
	}

	/**
	 * Validation errors from the last submission.
	 *
	 * @return array<string, string>
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 * The confirmed request, when the visitor has just made one.
	 *
	 * @return array{party: array<string, mixed>, quote: array<string, mixed>, package: int}|null
	 */
	public function confirmed(): ?array {
		return $this->confirmed;
	}

	/**
	 * Format a stored date the way the design shows it: 04 Mar 2026.
	 *
	 * @param string $date Date in Y-m-d.
	 */
	public static function date_label( string $date ): string {
		$time = strtotime( $date );

		return $time ? date_i18n( 'd M Y', $time ) : '';
	}

	/**
	 * Earliest date that can be booked, in the site's own timezone.
	 */
	public static function earliest(): string {
		/**
		 * Filter how many days ahead the first bookable date is.
		 *
		 * @param int $days Days of notice required.
		 */
		$notice = (int) apply_filters( 'tzh_booking_notice_days', 0 );

		return wp_date( 'Y-m-d', time() + ( $notice * DAY_IN_SECONDS ) ) ?: '';
	}

	/**
	 * Whether this request is the booking screen of a package.
	 */
	private function current_package(): ?TZH_Package {
		if ( ! is_singular( TZH_Package::POST_TYPE ) || 'book' !== TZH_Rewrites::current_action() ) {
			return null;
		}

		return TZH_Package::from( get_queried_object() );
	}

	/**
	 * Whether the current request is a form submission.
	 */
	private function is_post(): bool {
		return 'POST' === strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );
	}

	/**
	 * Party as submitted, clamped to something a person can honour.
	 *
	 * @return array{date: string, adults: int, children: int, infants: int}
	 */
	private function read_party(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by the caller.
		$date = isset( $_POST['tzh_date'] ) ? sanitize_text_field( wp_unslash( $_POST['tzh_date'] ) ) : '';

		$party = array(
			'date'     => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '',
			'adults'   => isset( $_POST['tzh_adults'] ) ? absint( wp_unslash( $_POST['tzh_adults'] ) ) : 0,
			'children' => isset( $_POST['tzh_children'] ) ? absint( wp_unslash( $_POST['tzh_children'] ) ) : 0,
			'infants'  => isset( $_POST['tzh_infants'] ) ? absint( wp_unslash( $_POST['tzh_infants'] ) ) : 0,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		/*
		 * A hand-edited form field could ask for a thousand travellers, which
		 * would be quoted quite happily and then have to be untangled by a
		 * person. The ceiling is generous and the floor is one adult.
		 */
		$party['adults']   = min( 30, max( 1, $party['adults'] ) );
		$party['children'] = min( 30, $party['children'] );
		$party['infants']  = min( 10, $party['infants'] );

		return $party;
	}

	/**
	 * Check a party against the package's own rules.
	 *
	 * @param TZH_Package          $package Package being booked.
	 * @param array<string, mixed> $party   Party to check.
	 *
	 * @return array<string, string>
	 */
	private function validate( TZH_Package $package, array $party ): array {
		$errors = array();

		if ( '' === $party['date'] ) {
			$errors['date'] = __( 'Please pick a travel date.', 'travelz-holidays' );
		} elseif ( $party['date'] < self::earliest() ) {
			$errors['date'] = __( 'Please pick a travel date that has not passed.', 'travelz-holidays' );
		}

		$counted = (int) $party['adults'] + (int) $party['children'];

		if ( $counted < $package->min_pax() ) {
			$errors['travelers'] = sprintf(
				/* translators: %s: smallest number of travellers */
				_n(
					'This package needs at least %s traveler.',
					'This package needs at least %s travelers.',
					$package->min_pax(),
					'travelz-holidays'
				),
				number_format_i18n( $package->min_pax() )
			);
		}

		return $errors;
	}

	/**
	 * Accept a valid request and send the visitor on.
	 *
	 * The result is stored under a one-use reference and the browser is
	 * redirected to it, so a refresh re-reads the confirmation instead of
	 * booking a second time.
	 *
	 * @param TZH_Package          $package Package being booked.
	 * @param array<string, mixed> $party   Party being booked.
	 */
	private function confirm( TZH_Package $package, array $party ): void {
		$quote = self::quote( $package, $party );

		/**
		 * Fires when a booking request passes validation.
		 *
		 * @param TZH_Package          $package Package being booked.
		 * @param array<string, mixed> $party   Party being booked.
		 * @param array<string, mixed> $quote   Priced breakdown.
		 */
		do_action( 'tzh_booking_submitted', $package, $party, $quote );

		/**
		 * Filter where a valid booking request goes next.
		 *
		 * Returning a URL hands the request over — this is where the
		 * WooCommerce checkout takes it from phase 13. An empty string keeps
		 * the plugin's own confirmation screen.
		 *
		 * @param string               $url     Destination, or '' for the built-in screen.
		 * @param TZH_Package          $package Package being booked.
		 * @param array<string, mixed> $party   Party being booked.
		 * @param array<string, mixed> $quote   Priced breakdown.
		 */
		$url = (string) apply_filters( 'tzh_booking_redirect', '', $package, $party, $quote );

		if ( '' === $url ) {
			$reference = strtoupper( wp_generate_password( 8, false, false ) );

			set_transient(
				'tzh_booking_' . $reference,
				array(
					'package' => $package->id(),
					'party'   => $party,
					'quote'   => $quote,
				),
				HOUR_IN_SECONDS
			);

			$url = add_query_arg( self::REF_VAR, $reference, $package->action_url( 'book' ) );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Load a confirmation the visitor has just been redirected to.
	 *
	 * @param TZH_Package $package Package being booked.
	 */
	private function restore_confirmation( TZH_Package $package ): void {
		$reference = (string) get_query_var( self::REF_VAR );

		if ( '' === $reference ) {
			return;
		}

		$stored = get_transient( 'tzh_booking_' . $reference );

		if ( ! is_array( $stored ) || (int) ( $stored['package'] ?? 0 ) !== $package->id() ) {
			return;
		}

		$stored['reference'] = $reference;

		$this->confirmed = $stored;
		$this->party     = wp_parse_args( (array) $stored['party'], $this->party );
	}
}

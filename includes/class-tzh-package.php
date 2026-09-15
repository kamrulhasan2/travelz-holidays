<?php
/**
 * Package model.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * A single holiday package.
 *
 * Holds the object names and meta keys the whole plugin agrees on, plus typed
 * readers so no other file has to remember whether a price is stored as a
 * string or an int.
 */
class TZH_Package {

	public const POST_TYPE = 'tzh_package';

	public const TAX_DESTINATION = 'tzh_destination';
	public const TAX_TIER        = 'tzh_tier';
	public const TAX_FAMILY      = 'tzh_family';

	/* Scalar meta — indexed individually because the front-end filters on them. */
	public const META_CODE          = '_tzh_code';
	public const META_SERIAL        = '_tzh_serial';
	public const META_DAYS          = '_tzh_days';
	public const META_NIGHTS        = '_tzh_nights';
	public const META_PRICE         = '_tzh_price';
	public const META_TOUR_TYPE     = '_tzh_tour_type';
	public const META_MIN_PAX       = '_tzh_min_pax';
	public const META_CHILD_RATE    = '_tzh_child_rate';
	public const META_INFANT_PRICE  = '_tzh_infant_price';
	public const META_WITH_AIRFARE  = '_tzh_with_airfare';
	public const META_BESTSELLER    = '_tzh_bestseller';
	public const META_RATING        = '_tzh_rating';
	public const META_BOOKED        = '_tzh_booked';
	public const META_HERO          = '_tzh_hero';
	public const META_VISA_DOC      = '_tzh_visa_doc';
	public const META_VISA_NOTE     = '_tzh_visa_note';
	public const META_WC_PRODUCT    = '_tzh_wc_product';

	/* Grouped meta — stored as one array each, never queried directly. */
	public const META_ITINERARY  = '_tzh_itinerary';
	public const META_HIGHLIGHTS = '_tzh_highlights';
	public const META_INCLUSION  = '_tzh_inclusion';
	public const META_EXCLUSION  = '_tzh_exclusion';
	public const META_TERMS      = '_tzh_terms';
	public const META_OTHER      = '_tzh_other';

	/**
	 * Underlying post.
	 */
	private WP_Post $post;

	/**
	 * @param WP_Post $post Package post.
	 */
	private function __construct( WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Build from a post, ID, or the current global post.
	 *
	 * @param WP_Post|int|null $post Package post or ID.
	 */
	public static function from( $post = null ): ?self {
		$post = get_post( $post );

		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return new self( $post );
	}

	/**
	 * Allowed tour types, keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	public static function tour_types(): array {
		return array(
			'private' => __( 'Private', 'travelz-holidays' ),
			'group'   => __( 'Group', 'travelz-holidays' ),
		);
	}

	/**
	 * Tier slugs seeded on install, in display order.
	 *
	 * @return array<string, string>
	 */
	public static function default_tiers(): array {
		return array(
			'standard' => __( 'Standard', 'travelz-holidays' ),
			'deluxe'   => __( 'Deluxe', 'travelz-holidays' ),
			'premium'  => __( 'Premium', 'travelz-holidays' ),
		);
	}

	/**
	 * Derive the sortable serial from a package code.
	 *
	 * "TZ 001" becomes 1, so the list orders the way packages were uploaded
	 * rather than alphabetically. Matches the sort in the React source.
	 *
	 * @param string $code Package code.
	 */
	public static function serial_from_code( string $code ): int {
		$digits = preg_replace( '/\D/', '', $code );

		return '' === (string) $digits ? 0 : (int) $digits;
	}

	/**
	 * Post ID.
	 */
	public function id(): int {
		return $this->post->ID;
	}

	/**
	 * Underlying post object.
	 */
	public function post(): WP_Post {
		return $this->post;
	}

	/**
	 * Package code, e.g. "TZ 001".
	 */
	public function code(): string {
		return (string) get_post_meta( $this->id(), self::META_CODE, true );
	}

	/**
	 * Duration in days.
	 */
	public function days(): int {
		return (int) get_post_meta( $this->id(), self::META_DAYS, true );
	}

	/**
	 * Duration in nights.
	 */
	public function nights(): int {
		return (int) get_post_meta( $this->id(), self::META_NIGHTS, true );
	}

	/**
	 * Adult price in taka.
	 */
	public function price(): int {
		return (int) get_post_meta( $this->id(), self::META_PRICE, true );
	}

	/**
	 * Tour type value: private or group.
	 */
	public function tour_type(): string {
		$value = (string) get_post_meta( $this->id(), self::META_TOUR_TYPE, true );

		return array_key_exists( $value, self::tour_types() ) ? $value : 'private';
	}

	/**
	 * Translated tour type label.
	 */
	public function tour_type_label(): string {
		return self::tour_types()[ $this->tour_type() ];
	}

	/**
	 * "05 Days 04 Nights", zero-padded exactly as the React source rendered it.
	 */
	public function duration_label(): string {
		return sprintf(
			/* translators: 1: zero-padded day count, 2: zero-padded night count */
			__( '%1$s Days %2$s Nights', 'travelz-holidays' ),
			str_pad( (string) $this->days(), 2, '0', STR_PAD_LEFT ),
			str_pad( (string) $this->nights(), 2, '0', STR_PAD_LEFT )
		);
	}

	/**
	 * Child price in taka, worked out from the adult price.
	 *
	 * The rate is stored as a percentage (70 means 70% of the adult price)
	 * because that is how a travel desk quotes it.
	 */
	public function child_price(): int {
		$rate = get_post_meta( $this->id(), self::META_CHILD_RATE, true );
		$rate = '' === $rate ? 70 : (float) $rate;

		return (int) round( $this->price() * $rate / 100 );
	}

	/**
	 * Flat infant price in taka.
	 */
	public function infant_price(): int {
		return (int) get_post_meta( $this->id(), self::META_INFANT_PRICE, true );
	}

	/**
	 * Smallest party the package can be booked for.
	 */
	public function min_pax(): int {
		return max( 1, (int) get_post_meta( $this->id(), self::META_MIN_PAX, true ) );
	}

	/**
	 * Whether the quoted price covers flights.
	 */
	public function with_airfare(): bool {
		return '1' === (string) get_post_meta( $this->id(), self::META_WITH_AIRFARE, true );
	}

	/**
	 * Whether to show the Bestseller badge.
	 */
	public function is_bestseller(): bool {
		return '1' === (string) get_post_meta( $this->id(), self::META_BESTSELLER, true );
	}

	/**
	 * Star rating out of five. Zero means "do not show".
	 */
	public function rating(): float {
		return (float) get_post_meta( $this->id(), self::META_RATING, true );
	}

	/**
	 * How many travellers have booked. Zero means "do not show".
	 */
	public function booked_count(): int {
		return (int) get_post_meta( $this->id(), self::META_BOOKED, true );
	}

	/**
	 * Itinerary days in travel order.
	 *
	 * Each day is [ title, meals, body, hotels[] ], with every key present even
	 * when empty, so templates can read them without isset() checks.
	 *
	 * @return array<int, array{title: string, meals: string, body: string, hotels: array<int, array{name: string, city: string, class: string, stay: string}>}>
	 */
	public function itinerary(): array {
		$saved = get_post_meta( $this->id(), self::META_ITINERARY, true );
		$days  = array();

		foreach ( (array) $saved as $day ) {
			if ( ! is_array( $day ) ) {
				continue;
			}

			$hotels = array();

			foreach ( (array) ( $day['hotels'] ?? array() ) as $hotel ) {
				if ( ! is_array( $hotel ) ) {
					continue;
				}

				$hotels[] = array(
					'name'  => (string) ( $hotel['name'] ?? '' ),
					'city'  => (string) ( $hotel['city'] ?? '' ),
					'class' => (string) ( $hotel['class'] ?? '' ),
					'stay'  => (string) ( $hotel['stay'] ?? '' ),
				);
			}

			$days[] = array(
				'title'  => (string) ( $day['title'] ?? '' ),
				'meals'  => (string) ( $day['meals'] ?? '' ),
				'body'   => (string) ( $day['body'] ?? '' ),
				'hotels' => $hotels,
			);
		}

		return $days;
	}


	/**
	 * Tour product highlights.
	 *
	 * @return string[]
	 */
	public function highlights(): array {
		return $this->list( self::META_HIGHLIGHTS, 'default_highlights' );
	}

	/**
	 * What the price includes.
	 *
	 * @return string[]
	 */
	public function inclusion(): array {
		return $this->list( self::META_INCLUSION, 'default_inclusion' );
	}

	/**
	 * What the price does not cover.
	 *
	 * @return string[]
	 */
	public function exclusion(): array {
		return $this->list( self::META_EXCLUSION, 'default_exclusion' );
	}

	/**
	 * Booking terms.
	 *
	 * @return string[]
	 */
	public function terms(): array {
		return $this->list( self::META_TERMS, 'default_terms' );
	}

	/**
	 * Free-form notes, as paragraphs.
	 *
	 * @return string[]
	 */
	public function other_details(): array {
		$raw = (string) get_post_meta( $this->id(), self::META_OTHER, true );

		if ( '' === trim( $raw ) ) {
			return array();
		}

		$paragraphs = preg_split( "/\n\s*\n/", str_replace( "\r\n", "\n", $raw ) );

		return array_values( array_filter( array_map( 'trim', (array) $paragraphs ), 'strlen' ) );
	}

	/**
	 * Visa document attachment ID.
	 */
	public function visa_doc_id(): int {
		return (int) get_post_meta( $this->id(), self::META_VISA_DOC, true );
	}

	/**
	 * Note shown above the visa document.
	 */
	public function visa_note(): string {
		$note = (string) get_post_meta( $this->id(), self::META_VISA_NOTE, true );

		return '' !== trim( $note ) ? $note : (string) TZH_Settings::get( 'visa_note', '' );
	}

	/**
	 * A stored list, falling back to the site-wide default when empty.
	 *
	 * @param string $meta_key    Meta key holding the list.
	 * @param string $setting_key Settings key holding the fallback list.
	 *
	 * @return string[]
	 */
	private function list( string $meta_key, string $setting_key ): array {
		$saved = get_post_meta( $this->id(), $meta_key, true );
		$items = array_values( array_filter( array_map( 'strval', (array) $saved ), 'strlen' ) );

		if ( $items ) {
			return $items;
		}

		return array_values( (array) TZH_Settings::get( $setting_key, array() ) );
	}

	/**
	 * Hero banner attachment ID, falling back to the featured image.
	 */
	public function hero_id(): int {
		$hero = (int) get_post_meta( $this->id(), self::META_HERO, true );

		return $hero > 0 ? $hero : (int) get_post_thumbnail_id( $this->id() );
	}

	/**
	 * One-line summary shown on cards.
	 */
	public function short_description(): string {
		return (string) $this->post->post_excerpt;
	}


	/**
	 * The other comfort levels of this same tour, keyed by category slug.
	 *
	 * Packages are tied together by their tour group, so a Deluxe version can
	 * be offered from the Standard page without either knowing about the other.
	 *
	 * @return array<string, TZH_Package>
	 */
	public function variants(): array {
		$family = $this->family();

		if ( ! $family instanceof WP_Term ) {
			$tier = $this->tier();

			return $tier ? array( $tier->slug => $this ) : array();
		}

		$ids = get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => 20,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => self::TAX_FAMILY,
						'field'    => 'term_id',
						'terms'    => $family->term_id,
					),
				),
			)
		);

		$variants = array();

		foreach ( $ids as $id ) {
			$package = self::from( $id );
			$tier    = $package ? $package->tier() : null;

			if ( $package && $tier instanceof WP_Term ) {
				$variants[ $tier->slug ] = $package;
			}
		}

		return $variants;
	}

	/**
	 * Hero banner URL at a size worth showing full width.
	 */
	public function hero_url(): string {
		return tzh_image_url( $this->hero_id(), 'full' );
	}

	/**
	 * Headline shown on the detail page: destination, then the tour name.
	 */
	public function full_title(): string {
		$destination = $this->destination();
		$title       = get_the_title( $this->id() );

		if ( ! $destination ) {
			return $title;
		}

		return sprintf(
			/* translators: 1: destination name, 2: tour name */
			__( '%1$s (%2$s)', 'travelz-holidays' ),
			$destination->name,
			$title
		);
	}

	/**
	 * Quick facts shown as chips under the hero.
	 *
	 * @return array<int, array{icon: string, label: string}>
	 */
	public function quick_facts(): array {
		$facts = array();

		if ( $this->days() ) {
			$facts[] = array(
				'icon'  => 'clock',
				/* translators: %s: duration, e.g. 05 Days 04 Nights */
				'label' => sprintf( __( 'Duration: %s', 'travelz-holidays' ), $this->duration_label() ),
			);
		}

		$facts[] = array(
			'icon'  => 'users',
			/* translators: %s: smallest number of travellers */
			'label' => sprintf( __( 'Minimum Person: %s', 'travelz-holidays' ), number_format_i18n( $this->min_pax() ) ),
		);

		$facts[] = array(
			'icon'  => 'shield-check',
			/* translators: %s: tour type, e.g. Private */
			'label' => sprintf( __( 'Tour Type: %s', 'travelz-holidays' ), $this->tour_type_label() ),
		);

		if ( '' !== $this->code() ) {
			$facts[] = array(
				'icon'  => 'sparkles',
				/* translators: %s: package code, e.g. TZ 001 */
				'label' => sprintf( __( 'Package Code: %s', 'travelz-holidays' ), $this->code() ),
			);
		}

		$tier = $this->tier();

		if ( $tier instanceof WP_Term ) {
			$facts[] = array(
				'icon'  => 'award',
				/* translators: %s: category name, e.g. Standard */
				'label' => sprintf( __( 'Category: %s', 'travelz-holidays' ), $tier->name ),
			);
		}

		return $facts;
	}

	/**
	 * URL of a sub-screen of this package.
	 *
	 * @param string $action Either book or pdf.
	 */
	public function action_url( string $action ): string {
		$base = untrailingslashit( (string) get_permalink( $this->id() ) );

		$action = 'pdf' === $action ? 'details' : 'book';

		return user_trailingslashit( $base . '/' . $action );
	}

	/**
	 * First destination term, or null when none is assigned.
	 */
	public function destination(): ?WP_Term {
		return $this->first_term( self::TAX_DESTINATION );
	}

	/**
	 * Tier term, or null when none is assigned.
	 */
	public function tier(): ?WP_Term {
		return $this->first_term( self::TAX_TIER );
	}

	/**
	 * Tour group term that ties this package to its other tiers.
	 */
	public function family(): ?WP_Term {
		return $this->first_term( self::TAX_FAMILY );
	}

	/**
	 * First assigned term of a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 */
	private function first_term( string $taxonomy ): ?WP_Term {
		$terms = get_the_terms( $this->post, $taxonomy );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		return $terms[0];
	}
}

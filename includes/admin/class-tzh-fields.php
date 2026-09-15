<?php
/**
 * Package editor field schema.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes every field on the package editor, grouped into tabs.
 *
 * The tabs mirror the tabs a visitor sees on the package page, so editing a
 * package and reading one follow the same map. Later phases append tabs here
 * rather than adding separate meta boxes.
 */
class TZH_Fields {

	/**
	 * Tab definitions keyed by tab slug.
	 *
	 * @return array<string, array{label: string, icon: string, description?: string, fields: array<int, array<string, mixed>>}>
	 */
	public static function tabs(): array {
		$tabs = array(
			'overview' => array(
				'label'  => __( 'Overview', 'travelz-holidays' ),
				'icon'   => 'dashicons-info-outline',
				'fields' => self::overview_fields(),
			),
			'pricing'  => array(
				'label'       => __( 'Pricing', 'travelz-holidays' ),
				'icon'        => 'dashicons-tag',
				'description' => __( 'Adult price drives everything else. Child and infant prices are worked out from it at checkout.', 'travelz-holidays' ),
				'fields'      => self::pricing_fields(),
			),
			'itinerary' => array(
				'label'       => __( 'Itinerary', 'travelz-holidays' ),
				'icon'        => 'dashicons-calendar-alt',
				'description' => __( 'One entry per day, in travel order. Add the hotels a day ends at, so travellers can see where they sleep.', 'travelz-holidays' ),
				'fields'      => self::itinerary_fields(),
			),
			'highlights' => array(
				'label'       => __( 'Highlights', 'travelz-holidays' ),
				'icon'        => 'dashicons-star-filled',
				'description' => __( 'The handful of things that sell this tour. Shown beside the itinerary.', 'travelz-holidays' ),
				'fields'      => self::highlight_fields(),
			),
			'included' => array(
				'label'       => __( 'Inclusion & Exclusion', 'travelz-holidays' ),
				'icon'        => 'dashicons-yes-alt',
				'description' => __( 'Leave either list empty to fall back to the site-wide defaults under Settings.', 'travelz-holidays' ),
				'fields'      => self::inclusion_fields(),
			),
			'terms' => array(
				'label'       => __( 'Terms & Details', 'travelz-holidays' ),
				'icon'        => 'dashicons-media-text',
				'description' => __( 'Booking conditions and any longer notes for this package.', 'travelz-holidays' ),
				'fields'      => self::terms_fields(),
			),
			'visa' => array(
				'label'       => __( 'Visa', 'travelz-holidays' ),
				'icon'        => 'dashicons-id',
				'description' => __( 'Upload the visa requirements document travellers can view and download.', 'travelz-holidays' ),
				'fields'      => self::visa_fields(),
			),
		);

		/**
		 * Filter the package editor tabs.
		 *
		 * @param array<string, array<string, mixed>> $tabs Tab definitions.
		 */
		return apply_filters( 'tzh_editor_tabs', $tabs );
	}

	/**
	 * Every field across every tab, flattened.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all_fields(): array {
		$fields = array();

		foreach ( self::tabs() as $tab ) {
			foreach ( $tab['fields'] as $field ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Overview tab: what the package is and where it goes.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function overview_fields(): array {
		return array(
			array(
				'key'         => TZH_Package::META_CODE,
				'type'        => 'text',
				'label'       => __( 'Package code', 'travelz-holidays' ),
				'placeholder' => 'TZ 001',
				'description' => __( 'Shown to travellers and used to order the package list. The number in it sets the position, so TZ 001 comes before TZ 010.', 'travelz-holidays' ),
				'class'       => 'tzh-field--half',
			),
			array(
				'key'      => TZH_Package::TAX_DESTINATION,
				'type'     => 'term',
				'store'    => 'term',
				'taxonomy' => TZH_Package::TAX_DESTINATION,
				'label'    => __( 'Destination', 'travelz-holidays' ),
				'required' => true,
				'class'    => 'tzh-field--half',
			),
			array(
				'key'      => TZH_Package::TAX_TIER,
				'type'     => 'term',
				'store'    => 'term',
				'taxonomy' => TZH_Package::TAX_TIER,
				'label'    => __( 'Category', 'travelz-holidays' ),
				'required' => true,
				'class'    => 'tzh-field--half',
			),
			array(
				'key'         => TZH_Package::TAX_FAMILY,
				'type'        => 'term',
				'store'       => 'term',
				'taxonomy'    => TZH_Package::TAX_FAMILY,
				'allow_new'   => true,
				'label'       => __( 'Tour group', 'travelz-holidays' ),
				'description' => __( 'Packages sharing a tour group are treated as the same itinerary at different comfort levels, and offered to each other as alternatives.', 'travelz-holidays' ),
				'class'       => 'tzh-field--half',
			),
			array(
				'key'     => TZH_Package::META_DAYS,
				'type'    => 'number',
				'label'   => __( 'Days', 'travelz-holidays' ),
				'suffix'  => __( 'days', 'travelz-holidays' ),
				'min'     => 1,
				'max'     => 60,
				'default' => 0,
				'class'   => 'tzh-field--quarter',
			),
			array(
				'key'     => TZH_Package::META_NIGHTS,
				'type'    => 'number',
				'label'   => __( 'Nights', 'travelz-holidays' ),
				'suffix'  => __( 'nights', 'travelz-holidays' ),
				'min'     => 0,
				'max'     => 60,
				'default' => 0,
				'class'   => 'tzh-field--quarter',
			),
			array(
				'key'     => TZH_Package::META_TOUR_TYPE,
				'type'    => 'select',
				'label'   => __( 'Tour type', 'travelz-holidays' ),
				'options' => TZH_Package::tour_types(),
				'default' => 'private',
				'class'   => 'tzh-field--quarter',
			),
			array(
				'key'     => TZH_Package::META_MIN_PAX,
				'type'    => 'number',
				'label'   => __( 'Minimum travellers', 'travelz-holidays' ),
				'suffix'  => __( 'people', 'travelz-holidays' ),
				'min'     => 1,
				'max'     => 50,
				'default' => 2,
				'class'   => 'tzh-field--quarter',
			),
			array(
				'key'         => 'tzh_excerpt',
				'type'        => 'textarea',
				'store'       => 'excerpt',
				'label'       => __( 'Short description', 'travelz-holidays' ),
				'rows'        => 2,
				'placeholder' => __( 'Two cities, one unforgettable Himalayan journey.', 'travelz-holidays' ),
				'description' => __( 'One line shown on the package card in search results and listings.', 'travelz-holidays' ),
			),
			array(
				'key'         => TZH_Package::META_HERO,
				'type'        => 'media',
				'label'       => __( 'Hero banner', 'travelz-holidays' ),
				'description' => __( 'Wide image behind the package title. Falls back to the package image when empty.', 'travelz-holidays' ),
			),
			array(
				'key'   => TZH_Package::META_BESTSELLER,
				'type'  => 'checkbox',
				'label' => __( 'Show the Bestseller badge', 'travelz-holidays' ),
			),
			array(
				'key'     => TZH_Package::META_RATING,
				'type'    => 'number',
				'label'   => __( 'Rating', 'travelz-holidays' ),
				'suffix'  => __( 'out of 5', 'travelz-holidays' ),
				'min'     => 0,
				'max'     => 5,
				'step'    => '0.1',
				'default' => 0,
				'class'   => 'tzh-field--quarter',
			),
			array(
				'key'         => TZH_Package::META_BOOKED,
				'type'        => 'number',
				'label'       => __( 'Travellers booked', 'travelz-holidays' ),
				'min'         => 0,
				'default'     => 0,
				'description' => __( 'Leave both at zero to hide the rating line entirely.', 'travelz-holidays' ),
				'class'       => 'tzh-field--quarter',
			),
		);
	}

	/**
	 * Pricing tab.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function pricing_fields(): array {
		return array(
			array(
				'key'         => TZH_Package::META_PRICE,
				'type'        => 'number',
				'label'       => __( 'Adult price', 'travelz-holidays' ),
				'min'         => 0,
				'default'     => 0,
				'prefix'      => '৳',
				'suffix'      => __( 'per person', 'travelz-holidays' ),
				'description' => __( 'Age 12 and above.', 'travelz-holidays' ),
				'class'       => 'tzh-field--half',
			),
			array(
				'key'         => TZH_Package::META_CHILD_RATE,
				'type'        => 'number',
				'label'       => __( 'Child price', 'travelz-holidays' ),
				'min'         => 0,
				'max'         => 100,
				'default'     => 70,
				'suffix'      => __( '% of adult price', 'travelz-holidays' ),
				'description' => __( 'Age 2 to 11.', 'travelz-holidays' ),
				'class'       => 'tzh-field--half',
			),
			array(
				'key'         => TZH_Package::META_INFANT_PRICE,
				'type'        => 'number',
				'label'       => __( 'Infant price', 'travelz-holidays' ),
				'min'         => 0,
				'default'     => 1500,
				'prefix'      => '৳',
				'description' => __( 'Age 0 to 1. A flat amount, not a percentage.', 'travelz-holidays' ),
				'class'       => 'tzh-field--half',
			),
			array(
				'key'         => TZH_Package::META_WITH_AIRFARE,
				'type'        => 'checkbox',
				'label'       => __( 'Price includes air fare', 'travelz-holidays' ),
				'description' => __( 'Adds the "With air fare" badge next to the price.', 'travelz-holidays' ),
			),
			array(
				'key'   => 'tzh_price_preview',
				'type'  => 'preview',
				'label' => __( 'What travellers will see', 'travelz-holidays' ),
			),
		);
	}

	/**
	 * Itinerary tab: a repeating day, each with its own hotels.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function itinerary_fields(): array {
		return array(
			array(
				'key'       => TZH_Package::META_ITINERARY,
				'type'      => 'repeater',
				'label'     => __( 'Days', 'travelz-holidays' ),
				'row_label' => __( 'Day', 'travelz-holidays' ),
				'add_label' => __( 'Add a day', 'travelz-holidays' ),
				'fields'    => array(
					array(
						'key'         => 'title',
						'type'        => 'text',
						'label'       => __( 'Day title', 'travelz-holidays' ),
						'placeholder' => __( 'Day 1 — Arrival in Kathmandu', 'travelz-holidays' ),
						'summary'     => true,
						'class'       => 'tzh-field--half',
					),
					array(
						'key'         => 'meals',
						'type'        => 'text',
						'label'       => __( 'Meals', 'travelz-holidays' ),
						'placeholder' => __( 'Breakfast', 'travelz-holidays' ),
						'description' => __( 'Shown as a badge on the day. Write "No" when none are included.', 'travelz-holidays' ),
						'class'       => 'tzh-field--half',
					),
					array(
						'key'         => 'body',
						'type'        => 'textarea',
						'label'       => __( 'What happens that day', 'travelz-holidays' ),
						'rows'        => 3,
						'placeholder' => __( 'Airport pickup, transfer to hotel, and evening walk through Thamel.', 'travelz-holidays' ),
					),
					array(
						'key'       => 'hotels',
						'type'      => 'repeater',
						'label'     => __( 'Accommodation', 'travelz-holidays' ),
						'row_label' => __( 'Hotel', 'travelz-holidays' ),
						'add_label' => __( 'Add a hotel', 'travelz-holidays' ),
						'fields'    => array(
							array(
								'key'         => 'name',
								'type'        => 'text',
								'label'       => __( 'Hotel', 'travelz-holidays' ),
								'placeholder' => __( 'Hotel Yak & Yeti', 'travelz-holidays' ),
								'summary'     => true,
								'class'       => 'tzh-field--half',
							),
							array(
								'key'         => 'city',
								'type'        => 'text',
								'label'       => __( 'City', 'travelz-holidays' ),
								'placeholder' => __( 'Kathmandu', 'travelz-holidays' ),
								'class'       => 'tzh-field--half',
							),
							array(
								'key'         => 'class',
								'type'        => 'text',
								'label'       => __( 'Class', 'travelz-holidays' ),
								'placeholder' => __( '4 Star', 'travelz-holidays' ),
								'class'       => 'tzh-field--half',
							),
							array(
								'key'         => 'stay',
								'type'        => 'text',
								'label'       => __( 'Length of stay', 'travelz-holidays' ),
								'placeholder' => __( '1 Night', 'travelz-holidays' ),
								'class'       => 'tzh-field--half',
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Highlights tab.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function highlight_fields(): array {
		return array(
			array(
				'key'         => TZH_Package::META_HIGHLIGHTS,
				'type'        => 'lines',
				'label'       => __( 'Tour product highlights', 'travelz-holidays' ),
				'rows'        => 7,
				'placeholder' => "Return airport transfers on private vehicle\nHandpicked centrally located hotels\nDaily breakfast throughout the tour",
			),
		);
	}

	/**
	 * Inclusion and exclusion tab.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function inclusion_fields(): array {
		return array(
			array(
				'key'         => TZH_Package::META_INCLUSION,
				'type'        => 'lines',
				'label'       => __( 'Inclusion', 'travelz-holidays' ),
				'rows'        => 8,
				'placeholder' => "Dhaka–Kathmandu–Dhaka flight\nAirport pick up & drop\nDaily breakfast",
				'class'       => 'tzh-field--half',
			),
			array(
				'key'         => TZH_Package::META_EXCLUSION,
				'type'        => 'lines',
				'label'       => __( 'Exclusion', 'travelz-holidays' ),
				'rows'        => 8,
				'placeholder' => "Lunch & Dinner\nBeverages of any kind\nPersonal & medical expenses",
				'class'       => 'tzh-field--half',
			),
		);
	}

	/**
	 * Terms and other details tab.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function terms_fields(): array {
		return array(
			array(
				'key'         => TZH_Package::META_TERMS,
				'type'        => 'lines',
				'label'       => __( 'Terms & conditions', 'travelz-holidays' ),
				'rows'        => 7,
				'description' => __( 'Left empty, the package shows the terms set under Settings.', 'travelz-holidays' ),
			),
			array(
				'key'         => TZH_Package::META_OTHER,
				'type'        => 'textarea',
				'label'       => __( 'Other details', 'travelz-holidays' ),
				'rows'        => 8,
				'description' => __( 'Longer notes — what to pack, passport validity, special requests. Leave a blank line between paragraphs.', 'travelz-holidays' ),
			),
		);
	}

	/**
	 * Visa tab.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function visa_fields(): array {
		return array(
			array(
				'key'         => TZH_Package::META_VISA_DOC,
				'type'        => 'media',
				'label'       => __( 'Visa requirements document', 'travelz-holidays' ),
				'description' => __( 'An image or scan travellers can open full size and download.', 'travelz-holidays' ),
			),
			array(
				'key'         => TZH_Package::META_VISA_NOTE,
				'type'        => 'textarea',
				'label'       => __( 'Note above the document', 'travelz-holidays' ),
				'rows'        => 3,
				'description' => __( 'Left empty, the note set under Settings is used.', 'travelz-holidays' ),
			),
		);
	}
}

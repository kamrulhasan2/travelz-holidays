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
				'min'     => 1,
				'max'     => 60,
				'default' => 0,
				'class'   => 'tzh-field--quarter',
			),
			array(
				'key'     => TZH_Package::META_NIGHTS,
				'type'    => 'number',
				'label'   => __( 'Nights', 'travelz-holidays' ),
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
}

<?php
/**
 * Taxonomy registration.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the three taxonomies a package is classified by.
 *
 * Destination and Category are what visitors filter on. Tour Group is
 * internal plumbing: it ties the Standard, Deluxe and Premium versions of one
 * itinerary together so the detail page can offer them as alternatives.
 */
class TZH_Taxonomies {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ), 4 );
	}

	/**
	 * Register all three taxonomies.
	 */
	public function register(): void {
		$this->register_destination();
		$this->register_tier();
		$this->register_family();
	}

	/**
	 * Destination — the country or region a package visits.
	 */
	private function register_destination(): void {
		register_taxonomy(
			TZH_Package::TAX_DESTINATION,
			array( TZH_Package::POST_TYPE ),
			array(
				'labels'            => array(
					'name'              => _x( 'Destinations', 'taxonomy general name', 'travelz-holidays' ),
					'singular_name'     => _x( 'Destination', 'taxonomy singular name', 'travelz-holidays' ),
					'menu_name'         => __( 'Destinations', 'travelz-holidays' ),
					'all_items'         => __( 'All Destinations', 'travelz-holidays' ),
					'edit_item'         => __( 'Edit Destination', 'travelz-holidays' ),
					'update_item'       => __( 'Update Destination', 'travelz-holidays' ),
					'add_new_item'      => __( 'Add New Destination', 'travelz-holidays' ),
					'new_item_name'     => __( 'New destination name', 'travelz-holidays' ),
					'search_items'      => __( 'Search Destinations', 'travelz-holidays' ),
					'parent_item'       => __( 'Parent Destination', 'travelz-holidays' ),
					'parent_item_colon' => __( 'Parent Destination:', 'travelz-holidays' ),
					'not_found'         => __( 'No destinations yet.', 'travelz-holidays' ),
					'back_to_items'     => __( '← Back to Destinations', 'travelz-holidays' ),
				),
				'description'       => __( 'Countries and regions packages are grouped under.', 'travelz-holidays' ),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'show_in_menu'      => true,
				'rewrite'           => array(
					'slug'         => 'destination',
					'with_front'   => false,
					'hierarchical' => false,
				),
			)
		);
	}

	/**
	 * Category — the comfort tier: Standard, Deluxe or Premium.
	 */
	private function register_tier(): void {
		register_taxonomy(
			TZH_Package::TAX_TIER,
			array( TZH_Package::POST_TYPE ),
			array(
				'labels'            => array(
					'name'          => _x( 'Categories', 'taxonomy general name', 'travelz-holidays' ),
					'singular_name' => _x( 'Category', 'taxonomy singular name', 'travelz-holidays' ),
					'menu_name'     => __( 'Categories', 'travelz-holidays' ),
					'all_items'     => __( 'All Categories', 'travelz-holidays' ),
					'edit_item'     => __( 'Edit Category', 'travelz-holidays' ),
					'update_item'   => __( 'Update Category', 'travelz-holidays' ),
					'add_new_item'  => __( 'Add New Category', 'travelz-holidays' ),
					'new_item_name' => __( 'New category name', 'travelz-holidays' ),
					'search_items'  => __( 'Search Categories', 'travelz-holidays' ),
					'not_found'     => __( 'No categories yet.', 'travelz-holidays' ),
					'back_to_items' => __( '← Back to Categories', 'travelz-holidays' ),
				),
				'description'       => __( 'Comfort level of a package: Standard, Deluxe or Premium.', 'travelz-holidays' ),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'show_in_menu'      => true,
				'rewrite'           => array(
					'slug'         => 'package-category',
					'with_front'   => false,
					'hierarchical' => false,
				),
			)
		);
	}

	/**
	 * Tour Group — links the tiers of one itinerary.
	 */
	private function register_family(): void {
		register_taxonomy(
			TZH_Package::TAX_FAMILY,
			array( TZH_Package::POST_TYPE ),
			array(
				'labels'            => array(
					'name'          => _x( 'Tour Groups', 'taxonomy general name', 'travelz-holidays' ),
					'singular_name' => _x( 'Tour Group', 'taxonomy singular name', 'travelz-holidays' ),
					'menu_name'     => __( 'Tour Groups', 'travelz-holidays' ),
					'all_items'     => __( 'All Tour Groups', 'travelz-holidays' ),
					'edit_item'     => __( 'Edit Tour Group', 'travelz-holidays' ),
					'update_item'   => __( 'Update Tour Group', 'travelz-holidays' ),
					'add_new_item'  => __( 'Add New Tour Group', 'travelz-holidays' ),
					'new_item_name' => __( 'New tour group name', 'travelz-holidays' ),
					'search_items'  => __( 'Search Tour Groups', 'travelz-holidays' ),
					'not_found'     => __( 'No tour groups yet.', 'travelz-holidays' ),
					'back_to_items' => __( '← Back to Tour Groups', 'travelz-holidays' ),
				),
				'description'       => __( 'Ties the Standard, Deluxe and Premium versions of one itinerary together.', 'travelz-holidays' ),
				'public'            => false,
				'publicly_queryable' => false,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'show_in_menu'      => true,
				'rewrite'           => false,
			)
		);
	}
}

<?php
/**
 * Post type registration.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the holiday package post type.
 *
 * The block editor is switched off here: a package is a structured record
 * (itinerary days, hotels, price tiers) edited through purpose-built fields,
 * not a body of prose.
 */
class TZH_Post_Types {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ), 5 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
	}

	/**
	 * Register the package post type.
	 */
	public function register(): void {
		$labels = array(
			'name'                  => _x( 'Packages', 'post type general name', 'travelz-holidays' ),
			'singular_name'         => _x( 'Package', 'post type singular name', 'travelz-holidays' ),
			'menu_name'             => _x( 'Packages', 'admin menu', 'travelz-holidays' ),
			'add_new'               => __( 'Add New', 'travelz-holidays' ),
			'add_new_item'          => __( 'Add New Package', 'travelz-holidays' ),
			'edit_item'             => __( 'Edit Package', 'travelz-holidays' ),
			'new_item'              => __( 'New Package', 'travelz-holidays' ),
			'view_item'             => __( 'View Package', 'travelz-holidays' ),
			'view_items'            => __( 'View Packages', 'travelz-holidays' ),
			'search_items'          => __( 'Search Packages', 'travelz-holidays' ),
			'not_found'             => __( 'No packages yet.', 'travelz-holidays' ),
			'not_found_in_trash'    => __( 'No packages in the Trash.', 'travelz-holidays' ),
			'all_items'             => __( 'All Packages', 'travelz-holidays' ),
			'archives'              => __( 'Package Archives', 'travelz-holidays' ),
			'featured_image'        => __( 'Package Image', 'travelz-holidays' ),
			'set_featured_image'    => __( 'Set package image', 'travelz-holidays' ),
			'remove_featured_image' => __( 'Remove package image', 'travelz-holidays' ),
			'use_featured_image'    => __( 'Use as package image', 'travelz-holidays' ),
			'item_published'        => __( 'Package published.', 'travelz-holidays' ),
			'item_updated'          => __( 'Package updated.', 'travelz-holidays' ),
		);

		register_post_type(
			TZH_Package::POST_TYPE,
			array(
				'labels'          => $labels,
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => tzh_menu_slug(),
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-palmtree',
				'supports'        => array( 'title', 'thumbnail', 'excerpt', 'revisions' ),
				'has_archive'     => 'holiday-packages',
				'rewrite'         => array(
					'slug'       => 'holiday-packages',
					'with_front' => false,
				),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'hierarchical'    => false,
				'menu_position'   => null,
				'taxonomies'      => array(
					TZH_Package::TAX_DESTINATION,
					TZH_Package::TAX_TIER,
					TZH_Package::TAX_FAMILY,
				),
			)
		);
	}

	/**
	 * Keep packages on the classic editor.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        Post type being edited.
	 */
	public function disable_block_editor( bool $use_block_editor, string $post_type ): bool {
		if ( TZH_Package::POST_TYPE === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Replace the generic "Add title" prompt with something useful.
	 *
	 * @param string  $text Placeholder text.
	 * @param WP_Post $post Post being edited.
	 */
	public function title_placeholder( string $text, WP_Post $post ): string {
		if ( TZH_Package::POST_TYPE !== $post->post_type ) {
			return $text;
		}

		return __( 'Tour name — e.g. Kathmandu – Pokhara', 'travelz-holidays' );
	}

	/**
	 * Say "Package" rather than "Post" in editor notices.
	 *
	 * @param array<string, array<int, string>> $messages Existing messages.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function updated_messages( array $messages ): array {
		$messages[ TZH_Package::POST_TYPE ] = array(
			0  => '',
			1  => __( 'Package updated.', 'travelz-holidays' ),
			2  => __( 'Custom field updated.', 'travelz-holidays' ),
			3  => __( 'Custom field deleted.', 'travelz-holidays' ),
			4  => __( 'Package updated.', 'travelz-holidays' ),
			5  => __( 'Package restored to revision.', 'travelz-holidays' ),
			6  => __( 'Package published.', 'travelz-holidays' ),
			7  => __( 'Package saved.', 'travelz-holidays' ),
			8  => __( 'Package submitted.', 'travelz-holidays' ),
			9  => __( 'Package scheduled.', 'travelz-holidays' ),
			10 => __( 'Package draft updated.', 'travelz-holidays' ),
		);

		return $messages;
	}
}

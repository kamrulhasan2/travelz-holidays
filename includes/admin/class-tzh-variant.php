<?php
/**
 * One-click tier variants.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates the Deluxe or Premium version of an existing package.
 *
 * The reference site sells the same itinerary at three comfort levels, so the
 * only real differences between the three records are the category and the
 * price. Retyping five itinerary days to change one number is where mistakes
 * come from, so this copies everything and leaves the operator two fields.
 */
class TZH_Variant {

	private const ACTION = 'tzh_create_variant';

	/**
	 * Meta keys a copy must not inherit.
	 *
	 * @var string[]
	 */
	private const UNIQUE_META = array(
		TZH_Package::META_CODE,
		TZH_Package::META_SERIAL,
		TZH_Package::META_WC_PRODUCT,
	);

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Offer a link for each tier this tour does not have yet.
	 *
	 * @param array<string, string> $actions Existing row actions.
	 * @param WP_Post               $post    Post in the row.
	 *
	 * @return array<string, string>
	 */
	public function row_actions( array $actions, WP_Post $post ): array {
		$package = TZH_Package::from( $post );

		if ( ! $package || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		foreach ( $this->missing_tiers( $package ) as $tier ) {
			$actions[ 'tzh_variant_' . $tier->slug ] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action'  => self::ACTION,
								'package' => $post->ID,
								'tier'    => $tier->term_id,
							),
							admin_url( 'admin-post.php' )
						),
						self::ACTION . '_' . $post->ID
					)
				),
				esc_html(
					sprintf(
						/* translators: %s: category name, e.g. Deluxe */
						__( 'New %s', 'travelz-holidays' ),
						$tier->name
					)
				)
			);
		}

		return $actions;
	}

	/**
	 * Copy the package into a new draft at the chosen tier.
	 */
	public function handle(): void {
		$package_id = isset( $_GET['package'] ) ? absint( $_GET['package'] ) : 0;
		$tier_id    = isset( $_GET['tier'] ) ? absint( $_GET['tier'] ) : 0;

		check_admin_referer( self::ACTION . '_' . $package_id );

		if ( ! current_user_can( 'edit_post', $package_id ) ) {
			wp_die( esc_html__( 'You are not allowed to copy this package.', 'travelz-holidays' ) );
		}

		$package = TZH_Package::from( $package_id );
		$tier    = get_term( $tier_id, TZH_Package::TAX_TIER );

		if ( ! $package || ! $tier instanceof WP_Term ) {
			wp_die( esc_html__( 'That package or category no longer exists.', 'travelz-holidays' ) );
		}

		$source = $package->post();

		$new_id = wp_insert_post(
			array(
				'post_type'    => TZH_Package::POST_TYPE,
				'post_title'   => $source->post_title,
				'post_excerpt' => $source->post_excerpt,
				'post_status'  => 'draft',
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}

		$this->copy_meta( $package->id(), (int) $new_id );
		$this->copy_terms( $package, (int) $new_id, $tier );

		wp_safe_redirect(
			add_query_arg(
				'tzh_variant',
				rawurlencode( $tier->name ),
				get_edit_post_link( (int) $new_id, 'url' )
			)
		);

		exit;
	}

	/**
	 * Tell the operator what was copied and what still needs filling in.
	 */
	public function notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only message after a redirect.
		if ( empty( $_GET['tzh_variant'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only message after a redirect.
		$tier = sanitize_text_field( wp_unslash( (string) $_GET['tzh_variant'] ) );

		printf(
			'<div class="notice notice-success"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: category name, e.g. Deluxe */
					__( '%s copy created as a draft. Set its package code and price, then publish — everything else came across.', 'travelz-holidays' ),
					$tier
				)
			)
		);
	}

	/**
	 * Tiers that no package in this tour group uses yet.
	 *
	 * @param TZH_Package $package Source package.
	 *
	 * @return WP_Term[]
	 */
	private function missing_tiers( TZH_Package $package ): array {
		$tiers = TZH_Term_Meta::ordered( TZH_Package::TAX_TIER );

		if ( ! $tiers ) {
			return array();
		}

		$taken  = array();
		$family = $package->family();

		if ( $family instanceof WP_Term ) {
			$siblings = get_posts(
				array(
					'post_type'      => TZH_Package::POST_TYPE,
					'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
					'numberposts'    => 50,
					'fields'         => 'ids',
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => TZH_Package::TAX_FAMILY,
							'field'    => 'term_id',
							'terms'    => $family->term_id,
						),
					),
				)
			);

			foreach ( $siblings as $sibling_id ) {
				$sibling = TZH_Package::from( $sibling_id );
				$term    = $sibling ? $sibling->tier() : null;

				if ( $term instanceof WP_Term ) {
					$taken[] = (int) $term->term_id;
				}
			}
		} else {
			$own = $package->tier();

			if ( $own instanceof WP_Term ) {
				$taken[] = (int) $own->term_id;
			}
		}

		return array_values(
			array_filter(
				$tiers,
				static function ( WP_Term $tier ) use ( $taken ): bool {
					return ! in_array( (int) $tier->term_id, $taken, true );
				}
			)
		);
	}

	/**
	 * Copy every meta value except the ones that must stay unique.
	 *
	 * @param int $source_id Source package ID.
	 * @param int $new_id    New package ID.
	 */
	private function copy_meta( int $source_id, int $new_id ): void {
		$meta = get_post_meta( $source_id );

		if ( ! is_array( $meta ) ) {
			return;
		}

		foreach ( $meta as $key => $values ) {
			if ( in_array( $key, self::UNIQUE_META, true ) ) {
				continue;
			}

			// Copy the featured image, but skip WordPress's internal bookkeeping.
			if ( '_' === $key[0] && '_thumbnail_id' !== $key && ! str_starts_with( $key, '_tzh_' ) ) {
				continue;
			}

			foreach ( (array) $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}
	}

	/**
	 * Put the copy in the same destination and tour group, at the new tier.
	 *
	 * @param TZH_Package $package Source package.
	 * @param int         $new_id  New package ID.
	 * @param WP_Term     $tier    Tier for the copy.
	 */
	private function copy_terms( TZH_Package $package, int $new_id, WP_Term $tier ): void {
		$destination = $package->destination();

		if ( $destination instanceof WP_Term ) {
			wp_set_object_terms( $new_id, array( (int) $destination->term_id ), TZH_Package::TAX_DESTINATION, false );
		}

		wp_set_object_terms( $new_id, array( (int) $tier->term_id ), TZH_Package::TAX_TIER, false );

		$family = $package->family();

		// A package with no tour group gets one now, named after the tour, so
		// the original and the copy end up linked rather than drifting apart.
		if ( ! $family instanceof WP_Term ) {
			$created = wp_insert_term( $package->post()->post_title, TZH_Package::TAX_FAMILY );

			if ( is_wp_error( $created ) ) {
				return;
			}

			$family_id = (int) $created['term_id'];

			wp_set_object_terms( $package->id(), array( $family_id ), TZH_Package::TAX_FAMILY, false );
		} else {
			$family_id = (int) $family->term_id;
		}

		wp_set_object_terms( $new_id, array( $family_id ), TZH_Package::TAX_FAMILY, false );
	}
}

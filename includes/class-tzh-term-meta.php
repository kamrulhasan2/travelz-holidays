<?php
/**
 * Term meta for destinations and categories.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Typed access to the extra fields a destination or category carries.
 *
 * A destination is a piece of content in its own right on the front-end — it
 * gets a card with a photo and a line of copy — so it needs more than a name.
 */
class TZH_Term_Meta {

	public const IMAGE = '_tzh_image';
	public const BLURB = '_tzh_blurb';
	public const ORDER = '_tzh_order';

	/**
	 * Destination card image.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function image_id( int $term_id ): int {
		return (int) get_term_meta( $term_id, self::IMAGE, true );
	}

	/**
	 * One-line description shown on the destination card.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function blurb( int $term_id ): string {
		return (string) get_term_meta( $term_id, self::BLURB, true );
	}

	/**
	 * Manual sort position. Lower comes first; 0 means unsorted.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function order( int $term_id ): int {
		return (int) get_term_meta( $term_id, self::ORDER, true );
	}

	/**
	 * Terms of a taxonomy in display order.
	 *
	 * Ordered by the manual position first, then alphabetically — so a site
	 * that never touches the order field still gets a sensible list.
	 *
	 * @param string $taxonomy   Taxonomy name.
	 * @param bool   $hide_empty Skip terms with no packages.
	 *
	 * @return WP_Term[]
	 */
	public static function ordered( string $taxonomy, bool $hide_empty = false ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => $hide_empty,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		usort(
			$terms,
			static function ( WP_Term $a, WP_Term $b ): int {
				$order_a = self::order( $a->term_id );
				$order_b = self::order( $b->term_id );

				// Unsorted terms sink below the ones given a position.
				$order_a = 0 === $order_a ? PHP_INT_MAX : $order_a;
				$order_b = 0 === $order_b ? PHP_INT_MAX : $order_b;

				if ( $order_a === $order_b ) {
					return strcasecmp( $a->name, $b->name );
				}

				return $order_a <=> $order_b;
			}
		);

		return $terms;
	}
}

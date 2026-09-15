<?php
/**
 * Package querying and filtering.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads the filters out of the URL and turns them into query arguments.
 *
 * The filters live in the query string rather than in JavaScript state, so a
 * filtered list can be bookmarked, shared and indexed — and it still works with
 * scripts switched off, because the filter panel is a plain form.
 */
class TZH_Query {

	/**
	 * Duration buckets, matching the original design.
	 *
	 * @return array<string, array{label: string, min: int, max: int|null}>
	 */
	public static function duration_buckets(): array {
		return array(
			'1-3' => array(
				'label' => __( '1–3 Days', 'travelz-holidays' ),
				'min'   => 1,
				'max'   => 3,
			),
			'4-6' => array(
				'label' => __( '4–6 Days', 'travelz-holidays' ),
				'min'   => 4,
				'max'   => 6,
			),
			'7'   => array(
				'label' => __( '7+ Days', 'travelz-holidays' ),
				'min'   => 7,
				'max'   => null,
			),
		);
	}

	/**
	 * Read and sanitize the filters from a set of request values.
	 *
	 * @param array<string, mixed> $source      Raw values, usually $_GET.
	 * @param string               $destination Slug of the destination being viewed.
	 *
	 * @return array<string, mixed>
	 */
	public static function filters( array $source, string $destination = '' ): array {
		$slugs = static function ( $value ): array {
			$items = is_array( $value ) ? $value : explode( ',', (string) $value );

			return array_values(
				array_filter(
					array_map(
						static function ( $item ) {
							return sanitize_title( (string) $item );
						},
						$items
					),
					'strlen'
				)
			);
		};

		$bounds = self::price_bounds();

		$destinations = isset( $source['dest'] ) ? $slugs( $source['dest'] ) : array();

		// The destination whose page this is stays selected unless the visitor
		// has explicitly changed the country boxes.
		if ( ! $destinations && '' !== $destination ) {
			$destinations = array( $destination );
		}

		$min = isset( $source['min'] ) ? (int) $source['min'] : $bounds['min'];
		$max = isset( $source['max'] ) ? (int) $source['max'] : $bounds['max'];

		if ( $min > $max ) {
			list( $min, $max ) = array( $max, $min );
		}

		$type = isset( $source['type'] ) ? sanitize_key( (string) $source['type'] ) : 'all';

		return array(
			'dest'   => $destinations,
			'tier'   => isset( $source['tier'] ) ? $slugs( $source['tier'] ) : array(),
			'days'   => array_values(
				array_intersect(
					isset( $source['days'] ) ? $slugs( $source['days'] ) : array(),
					array_keys( self::duration_buckets() )
				)
			),
			'min'    => max( $bounds['min'], $min ),
			'max'    => min( $bounds['max'], $max ),
			'type'   => array_key_exists( $type, TZH_Package::tour_types() ) ? $type : 'all',
			'bounds' => $bounds,
		);
	}

	/**
	 * Whether any filter differs from the default view.
	 *
	 * @param array<string, mixed> $filters     Sanitized filters.
	 * @param string               $destination Slug of the destination being viewed.
	 */
	public static function is_filtered( array $filters, string $destination = '' ): bool {
		if ( $filters['tier'] || $filters['days'] || 'all' !== $filters['type'] ) {
			return true;
		}

		if ( $filters['min'] > $filters['bounds']['min'] || $filters['max'] < $filters['bounds']['max'] ) {
			return true;
		}

		return array( $destination ) !== $filters['dest'] && '' !== $destination;
	}

	/**
	 * Cheapest and dearest package prices, rounded to a tidy step.
	 *
	 * @return array{min: int, max: int, step: int}
	 */
	public static function price_bounds(): array {
		$key    = 'tzh_price_bounds_' . TZH_Cache::version();
		$cached = wp_cache_get( $key, 'tzh' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$settings = TZH_Settings::all();
		$step     = max( 100, (int) ( $settings['price_step'] ?? 1000 ) );
		$floor    = max( 0, (int) ( $settings['price_min'] ?? 0 ) );
		$ceiling  = max( 0, (int) ( $settings['price_max'] ?? 0 ) );

		// Zero means "work it out from the catalogue", which is what a site
		// wants until someone decides otherwise.
		$high = $ceiling > 0 ? $ceiling : self::dearest();

		$bounds = array(
			'min'  => $floor,
			'max'  => $ceiling > 0 ? $ceiling : ( $high > 0 ? (int) ( ceil( $high / $step ) * $step ) : 100000 ),
			'step' => $step,
		);

		// A single-package catalogue, or a ceiling typed below the floor, would
		// otherwise give a slider with no width at all.
		if ( $bounds['max'] <= $bounds['min'] ) {
			$bounds['max'] = $bounds['min'] + ( $step * 10 );
		}

		wp_cache_set( $key, $bounds, 'tzh', HOUR_IN_SECONDS );

		return $bounds;
	}

	/**
	 * Price of the dearest published package, or 0 when there is none.
	 */
	private static function dearest(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The caller caches the result.
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(CAST(meta_value AS UNSIGNED))
				 FROM {$wpdb->postmeta} m
				 INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
				 WHERE m.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish'",
				TZH_Package::META_PRICE,
				TZH_Package::POST_TYPE
			)
		);

		return (int) $value;
	}

	/**
	 * Build WP_Query arguments for a set of filters.
	 *
	 * @param array<string, mixed> $filters Sanitized filters.
	 * @param int                  $paged   Page number.
	 *
	 * @return array<string, mixed>
	 */
	public static function args( array $filters, int $paged = 1 ): array {
		$args = array(
			'post_type'      => TZH_Package::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => self::per_page(),
			'paged'          => max( 1, $paged ),
			'tax_query'      => self::tax_query( $filters ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'meta_query'     => self::meta_query( $filters ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'orderby'        => array(
				'tzh_serial' => 'ASC',
				'date'       => 'DESC',
			),
		);

		return $args;
	}

	/**
	 * How many packages a page shows.
	 */
	public static function per_page(): int {
		/**
		 * Filter the number of packages listed per page.
		 *
		 * @param int $per_page Packages per page.
		 */
		return max( 1, (int) apply_filters( 'tzh_packages_per_page', 12 ) );
	}

	/**
	 * Apply the filters to a query that is already on the right archive.
	 *
	 * @param WP_Query             $query   Query to modify.
	 * @param array<string, mixed> $filters Sanitized filters.
	 */
	public static function apply( WP_Query $query, array $filters ): void {
		$query->set( 'posts_per_page', self::per_page() );
		$query->set( 'tax_query', self::tax_query( $filters ) );
		$query->set( 'meta_query', self::meta_query( $filters ) );
		$query->set(
			'orderby',
			array(
				'tzh_serial' => 'ASC',
				'date'       => 'DESC',
			)
		);
	}

	/**
	 * Taxonomy clauses: which destinations and which categories.
	 *
	 * @param array<string, mixed> $filters Sanitized filters.
	 *
	 * @return array<int|string, mixed>
	 */
	private static function tax_query( array $filters ): array {
		$clauses = array( 'relation' => 'AND' );

		if ( $filters['dest'] ) {
			$clauses[] = array(
				'taxonomy' => TZH_Package::TAX_DESTINATION,
				'field'    => 'slug',
				'terms'    => $filters['dest'],
			);
		}

		if ( $filters['tier'] ) {
			$clauses[] = array(
				'taxonomy' => TZH_Package::TAX_TIER,
				'field'    => 'slug',
				'terms'    => $filters['tier'],
			);
		}

		return count( $clauses ) > 1 ? $clauses : array();
	}

	/**
	 * Meta clauses: price, duration, tour type — plus the sort key.
	 *
	 * The sort clause is an OR pair rather than a plain meta_key so a package
	 * with no code yet is still listed instead of being quietly dropped by the
	 * inner join WordPress would otherwise build.
	 *
	 * @param array<string, mixed> $filters Sanitized filters.
	 *
	 * @return array<int|string, mixed>
	 */
	private static function meta_query( array $filters ): array {
		$clauses = array(
			'relation'   => 'AND',
			'tzh_serial' => array(
				'relation'   => 'OR',
				array(
					'key'     => TZH_Package::META_SERIAL,
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => TZH_Package::META_SERIAL,
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$bounds = $filters['bounds'];

		if ( $filters['min'] > $bounds['min'] || $filters['max'] < $bounds['max'] ) {
			$clauses[] = array(
				'key'     => TZH_Package::META_PRICE,
				'value'   => array( $filters['min'], $filters['max'] ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		if ( 'all' !== $filters['type'] ) {
			$match = array(
				'key'     => TZH_Package::META_TOUR_TYPE,
				'value'   => $filters['type'],
				'compare' => '=',
			);

			/*
			 * A package with no tour type saved — imported, or created through
			 * the REST API — reads as Private everywhere else in the plugin, so
			 * the filter has to agree or those packages vanish from a list that
			 * still calls them Private Tour.
			 */
			$clauses[] = 'private' === $filters['type']
				? array(
					'relation' => 'OR',
					$match,
					array(
						'key'     => TZH_Package::META_TOUR_TYPE,
						'compare' => 'NOT EXISTS',
					),
				)
				: $match;
		}

		if ( $filters['days'] ) {
			$buckets = self::duration_buckets();
			$any     = array( 'relation' => 'OR' );

			foreach ( $filters['days'] as $key ) {
				$bucket = $buckets[ $key ];

				$any[] = null === $bucket['max']
					? array(
						'key'     => TZH_Package::META_DAYS,
						'value'   => $bucket['min'],
						'compare' => '>=',
						'type'    => 'NUMERIC',
					)
					: array(
						'key'     => TZH_Package::META_DAYS,
						'value'   => array( $bucket['min'], $bucket['max'] ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
			}

			$clauses[] = $any;
		}

		return $clauses;
	}
}

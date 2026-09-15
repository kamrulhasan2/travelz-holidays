<?php
/**
 * Search engine metadata.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes the plugin's screens to search engines.
 *
 * Two jobs, kept apart on purpose. The structured data is always printed: no
 * general-purpose SEO plugin knows that a package is a tour with a price, a
 * duration and an itinerary. The plain meta and OpenGraph tags are printed only
 * when nothing else is doing it, because Rank Math or Yoast writing one
 * description and this plugin writing another is worse than either alone.
 */
class TZH_Seo {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_filter( 'document_title_parts', array( $this, 'title' ), 20 );
		add_action( 'wp_head', array( $this, 'meta' ), 5 );
		add_action( 'wp_head', array( $this, 'schema' ), 20 );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'hide_mirror_products' ), 10, 2 );
	}

	/**
	 * Whether another plugin is already writing meta tags.
	 */
	public static function seo_plugin_active(): bool {
		return defined( 'RANK_MATH_VERSION' )
			|| defined( 'WPSEO_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| class_exists( 'The_SEO_Framework\\Load' );
	}

	/**
	 * Currency code used in structured data.
	 */
	public static function currency_code(): string {
		/**
		 * Filter the ISO 4217 currency code advertised to search engines.
		 *
		 * The visible currency is a symbol chosen in Settings; structured data
		 * needs the code, and the two cannot be derived from each other.
		 *
		 * @param string $code Currency code.
		 */
		return (string) apply_filters( 'tzh_currency_code', 'BDT' );
	}

	/**
	 * Give destination archives a title of their own.
	 *
	 * @param array<string, string> $parts Title parts.
	 *
	 * @return array<string, string>
	 */
	public function title( array $parts ): array {
		if ( self::seo_plugin_active() ) {
			return $parts;
		}

		$term = $this->current_destination();

		if ( $term instanceof WP_Term ) {
			$parts['title'] = sprintf(
				/* translators: %s: destination name */
				__( '%s Holiday Packages', 'travelz-holidays' ),
				$term->name
			);
		}

		return $parts;
	}

	/**
	 * Print the description and the social tags.
	 */
	public function meta(): void {
		/**
		 * Filter whether the plugin prints its own meta and OpenGraph tags.
		 *
		 * @param bool $print Whether to print them.
		 */
		if ( ! apply_filters( 'tzh_seo_meta', ! self::seo_plugin_active() ) ) {
			return;
		}

		$context = $this->context();

		if ( ! $context ) {
			return;
		}

		printf(
			"<meta name=\"description\" content=\"%s\" />\n",
			esc_attr( $context['description'] )
		);

		$tags = array(
			'og:type'        => $context['type'],
			'og:title'       => $context['title'],
			'og:description' => $context['description'],
			'og:url'         => $context['url'],
			'og:site_name'   => get_bloginfo( 'name' ),
		);

		if ( '' !== $context['image'] ) {
			$tags['og:image'] = $context['image'];
		}

		foreach ( $tags as $property => $value ) {
			printf(
				"<meta property=\"%s\" content=\"%s\" />\n",
				esc_attr( $property ),
				esc_attr( $value )
			);
		}

		printf(
			"<meta name=\"twitter:card\" content=\"%s\" />\n",
			esc_attr( '' !== $context['image'] ? 'summary_large_image' : 'summary' )
		);
	}

	/**
	 * Print the JSON-LD graph for the current screen.
	 */
	public function schema(): void {
		/**
		 * Filter whether the plugin prints its structured data.
		 *
		 * @param bool $print Whether to print it.
		 */
		if ( ! apply_filters( 'tzh_seo_schema', true ) ) {
			return;
		}

		$graph = $this->graph();

		if ( ! $graph ) {
			return;
		}

		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
		);
	}

	/**
	 * Keep the hidden booking products out of the sitemap.
	 *
	 * They exist only so a booking becomes a WooCommerce order; they have no
	 * page worth visiting and would be a hundred thin duplicates of the
	 * packages a search engine should be reading instead.
	 *
	 * @param array<string, mixed> $args      Query arguments.
	 * @param string               $post_type Post type being listed.
	 *
	 * @return array<string, mixed>
	 */
	public function hide_mirror_products( array $args, string $post_type ): array {
		if ( 'product' !== $post_type ) {
			return $args;
		}

		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => TZH_Woo::PRODUCT_LINK,
				'compare' => 'NOT EXISTS',
			),
		);

		return $args;
	}

	/**
	 * What this screen is about, or null when it is not one of ours.
	 *
	 * @return array{type: string, title: string, description: string, url: string, image: string}|null
	 */
	private function context(): ?array {
		$package = $this->current_package();

		if ( $package ) {
			return array(
				'type'        => 'product',
				'title'       => $package->full_title(),
				'description' => $this->package_description( $package ),
				'url'         => (string) get_permalink( $package->id() ),
				'image'       => $package->hero_url(),
			);
		}

		$term = $this->current_destination();

		if ( $term instanceof WP_Term ) {
			return array(
				'type'        => 'website',
				'title'       => sprintf(
					/* translators: %s: destination name */
					__( '%s Holiday Packages', 'travelz-holidays' ),
					$term->name
				),
				'description' => $this->term_description( $term ),
				'url'         => (string) get_term_link( $term ),
				'image'       => tzh_image_url( TZH_Term_Meta::image_id( $term->term_id ), 'large' ),
			);
		}

		if ( is_post_type_archive( TZH_Package::POST_TYPE ) ) {
			return array(
				'type'        => 'website',
				'title'       => (string) TZH_Settings::get( 'archive_title', __( 'Explore Holiday Packages', 'travelz-holidays' ) ),
				'description' => (string) TZH_Settings::get( 'archive_subtitle', '' ),
				'url'         => TZH_Rewrites::grid_url(),
				'image'       => '',
			);
		}

		return null;
	}

	/**
	 * The JSON-LD nodes for this screen.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function graph(): array {
		$package = $this->current_package();

		if ( $package ) {
			return array(
				$this->trip_node( $package ),
				$this->crumbs_node(
					array(
						array( get_bloginfo( 'name' ), home_url( '/' ) ),
						array( (string) TZH_Settings::get( 'archive_title', __( 'Holiday Packages', 'travelz-holidays' ) ), TZH_Rewrites::grid_url() ),
						$package->destination()
							? array( $package->destination()->name, (string) get_term_link( $package->destination() ) )
							: null,
						array( get_the_title( $package->id() ), (string) get_permalink( $package->id() ) ),
					)
				),
			);
		}

		$term = $this->current_destination();

		if ( $term instanceof WP_Term ) {
			return array(
				$this->list_node(),
				$this->crumbs_node(
					array(
						array( get_bloginfo( 'name' ), home_url( '/' ) ),
						array( (string) TZH_Settings::get( 'archive_title', __( 'Holiday Packages', 'travelz-holidays' ) ), TZH_Rewrites::grid_url() ),
						array( $term->name, (string) get_term_link( $term ) ),
					)
				),
			);
		}

		return array();
	}

	/**
	 * A package as a TouristTrip with an offer.
	 *
	 * @param TZH_Package $package Package being described.
	 *
	 * @return array<string, mixed>
	 */
	private function trip_node( TZH_Package $package ): array {
		$destination = $package->destination();
		$node        = array(
			'@type'       => 'TouristTrip',
			'@id'         => get_permalink( $package->id() ) . '#trip',
			'name'        => $package->full_title(),
			'description' => $this->package_description( $package ),
			'url'         => (string) get_permalink( $package->id() ),
			'offers'      => array(
				'@type'         => 'Offer',
				'price'         => (string) $package->price(),
				'priceCurrency' => self::currency_code(),
				'availability'  => 'https://schema.org/InStock',
				'url'           => $package->action_url( 'book' ),
			),
		);

		if ( '' !== $package->hero_url() ) {
			$node['image'] = $package->hero_url();
		}

		if ( $package->days() > 0 ) {
			// ISO 8601: a four-day tour lasts P4D, whatever the label says.
			$node['duration'] = 'P' . $package->days() . 'D';
		}

		$node['touristType'] = $package->tour_type_label();
		$node['provider']    = array(
			'@type' => 'TravelAgency',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		if ( $destination instanceof WP_Term ) {
			$node['itinerary'] = $this->itinerary_node( $package, $destination );
			$node['subjectOf'] = array(
				'@type' => 'WebPage',
				'url'   => (string) get_term_link( $destination ),
			);
		}

		if ( $package->rating() > 0 && $package->booked_count() > 0 ) {
			$node['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => (string) $package->rating(),
				'bestRating'  => '5',
				'ratingCount' => (string) $package->booked_count(),
			);
		}

		return $node;
	}

	/**
	 * The itinerary as an ordered list of places.
	 *
	 * @param TZH_Package $package     Package being described.
	 * @param WP_Term     $destination Destination the tour visits.
	 *
	 * @return array<string, mixed>
	 */
	private function itinerary_node( TZH_Package $package, WP_Term $destination ): array {
		$items = array();

		foreach ( $package->itinerary() as $index => $day ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'item'     => array(
					'@type'   => 'TouristAttraction',
					'name'    => '' !== $day['title'] ? $day['title'] : $destination->name,
					'address' => $destination->name,
				),
			);
		}

		return array(
			'@type'           => 'ItemList',
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		);
	}

	/**
	 * The packages on a destination archive, as an ItemList.
	 *
	 * @return array<string, mixed>
	 */
	private function list_node(): array {
		$items = array();
		$posts = ( $GLOBALS['wp_query'] instanceof WP_Query ) ? $GLOBALS['wp_query']->posts : array();

		foreach ( (array) $posts as $index => $post ) {
			$package = TZH_Package::from( $post );

			if ( ! $package ) {
				continue;
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'url'      => (string) get_permalink( $package->id() ),
				'name'     => $package->full_title(),
			);
		}

		return array(
			'@type'           => 'ItemList',
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		);
	}

	/**
	 * A breadcrumb trail.
	 *
	 * @param array<int, array{0: string, 1: string}|null> $crumbs Label and URL pairs.
	 *
	 * @return array<string, mixed>
	 */
	private function crumbs_node( array $crumbs ): array {
		$items    = array();
		$position = 0;

		foreach ( $crumbs as $crumb ) {
			if ( ! is_array( $crumb ) ) {
				continue;
			}

			++$position;

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => $crumb[0],
				'item'     => $crumb[1],
			);
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	/**
	 * A sentence describing a package.
	 *
	 * @param TZH_Package $package Package being described.
	 */
	private function package_description( TZH_Package $package ): string {
		$excerpt = trim( $package->short_description() );

		if ( '' !== $excerpt ) {
			return wp_strip_all_tags( $excerpt );
		}

		$destination = $package->destination();

		return sprintf(
			/* translators: 1: destination name, 2: duration, 3: price */
			__( '%1$s holiday package — %2$s from %3$s per person, with itinerary, inclusions and booking.', 'travelz-holidays' ),
			$destination ? $destination->name : get_the_title( $package->id() ),
			$package->duration_label(),
			tzh_price( $package->price() )
		);
	}

	/**
	 * A sentence describing a destination.
	 *
	 * @param WP_Term $term Destination term.
	 */
	private function term_description( WP_Term $term ): string {
		$blurb = trim( TZH_Term_Meta::blurb( $term->term_id ) );

		if ( '' === $blurb ) {
			$blurb = trim( wp_strip_all_tags( $term->description ) );
		}

		if ( '' !== $blurb ) {
			return $blurb;
		}

		return sprintf(
			/* translators: %s: destination name */
			__( 'Holiday packages to %s — itineraries, prices and inclusions for every budget.', 'travelz-holidays' ),
			$term->name
		);
	}

	/**
	 * The package being viewed, if any.
	 */
	private function current_package(): ?TZH_Package {
		if ( ! is_singular( TZH_Package::POST_TYPE ) || '' !== TZH_Rewrites::current_action() ) {
			return null;
		}

		return TZH_Package::from( get_queried_object() );
	}

	/**
	 * The destination being viewed, if any.
	 */
	private function current_destination(): ?WP_Term {
		if ( ! is_tax( TZH_Package::TAX_DESTINATION ) ) {
			return null;
		}

		$term = get_queried_object();

		return $term instanceof WP_Term ? $term : null;
	}
}

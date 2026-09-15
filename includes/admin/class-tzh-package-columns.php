<?php
/**
 * Packages list table.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shapes the Packages list into something a travel desk can scan.
 *
 * Default order is by package code, matching the serial-wise ordering the
 * front-end list uses, so the admin list and the public list read the same way.
 */
class TZH_Package_Columns {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		$type = TZH_Package::POST_TYPE;

		add_filter( "manage_edit-{$type}_columns", array( $this, 'columns' ) );
		add_action( "manage_{$type}_posts_custom_column", array( $this, 'render' ), 10, 2 );
		add_filter( "manage_edit-{$type}_sortable_columns", array( $this, 'sortable' ) );
		add_action( 'restrict_manage_posts', array( $this, 'filters' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_ordering' ) );
	}

	/**
	 * Define the columns.
	 *
	 * @param array<string, string> $columns Default columns.
	 *
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$date = $columns['date'] ?? '';
		unset( $columns['date'] );

		$columns = array_merge(
			array( 'cb' => $columns['cb'] ?? '' ),
			array(
				'tzh_thumb'       => '<span class="screen-reader-text">' . esc_html__( 'Image', 'travelz-holidays' ) . '</span>',
				'title'           => __( 'Tour', 'travelz-holidays' ),
				'tzh_code'        => __( 'Code', 'travelz-holidays' ),
				'tzh_destination' => __( 'Destination', 'travelz-holidays' ),
				'tzh_tier'        => __( 'Category', 'travelz-holidays' ),
				'tzh_duration'    => __( 'Duration', 'travelz-holidays' ),
				'tzh_type'        => __( 'Type', 'travelz-holidays' ),
				'tzh_price'       => __( 'From', 'travelz-holidays' ),
			)
		);

		if ( $date ) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * Render one cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Package ID.
	 */
	public function render( string $column, int $post_id ): void {
		$package = TZH_Package::from( $post_id );

		if ( ! $package ) {
			return;
		}

		switch ( $column ) {
			case 'tzh_thumb':
				$this->render_thumb( $package );
				break;

			case 'tzh_code':
				echo $package->code()
					? '<code>' . esc_html( $package->code() ) . '</code>'
					: $this->dash();
				break;

			case 'tzh_destination':
				$this->render_term( $package->destination(), TZH_Package::TAX_DESTINATION );
				break;

			case 'tzh_tier':
				$this->render_term( $package->tier(), TZH_Package::TAX_TIER );
				break;

			case 'tzh_duration':
				echo $package->days()
					? esc_html( $package->duration_label() )
					: $this->dash();
				break;

			case 'tzh_type':
				echo esc_html( $package->tour_type_label() );
				break;

			case 'tzh_price':
				echo $package->price()
					? '<strong>' . esc_html( tzh_price( $package->price() ) ) . '</strong>'
					: $this->dash();
				break;
		}
	}

	/**
	 * Columns the list can be sorted by.
	 *
	 * @param array<string, string> $columns Sortable columns.
	 *
	 * @return array<string, string>
	 */
	public function sortable( array $columns ): array {
		$columns['tzh_code']     = 'tzh_serial';
		$columns['tzh_price']    = 'tzh_price';
		$columns['tzh_duration'] = 'tzh_days';

		return $columns;
	}

	/**
	 * Destination and category dropdowns above the list.
	 *
	 * @param string $post_type Current post type.
	 */
	public function filters( string $post_type ): void {
		if ( TZH_Package::POST_TYPE !== $post_type ) {
			return;
		}

		foreach ( array( TZH_Package::TAX_DESTINATION, TZH_Package::TAX_TIER ) as $taxonomy ) {
			$object = get_taxonomy( $taxonomy );

			if ( ! $object ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
			$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : '';

			wp_dropdown_categories(
				array(
					'taxonomy'        => $taxonomy,
					'name'            => $taxonomy,
					'value_field'     => 'slug',
					'selected'        => $selected,
					/* translators: %s: taxonomy plural name */
					'show_option_all' => sprintf( __( 'All %s', 'travelz-holidays' ), $object->labels->name ),
					'hide_empty'      => false,
					'hierarchical'    => true,
					'orderby'         => 'name',
				)
			);
		}
	}

	/**
	 * Default to serial order, and honour clicks on the sortable headers.
	 *
	 * @param WP_Query $query Current query.
	 */
	public function apply_ordering( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( TZH_Package::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = (string) $query->get( 'orderby' );

		$meta_sorts = array(
			'tzh_serial' => TZH_Package::META_SERIAL,
			'tzh_price'  => TZH_Package::META_PRICE,
			'tzh_days'   => TZH_Package::META_DAYS,
		);

		if ( isset( $meta_sorts[ $orderby ] ) ) {
			$this->order_by_meta( $query, $meta_sorts[ $orderby ], (string) $query->get( 'order' ) ?: 'ASC' );

			return;
		}

		if ( '' === $orderby ) {
			$this->order_by_meta( $query, TZH_Package::META_SERIAL, 'ASC' );
		}
	}

	/**
	 * Order by a numeric meta value without dropping rows that lack it.
	 *
	 * A plain meta_key + meta_value_num sort makes WP_Query INNER JOIN the meta
	 * table, so a half-finished package with no code or price would silently
	 * disappear from the list. The OR/NOT EXISTS pair turns that into a LEFT
	 * JOIN, keeping every package visible.
	 *
	 * @param WP_Query $query    Query to modify.
	 * @param string   $meta_key Numeric meta key to sort on.
	 * @param string   $order    ASC or DESC.
	 */
	private function order_by_meta( WP_Query $query, string $meta_key, string $order ): void {
		$query->set(
			'meta_query',
			array(
				'relation'   => 'OR',
				'tzh_sort'   => array(
					'key'     => $meta_key,
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				),
				'tzh_absent' => array(
					'key'     => $meta_key,
					'compare' => 'NOT EXISTS',
				),
			)
		);

		$query->set(
			'orderby',
			array(
				'tzh_sort' => strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC',
				'date'     => 'DESC',
			)
		);
	}

	/**
	 * Featured image thumbnail, or a neutral placeholder.
	 *
	 * @param TZH_Package $package Package.
	 */
	private function render_thumb( TZH_Package $package ): void {
		if ( has_post_thumbnail( $package->id() ) ) {
			echo '<span class="tzh-col-thumb">';
			echo get_the_post_thumbnail( $package->id(), array( 60, 45 ) );
			echo '</span>';

			return;
		}

		echo '<span class="tzh-col-thumb tzh-col-thumb--empty" aria-hidden="true"><span class="dashicons dashicons-format-image"></span></span>';
	}

	/**
	 * Term name linked to the filtered list, or a dash.
	 *
	 * @param WP_Term|null $term     Term to render.
	 * @param string       $taxonomy Taxonomy name.
	 */
	private function render_term( ?WP_Term $term, string $taxonomy ): void {
		if ( ! $term ) {
			echo $this->dash();

			return;
		}

		printf(
			'<a href="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'post_type' => TZH_Package::POST_TYPE,
						$taxonomy   => $term->slug,
					),
					admin_url( 'edit.php' )
				)
			),
			esc_html( $term->name )
		);
	}

	/**
	 * Muted em dash for empty cells.
	 */
	private function dash(): string {
		return '<span class="tzh-muted" aria-hidden="true">&mdash;</span>';
	}
}

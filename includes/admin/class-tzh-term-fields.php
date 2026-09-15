<?php
/**
 * Extra fields on the destination and category screens.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds an image, a blurb and a sort position to the term screens.
 *
 * Destinations get all three because they appear as picture cards; categories
 * only get the position, since Standard should always precede Premium whatever
 * the alphabet says.
 */
class TZH_Term_Fields {

	private const NONCE_ACTION = 'tzh_save_term';
	private const NONCE_NAME   = 'tzh_term_nonce';

	/**
	 * Register hooks for each taxonomy that has extra fields.
	 */
	public function hooks(): void {
		foreach ( array_keys( $this->schema() ) as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( $this, 'add_form' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edit_form' ), 10, 2 );
			add_action( "created_{$taxonomy}", array( $this, 'save' ) );
			add_action( "edited_{$taxonomy}", array( $this, 'save' ) );
			add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'columns' ) );
			add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'column' ), 10, 3 );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Which fields each taxonomy gets.
	 *
	 * @return array<string, string[]>
	 */
	private function schema(): array {
		return array(
			TZH_Package::TAX_DESTINATION => array( 'image', 'blurb', 'order' ),
			TZH_Package::TAX_TIER        => array( 'order' ),
		);
	}

	/**
	 * Fields on the "Add New" form, which has no term yet.
	 *
	 * @param string $taxonomy Taxonomy name.
	 */
	public function add_form( string $taxonomy ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		foreach ( $this->schema()[ $taxonomy ] ?? array() as $field ) {
			echo '<div class="form-field tzh-term-field">';
			$this->render( $field, 0 );
			echo '</div>';
		}
	}

	/**
	 * Fields on the edit form, which uses a table layout.
	 *
	 * @param WP_Term $term     Term being edited.
	 * @param string  $taxonomy Taxonomy name.
	 */
	public function edit_form( WP_Term $term, string $taxonomy ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		foreach ( $this->schema()[ $taxonomy ] ?? array() as $field ) {
			echo '<tr class="form-field tzh-term-field"><th scope="row">';
			$this->label( $field );
			echo '</th><td>';
			$this->render( $field, (int) $term->term_id, false );
			echo '</td></tr>';
		}
	}

	/**
	 * Save the submitted values.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save( int $term_id ): void {
		$nonce = isset( $_POST[ self::NONCE_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$term = get_term( $term_id );

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$taxonomy = get_taxonomy( $term->taxonomy );

		if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			return;
		}

		foreach ( $this->schema()[ $term->taxonomy ] ?? array() as $field ) {
			$name = 'tzh_term_' . $field;

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
			$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';

			switch ( $field ) {
				case 'image':
					update_term_meta( $term_id, TZH_Term_Meta::IMAGE, (int) $raw );
					break;

				case 'blurb':
					update_term_meta( $term_id, TZH_Term_Meta::BLURB, sanitize_text_field( (string) $raw ) );
					break;

				case 'order':
					update_term_meta( $term_id, TZH_Term_Meta::ORDER, max( 0, (int) $raw ) );
					break;
			}
		}
	}

	/**
	 * Add the image and blurb columns to the destination list.
	 *
	 * @param array<string, string> $columns Existing columns.
	 *
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$screen = get_current_screen();

		if ( $screen && TZH_Package::TAX_DESTINATION === $screen->taxonomy ) {
			$columns = array_merge(
				array( 'cb' => $columns['cb'] ?? '' ),
				array( 'tzh_image' => '<span class="screen-reader-text">' . esc_html__( 'Image', 'travelz-holidays' ) . '</span>' ),
				$columns
			);

			unset( $columns['description'] );

			$columns['tzh_blurb'] = __( 'Blurb', 'travelz-holidays' );
		}

		$columns['tzh_order'] = __( 'Order', 'travelz-holidays' );

		return $columns;
	}

	/**
	 * Render a custom term column.
	 *
	 * @param string $content Existing content.
	 * @param string $column  Column key.
	 * @param int    $term_id Term ID.
	 */
	public function column( string $content, string $column, int $term_id ): string {
		switch ( $column ) {
			case 'tzh_image':
				$image_id = TZH_Term_Meta::image_id( $term_id );

				if ( $image_id ) {
					return '<span class="tzh-col-thumb">' . wp_get_attachment_image( $image_id, array( 60, 45 ) ) . '</span>';
				}

				return '<span class="tzh-col-thumb tzh-col-thumb--empty" aria-hidden="true"><span class="dashicons dashicons-format-image"></span></span>';

			case 'tzh_blurb':
				$blurb = TZH_Term_Meta::blurb( $term_id );

				return '' !== $blurb ? esc_html( $blurb ) : '<span class="tzh-muted">&mdash;</span>';

			case 'tzh_order':
				$order = TZH_Term_Meta::order( $term_id );

				return $order ? (string) $order : '<span class="tzh-muted">&mdash;</span>';
		}

		return $content;
	}

	/**
	 * Load the media picker on the term screens.
	 *
	 * @param string $hook_suffix Current screen hook.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ! array_key_exists( (string) $screen->taxonomy, $this->schema() ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'tzh-media-picker',
			TZH_URL . 'assets/js/media-picker.js',
			array(),
			TZH_Assets::version( 'assets/js/media-picker.js' ),
			true
		);

		wp_localize_script(
			'tzh-media-picker',
			'tzhMedia',
			array(
				'title'  => __( 'Choose image', 'travelz-holidays' ),
				'button' => __( 'Use this image', 'travelz-holidays' ),
			)
		);
	}

	/**
	 * Field label.
	 *
	 * @param string $field Field key.
	 */
	private function label( string $field ): void {
		$labels = array(
			'image' => __( 'Card image', 'travelz-holidays' ),
			'blurb' => __( 'Blurb', 'travelz-holidays' ),
			'order' => __( 'Order', 'travelz-holidays' ),
		);

		printf(
			'<label for="tzh-term-%s">%s</label>',
			esc_attr( $field ),
			esc_html( $labels[ $field ] ?? $field )
		);
	}

	/**
	 * Render one field.
	 *
	 * @param string $field     Field key.
	 * @param int    $term_id   Term ID, or 0 on the add form.
	 * @param bool   $with_label Whether to print the label first.
	 */
	private function render( string $field, int $term_id, bool $with_label = true ): void {
		if ( $with_label ) {
			$this->label( $field );
		}

		$id   = 'tzh-term-' . $field;
		$name = 'tzh_term_' . $field;

		switch ( $field ) {
			case 'image':
				$image_id = $term_id ? TZH_Term_Meta::image_id( $term_id ) : 0;
				$thumb    = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

				printf(
					'<div class="tzh-media" data-tzh-media><input type="hidden" id="%s" name="%s" value="%d" data-tzh-media-input />',
					esc_attr( $id ),
					esc_attr( $name ),
					$image_id
				);

				printf(
					'<div class="tzh-media__preview" data-tzh-media-preview>%s</div>',
					$thumb ? '<img src="' . esc_url( $thumb ) . '" alt="" />' : ''
				);

				printf(
					'<p class="tzh-media__actions"><button type="button" class="button" data-tzh-media-pick>%s</button> <button type="button" class="button-link tzh-media__remove" data-tzh-media-clear%s>%s</button></p>',
					esc_html__( 'Choose image', 'travelz-holidays' ),
					$image_id ? '' : ' hidden',
					esc_html__( 'Remove', 'travelz-holidays' )
				);

				echo '</div>';

				printf(
					'<p class="description">%s</p>',
					esc_html__( 'Shown on the destination card. A tall photo works best.', 'travelz-holidays' )
				);
				break;

			case 'blurb':
				printf(
					'<input type="text" id="%s" name="%s" value="%s" placeholder="%s" class="tzh-input" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $term_id ? TZH_Term_Meta::blurb( $term_id ) : '' ),
					esc_attr__( 'Turquoise lagoons and overwater villas.', 'travelz-holidays' )
				);

				printf(
					'<p class="description">%s</p>',
					esc_html__( 'One line under the destination name.', 'travelz-holidays' )
				);
				break;

			case 'order':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" min="0" class="small-text" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) ( $term_id ? TZH_Term_Meta::order( $term_id ) : 0 ) )
				);

				printf(
					'<p class="description">%s</p>',
					esc_html__( 'Lower numbers come first. Leave at 0 to sort alphabetically.', 'travelz-holidays' )
				);
				break;
		}
	}
}

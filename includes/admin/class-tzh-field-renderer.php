<?php
/**
 * Field rendering.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns a field definition plus a value into markup.
 *
 * Every input is named tzh[<key>] so the whole editor posts back as one array.
 * Repeater rows reuse the same code path with an explicit name and value handed
 * in, which is what lets a row template and a saved row render identically.
 */
class TZH_Field_Renderer {

	/**
	 * Package being edited.
	 */
	private TZH_Package $package;

	/**
	 * @param TZH_Package $package Package being edited.
	 */
	public function __construct( TZH_Package $package ) {
		$this->package = $package;
	}

	/**
	 * Render one field, wrapper included.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param array<string, mixed> $ctx   Optional overrides: value, name, id, leaf.
	 */
	public function render( array $field, array $ctx = array() ): void {
		$type  = (string) ( $field['type'] ?? 'text' );
		$key   = (string) $field['key'];
		$value = array_key_exists( 'value', $ctx ) ? $ctx['value'] : $this->stored_value( $field );
		$name  = $ctx['name'] ?? $this->name( $key );
		$id    = $ctx['id'] ?? 'tzh-field-' . sanitize_html_class( $key );
		$leaf  = $ctx['leaf'] ?? null;
		$class = (string) ( $field['class'] ?? '' );

		printf(
			'<div class="tzh-field tzh-field--type-%s %s">',
			esc_attr( $type ),
			esc_attr( $class )
		);

		if ( 'checkbox' !== $type ) {
			$required = ! empty( $field['required'] ) ? ' <span class="tzh-req" aria-hidden="true">*</span>' : '';

			// Repeater rows are cloned, so their inputs get no id — a label
			// pointing at a duplicated id would focus the wrong field.
			if ( '' === $id ) {
				printf(
					'<span class="tzh-field__label">%s%s</span>',
					esc_html( (string) ( $field['label'] ?? '' ) ),
					$required
				);
			} else {
				printf(
					'<label class="tzh-field__label" for="%s">%s%s</label>',
					esc_attr( $id ),
					esc_html( (string) ( $field['label'] ?? '' ) ),
					$required
				);
			}
		}

		$method = 'render_' . $type;

		if ( method_exists( $this, $method ) ) {
			$this->$method( $field, $name, $value, $id, $leaf );
		}

		if ( ! empty( $field['description'] ) ) {
			printf(
				'<p class="tzh-field__help">%s</p>',
				esc_html( (string) $field['description'] )
			);
		}

		echo '</div>';
	}

	/**
	 * Saved value for a top-level field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 *
	 * @return mixed
	 */
	private function stored_value( array $field ) {
		$store = (string) ( $field['store'] ?? 'meta' );
		$key   = (string) $field['key'];

		if ( 'excerpt' === $store ) {
			return $this->package->post()->post_excerpt;
		}

		if ( 'term' === $store ) {
			$terms = get_the_terms( $this->package->post(), (string) $field['taxonomy'] );

			return ( is_array( $terms ) && $terms ) ? (int) $terms[0]->term_id : 0;
		}

		$saved = get_post_meta( $this->package->id(), $key, true );

		if ( '' === $saved || null === $saved ) {
			return $field['default'] ?? '';
		}

		return $saved;
	}

	/**
	 * Top-level name attribute.
	 *
	 * @param string $key Field key.
	 */
	private function name( string $key ): string {
		return 'tzh[' . $key . ']';
	}

	/**
	 * Shared attributes for inputs that live inside repeater rows.
	 *
	 * @param string|null $leaf Relative key within a row, when applicable.
	 */
	private function leaf_attr( ?string $leaf ): string {
		return null === $leaf ? '' : ' data-tzh-leaf="' . esc_attr( $leaf ) . '"';
	}

	/**
	 * Optional id attribute.
	 *
	 * Repeater sub-fields pass an empty id because their markup is cloned.
	 *
	 * @param string $id Element id, possibly empty.
	 */
	private function attr_id( string $id ): string {
		return '' === $id ? '' : ' id="' . esc_attr( $id ) . '"';
	}

	/**
	 * Single-line text input.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Current value.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_text( array $field, string $name, $value, string $id, ?string $leaf ): void {
		printf(
			'<input type="text"%s name="%s" value="%s" placeholder="%s" class="tzh-input"%s%s />',
			$this->attr_id( $id ),
			esc_attr( $name ),
			esc_attr( (string) $value ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$this->leaf_attr( $leaf ),
			! empty( $field['summary'] ) ? ' data-tzh-summary-source' : ''
		);
	}

	/**
	 * Number input, optionally wrapped in a prefix and suffix.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Current value.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_number( array $field, string $name, $value, string $id, ?string $leaf ): void {
		echo '<span class="tzh-number">';

		if ( ! empty( $field['prefix'] ) ) {
			printf( '<span class="tzh-number__affix">%s</span>', esc_html( (string) $field['prefix'] ) );
		}

		printf(
			'<input type="number"%s name="%s" value="%s" class="tzh-input tzh-input--number"%s%s%s%s />',
			$this->attr_id( $id ),
			esc_attr( $name ),
			esc_attr( (string) $value ),
			isset( $field['min'] ) ? ' min="' . esc_attr( (string) $field['min'] ) . '"' : '',
			isset( $field['max'] ) ? ' max="' . esc_attr( (string) $field['max'] ) . '"' : '',
			isset( $field['step'] ) ? ' step="' . esc_attr( (string) $field['step'] ) . '"' : '',
			$this->leaf_attr( $leaf )
		);

		if ( ! empty( $field['suffix'] ) ) {
			printf( '<span class="tzh-number__affix">%s</span>', esc_html( (string) $field['suffix'] ) );
		}

		echo '</span>';
	}

	/**
	 * Multi-line text.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Current value.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_textarea( array $field, string $name, $value, string $id, ?string $leaf ): void {
		printf(
			'<textarea%s name="%s" rows="%d" placeholder="%s" class="tzh-input tzh-textarea"%s>%s</textarea>',
			$this->attr_id( $id ),
			esc_attr( $name ),
			(int) ( $field['rows'] ?? 3 ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$this->leaf_attr( $leaf ),
			esc_textarea( (string) $value )
		);
	}

	/**
	 * Dropdown from a fixed option list.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Current value.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_select( array $field, string $name, $value, string $id, ?string $leaf ): void {
		printf(
			'<select%s name="%s" class="tzh-input"%s>',
			$this->attr_id( $id ),
			esc_attr( $name ),
			$this->leaf_attr( $leaf )
		);

		foreach ( (array) ( $field['options'] ?? array() ) as $option_value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( (string) $option_value ),
				selected( (string) $value, (string) $option_value, false ),
				esc_html( (string) $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Single checkbox with its own label.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Current value.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_checkbox( array $field, string $name, $value, string $id, ?string $leaf ): void {
		printf(
			'<label class="tzh-check"><input type="checkbox"%s name="%s" value="1"%s%s /> <span>%s</span></label>',
			$this->attr_id( $id ),
			esc_attr( $name ),
			checked( (string) $value, '1', false ),
			$this->leaf_attr( $leaf ),
			esc_html( (string) ( $field['label'] ?? '' ) )
		);
	}

	/**
	 * Media library picker storing an attachment ID.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Current value.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_media( array $field, string $name, $value, string $id, ?string $leaf ): void {
		$attachment_id = (int) $value;
		$thumb         = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';

		printf(
			'<div class="tzh-media" data-tzh-media><input type="hidden"%s name="%s" value="%d" data-tzh-media-input%s />',
			$this->attr_id( $id ),
			esc_attr( $name ),
			$attachment_id,
			$this->leaf_attr( $leaf )
		);

		printf(
			'<div class="tzh-media__preview" data-tzh-media-preview>%s</div>',
			$thumb ? '<img src="' . esc_url( $thumb ) . '" alt="" />' : ''
		);

		printf(
			'<p class="tzh-media__actions"><button type="button" class="button" data-tzh-media-pick>%s</button> <button type="button" class="button-link tzh-media__remove" data-tzh-media-clear%s>%s</button></p>',
			esc_html__( 'Choose image', 'travelz-holidays' ),
			$attachment_id ? '' : ' hidden',
			esc_html__( 'Remove', 'travelz-holidays' )
		);

		echo '</div>';
	}

	/**
	 * Taxonomy dropdown, optionally with a box to name a new term.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Current value.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_term( array $field, string $name, $value, string $id, ?string $leaf ): void {
		$taxonomy = (string) $field['taxonomy'];
		$current  = (int) $value;
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		printf(
			'<select%s name="%s" class="tzh-input">',
			$this->attr_id( $id ),
			esc_attr( $name )
		);

		printf( '<option value="0">%s</option>', esc_html__( '— Select —', 'travelz-holidays' ) );

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				printf(
					'<option value="%d"%s>%s</option>',
					(int) $term->term_id,
					selected( $current, (int) $term->term_id, false ),
					esc_html( $term->name )
				);
			}
		}

		echo '</select>';

		if ( empty( $field['allow_new'] ) ) {
			return;
		}

		printf(
			'<input type="text" name="%s" value="" placeholder="%s" class="tzh-input tzh-input--new-term" />',
			esc_attr( 'tzh_new[' . $taxonomy . ']' ),
			esc_attr__( '…or type a new one', 'travelz-holidays' )
		);
	}

	/**
	 * Read-only price preview, filled in by JavaScript as prices change.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Current value.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_preview( array $field, string $name, $value, string $id, ?string $leaf ): void {
		$rows = array(
			'adult'  => __( 'Adult (12+)', 'travelz-holidays' ),
			'child'  => __( 'Child (2–11)', 'travelz-holidays' ),
			'infant' => __( 'Infant (0–1)', 'travelz-holidays' ),
		);

		echo '<div class="tzh-preview" id="' . esc_attr( $id ) . '">';

		foreach ( $rows as $slug => $label ) {
			printf(
				'<div class="tzh-preview__row"><span>%s</span><b data-tzh-preview="%s">—</b></div>',
				esc_html( $label ),
				esc_attr( $slug )
			);
		}

		echo '</div>';
	}


	/**
	 * One item per line.
	 *
	 * Inclusions, terms and highlights arrive as pasted lists far more often
	 * than they are typed one box at a time, so a plain textarea beats a
	 * repeater here: paste, done.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Input name.
	 * @param mixed                $value Saved items.
	 * @param string               $id    Input id.
	 * @param string|null          $leaf  Relative key inside a repeater row.
	 */
	private function render_lines( array $field, string $name, $value, string $id, ?string $leaf ): void {
		$items = array_map( 'strval', (array) $value );

		printf(
			'<textarea%s name="%s" rows="%d" placeholder="%s" class="tzh-input tzh-textarea tzh-lines"%s>%s</textarea>',
			$this->attr_id( $id ),
			esc_attr( $name ),
			(int) ( $field['rows'] ?? 6 ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$this->leaf_attr( $leaf ),
			esc_textarea( implode( "\n", $items ) )
		);

		printf(
			'<p class="tzh-field__hint">%s</p>',
			esc_html__( 'One per line.', 'travelz-holidays' )
		);
	}

	/**
	 * Repeating group of sub-fields, nestable.
	 *
	 * Row inputs carry only their relative key; JavaScript rewrites the full
	 * name attributes after every add, remove or move, which keeps indexes
	 * contiguous no matter how the list is edited.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $name  Base name for the whole repeater.
	 * @param mixed                $value Saved rows.
	 * @param string               $id    Element id.
	 * @param string|null          $leaf  Relative key inside a parent row.
	 */
	private function render_repeater( array $field, string $name, $value, string $id, ?string $leaf ): void {
		$rows      = is_array( $value ) ? $value : array();
		$sub       = (array) ( $field['fields'] ?? array() );
		$row_label = (string) ( $field['row_label'] ?? __( 'Item', 'travelz-holidays' ) );

		printf(
			'<div class="tzh-rep" id="%s" data-tzh-repeater data-tzh-key="%s" data-tzh-label="%s"%s%s>',
			esc_attr( $id ),
			esc_attr( null === $leaf ? (string) $field['key'] : $leaf ),
			esc_attr( $row_label ),
			null === $leaf ? ' data-tzh-base="' . esc_attr( $name ) . '"' : '',
			$this->leaf_attr( $leaf )
		);

		echo '<div class="tzh-rep__rows" data-tzh-rows>';

		foreach ( array_values( $rows ) as $index => $row ) {
			$this->render_repeater_row( $sub, (array) $row, $name . '[' . $index . ']', $index, $row_label );
		}

		echo '</div>';

		// The template's names are placeholders; JavaScript rewrites them on add.
		echo '<template data-tzh-row-template>';
		$this->render_repeater_row( $sub, array(), $name . '[0]', 0, $row_label );
		echo '</template>';

		printf(
			'<p class="tzh-rep__add"><button type="button" class="button" data-tzh-add><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> %s</button></p>',
			esc_html( (string) ( $field['add_label'] ?? __( 'Add row', 'travelz-holidays' ) ) )
		);

		echo '</div>';
	}

	/**
	 * One repeater row: header bar plus its sub-fields.
	 *
	 * @param array<int, array<string, mixed>> $sub       Sub-field definitions.
	 * @param array<string, mixed>             $row       Saved values for this row.
	 * @param string                           $base      Name prefix for this row.
	 * @param int                              $index     Zero-based row index.
	 * @param string                           $row_label Singular label for a row.
	 */
	private function render_repeater_row( array $sub, array $row, string $base, int $index, string $row_label ): void {
		echo '<div class="tzh-rep__row" data-tzh-row>';

		printf(
			'<div class="tzh-rep__head">
				<button type="button" class="tzh-rep__toggle" data-tzh-toggle aria-expanded="true"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span><span class="screen-reader-text">%s</span></button>
				<span class="tzh-rep__num" data-tzh-num>%s %d</span>
				<span class="tzh-rep__summary" data-tzh-summary></span>
				<span class="tzh-rep__actions">
					<button type="button" class="button-link tzh-rep__move" data-tzh-up title="%s"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span><span class="screen-reader-text">%s</span></button>
					<button type="button" class="button-link tzh-rep__move" data-tzh-down title="%s"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span><span class="screen-reader-text">%s</span></button>
					<button type="button" class="button-link tzh-rep__remove" data-tzh-remove title="%s"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span class="screen-reader-text">%s</span></button>
				</span>
			</div>',
			esc_html__( 'Collapse', 'travelz-holidays' ),
			esc_html( $row_label ),
			$index + 1,
			esc_attr__( 'Move up', 'travelz-holidays' ),
			esc_html__( 'Move up', 'travelz-holidays' ),
			esc_attr__( 'Move down', 'travelz-holidays' ),
			esc_html__( 'Move down', 'travelz-holidays' ),
			esc_attr__( 'Remove', 'travelz-holidays' ),
			esc_html__( 'Remove', 'travelz-holidays' )
		);

		echo '<div class="tzh-rep__body"><div class="tzh-fields">';

		foreach ( $sub as $sub_field ) {
			$leaf    = (string) $sub_field['key'];
			$default = $sub_field['default'] ?? ( 'repeater' === ( $sub_field['type'] ?? '' ) ? array() : '' );

			$this->render(
				$sub_field,
				array(
					'value' => $row[ $leaf ] ?? $default,
					'name'  => $base . '[' . $leaf . ']',
					'id'    => '',
					'leaf'  => $leaf,
				)
			);
		}

		echo '</div></div></div>';
	}
}

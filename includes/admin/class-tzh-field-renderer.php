<?php
/**
 * Field rendering.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns a field definition plus a saved value into markup.
 *
 * Every input is named tzh[<key>] so the whole editor posts back as one array
 * and saving stays a single loop over the schema.
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
	 */
	public function render( array $field ): void {
		$type  = (string) ( $field['type'] ?? 'text' );
		$key   = (string) $field['key'];
		$id    = 'tzh-field-' . sanitize_html_class( $key );
		$class = (string) ( $field['class'] ?? '' );

		printf(
			'<div class="tzh-field tzh-field--type-%s %s">',
			esc_attr( $type ),
			esc_attr( $class )
		);

		if ( 'checkbox' !== $type ) {
			printf(
				'<label class="tzh-field__label" for="%s">%s%s</label>',
				esc_attr( $id ),
				esc_html( (string) ( $field['label'] ?? '' ) ),
				! empty( $field['required'] ) ? ' <span class="tzh-req" aria-hidden="true">*</span>' : ''
			);
		}

		$method = 'render_' . $type;

		if ( method_exists( $this, $method ) ) {
			$this->$method( $field, $id );
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
	 * Current value for a field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 *
	 * @return mixed
	 */
	private function value( array $field ) {
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
	 * Shared name attribute.
	 *
	 * @param string $key Field key.
	 */
	private function name( string $key ): string {
		return 'tzh[' . $key . ']';
	}

	/**
	 * Single-line text input.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $id    Input id.
	 */
	private function render_text( array $field, string $id ): void {
		printf(
			'<input type="text" id="%s" name="%s" value="%s" placeholder="%s" class="tzh-input" />',
			esc_attr( $id ),
			esc_attr( $this->name( (string) $field['key'] ) ),
			esc_attr( (string) $this->value( $field ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) )
		);
	}

	/**
	 * Number input, optionally wrapped in a prefix and suffix.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $id    Input id.
	 */
	private function render_number( array $field, string $id ): void {
		echo '<span class="tzh-number">';

		if ( ! empty( $field['prefix'] ) ) {
			printf( '<span class="tzh-number__affix">%s</span>', esc_html( (string) $field['prefix'] ) );
		}

		printf(
			'<input type="number" id="%s" name="%s" value="%s" class="tzh-input tzh-input--number"%s%s%s />',
			esc_attr( $id ),
			esc_attr( $this->name( (string) $field['key'] ) ),
			esc_attr( (string) $this->value( $field ) ),
			isset( $field['min'] ) ? ' min="' . esc_attr( (string) $field['min'] ) . '"' : '',
			isset( $field['max'] ) ? ' max="' . esc_attr( (string) $field['max'] ) . '"' : '',
			isset( $field['step'] ) ? ' step="' . esc_attr( (string) $field['step'] ) . '"' : ''
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
	 * @param string               $id    Input id.
	 */
	private function render_textarea( array $field, string $id ): void {
		printf(
			'<textarea id="%s" name="%s" rows="%d" placeholder="%s" class="tzh-input tzh-textarea">%s</textarea>',
			esc_attr( $id ),
			esc_attr( $this->name( (string) $field['key'] ) ),
			(int) ( $field['rows'] ?? 3 ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			esc_textarea( (string) $this->value( $field ) )
		);
	}

	/**
	 * Dropdown from a fixed option list.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $id    Input id.
	 */
	private function render_select( array $field, string $id ): void {
		$current = (string) $this->value( $field );

		printf(
			'<select id="%s" name="%s" class="tzh-input">',
			esc_attr( $id ),
			esc_attr( $this->name( (string) $field['key'] ) )
		);

		foreach ( (array) ( $field['options'] ?? array() ) as $option_value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( (string) $option_value ),
				selected( $current, (string) $option_value, false ),
				esc_html( (string) $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Single checkbox with its own label.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $id    Input id.
	 */
	private function render_checkbox( array $field, string $id ): void {
		printf(
			'<label class="tzh-check" for="%s"><input type="checkbox" id="%s" name="%s" value="1"%s /> <span>%s</span></label>',
			esc_attr( $id ),
			esc_attr( $id ),
			esc_attr( $this->name( (string) $field['key'] ) ),
			checked( (string) $this->value( $field ), '1', false ),
			esc_html( (string) ( $field['label'] ?? '' ) )
		);
	}

	/**
	 * Media library picker storing an attachment ID.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $id    Input id.
	 */
	private function render_media( array $field, string $id ): void {
		$attachment_id = (int) $this->value( $field );
		$thumb         = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';

		printf(
			'<div class="tzh-media" data-tzh-media><input type="hidden" id="%s" name="%s" value="%d" data-tzh-media-input />',
			esc_attr( $id ),
			esc_attr( $this->name( (string) $field['key'] ) ),
			$attachment_id
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
	 * @param string               $id    Input id.
	 */
	private function render_term( array $field, string $id ): void {
		$taxonomy = (string) $field['taxonomy'];
		$current  = (int) $this->value( $field );
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		printf(
			'<select id="%s" name="%s" class="tzh-input">',
			esc_attr( $id ),
			esc_attr( $this->name( (string) $field['key'] ) )
		);

		printf(
			'<option value="0">%s</option>',
			esc_html__( '— Select —', 'travelz-holidays' )
		);

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
	 * @param string               $id    Input id.
	 */
	private function render_preview( array $field, string $id ): void {
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
}

<?php
/**
 * Package editor meta box.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * One tabbed panel holding every package field, and the save routine behind it.
 *
 * WordPress's own taxonomy boxes are removed: a package belongs to exactly one
 * destination, one category and one tour group, which checkbox lists invite
 * people to get wrong.
 */
class TZH_Meta_Box {

	private const NONCE_ACTION = 'tzh_save_package';
	private const NONCE_NAME   = 'tzh_package_nonce';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'register' ), 10, 2 );
		add_action( 'save_post_' . TZH_Package::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Add our box and take away the ones it replaces.
	 *
	 * @param string  $post_type Current post type.
	 * @param WP_Post $post      Post being edited.
	 */
	public function register( string $post_type, WP_Post $post ): void {
		if ( TZH_Package::POST_TYPE !== $post_type ) {
			return;
		}

		add_meta_box(
			'tzh-package-details',
			__( 'Package details', 'travelz-holidays' ),
			array( $this, 'render' ),
			TZH_Package::POST_TYPE,
			'normal',
			'high'
		);

		remove_meta_box( 'tzh_destinationdiv', TZH_Package::POST_TYPE, 'side' );
		remove_meta_box( 'tzh_tierdiv', TZH_Package::POST_TYPE, 'side' );
		remove_meta_box( 'tagsdiv-' . TZH_Package::TAX_FAMILY, TZH_Package::POST_TYPE, 'side' );
		remove_meta_box( 'postexcerpt', TZH_Package::POST_TYPE, 'normal' );
	}

	/**
	 * Render the tabbed panel.
	 *
	 * @param WP_Post $post Package being edited.
	 */
	public function render( WP_Post $post ): void {
		$package = TZH_Package::from( $post );

		if ( ! $package ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$tabs     = TZH_Fields::tabs();
		$renderer = new TZH_Field_Renderer( $package );
		$first    = array_key_first( $tabs );

		echo '<div class="tzh-editor" data-tzh-editor>';

		echo '<ul class="tzh-editor__tabs" role="tablist">';

		foreach ( $tabs as $slug => $tab ) {
			printf(
				'<li role="presentation"><button type="button" class="tzh-editor__tab%s" role="tab" data-tzh-tab="%s" aria-selected="%s" aria-controls="tzh-panel-%s" id="tzh-tab-%s"><span class="dashicons %s" aria-hidden="true"></span> %s</button></li>',
				$slug === $first ? ' is-active' : '',
				esc_attr( $slug ),
				$slug === $first ? 'true' : 'false',
				esc_attr( $slug ),
				esc_attr( $slug ),
				esc_attr( (string) ( $tab['icon'] ?? 'dashicons-admin-generic' ) ),
				esc_html( (string) $tab['label'] )
			);
		}

		echo '</ul>';

		echo '<div class="tzh-editor__panels">';

		foreach ( $tabs as $slug => $tab ) {
			printf(
				'<section class="tzh-editor__panel%s" id="tzh-panel-%s" role="tabpanel" aria-labelledby="tzh-tab-%s" data-tzh-panel="%s"%s>',
				$slug === $first ? ' is-active' : '',
				esc_attr( $slug ),
				esc_attr( $slug ),
				esc_attr( $slug ),
				$slug === $first ? '' : ' hidden'
			);

			if ( ! empty( $tab['description'] ) ) {
				printf( '<p class="tzh-panel-intro">%s</p>', esc_html( (string) $tab['description'] ) );
			}

			echo '<div class="tzh-fields">';

			foreach ( $tab['fields'] as $field ) {
				$renderer->render( $field );
			}

			echo '</div></section>';
		}

		echo '</div></div>';
	}

	/**
	 * Persist the submitted values.
	 *
	 * @param int     $post_id Package ID.
	 * @param WP_Post $post    Package post.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each value is sanitized per field type below.
		$submitted = isset( $_POST['tzh'] ) ? wp_unslash( (array) $_POST['tzh'] ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in save_terms().
		$new_terms = isset( $_POST['tzh_new'] ) ? wp_unslash( (array) $_POST['tzh_new'] ) : array();

		$excerpt = null;

		foreach ( TZH_Fields::all_fields() as $field ) {
			$key   = (string) $field['key'];
			$store = (string) ( $field['store'] ?? 'meta' );
			$type  = (string) ( $field['type'] ?? 'text' );

			if ( 'preview' === $type ) {
				continue;
			}

			// A checkbox that was unticked and a repeater whose last row was
			// removed both post nothing at all — they need an explicit empty
			// value or the previous contents would survive the save.
			$missing = in_array( $type, array( 'checkbox', 'lines', 'textarea' ), true )
				? ( 'lines' === $type ? array() : '' )
				: ( 'repeater' === $type ? array() : null );
			$raw     = $submitted[ $key ] ?? $missing;

			if ( null === $raw ) {
				continue;
			}

			if ( 'excerpt' === $store ) {
				$excerpt = sanitize_textarea_field( (string) $raw );
				continue;
			}

			if ( 'term' === $store ) {
				$this->save_term( $post_id, $field, (int) $raw, $new_terms );
				continue;
			}

			update_post_meta( $post_id, $key, $this->sanitize( $raw, $field ) );
		}

		$this->sync_serial( $post_id );

		if ( null !== $excerpt && $excerpt !== $post->post_excerpt ) {
			remove_action( 'save_post_' . TZH_Package::POST_TYPE, array( $this, 'save' ), 10 );

			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_excerpt' => $excerpt,
				)
			);

			add_action( 'save_post_' . TZH_Package::POST_TYPE, array( $this, 'save' ), 10, 2 );
		}
	}

	/**
	 * Assign the chosen term, creating one first when a new name was typed.
	 *
	 * @param int                  $post_id   Package ID.
	 * @param array<string, mixed> $field     Field definition.
	 * @param int                  $term_id   Selected term ID, or 0.
	 * @param array<string, mixed> $new_terms Raw new-term names keyed by taxonomy.
	 */
	private function save_term( int $post_id, array $field, int $term_id, array $new_terms ): void {
		$taxonomy = (string) $field['taxonomy'];
		$name     = isset( $new_terms[ $taxonomy ] ) ? sanitize_text_field( (string) $new_terms[ $taxonomy ] ) : '';

		// A typed name wins over the dropdown: someone who typed it meant it.
		if ( '' !== $name ) {
			$existing = get_term_by( 'name', $name, $taxonomy );

			if ( $existing instanceof WP_Term ) {
				$term_id = (int) $existing->term_id;
			} else {
				$created = wp_insert_term( $name, $taxonomy );

				if ( ! is_wp_error( $created ) ) {
					$term_id = (int) $created['term_id'];
				}
			}
		}

		if ( $term_id > 0 ) {
			wp_set_object_terms( $post_id, array( $term_id ), $taxonomy, false );

			return;
		}

		wp_set_object_terms( $post_id, array(), $taxonomy, false );
	}

	/**
	 * Keep the numeric sort key in step with the package code.
	 *
	 * @param int $post_id Package ID.
	 */
	private function sync_serial( int $post_id ): void {
		$code = (string) get_post_meta( $post_id, TZH_Package::META_CODE, true );

		update_post_meta( $post_id, TZH_Package::META_SERIAL, TZH_Package::serial_from_code( $code ) );
	}

	/**
	 * Sanitize one submitted value according to its field type.
	 *
	 * @param mixed                $raw   Submitted value.
	 * @param array<string, mixed> $field Field definition.
	 *
	 * @return mixed
	 */
	private function sanitize( $raw, array $field ) {
		switch ( (string) ( $field['type'] ?? 'text' ) ) {
			case 'number':
				$number = is_numeric( $raw ) ? (float) $raw : (float) ( $field['default'] ?? 0 );

				if ( isset( $field['min'] ) ) {
					$number = max( (float) $field['min'], $number );
				}

				if ( isset( $field['max'] ) ) {
					$number = min( (float) $field['max'], $number );
				}

				// Whole numbers stay whole so meta_value_num sorts cleanly.
				return isset( $field['step'] ) ? $number : (int) round( $number );

			case 'checkbox':
				return '1' === (string) $raw ? '1' : '';

			case 'media':
				return (int) $raw;

			case 'select':
				$options = (array) ( $field['options'] ?? array() );

				return array_key_exists( (string) $raw, $options )
					? (string) $raw
					: (string) ( $field['default'] ?? '' );

			case 'textarea':
				return sanitize_textarea_field( (string) $raw );

			case 'lines':
				return $this->sanitize_lines( $raw );

			case 'repeater':
				return $this->sanitize_rows(
					is_array( $raw ) ? $raw : array(),
					(array) ( $field['fields'] ?? array() )
				);

			default:
				return sanitize_text_field( (string) $raw );
		}
	}

	/**
	 * Split a textarea into a clean list, one item per line.
	 *
	 * @param mixed $raw Submitted value.
	 *
	 * @return string[]
	 */
	private function sanitize_lines( $raw ): array {
		if ( is_array( $raw ) ) {
			$lines = $raw;
		} else {
			$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		}

		$clean = array();

		foreach ( (array) $lines as $line ) {
			$line = sanitize_text_field( trim( (string) $line ) );

			if ( '' !== $line ) {
				$clean[] = $line;
			}
		}

		return $clean;
	}

	/**
	 * Clean a repeater's rows, dropping the ones nobody filled in.
	 *
	 * Row keys are discarded and rebuilt from zero so a gap left by a removed
	 * row never reaches the database, whatever the browser posted.
	 *
	 * @param array<int|string, mixed>         $rows       Submitted rows.
	 * @param array<int, array<string, mixed>> $sub_fields Sub-field definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function sanitize_rows( array $rows, array $sub_fields ): array {
		$clean = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$entry = array();
			$empty = true;

			foreach ( $sub_fields as $sub_field ) {
				$leaf = (string) $sub_field['key'];
				$type = (string) ( $sub_field['type'] ?? 'text' );
				$raw  = $row[ $leaf ] ?? ( 'repeater' === $type ? array() : '' );

				$value = $this->sanitize( $raw, $sub_field );

				$entry[ $leaf ] = $value;

				if ( is_array( $value ) ? array() !== $value : '' !== (string) $value ) {
					$empty = false;
				}
			}

			if ( ! $empty ) {
				$clean[] = $entry;
			}
		}

		return $clean;
	}

	/**
	 * Load the editor script and the media library on the package screen.
	 *
	 * @param string $hook_suffix Current screen hook.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || TZH_Package::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'tzh-package-editor',
			TZH_URL . 'assets/js/package-editor.js',
			array(),
			TZH_VERSION,
			true
		);

		wp_localize_script(
			'tzh-package-editor',
			'tzhEditor',
			array(
				'currency'   => (string) apply_filters( 'tzh_currency_symbol', '৳' ),
				'mediaTitle' => __( 'Choose hero banner', 'travelz-holidays' ),
				'mediaButton' => __( 'Use this image', 'travelz-holidays' ),
				'priceKeys'  => array(
					'adult'  => TZH_Package::META_PRICE,
					'rate'   => TZH_Package::META_CHILD_RATE,
					'infant' => TZH_Package::META_INFANT_PRICE,
				),
			)
		);
	}
}

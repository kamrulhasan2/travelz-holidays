<?php
/**
 * Settings screen.
 *
 * @package TravelZ_Holidays
 *
 * @var array<string, mixed> $values
 */

defined( 'ABSPATH' ) || exit;

$option = TZH_Settings::OPTION;

/**
 * Field name for a setting.
 *
 * @param string $key Setting key.
 */
$tzh_name = static function ( string $key ) use ( $option ): string {
	return $option . '[' . $key . ']';
};

/**
 * Saved list as newline-separated text.
 *
 * @param mixed $value Saved value.
 */
$tzh_lines = static function ( $value ): string {
	return implode( "\n", array_map( 'strval', (array) $value ) );
};
?>
<div class="wrap tzh-wrap">
	<h1><?php esc_html_e( 'Settings', 'travelz-holidays' ); ?></h1>

	<?php
	// Custom menu pages do not print the "Settings saved." notice on their own.
	settings_errors( 'tzh_settings_group' );
	?>

	<p class="tzh-lede">
		<?php esc_html_e( 'Defaults every package falls back to. A package that fills in its own values ignores what is set here.', 'travelz-holidays' ); ?>
	</p>

	<form method="post" action="options.php">
		<?php settings_fields( 'tzh_settings_group' ); ?>

		<div class="tzh-panel">
			<h2><?php esc_html_e( 'Contact & currency', 'travelz-holidays' ); ?></h2>

			<table class="form-table" role="presentation">
				<tbody>
				<tr>
					<th scope="row">
						<label for="tzh-whatsapp"><?php esc_html_e( 'WhatsApp number', 'travelz-holidays' ); ?></label>
					</th>
					<td>
						<input type="text" id="tzh-whatsapp" class="regular-text"
							name="<?php echo esc_attr( $tzh_name( 'whatsapp' ) ); ?>"
							value="<?php echo esc_attr( (string) $values['whatsapp'] ); ?>"
							placeholder="8801700000000" />
						<p class="description">
							<?php esc_html_e( 'Country code first, no plus sign or spaces. Used by the enquiry button on every package.', 'travelz-holidays' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="tzh-currency"><?php esc_html_e( 'Currency symbol', 'travelz-holidays' ); ?></label>
					</th>
					<td>
						<input type="text" id="tzh-currency" class="small-text"
							name="<?php echo esc_attr( $tzh_name( 'currency_symbol' ) ); ?>"
							value="<?php echo esc_attr( (string) $values['currency_symbol'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Checkout', 'travelz-holidays' ); ?></th>
					<td>
						<label for="tzh-woo-checkout">
							<input type="checkbox" id="tzh-woo-checkout" value="1"
								name="<?php echo esc_attr( $tzh_name( 'woo_checkout' ) ); ?>"
								<?php checked( ! empty( $values['woo_checkout'] ) ); ?>
								<?php disabled( ! TZH_Woo::active() ); ?> />
							<?php esc_html_e( 'Send bookings to WooCommerce checkout', 'travelz-holidays' ); ?>
						</label>
						<p class="description">
							<?php
							echo TZH_Woo::active()
								? esc_html__( 'Turn this off to collect booking requests without taking payment — travelers get a reference and a consultant follows up.', 'travelz-holidays' )
								: esc_html__( 'WooCommerce is not active. Bookings are collected as requests with a reference number.', 'travelz-holidays' );
							?>
						</p>
					</td>
				</tr>
				</tbody>
			</table>
		</div>

		<div class="tzh-panel">
			<h2><?php esc_html_e( 'Catalogue page', 'travelz-holidays' ); ?></h2>
			<p class="tzh-note">
				<?php
				printf(
					/* translators: %s: URL of the destination grid */
					esc_html__( 'The heading travellers see at %s.', 'travelz-holidays' ),
					'<a href="' . esc_url( TZH_Rewrites::grid_url() ) . '" target="_blank" rel="noreferrer">' . esc_html( TZH_Rewrites::grid_url() ) . '</a>'
				);
				?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
				<?php
				$tzh_copy = array(
					'archive_eyebrow'  => __( 'Eyebrow badge', 'travelz-holidays' ),
					'archive_title'    => __( 'Heading', 'travelz-holidays' ),
					'archive_subtitle' => __( 'Subheading', 'travelz-holidays' ),
				);

				foreach ( $tzh_copy as $tzh_key => $tzh_label ) :
					$tzh_id = 'tzh-' . str_replace( '_', '-', $tzh_key );
					?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $tzh_id ); ?>"><?php echo esc_html( $tzh_label ); ?></label></th>
						<td>
							<input type="text" id="<?php echo esc_attr( $tzh_id ); ?>" class="regular-text"
								name="<?php echo esc_attr( $tzh_name( $tzh_key ) ); ?>"
								value="<?php echo esc_attr( (string) $values[ $tzh_key ] ); ?>" />
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="tzh-panel">
			<h2><?php esc_html_e( 'Default pricing', 'travelz-holidays' ); ?></h2>

			<table class="form-table" role="presentation">
				<tbody>
				<tr>
					<th scope="row">
						<label for="tzh-child-rate"><?php esc_html_e( 'Child price', 'travelz-holidays' ); ?></label>
					</th>
					<td>
						<input type="number" id="tzh-child-rate" class="small-text" min="0" max="100"
							name="<?php echo esc_attr( $tzh_name( 'child_rate' ) ); ?>"
							value="<?php echo esc_attr( (string) $values['child_rate'] ); ?>" />
						<span class="tzh-number__affix"><?php esc_html_e( '% of adult price', 'travelz-holidays' ); ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="tzh-infant-price"><?php esc_html_e( 'Infant price', 'travelz-holidays' ); ?></label>
					</th>
					<td>
						<span class="tzh-number__affix"><?php echo esc_html( (string) $values['currency_symbol'] ); ?></span>
						<input type="number" id="tzh-infant-price" class="small-text" min="0"
							name="<?php echo esc_attr( $tzh_name( 'infant_price' ) ); ?>"
							value="<?php echo esc_attr( (string) $values['infant_price'] ); ?>" />
					</td>
				</tr>
				</tbody>
			</table>
		</div>

		<div class="tzh-panel">
			<h2><?php esc_html_e( 'Default lists', 'travelz-holidays' ); ?></h2>
			<p class="tzh-note">
				<?php esc_html_e( 'One item per line. These appear on any package that leaves the matching list empty.', 'travelz-holidays' ); ?>
			</p>

			<div class="tzh-settings-grid">
				<?php
				$tzh_lists = array(
					'default_highlights' => __( 'Highlights', 'travelz-holidays' ),
					'default_inclusion'  => __( 'Inclusion', 'travelz-holidays' ),
					'default_exclusion'  => __( 'Exclusion', 'travelz-holidays' ),
					'default_terms'      => __( 'Terms & conditions', 'travelz-holidays' ),
				);

				foreach ( $tzh_lists as $tzh_key => $tzh_label ) :
					$tzh_id = 'tzh-' . str_replace( '_', '-', $tzh_key );
					?>
					<div class="tzh-field">
						<label class="tzh-field__label" for="<?php echo esc_attr( $tzh_id ); ?>">
							<?php echo esc_html( $tzh_label ); ?>
						</label>
						<textarea id="<?php echo esc_attr( $tzh_id ); ?>" rows="7" class="tzh-input tzh-textarea tzh-lines"
							name="<?php echo esc_attr( $tzh_name( $tzh_key ) ); ?>"><?php echo esc_textarea( $tzh_lines( $values[ $tzh_key ] ) ); ?></textarea>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="tzh-panel">
			<h2><?php esc_html_e( 'Visa', 'travelz-holidays' ); ?></h2>

			<div class="tzh-field">
				<label class="tzh-field__label" for="tzh-visa-note">
					<?php esc_html_e( 'Default note above the visa document', 'travelz-holidays' ); ?>
				</label>
				<textarea id="tzh-visa-note" rows="3" class="tzh-input tzh-textarea"
					name="<?php echo esc_attr( $tzh_name( 'visa_note' ) ); ?>"><?php echo esc_textarea( (string) $values['visa_note'] ); ?></textarea>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>

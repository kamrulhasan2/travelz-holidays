<?php
/**
 * Filter panel.
 *
 * A plain GET form: with scripts off it reloads the page, with scripts on it
 * swaps the results in place. Either way the URL always describes what is shown.
 *
 * @package TravelZ_Holidays
 *
 * @var array<string, mixed> $filters Sanitized filters.
 * @var string               $action  Form action URL.
 * @var string               $current Slug of the destination whose page this is, if any.
 */

defined( 'ABSPATH' ) || exit;

$tzh_current      = isset( $current ) ? (string) $current : '';
$tzh_destinations = TZH_Term_Meta::ordered( TZH_Package::TAX_DESTINATION, true );

/*
 * Every other filter narrows the list in place; a country is a different page,
 * because one destination is one URL in this plugin. So the country list
 * carries the filters across rather than pretending to be a checkbox that
 * could hold two countries at once on a page that only has room for one.
 */
$tzh_carry = array_filter(
	array(
		'tier' => $filters['tier'],
		'days' => $filters['days'],
		'type' => 'all' === $filters['type'] ? '' : $filters['type'],
		'min'  => $filters['min'] > $filters['bounds']['min'] ? (string) $filters['min'] : '',
		'max'  => $filters['max'] < $filters['bounds']['max'] ? (string) $filters['max'] : '',
	)
);
$tzh_tiers        = TZH_Term_Meta::ordered( TZH_Package::TAX_TIER, true );
$tzh_buckets      = TZH_Query::duration_buckets();
$tzh_bounds       = $filters['bounds'];
?>
<form class="tz-filters" method="get" action="<?php echo esc_url( $action ); ?>" data-tz-filters>
	<h2 class="tz-heading tz-filters__title"><?php esc_html_e( 'Filters', 'travelz-holidays' ); ?></h2>

	<?php if ( count( $tzh_destinations ) > 1 ) : ?>
		<div class="tz-filters__group">
			<h3 class="tz-filters__legend"><?php esc_html_e( 'Country', 'travelz-holidays' ); ?></h3>
			<div class="tz-filters__scroll">
				<?php foreach ( $tzh_destinations as $tzh_term ) : ?>
					<?php $tzh_is_here = $tzh_term->slug === $tzh_current; ?>
					<a class="tz-opt tz-opt--link<?php echo $tzh_is_here ? ' is-current' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( $tzh_carry, (string) get_term_link( $tzh_term ) ) ); ?>"
						<?php echo $tzh_is_here ? 'aria-current="page"' : ''; ?>>
						<span class="tz-opt__dot" aria-hidden="true"></span>
						<span><?php echo esc_html( $tzh_term->name ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $tzh_tiers ) : ?>
		<fieldset class="tz-filters__group">
			<legend class="tz-filters__legend"><?php esc_html_e( 'Category', 'travelz-holidays' ); ?></legend>
			<?php foreach ( $tzh_tiers as $tzh_term ) : ?>
				<label class="tz-opt">
					<input type="checkbox" name="tier[]" value="<?php echo esc_attr( $tzh_term->slug ); ?>"
						<?php checked( in_array( $tzh_term->slug, $filters['tier'], true ) ); ?> />
					<span><?php echo esc_html( $tzh_term->name ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
	<?php endif; ?>

	<fieldset class="tz-filters__group">
		<legend class="tz-filters__legend"><?php esc_html_e( 'Duration', 'travelz-holidays' ); ?></legend>
		<?php foreach ( $tzh_buckets as $tzh_key => $tzh_bucket ) : ?>
			<label class="tz-opt">
				<input type="checkbox" name="days[]" value="<?php echo esc_attr( $tzh_key ); ?>"
					<?php checked( in_array( (string) $tzh_key, $filters['days'], true ) ); ?> />
				<span><?php echo esc_html( $tzh_bucket['label'] ); ?></span>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<fieldset class="tz-filters__group">
		<legend class="tz-filters__legend">
			<?php
			printf(
				/* translators: %s: currency symbol */
				esc_html__( 'Price Range (%s)', 'travelz-holidays' ),
				esc_html( (string) TZH_Settings::get( 'currency_symbol', '৳' ) )
			);
			?>
		</legend>

		<div class="tz-range" data-tz-range>
			<span class="tz-range__track" aria-hidden="true"><span class="tz-range__fill" data-tz-range-fill></span></span>

			<input type="range" name="min" data-tz-range-min
				min="<?php echo esc_attr( (string) $tzh_bounds['min'] ); ?>"
				max="<?php echo esc_attr( (string) $tzh_bounds['max'] ); ?>"
				step="<?php echo esc_attr( (string) $tzh_bounds['step'] ); ?>"
				value="<?php echo esc_attr( (string) $filters['min'] ); ?>"
				aria-label="<?php esc_attr_e( 'Lowest price', 'travelz-holidays' ); ?>" />

			<input type="range" name="max" data-tz-range-max
				min="<?php echo esc_attr( (string) $tzh_bounds['min'] ); ?>"
				max="<?php echo esc_attr( (string) $tzh_bounds['max'] ); ?>"
				step="<?php echo esc_attr( (string) $tzh_bounds['step'] ); ?>"
				value="<?php echo esc_attr( (string) $filters['max'] ); ?>"
				aria-label="<?php esc_attr_e( 'Highest price', 'travelz-holidays' ); ?>" />
		</div>

		<div class="tz-range__values">
			<span data-tz-range-out="min"><?php echo esc_html( tzh_price( $filters['min'] ) ); ?></span>
			<span data-tz-range-out="max"><?php echo esc_html( tzh_price( $filters['max'] ) ); ?></span>
		</div>
	</fieldset>

	<fieldset class="tz-filters__group">
		<legend class="tz-filters__legend"><?php esc_html_e( 'Tour Type', 'travelz-holidays' ); ?></legend>
		<?php
		$tzh_types = array_merge(
			array( 'all' => __( 'All', 'travelz-holidays' ) ),
			TZH_Package::tour_types()
		);

		foreach ( $tzh_types as $tzh_value => $tzh_label ) :
			?>
			<label class="tz-opt">
				<input type="radio" name="type" value="<?php echo esc_attr( $tzh_value ); ?>"
					<?php checked( $filters['type'], $tzh_value ); ?> />
				<span><?php echo esc_html( $tzh_label ); ?></span>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<div class="tz-filters__actions">
		<button type="submit" class="tz-btn tz-btn--block tz-filters__apply">
			<?php esc_html_e( 'Apply Filters', 'travelz-holidays' ); ?>
		</button>

		<a class="tz-link" href="<?php echo esc_url( $action ); ?>" data-tz-filters-clear>
			<?php esc_html_e( 'Clear Filters', 'travelz-holidays' ); ?>
		</a>
	</div>
</form>

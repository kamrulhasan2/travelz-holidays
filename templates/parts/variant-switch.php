<?php
/**
 * "Check out other packages of this tour" — the comfort-level switcher.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package being shown.
 */

defined( 'ABSPATH' ) || exit;

$tzh_tiers    = TZH_Term_Meta::ordered( TZH_Package::TAX_TIER );
$tzh_variants = $package->variants();

if ( count( $tzh_tiers ) < 2 ) {
	return;
}

$tzh_current = $package->tier();
?>
<div class="tz-card">
	<div class="tz-card__body">
		<h2 class="tz-heading"><?php esc_html_e( 'Check out other packages of this tour', 'travelz-holidays' ); ?></h2>
		<p class="tz-muted tz-variants__note">
			<?php esc_html_e( 'Same itinerary, different comfort level. Unavailable categories are disabled.', 'travelz-holidays' ); ?>
		</p>

		<div class="tz-tiers">
			<?php
			foreach ( $tzh_tiers as $tzh_tier ) :
				$tzh_variant = $tzh_variants[ $tzh_tier->slug ] ?? null;
				$tzh_is_this = $tzh_current && $tzh_current->slug === $tzh_tier->slug;
				?>
				<?php if ( $tzh_variant && ! $tzh_is_this ) : ?>
					<a class="tz-tier" href="<?php echo esc_url( (string) get_permalink( $tzh_variant->id() ) ); ?>">
						<span class="tz-tier__name">
							<?php echo tzh_icon( 'award' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
							<?php echo esc_html( $tzh_tier->name ); ?>
						</span>
						<span class="tz-tier__price"><?php echo esc_html( tzh_price( $tzh_variant->price() ) ); ?></span>
						<span class="tz-tier__note"><?php esc_html_e( 'View this version', 'travelz-holidays' ); ?></span>
					</a>
				<?php else : ?>
					<span class="tz-tier<?php echo $tzh_is_this ? ' is-current' : ' is-unavailable'; ?>"<?php echo $tzh_is_this ? ' aria-current="true"' : ''; ?>>
						<span class="tz-tier__name">
							<?php echo tzh_icon( 'award' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
							<?php echo esc_html( $tzh_tier->name ); ?>
						</span>
						<span class="tz-tier__price">
							<?php
							echo $tzh_is_this
								? esc_html( tzh_price( $package->price() ) )
								: esc_html__( 'Not available', 'travelz-holidays' );
							?>
						</span>
						<span class="tz-tier__note">
							<?php
							echo $tzh_is_this
								? esc_html__( 'Currently viewing', 'travelz-holidays' )
								: esc_html__( 'No variation for this tour', 'travelz-holidays' );
							?>
						</span>
					</span>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</div>

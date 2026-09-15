<?php
/**
 * Tour Plan tab: the day-by-day itinerary and the highlights beside it.
 *
 * @package TravelZ_Holidays
 *
 * @var TZH_Package $package Package being shown.
 */

defined( 'ABSPATH' ) || exit;

$tzh_days       = $package->itinerary();
$tzh_highlights = $package->highlights();
?>
<div class="tz-panel tz-panel--split">
	<div class="tz-card">
		<div class="tz-card__body">
			<h2 class="tz-heading"><?php esc_html_e( 'Itinerary', 'travelz-holidays' ); ?></h2>

			<?php if ( $tzh_days ) : ?>
				<div class="tz-acc">
					<?php foreach ( $tzh_days as $tzh_i => $tzh_day ) : ?>
						<details class="tz-acc__item"<?php echo 0 === $tzh_i ? ' open' : ''; ?>>
							<summary>
								<span class="tz-acc__title"><?php echo esc_html( $tzh_day['title'] ); ?></span>
								<span class="tz-acc__aside">
									<?php if ( '' !== $tzh_day['meals'] ) : ?>
										<span class="tz-badge tz-badge--outline">
											<?php
											printf(
												/* translators: %s: meals included that day */
												esc_html__( 'Meals: %s', 'travelz-holidays' ),
												esc_html( $tzh_day['meals'] )
											);
											?>
										</span>
									<?php endif; ?>
									<?php echo tzh_icon( 'chevron-down', array( 'class' => 'tz-acc__caret' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
								</span>
							</summary>

							<div class="tz-acc__body">
								<?php if ( '' !== $tzh_day['body'] ) : ?>
									<p><?php echo esc_html( $tzh_day['body'] ); ?></p>
								<?php endif; ?>

								<?php if ( $tzh_day['hotels'] ) : ?>
									<span class="tz-acc__label"><?php esc_html_e( 'Accommodation', 'travelz-holidays' ); ?></span>
									<ul class="tz-hotels">
										<?php foreach ( $tzh_day['hotels'] as $tzh_hotel ) : ?>
											<li class="tz-hotel">
												<span class="tz-hotel__icon">
													<?php echo tzh_icon( 'hotel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?>
												</span>
												<span>
													<?php if ( '' !== $tzh_hotel['city'] ) : ?>
														<span class="tz-hotel__city"><?php echo esc_html( $tzh_hotel['city'] ); ?></span>
													<?php endif; ?>
													<span class="tz-hotel__name"><?php echo esc_html( $tzh_hotel['name'] ); ?></span>
													<?php if ( '' !== $tzh_hotel['class'] || '' !== $tzh_hotel['stay'] ) : ?>
														<span class="tz-hotel__class">
															<?php echo esc_html( trim( $tzh_hotel['class'] . ( '' !== $tzh_hotel['stay'] ? ' · ' . $tzh_hotel['stay'] : '' ) ) ); ?>
														</span>
													<?php endif; ?>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="tz-muted"><?php esc_html_e( 'The day-by-day plan for this tour is being finalised.', 'travelz-holidays' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $tzh_highlights ) : ?>
		<aside class="tz-card tz-highlights">
			<div class="tz-card__body">
				<h2 class="tz-heading"><?php esc_html_e( 'Tour Product Highlights', 'travelz-holidays' ); ?></h2>
				<ul class="tz-ticks tz-ticks--soft">
					<?php foreach ( $tzh_highlights as $tzh_item ) : ?>
						<li>
							<span class="tz-ticks__mark"><?php echo tzh_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed sprite id. ?></span>
							<span><?php echo esc_html( $tzh_item ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</aside>
	<?php endif; ?>
</div>

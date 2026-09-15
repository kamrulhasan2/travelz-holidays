<?php
/**
 * Setup guide.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * A one-screen answer to "I have just installed this, now what?".
 *
 * The checklist is live rather than written down: each step reports whether it
 * is already done by looking at the site, so the page is useful on day one and
 * still honest six months later. Nothing here stores anything — dismissing a
 * step is not possible because a step is done or it is not.
 */
class TZH_Setup_Page {

	/**
	 * Page slug.
	 */
	public const SLUG = 'travelz-holidays-setup';

	/**
	 * Render the screen.
	 */
	public function render(): void {
		tzh_admin_view(
			'setup',
			array(
				'steps'      => $this->steps(),
				'shortcodes' => $this->shortcodes(),
				'done'       => count(
					array_filter(
						$this->steps(),
						static function ( array $step ): bool {
							return $step['done'];
						}
					)
				),
			)
		);
	}

	/**
	 * The setup steps, in the order they should be done.
	 *
	 * @return array<int, array{title: string, body: string, done: bool, action: string, label: string, blank: bool}>
	 */
	private function steps(): array {
		$settings   = TZH_Settings::all();
		$counts     = wp_count_posts( TZH_Package::POST_TYPE );
		$published  = (int) ( $counts->publish ?? 0 );
		$dest_count = (int) wp_count_terms(
			array(
				'taxonomy'   => TZH_Package::TAX_DESTINATION,
				'hide_empty' => false,
			)
		);

		$steps = array(
			array(
				'title'  => __( 'Turn on pretty permalinks', 'travelz-holidays' ),
				'body'   => __( 'Every package and destination URL depends on this. Settings → Permalinks → anything except Plain, then Save.', 'travelz-holidays' ),
				'done'   => (bool) get_option( 'permalink_structure' ),
				'action' => admin_url( 'options-permalink.php' ),
				'label'  => __( 'Open Permalinks', 'travelz-holidays' ),
				'blank'  => false,
			),
			array(
				'title'  => __( 'Add your destinations', 'travelz-holidays' ),
				'body'   => __( 'A destination is a country or a city — Maldives, Dubai, Kashmir. Give each one a cover image, a one-line blurb and a display order; the order decides where it sits on the catalogue page.', 'travelz-holidays' ),
				'done'   => $dest_count > 0,
				'action' => admin_url( 'edit-tags.php?taxonomy=' . TZH_Package::TAX_DESTINATION . '&post_type=' . TZH_Package::POST_TYPE ),
				'label'  => __( 'Add destinations', 'travelz-holidays' ),
				'blank'  => false,
			),
			array(
				'title'  => __( 'Fill in the company-wide defaults', 'travelz-holidays' ),
				'body'   => __( 'WhatsApp number, currency, and the inclusion, exclusion and terms lists that are the same on every tour. A package that leaves those blank falls back to what is set here, so you type them once.', 'travelz-holidays' ),
				'done'   => '' !== trim( (string) ( $settings['whatsapp'] ?? '' ) ) && ! empty( $settings['default_terms'] ),
				'action' => tzh_admin_url( 'travelz-holidays-settings' ),
				'label'  => __( 'Open Settings', 'travelz-holidays' ),
				'blank'  => false,
			),
			array(
				'title'  => __( 'Publish your first package', 'travelz-holidays' ),
				'body'   => __( 'Give it a code such as TZ 001 — the number in the code sets the order of the whole list. Then destination, category, duration, price, itinerary and a banner. The bar at the top of the editor tells you what is still missing.', 'travelz-holidays' ),
				'done'   => $published > 0,
				'action' => admin_url( 'post-new.php?post_type=' . TZH_Package::POST_TYPE ),
				'label'  => __( 'Add a package', 'travelz-holidays' ),
				'blank'  => false,
			),
			array(
				'title'  => __( 'Offer the same tour at several comfort levels', 'travelz-holidays' ),
				'body'   => __( 'Optional. Give two packages the same Tour group and different Categories — Standard and Deluxe — and each one offers the other on its page as an alternative, with its own price.', 'travelz-holidays' ),
				'done'   => $this->has_variants(),
				'action' => admin_url( 'edit-tags.php?taxonomy=' . TZH_Package::TAX_FAMILY . '&post_type=' . TZH_Package::POST_TYPE ),
				'label'  => __( 'Tour groups', 'travelz-holidays' ),
				'blank'  => false,
			),
			array(
				'title'  => __( 'Connect checkout', 'travelz-holidays' ),
				'body'   => TZH_Woo::active()
					? __( 'WooCommerce is running. Enable at least one payment method, and bookings become ordinary orders with emails, invoices and reporting.', 'travelz-holidays' )
					: __( 'Install and activate WooCommerce to take payment at checkout. Without it, bookings are still collected — the traveler gets a reference number and a consultant follows up.', 'travelz-holidays' ),
				'done'   => TZH_Woo::active() && $this->has_gateway(),
				'action' => TZH_Woo::active()
					? admin_url( 'admin.php?page=wc-settings&tab=checkout' )
					: admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ),
				'label'  => TZH_Woo::active()
					? __( 'Payment methods', 'travelz-holidays' )
					: __( 'Get WooCommerce', 'travelz-holidays' ),
				'blank'  => false,
			),
			array(
				'title'  => __( 'Put the catalogue in your menu', 'travelz-holidays' ),
				'body'   => __( 'The catalogue lives at its own address — no page needs to be created for it. Add that address to your navigation menu as a custom link.', 'travelz-holidays' ),
				'done'   => $published > 0 && $dest_count > 0,
				'action' => admin_url( 'nav-menus.php' ),
				'label'  => __( 'Open Menus', 'travelz-holidays' ),
				'blank'  => false,
			),
			array(
				'title'  => __( 'Check it on the site', 'travelz-holidays' ),
				'body'   => __( 'Open the catalogue, pick a destination, open a package, and press Book Now. Do it once on a phone too — every screen is built for one.', 'travelz-holidays' ),
				'done'   => false,
				'action' => TZH_Rewrites::grid_url(),
				'label'  => __( 'View catalogue', 'travelz-holidays' ),
				'blank'  => true,
			),
		);

		return $steps;
	}

	/**
	 * The shortcodes, with an example of each.
	 *
	 * @return array<int, array{code: string, title: string, body: string}>
	 */
	private function shortcodes(): array {
		$destination = $this->first_destination_slug();

		return array(
			array(
				'code'  => '[travelz_destinations columns="4"]',
				'title' => __( 'Destination grid', 'travelz-holidays' ),
				'body'  => __( 'The cover images that open the catalogue. Use it on a home page built in any page builder. columns takes 1 to 6; limit="8" caps how many appear; empty="show" includes destinations that have no packages yet.', 'travelz-holidays' ),
			),
			array(
				'code'  => '' === $destination
					? '[travelz_packages limit="3"]'
					: sprintf( '[travelz_packages destination="%s" limit="3"]', $destination ),
				'title' => __( 'A row of packages', 'travelz-holidays' ),
				'body'  => __( 'The same cards the catalogue uses. Narrow it with destination="slug" or category="deluxe", show only your best with bestseller="yes", and order by orderby="price" instead of by package code.', 'travelz-holidays' ),
			),
		);
	}

	/**
	 * Whether any tour group holds more than one package.
	 */
	private function has_variants(): bool {
		$terms = get_terms(
			array(
				'taxonomy'   => TZH_Package::TAX_FAMILY,
				'hide_empty' => true,
				'number'     => 50,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term && $term->count > 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether WooCommerce has a payment method a traveller could actually use.
	 */
	private function has_gateway(): bool {
		if ( ! TZH_Woo::active() || ! function_exists( 'WC' ) ) {
			return false;
		}

		$gateways = WC()->payment_gateways();

		return $gateways && (bool) $gateways->get_available_payment_gateways();
	}

	/**
	 * Slug of the first destination, for the examples.
	 */
	private function first_destination_slug(): string {
		$terms = get_terms(
			array(
				'taxonomy'   => TZH_Package::TAX_DESTINATION,
				'hide_empty' => false,
				'number'     => 1,
				'orderby'    => 'name',
			)
		);

		return ( ! is_wp_error( $terms ) && $terms ) ? (string) $terms[0]->slug : '';
	}
}

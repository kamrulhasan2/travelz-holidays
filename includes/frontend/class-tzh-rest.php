<?php
/**
 * REST endpoint behind the filter panel.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the rendered results list for a set of filters.
 *
 * Rendering happens on the server and travels as HTML rather than as JSON the
 * browser would have to template, so the filtered list and the first page load
 * are produced by exactly the same code — there is no second implementation to
 * drift out of step.
 */
class TZH_Rest {

	private const NAMESPACE = 'tzh/v1';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	/**
	 * Register the route.
	 */
	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/packages',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'packages' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'dest' => array( 'required' => false ),
					'tier' => array( 'required' => false ),
					'days' => array( 'required' => false ),
					'min'  => array( 'required' => false ),
					'max'  => array( 'required' => false ),
					'type' => array( 'required' => false ),
					'page' => array( 'required' => false ),
				),
			)
		);
	}

	/**
	 * Run the filtered query and hand back rendered markup.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 */
	public function packages( WP_REST_Request $request ): WP_REST_Response {
		$filters = TZH_Query::filters( (array) $request->get_query_params() );
		$paged   = max( 1, (int) $request->get_param( 'page' ) );

		$query = new WP_Query( TZH_Query::args( $filters, $paged ) );

		$html = TZH_Template::capture( 'parts/results', array( 'query' => $query ) );

		wp_reset_postdata();

		return new WP_REST_Response(
			array(
				'html'  => $html,
				'total' => (int) $query->found_posts,
				'pages' => (int) $query->max_num_pages,
			),
			200
		);
	}
}

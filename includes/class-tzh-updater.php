<?php
/**
 * Updates from GitHub.
 *
 * @package TravelZ_Holidays
 */

defined( 'ABSPATH' ) || exit;

/**
 * Makes the plugin update itself from its own GitHub repository.
 *
 * WordPress only offers updates for plugins it can find on wordpress.org, so a
 * plugin hosted anywhere else has to answer three questions itself: is there a
 * newer version, where is its zip, and what changed. This class answers all
 * three from the repository's Releases, and falls back to the version in the
 * plugin header on the default branch when no release has been published yet.
 *
 * Nothing here downloads or installs anything: once the answer is in the update
 * transient, WordPress's own updater does the rest, exactly as it would for a
 * plugin from the directory.
 */
final class TZH_Updater {

	/**
	 * Repository owner.
	 */
	public const OWNER = 'kamrulhasan2';

	/**
	 * Repository name. Also the folder the plugin must end up in.
	 */
	public const REPO = 'travelz-holidays';

	/**
	 * Branch read when the repository has no releases.
	 */
	public const BRANCH = 'main';

	/**
	 * Transient holding the last answer from GitHub.
	 */
	public const CACHE = 'tzh_update_source';

	/**
	 * How long a good answer is trusted.
	 */
	public const TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * How long a failure is remembered, so a site with no outbound network
	 * does not call GitHub on every admin page load.
	 */
	public const TTL_FAILED = 30 * MINUTE_IN_SECONDS;

	/**
	 * Query argument behind the "Check for updates" link.
	 */
	public const ACTION = 'tzh-check-update';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject' ) );
		add_filter( 'plugins_api', array( $this, 'details' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'rename_source' ), 10, 4 );
		add_filter( 'http_request_args', array( $this, 'authorize' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'forget' ), 10, 2 );

		if ( is_admin() ) {
			add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'manual_check' ) );
			add_action( 'admin_notices', array( $this, 'checked_notice' ) );
			add_action( 'in_plugin_update_message-' . TZH_BASENAME, array( $this, 'update_message' ) );
		}
	}

	/**
	 * Answer WordPress's "is there an update?" question.
	 *
	 * @param mixed $transient Update transient being rebuilt.
	 *
	 * @return mixed
	 */
	public function inject( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->release();

		if ( empty( $release['version'] ) ) {
			return $transient;
		}

		$item = $this->item( $release );

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}

		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}

		// A plugin that is up to date still belongs in the transient, under
		// no_update — that is what makes "Enable auto-updates" appear next to
		// it on the Plugins screen.
		if ( version_compare( $release['version'], TZH_VERSION, '>' ) && ! empty( $release['package'] ) ) {
			$transient->response[ TZH_BASENAME ] = $item;
			unset( $transient->no_update[ TZH_BASENAME ] );
		} else {
			$transient->no_update[ TZH_BASENAME ] = $item;
			unset( $transient->response[ TZH_BASENAME ] );
		}

		return $transient;
	}

	/**
	 * Fill the "View details" modal.
	 *
	 * @param mixed  $result Result other handlers settled on.
	 * @param string $action Requested API action.
	 * @param mixed  $args   Request arguments.
	 *
	 * @return mixed
	 */
	public function details( $result, $action = '', $args = null ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		$slug = is_object( $args ) ? ( $args->slug ?? '' ) : ( is_array( $args ) ? ( $args['slug'] ?? '' ) : '' );

		if ( self::REPO !== $slug ) {
			return $result;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$release = $this->release();
		$data    = get_plugin_data( TZH_FILE, false, false );

		$information = array(
			'name'           => $data['Name'] ?? 'TravelZ Holidays',
			'slug'           => self::REPO,
			'version'        => $release['version'] ?? TZH_VERSION,
			'author'         => $data['Author'] ?? 'Kamrul Hasan',
			'author_profile' => $data['AuthorURI'] ?? '',
			'homepage'       => $data['PluginURI'] ?? $this->repo_url(),
			'download_link'  => $release['package'] ?? '',
			'requires'       => TZH_MIN_WP,
			'requires_php'   => TZH_MIN_PHP,
			'tested'         => $release['tested'] ?? get_bloginfo( 'version' ),
			'last_updated'   => $release['date'] ?? '',
			'sections'       => array(
				'description' => wpautop( esc_html( (string) ( $data['Description'] ?? '' ) ) ),
				'changelog'   => $this->changelog( (string) ( $release['notes'] ?? '' ) ),
			),
		);

		return (object) $information;
	}

	/**
	 * Put the unpacked files in the folder the plugin already lives in.
	 *
	 * GitHub names the folder inside its zip after the repository and the tag —
	 * travelz-holidays-0.1.2 — so without this the update would install a second
	 * copy of the plugin next to the first one and deactivate the site's own.
	 *
	 * @param mixed $source        Folder the zip unpacked into.
	 * @param mixed $remote_source Working directory holding it.
	 * @param mixed $upgrader      Upgrader running the job.
	 * @param mixed $extra         Hook extras; carries the plugin basename.
	 *
	 * @return mixed
	 */
	public function rename_source( $source, $remote_source = '', $upgrader = null, $extra = array() ) {
		global $wp_filesystem;

		if ( ! is_string( $source ) || '' === $source ) {
			return $source;
		}

		$plugin = is_array( $extra ) ? (string) ( $extra['plugin'] ?? '' ) : '';

		if ( TZH_BASENAME !== $plugin ) {
			return $source;
		}

		// Only rename something that really is this plugin.
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base || ! $wp_filesystem->exists( trailingslashit( $source ) . self::REPO . '.php' ) ) {
			return $source;
		}

		$wanted = trailingslashit( dirname( untrailingslashit( $source ) ) ) . self::REPO;

		if ( untrailingslashit( $source ) === untrailingslashit( $wanted ) ) {
			return $source;
		}

		if ( $wp_filesystem->exists( $wanted ) ) {
			$wp_filesystem->delete( $wanted, true );
		}

		if ( ! $wp_filesystem->move( untrailingslashit( $source ), $wanted, true ) ) {
			return new WP_Error(
				'tzh_rename_failed',
				__( 'Could not rename the downloaded folder to travelz-holidays.', 'travelz-holidays' )
			);
		}

		return trailingslashit( $wanted );
	}

	/**
	 * Sign requests to GitHub when a token is configured.
	 *
	 * Only needed for a private repository, or to lift the anonymous rate
	 * limit. The token is attached to GitHub hosts and nowhere else.
	 *
	 * @param mixed $args Request arguments.
	 * @param mixed $url  Request URL.
	 *
	 * @return mixed
	 */
	public function authorize( $args, $url = '' ) {
		if ( ! is_array( $args ) || ! is_string( $url ) ) {
			return $args;
		}

		$token = $this->token();

		if ( '' === $token ) {
			return $args;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! in_array( $host, array( 'api.github.com', 'github.com', 'codeload.github.com', 'objects.githubusercontent.com' ), true ) ) {
			return $args;
		}

		$args['headers'] = is_array( $args['headers'] ?? null ) ? $args['headers'] : array();

		$args['headers']['Authorization'] = 'Bearer ' . $token;

		return $args;
	}

	/**
	 * Forget the cached release after an update runs.
	 *
	 * @param mixed $upgrader Upgrader that finished.
	 * @param mixed $extra    What it did.
	 */
	public function forget( $upgrader = null, $extra = array() ): void {
		unset( $upgrader );

		$extra = is_array( $extra ) ? $extra : array();

		if ( 'update' !== ( $extra['action'] ?? '' ) || 'plugin' !== ( $extra['type'] ?? '' ) ) {
			return;
		}

		delete_site_transient( self::CACHE );
	}

	/**
	 * Add a "Check for updates" link to the plugin's row.
	 *
	 * @param mixed $links Row links.
	 * @param mixed $file  Plugin basename for that row.
	 *
	 * @return mixed
	 */
	public function row_meta( $links, $file = '' ) {
		if ( ! is_array( $links ) || TZH_BASENAME !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->check_url() ),
			esc_html__( 'Check for updates', 'travelz-holidays' )
		);

		return $links;
	}

	/**
	 * Handle the "Check for updates" link.
	 */
	public function manual_check(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified below.
		if ( empty( $_GET[ self::ACTION ] ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) || ! check_admin_referer( self::ACTION ) ) {
			return;
		}

		delete_site_transient( self::CACHE );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();

		$release = $this->release();
		$fresh   = ! empty( $release['version'] ) && version_compare( $release['version'], TZH_VERSION, '>' );

		wp_safe_redirect(
			add_query_arg(
				'tzh-checked',
				$fresh ? rawurlencode( $release['version'] ) : 'current',
				self_admin_url( 'plugins.php' )
			)
		);
		exit;
	}

	/**
	 * Say what the check found.
	 */
	public function checked_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only message, no action taken.
		$checked = isset( $_GET['tzh-checked'] ) ? sanitize_text_field( wp_unslash( $_GET['tzh-checked'] ) ) : '';

		if ( '' === $checked ) {
			return;
		}

		if ( 'current' === $checked ) {
			$message = sprintf(
				/* translators: %s: version number. */
				__( 'TravelZ Holidays is up to date (version %s).', 'travelz-holidays' ),
				TZH_VERSION
			);
		} else {
			$message = sprintf(
				/* translators: %s: version number. */
				__( 'TravelZ Holidays %s is available — reload this screen to install it.', 'travelz-holidays' ),
				$checked
			);
		}

		printf(
			'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Show the release notes under the update row on the Plugins screen.
	 */
	public function update_message(): void {
		$release = $this->release();
		$notes   = trim( (string) ( $release['notes'] ?? '' ) );

		if ( '' === $notes ) {
			return;
		}

		$first = strtok( $notes, "\n" );

		printf(
			'<br /><strong>%s</strong> %s',
			esc_html__( 'What changed:', 'travelz-holidays' ),
			esc_html( wp_trim_words( (string) $first, 30 ) )
		);
	}

	/**
	 * URL of the "Check for updates" link.
	 */
	public function check_url(): string {
		return wp_nonce_url(
			add_query_arg( self::ACTION, '1', self_admin_url( 'plugins.php' ) ),
			self::ACTION
		);
	}

	/**
	 * Repository home page.
	 */
	public function repo_url(): string {
		return 'https://github.com/' . self::OWNER . '/' . self::REPO;
	}

	/**
	 * The latest version GitHub knows about, cached.
	 *
	 * @return array<string, string>
	 */
	public function release(): array {
		$cached = get_site_transient( self::CACHE );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$release = $this->fetch();

		set_site_transient(
			self::CACHE,
			$release,
			empty( $release['version'] ) ? self::TTL_FAILED : self::TTL
		);

		return $release;
	}

	/**
	 * Ask GitHub what the latest version is.
	 *
	 * @return array<string, string>
	 */
	private function fetch(): array {
		$response = $this->get( 'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/releases/latest' );

		if ( is_array( $response ) && ! empty( $response['tag_name'] ) ) {
			return $this->from_release( $response );
		}

		return $this->from_branch();
	}

	/**
	 * Read a published release.
	 *
	 * @param array<string, mixed> $release Decoded release payload.
	 *
	 * @return array<string, string>
	 */
	private function from_release( array $release ): array {
		$tag     = (string) $release['tag_name'];
		$version = ltrim( $tag, 'vV' );

		if ( ! preg_match( '/^[0-9]+(\.[0-9]+)*/', $version ) ) {
			return array();
		}

		// A release that carries a built zip is preferred: it holds only what
		// belongs in the plugin, while the source archive also carries the
		// repository's own files.
		$package = '';

		foreach ( (array) ( $release['assets'] ?? array() ) as $asset ) {
			$name = (string) ( $asset['name'] ?? '' );

			if ( str_ends_with( strtolower( $name ), '.zip' ) ) {
				$package = (string) ( $asset['browser_download_url'] ?? '' );
				break;
			}
		}

		if ( '' === $package ) {
			$package = $this->repo_url() . '/archive/refs/tags/' . rawurlencode( $tag ) . '.zip';
		}

		return array(
			'version' => $version,
			'package' => $package,
			'notes'   => (string) ( $release['body'] ?? '' ),
			'date'    => (string) ( $release['published_at'] ?? '' ),
			'tested'  => '',
		);
	}

	/**
	 * Read the plugin header straight from the default branch.
	 *
	 * This is the path a repository takes before its first release: push a
	 * version bump to the branch and the site sees an update.
	 *
	 * @return array<string, string>
	 */
	private function from_branch(): array {
		$url = 'https://raw.githubusercontent.com/' . self::OWNER . '/' . self::REPO . '/' . self::BRANCH . '/' . self::REPO . '.php';

		$header = $this->get( $url, false );

		if ( ! is_string( $header ) || ! preg_match( '/^[ \t\/*#@]*Version:\s*(.+)$/mi', $header, $found ) ) {
			return array();
		}

		$version = trim( $found[1] );

		if ( ! preg_match( '/^[0-9]+(\.[0-9]+)*/', $version ) ) {
			return array();
		}

		return array(
			'version' => $version,
			'package' => $this->repo_url() . '/archive/refs/heads/' . self::BRANCH . '.zip',
			'notes'   => '',
			'date'    => '',
			'tested'  => '',
		);
	}

	/**
	 * One GET against GitHub.
	 *
	 * @param string $url    Address to read.
	 * @param bool   $decode Decode the body as JSON.
	 *
	 * @return array<string, mixed>|string|null
	 */
	private function get( string $url, bool $decode = true ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 12,
				'user-agent' => 'TravelZ-Holidays/' . TZH_VERSION . '; ' . home_url( '/' ),
				'headers'    => array(
					'Accept' => $decode ? 'application/vnd.github+json' : 'text/plain',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $decode ) {
			return $body;
		}

		$data = json_decode( $body, true );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Build the object WordPress puts in the update transient.
	 *
	 * @param array<string, string> $release Release details.
	 */
	private function item( array $release ): object {
		return (object) array(
			'id'            => 'github.com/' . self::OWNER . '/' . self::REPO,
			'slug'          => self::REPO,
			'plugin'        => TZH_BASENAME,
			'new_version'   => $release['version'],
			'url'           => $this->repo_url(),
			'package'       => $release['package'] ?? '',
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'tested'        => $release['tested'] ?? '',
			'requires'      => TZH_MIN_WP,
			'requires_php'  => TZH_MIN_PHP,
			'compatibility' => new stdClass(),
		);
	}

	/**
	 * Turn release notes into the markup the details modal expects.
	 *
	 * Release bodies are Markdown; this covers the handful of things a
	 * changelog actually uses rather than pulling in a parser.
	 *
	 * @param string $notes Raw release body.
	 */
	private function changelog( string $notes ): string {
		$notes = trim( $notes );

		if ( '' === $notes ) {
			return '<p>' . sprintf(
				/* translators: %s: repository URL. */
				wp_kses_post( __( 'Release notes are published on <a href="%s">GitHub</a>.', 'travelz-holidays' ) ),
				esc_url( $this->repo_url() . '/releases' )
			) . '</p>';
		}

		$html  = '';
		$list  = false;
		$lines = preg_split( '/\r\n|\r|\n/', $notes );

		foreach ( (array) $lines as $line ) {
			$line = trim( (string) $line );

			if ( '' === $line ) {
				continue;
			}

			$text = esc_html( $line );
			$text = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );
			$text = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text );

			if ( preg_match( '/^(#{1,6})\s*(.+)$/', $line, $heading ) ) {
				$html .= $this->close_list( $list );
				$html .= '<h4>' . esc_html( $heading[2] ) . '</h4>';
				continue;
			}

			if ( preg_match( '/^[-*+]\s+(.+)$/', $line, $bullet ) ) {
				if ( ! $list ) {
					$html .= '<ul>';
					$list  = true;
				}

				$item  = preg_replace( '/`([^`]+)`/', '<code>$1</code>', esc_html( $bullet[1] ) );
				$item  = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', (string) $item );
				$html .= '<li>' . $item . '</li>';
				continue;
			}

			$html .= $this->close_list( $list );
			$html .= '<p>' . $text . '</p>';
		}

		$html .= $this->close_list( $list );

		return $html;
	}

	/**
	 * Close an open list, if there is one.
	 *
	 * @param bool $open Whether a list is open. Set to false on return.
	 */
	private function close_list( bool &$open ): string {
		if ( ! $open ) {
			return '';
		}

		$open = false;

		return '</ul>';
	}

	/**
	 * Personal access token, if one is configured.
	 *
	 * A constant in wp-config.php wins over the settings field, so a private
	 * repository's token never has to live in the database.
	 */
	private function token(): string {
		if ( defined( 'TZH_GITHUB_TOKEN' ) && is_string( TZH_GITHUB_TOKEN ) ) {
			return trim( TZH_GITHUB_TOKEN );
		}

		return trim( (string) TZH_Settings::get( 'github_token', '' ) );
	}
}

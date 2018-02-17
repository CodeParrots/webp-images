<?php

/**
 * WebP_Images
 *
 * @since 1.0.0
 */

class WebP_Images {

	public static $cwebp_is_installed;
	public static $cwebp_version;

	/**
	* pseudo-constructor
	*
	* @since 1.0.0
	*/
	public static function instance() {

		new self();

	}

	/**
	* constructor
	*
	* @since   1.0.0
	*/
	public function __construct() {

		// Load the AJAX handler
		new WebP_Images_AJAX_Handler();

		// self::$cwebp_is_installed = strpos( shell_exec( "22 -version 2>&1" ), 'sh: 22' ) === false;
		self::$cwebp_is_installed = strpos( shell_exec( "cwebp -version 2>&1" ), 'sh: cwebp' ) === false;

		if ( ! self::$cwebp_is_installed ) {

			// Setup an admin notice, not a die statement.
			wp_die( sprintf(
				__( 'cwebp is not installed. Please install the cwebp package. %s', 'webp-images' ),
				sprintf(
					'<a href="%1$s" target="_blank" title="%2$s">%2$s</a>',
					esc_url( 'https://developers.google.com/speed/webp/download' ),
					esc_html__( 'Downloading and Installing WebP', 'webp-images' )
				)
			) );

		}

		new WebP_Images_Media_Fields();

		// Store the cwebp version, for reference.
		self::$cwebp_version = shell_exec( 'cwebp -version 2>&1' );

		// Image rewriter
		add_action( 'template_redirect', [ __CLASS__, 'handle_rewrite_hook' ] );

		// i18n
		add_action( 'admin_init', [ __CLASS__, 'register_textdomain' ] );

		// Settings
		add_action( 'admin_init', [ 'WebP_Images_Settings', 'register_settings' ] );

		// Settings page
		add_action( 'admin_menu', [ 'WebP_Images_Settings', 'add_settings_page' ] );

		// Plugin action links
		add_filter( 'plugin_action_links_' . WEBP_IMAGES_BASE, [ __CLASS__, 'add_action_link' ] );

		// Admin notices
		add_action( 'all_admin_notices', [ __CLASS__, 'webp_images_requirements_check' ] );

		// Generate the webp images
		add_filter( 'wp_generate_attachment_metadata', function( $metadata, $attachment_id ) {

			// Only resize images.
			if ( ! wp_attachment_is_image( $attachment_id ) ) {

				return $metadata;

			}

			new WebP_Images_Handle_Uploads( 'upload', $metadata );

			return $metadata;

		}, PHP_INT_MAX, 2 );

		// Delete the webp images when an image is removed
		add_filter( 'delete_attachment', function( $post_id ) {

			new WebP_Images_Handle_Uploads( 'delete', $post_id );

			return $post_id;

		}, PHP_INT_MAX, 2 );

	}

	/**
	* Plugin action links
	*
	* @since 1.0.0
	*
	* @param  array $data Original links.
	*
	* @return array $data Custom link array.
	*/
	public static function add_action_link( $data ) {

		if ( ! current_user_can( 'manage_options' ) ) {

			return $data;

		}

		return array_merge( $data, [
			sprintf(
				'<a href="%s">%s</a>',
				add_query_arg(
					[
						'page' => 'webp_images',
					],
					admin_url( 'options-general.php' )
				),
				esc_html__( 'Settings' )
			),
		] );

	}

	/**
	 * Uninstall hook
	 *
	 * @since 1.0.0
	 */
	public static function handle_uninstall_hook() {

		delete_option( 'webp_images' );

	}

	/**
	* Activation hook
	*
	* @since 1.0.0
	*/
	public static function handle_activation_hook() {

		add_option( 'cdn_enabler', [
			'dirs'    => 'wp-content/uploads',
			'quality' => 80,
		] );

	}

	/**
	 * check plugin requirements
	 *
	 * @since 1.0.0
	 */
	public static function webp_images_requirements_check() {

		// WordPress version check
		if ( version_compare( $GLOBALS['wp_version'], WEBP_IMAGES_MIN_WP . 'alpha', '>' ) ) {

			return;

		}

		?>

		<div class="notice notice-error">

			<p><?php esc_html_e( 'CDN Enabler is optimized for WordPress 4.0. Please disable the plugin or upgrade your WordPress installation (recommended).', 'cdn-enabler' ); ?><p>

		</div>

		<?php

	}

	/**
	 * Register textdomain
	 *
	 * @since 1.0.0
	 */
	public static function register_textdomain() {

		load_plugin_textdomain( 'webp-images', false, 'webp-images/i18n' );

	}

	/**
	 * Return plugin options
	 *
	 * @since 1.0.0
	 *
	 * @return array $diff data pairs
	 */
	public static function get_options() {

		return wp_parse_args( get_option( 'webp_images' ), [
			'dirs'    => 'wp-content/uploads',
			'quality' => 80,
		] );

	}

	/**
	 * run rewrite hook
	 *
	 * @since 0.0.1
	 */
	public static function handle_rewrite_hook() {

		$options = self::get_options();

		$rewriter = new WebP_Images_Rewriter(
			get_option( 'home' ),
			$options['dirs']
		);

		ob_start( array( $rewriter, 'rewrite' ) );

	}

}

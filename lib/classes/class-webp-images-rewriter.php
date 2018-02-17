<?php

/**
 * WebP_Images_Rewriter
 *
 * @since 1.0.0
 */

class WebP_Images_Rewriter {

	/**
	 * Origin URL
	 *
	 * @var string
	 */
	private $blog_url;

	/**
	 * webp image directories
	 *
	 * @var string
	 */
	private $dirs;

	/**
	 * Upload and image vars
	 *
	 * @var string
	 */
	private $attachment_url;
	private $webp_dir;
	private $webp_url;

	/**
	 * constructor
	 *
	 * @since 1.0.0
	 */
	function __construct(
		$blog_url,
		$dirs
	) {

		$this->blog_url = $blog_url;
		$this->dirs     = $dirs;

		$this->setup_uploads_dir();

	}


	/**
	* Exclude assets that should not be rewritten, if exclude checkbox is checked in media library
	*
	* @since 1.0.0
	*
	* @param string $asset current asset
	*
	* @return boolean True if excluded, else false
	*/
	protected function exclude_asset( $asset ) {

		$assets_split = explode( ', ', $asset );
		$original_url = isset( $assets_split[0] ) ? strtok( $assets_split[0], ' ' ) : false;
		$attachment_id = $this->get_attachment_id( $original_url );

		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {

			return true;

		}

		return get_post_meta( $attachment_id, 'webp_image_exclude', true );

	}


	/**
	 * Relative url
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Full URL
	 *
	 * @return string Relative url
	 */
	protected function relative_url( $url ) {

		return substr( $url, strpos( $url, '//' ) );

	}

	/**
	 * Setup the upload dir properties.
	 *
	 * @since 1.0.0
	 */
	public function setup_uploads_dir() {

		$upload_dir = wp_upload_dir();

		$this->upload_dir_base = str_replace( site_url() . '/', '', $upload_dir['baseurl'] );
		$this->attachment_url  = trailingslashit( $upload_dir['url'] );
		$this->webp_dir        = trailingslashit( $upload_dir['basedir'] . '/webp' . $upload_dir['subdir'] );
		$this->webp_url        = trailingslashit( $upload_dir['baseurl'] . '/webp' . $upload_dir['subdir'] );

	}

	/**
	 * Rewrite Image URL
	 *
	 * @since 1.0.0
	 *
	 * @param string $asset Current asset
	 *
	 * @return string Updated URL if not excluded, else original URL.
	 */
	protected function rewrite_url( $asset ) {

		if ( $this->exclude_asset( $asset[0] ) ) {

			return $asset[0];

		}

		$final_assets = [];

		foreach ( explode( ', ', $asset[0] ) as $single_asset ) {

			$split_asset = explode( ' ', $single_asset );

			$url   = $split_asset[0];
			$width = $split_asset[1];

			$file_ext = pathinfo( $url, PATHINFO_EXTENSION );

			$webp_file_path = str_replace( $this->attachment_url, $this->webp_dir, str_replace( $file_ext, 'webp', $url ) );
			$webp_file_url  = str_replace( $this->attachment_url, $this->webp_url, str_replace( $file_ext, 'webp', $url ) );

			$final_assets[] = file_exists( $webp_file_path ) ? $webp_file_url . ' ' . $width : $single_asset;

		}

		return implode( ', ', $final_assets );

	}


	/**
	 * get directory scope
	 *
	 * @since 1.0.0
	 *
	 * @return string Directory scope
	 */
	protected function get_dir_scope() {

		// Remove duplicate upload dir
		$input   = explode( ',', $this->dirs );
		$input[] = $this->upload_dir_base;
		$input   = array_unique( $input );

		return implode( '|', array_map( 'quotemeta', array_map( 'trim', $input ) ) );

	}


	/**
	 * Rewrite image URLs in the HTML doc
	 *
	 * @since 1.0.0
	 *
	 * @param string $html Current raw HTML doc
	 *
	 * @return string Updated HTML doc with webp image links
	 */
	public function rewrite( $html ) {

		// Check if webp is supported, else bail
		if ( ! self::is_webp_supported() ) {

			return $html;

		}

		$option = WebP_Images::get_options();

		$dirs       = $this->get_dir_scope();
		$blog_url   = '(https?:|)' . $this->relative_url( quotemeta( $this->blog_url ) );
		$regex_rule = '#(?<=[(\"\'])' . $blog_url . '/(?:((?:' . $dirs . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';

		return preg_replace_callback( $regex_rule, [ $this, 'rewrite_url' ], $html );

	}

	/**
	 * Detect if the current browser supports webp images
	 *
	 * @since 1.0.0
	 *
	 * @return boolean Return true when browser is not mobile, and has webp image support, else false
	 */
	public static function is_webp_supported() {

		return ( ! wp_is_mobile() && strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false );

	}

	/**
	 * Get an attachment ID given a URL.
	 *
	 * @param string $image_url
	 *
	 * @return int Attachment ID on success, 0 on failure
	 */
	public function get_attachment_id( $image_url ) {

		global $wpdb;

		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) );

		return isset( $attachment[0] ) ? $attachment[0] : false;

	} // @codingStandardsIgnoreLine

}

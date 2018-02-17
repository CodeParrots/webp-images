<?php
/**
 * WebP_Images_Upload_Paths
 *
 * @since 1.0.0
 */

class WebP_Images_Upload_Paths {

	private $file_path;

	public static $upload_path;
	public static $upload_url;
	public static $sub_dir;
	public static $base_dir;
	public static $base_url;

	public static $webp_dir_url;
	public static $webp_dir_path;
	public static $webp_file;
	public static $webp_file_sub_dir;
	public static $webp_file_path;
	public static $webp_file_url;

	/**
	 * constructor
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Path to file.
	 */
	public function __construct( $file_path = '' ) {

		$this->file_path = $file_path;

		$this->setup_upload_paths();

	}

	/**
	 * Setup the upload paths.
	 *
	 * @since 1.0.0
	 *
	 * @return null
	 */
	public function setup_upload_paths() {

		$upload_dir = wp_upload_dir();

		$file_ext = pathinfo( $this->file_path, PATHINFO_EXTENSION );

		// Original paths
		self::$upload_path = trailingslashit( $upload_dir['path'] );
		self::$upload_url  = trailingslashit( $upload_dir['url'] );
		self::$sub_dir     = trailingslashit( $upload_dir['subdir'] );
		self::$base_dir    = trailingslashit( $upload_dir['basedir'] );
		self::$base_url    = trailingslashit( $upload_dir['baseurl'] );

		// WebP paths
		self::$webp_dir_url      = trailingslashit( self::$base_url . 'webp' );
		self::$webp_dir_path     = trailingslashit( self::$base_dir . 'webp' );
		self::$webp_file         = str_replace( $file_ext, 'webp', $this->file_path );
		self::$webp_file_sub_dir = trailingslashit( dirname( $this->file_path ) );

		self::$webp_file_path = self::$webp_dir_path . self::$webp_file_sub_dir;
		self::$webp_file_url  = self::$webp_dir_url . self::$webp_file_sub_dir;

	}

}

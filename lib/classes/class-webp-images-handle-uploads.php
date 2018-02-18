<?php
/**
 * WebP_Images_Handle_Uploads
 *
 * @since 1.0.0
 */

class WebP_Images_Handle_Uploads {

	// WebP compressions options
	private $webp_options;

	// Attachment meta array
	private $attachment_meta;

	// Upload paths class
	private $upload_paths;

	public function __construct( $action, $data ) {

		$this->webp_options = WebP_Images::get_options();

		switch ( $action ) {

			default:
			case 'upload':
				$this->attachment_meta = $data;
				$this->generate_webp_images();
				return;

			case 'delete':
				$this->delete_webp_images( $data );

		}

	}

	/**
	 * Delete associated webp images out of wp-content/uploads/webp if they exist
	 *
	 * @since 1.0.0
	 *
	 * @param  integer $attachment_id Attachment ID.
	 *
	 * @return null
	 */
	public function delete_webp_images( $attachment_id ) {

		if ( ! wp_attachment_is_image( $attachment_id ) ) {

			return;

		}

		$this->attachment_meta = wp_get_attachment_metadata( $attachment_id );

		$this->upload_paths = new WebP_Images_Upload_Paths( $this->attachment_meta['file'] );

		// Delete original size
		$this->delete_webp_image( basename( $this->attachment_meta['file'] ) );

		// Delete auto-generated sizes
		foreach ( $this->attachment_meta['sizes'] as $size => $image_meta ) {

			$this->delete_webp_image( $image_meta['file'] );

			// Do error checking here

		}

	}

	/**
	 * Generate the duplicate webp images into the wp-content/uploads/webp/ dir.
	 */
	public function generate_webp_images() {

		$this->upload_paths = new WebP_Images_Upload_Paths( $this->attachment_meta['file'] );

		// Make webp directory
		if ( wp_mkdir_p( $this->upload_paths::$webp_file_path ) ) {

			// Flush rewrite rules so the webp dir is accessible
			flush_rewrite_rules();

		}

		// Generate the full size webp image
		$this->generate_webp_image( basename( $this->attachment_meta['file'] ) );

		// Sizes array is not set when we regenerate a specified image size in regenerate_webp_image_size()
		if ( ! isset( $this->attachment_meta['sizes'] ) || empty( $this->attachment_meta['sizes'] ) ) {

			return;

		}

		foreach ( $this->attachment_meta['sizes'] as $size => $image_meta ) {

			$this->generate_webp_image( $image_meta['file'] );

			// Do error checking here

		}

	}

	/**
	 * Generate an associated webp image.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_name Name of the file to generate a webp for.
	 */
	public function generate_webp_image( $file ) {

		if ( ! $file ) {

			return;

		}

		$input  = untrailingslashit( $this->upload_paths::$base_dir ) . $this->upload_paths::$sub_dir . $file;
		$output = untrailingslashit( $this->upload_paths::$webp_dir_path ) . $this->upload_paths::$sub_dir . str_replace( pathinfo( $file, PATHINFO_EXTENSION ), 'webp', $file );

		shell_exec( "cwebp -q {$this->webp_options['quality']} {$input} -o {$output}" );

	}

	/**
	 * Delete a single webp image out of the wp-content/uploads/webp dir
	 *
	 * @since 1.0.0
	 *
	 * @param  string $file File name to delete.
	 *
	 * @return null
	 */
	private function delete_webp_image( $file ) {

		if ( ! $file ) {

			return;

		}

		$webp_file_path = $this->upload_paths::$webp_dir_path . $this->upload_paths::$webp_file_sub_dir . str_replace( pathinfo( $file, PATHINFO_EXTENSION ), 'webp', $file );

		if ( ! file_exists( $webp_file_path ) ) {

			return;

		}

		unlink( $webp_file_path );

	}

}

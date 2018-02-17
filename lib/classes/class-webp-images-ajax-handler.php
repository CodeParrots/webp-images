<?php
/**
 * WebP_Images_AJAX_Handler
 *
 * @since 1.0.0
 */

class WebP_Images_AJAX_Handler {

	public function __construct() {

		add_action( 'wp_ajax_regenerate_webp_images', [ $this, 'regenerate_webp_images' ] );

	}

	public function regenerate_webp_images() {

		$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {

			wp_send_json_error();

		}

		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		$paths  = new WebP_Images_Upload_Paths( $attachment_meta['file'] );
		$return = filter_input( INPUT_POST, 'return', FILTER_SANITIZE_STRING );

		new WebP_Images_Handle_Uploads( 'upload', $attachment_meta );

		if ( ! $return ) {

			wp_send_json_success( [
				'webpImageURL' => $paths::$webp_file_url,
			] );

		}

		$media_fields = new WebP_Images_Media_Fields();

		$file_ext = pathinfo( $attachment_meta['file'], PATHINFO_EXTENSION );

		$original_file = $paths::$base_dir . $attachment_meta['file'];
		$webp_file     = $paths::$webp_file_path . basename( str_replace( $file_ext, 'webp', $attachment_meta['file'] ) );

		wp_send_json_success( [
			'compressionResults' => $media_fields::file_size_diff( $original_file, $webp_file, true ),
		] );

	}

}

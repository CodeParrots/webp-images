<?php
/**
 * WebP_Images_AJAX_Handler
 *
 * @since 1.0.0
 */

class WebP_Images_AJAX_Handler {

	public function __construct() {

		add_action( 'wp_ajax_regenerate_webp_images', [ $this, 'regenerate_webp_images' ] );

		add_action( 'wp_ajax_regenerate_webp_image_size', [ $this, 'regenerate_webp_image_size' ] );

	}

	/**
	 * Regenerate all image sizes.
	 *
	 * @since 1.0.0
	 */
	public function regenerate_webp_images() {

		$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {

			$error = ! $attachment_id ? __( 'Error: Missing attachment ID.', 'webp-images' ) : __( 'Error: Attachment is not an image.', 'webp-images' );

			wp_send_json_error( [
				'message' => $error,
			] );

		}

		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$return          = filter_input( INPUT_POST, 'return', FILTER_SANITIZE_STRING );

		new WebP_Images_Handle_Uploads( 'upload', $attachment_meta );

		if ( ! $return ) {

			$paths = new WebP_Images_Upload_Paths( $attachment_meta['file'] );

			wp_send_json_success( [
				'webpImageURL' => $paths::$webp_file_url,
			] );

		}

		$media_fields = new WebP_Images_Media_Fields();

		wp_send_json_success( [
			'compressionResults' => $media_fields::file_size_diff( $attachment_id ),
		] );

	}

	/**
	 * Regenerate a specific webp image size from the original
	 *
	 * @return [type] [description]
	 */
	public function regenerate_webp_image_size() {

		$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_SANITIZE_NUMBER_INT );
		$image_size    = filter_input( INPUT_POST, 'size', FILTER_SANITIZE_STRING );

		if ( ! $attachment_id || ! $image_size ) {

			wp_send_json_error( [
				'message' => ! $attachment_id ? __( 'Missing attachment ID.', 'webp-images' ) : __( 'Missing image size.', 'webp-images' ),
			] );

		}

		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		if ( ! array_key_exists( $image_size, $attachment_meta['sizes'] ) ) {

			wp_send_json_error( [
				'message' => sprintf(
					/* translators: 1. Image size. */
					__( "Image size '%s' not found.", 'webp-images' ),
					$image_size
				),
			] );

		}

		$paths    = new WebP_Images_Upload_Paths( $attachment_meta['file'] );
		$file_ext = pathinfo( $attachment_meta['sizes'][ $image_size ]['file'], PATHINFO_EXTENSION );

		new WebP_Images_Handle_Uploads( 'upload', [
			'file' => trailingslashit( dirname( $attachment_meta['file'] ) ) . $attachment_meta['sizes'][ $image_size ]['file'],
		] );

		if ( ! file_exists( $paths::$webp_file_path . str_replace( $file_ext, 'webp', $attachment_meta['sizes'][ $image_size ]['file'] ) ) ) {

			wp_send_json_error( [
				'message' => sprintf(
					/* translators: 1. Image size. */
					__( "Error generating a .webp file for image size '%s'.", 'webp-images' ),
					$image_size
				),
			] );

		}

		$media_fields = new WebP_Images_Media_Fields();

		wp_send_json_success( [
			'compressionResults' => $media_fields::file_size_diff( $attachment_id, $image_size ),
		] );

	}

}

<?php

final class WebP_Images_Media_Fields {

	public function __construct() {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_filter( 'attachment_fields_to_edit', [ $this, 'additional_fields' ], PHP_INT_MAX, 2 );

		add_filter( 'attachment_fields_to_save', [ $this, 'save_additional_fields' ], PHP_INT_MAX, 2 );

		add_filter( 'manage_media_columns', [ $this, 'add_webp_column' ] );
		add_action( 'manage_media_custom_column', [ $this, 'webp_custom_column' ], 10, 2 );

		add_filter( 'bulk_actions-upload', [ $this, 'custom_bulk_actions' ] );

		add_filter( 'media_row_actions', [ $this, 'custom_row_actions' ], 10, 3 );

	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {

		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		if ( 'upload.php' !== $hook && ( 'post.php' !== $hook || ! $post_id || 'attachment' !== get_post_type( $post_id ) ) ) {

			return;

		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'webp-images-media-element', WEBP_IMAGES_URL . "lib/assets/js/webp-images-media-element{$suffix}.js", [ 'jquery' ], WEBP_IMAGES_VERSION, true );

		wp_localize_script( 'webp-images-media-element', 'webpMediaElementData', [
			'errorResponse' => '<strong>' . esc_html__( 'Error:', 'webp-images' ) . '</strong> ' . esc_html__( 'We encountered a snag while generated your webpimage. Please try again.', 'webp-images' ),
			'preloader'     => sprintf(
				'<img src="%s" class="preloader webp-images">',
				esc_url( admin_url( 'images/wpspin_light.gif' ) )
			),
		] );

	}

	/**
	 * Append our additional fields to the media library.
	 *
	 * @param  array  $form_fields Initial form fields array.
	 * @param  object $post        Post object.
	 *
	 * @filter attachment_fields_to_edit
	 * @since  1.0.0
	 *
	 * @return array               Form fields array.
	 */
	public function additional_fields( $form_fields, $post ) {

		if ( ! wp_attachment_is_image( $post->ID ) ) {

			return $form_fields;

		}

		$attachment_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		// Minor styles for webp image fields, in list mode
		if ( wp_attachment_is_image( $attachment_id ) ) {
			?>
			<style>
			.compat-attachment-fields td.field { width: 80% }
			.webp_image_exclude{ float: left; margin: 1em 10px 0 0 }
			</style>
			<?php
		}

		$webp_image_url = $this->get_webp_url( $post->ID );

		$form_fields['webp_image_url'] = [
			'label' => esc_html__( 'WebP URL', 'user-downloads' ),
			'input' => 'html',
			'html'  => ! $webp_image_url ? sprintf(
				/* translators: 1. No webp images found error. 2. Generate .webp image link. */
				'<span class="webp-images-url-error" style="font-weight: bold; line-height: 30px; color: #B33A3A;">%1$s</span>&nbsp;
				%2$s
				<a href="#" class="button button-primary js-generate-webp" data-attachment="%3$s">%4$s</a>
				<img src="%5$s" class="preloader webp-images hidden">',
				esc_html__( 'No webp image found.', 'webp-images' ),
				wp_nonce_field( 'webp_images', 'generate_webp_image_' . $post->ID, true, false ),
				esc_attr( $post->ID ),
				esc_html__( 'Generate .webp', 'webp-images' ),
				esc_url( admin_url( 'images/wpspin_light.gif'))
			) : sprintf(
				'<label for="attachments-%1$s-webp_image_url">
					<input type="text" readonly="readonly" class="widefat" id="attachments-%1$s-webp_image_url" name="attachments[%1$s][webp_image_url]" value="%2$s" />
				</label>',
				esc_attr( $post->ID ),
				esc_url( $webp_image_url )
			),
		];

		// If no webp image exists, do not display the exclusion checkbox
		if ( ! $webp_image_url ) {

			return $form_fields;

		}

		$form_fields['webp_image_exclude'] = [
			'label' => esc_html__( 'Exclude WebP Format', 'user-downloads' ),
			'input' => 'html',
			'html'  => sprintf(
				'<label for="attachments-%1$s-webp_image_exclude" class="webp_image_exclude">
					<input type="checkbox" id="attachments-%1$s-webp_image_exclude" name="attachments[%1$s][webp_image_exclude]" value="1" %2$s />
				</label>',
				esc_attr( $post->ID ),
				checked( get_post_meta( $post->ID, 'webp_image_exclude', true ), '1', false )
			),
			'helps' => sprintf(
				/* translators: 1. .webp wrapped in <code> tags */
				__( 'Exclude this image from ever displaying in a %s format.', 'webp-images' ),
				'<code>.webp</code>'
			),
		];

		return $form_fields;

	}

	/**
	 * Save the additional media library fields.
	 *
	 * @param  object $post       Post object.
	 * @param  object $attachment Attachment object.
	 *
	 * @filter attachment_fields_to_save
	 * @since  1.0.0
	 *
	 * @return array               Sanitized form fields array.
	 */
	public function save_additional_fields( $post, $attachment ) {

		update_post_meta( $post['ID'], 'webp_image_exclude', isset( $attachment['webp_image_exclude'] ) );

		return $post;

	}

	/**
	 * Add custom WebP Image column.
	 *
	 * @param array $posts_columns [description]
	 */
	public function add_webp_column( $posts_columns ) {

		$posts_columns['webp_images'] = esc_html__( 'WebP Savings', 'webp-images' );

		return $posts_columns;

	}

	/**
	 * Custom 'WebP Savings' column
	 *
	 * @param  array   $column_name   Current column name
	 * @param  integer $attachment_id Current attachment ID.
	 *
	 * @return string|mixed           Error message or mixed markup for the row.
	 */
	public function webp_custom_column( $column_name, $attachment_id ) {

		switch ( $column_name ) {

			case 'webp_images':
				if ( ! wp_attachment_is_image( $attachment_id ) ) {

					return printf(
						'<small><em>%s</em></small>',
						esc_html__( 'Cannot be converted to .webp format.', 'webp-images' )
					);

				}

				$webp_image_path = $this->get_webp_url( $attachment_id, 'path' );

				// No webp image was found, regenerate it
				if ( ! $webp_image_path || empty( $webp_image_path ) ) {

					return printf(
						'%1$s
						<a href="%2$s" data-attachment="%4$s" class="button button-secondary js-generate-webp-table">%3$s</a>
						<img class="preloader generate-webp-%4$s hidden" src="%5$s">',
						wp_nonce_field( 'webp_images', 'generate_webp_image_' . $attachment_id, true, false ),
						'#',
						esc_html__( 'Generate .wep Images', 'webp-images' ),
						esc_attr( $attachment_id ),
						esc_url( admin_url( 'images/wpspin_light.gif' ) )
					);

				}

				if ( ! file_exists( $webp_image_path ) ) {

					return printf(
						'<em>%1$s</em>',
						esc_html__( 'No associated .webp image found.', 'webp-images' )
					);

				}

				$original_file_path = $this->get_file_path( $attachment_id );

				self::file_size_diff( $original_file_path, $webp_image_path );

				printf(
					'<img src="%1$s" class="preloader generate-webp-%2$s hidden">',
					esc_url( admin_url( 'images/wpspin_light.gif' ) ),
					esc_attr( $attachment_id )
				);

				break;

			default:
				break;

		}

	}

	/**
	 * Register custom bulk action.
	 *
	 * @param  array $bulk_actions Original bulk actions.
	 *
	 * @return array               Filtered bulk actions.
	 */
	public function custom_bulk_actions( $bulk_actions ) {

		$bulk_actions['regen_webp_images'] = __( 'Generate .webp Images', 'webp-images' );

		return $bulk_actions;

	}

	public function custom_row_actions( $actions, $post, $detached ) {

		if ( ! wp_attachment_is_image( $post->ID ) ) {

			return $actions;

		}

		$actions['regen_webp_image'] = sprintf(
			'<a href="#" title="%1$s" class="js-webp-regen-image-link" data-attachment="%2$s">.webp</a>',
			esc_attr__( 'Regenerate .webp images', 'webp-images' ),
			esc_attr( $post->ID )
		);

		return $actions;

	}

	/**
	 * Return the webp image URL
	 *
	 * @param  integer $post_id Media element post ID.
	 *
	 * @return string          URL to the media element if found, else false.
	 */
	private function get_webp_url( $post_id, $type = 'url' ) {

		$attachment_meta = wp_get_attachment_metadata( $post_id );

		if ( ! $attachment_meta ) {

			return false;

		}

		$file_ext  = pathinfo( $attachment_meta['file'], PATHINFO_EXTENSION );
		$webp_file = 'webp/' . str_replace( $file_ext, 'webp', $attachment_meta['file'] );

		$upload_dir = wp_upload_dir();
		$webp_path  = trailingslashit( $upload_dir['basedir'] ) . $webp_file;
		$webp_url   = trailingslashit( $upload_dir['baseurl'] ) . $webp_file;

		if ( ! file_exists( $webp_path ) ) {

			return false;

		}

		return ( 'path' === $type ) ? trailingslashit( $upload_dir['basedir'] ) . $webp_file : trailingslashit( $upload_dir['baseurl'] ) . $webp_file;

	}

	/**
	 * Return the original image path.
	 *
	 * @return string Path to the original file.
	 */
	public function get_file_path( $attachment_id ) {

		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . $attachment_meta['file'];

	}

	/**
	 * Print the file size difference calculations.
	 *
	 * @param  string $file1 Path to the first file.
	 * @param  string $file2 Path to the second file.
	 *
	 * @return string        Compression savings, or empty when error.
	 */
	public static function file_size_diff( $file1, $file2, $ajax = false ) {

		if ( empty( $file1 ) || empty( $file2 ) ) {

			return;

		}

		$percent_saved = round( ( filesize( $file1 ) - filesize( $file2 ) ) / filesize( $file1 ) * 100, 2 ) . '%';

		$bytes    = filesize( $file1 ) - filesize( $file2 );
		$size     = [ 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];
		$factor   = floor( ( strlen( $bytes ) - 1 ) / 3 );
		$kb_saved = sprintf( '%.2f', $bytes / pow( 1024, $factor ) ) . ' ' . $size[ $factor ];

		if ( $ajax ) {

			ob_start();

			printf(
				'<small>Reduced by %1$s (%2$s)</small>',
				$percent_saved,
				$kb_saved
			);

			$contents = ob_get_contents();
			ob_get_clean();

			return $contents;

		}

		printf(
			'<small>Reduced by %1$s (%2$s)</small>',
			$percent_saved,
			$kb_saved
		);

	}

}

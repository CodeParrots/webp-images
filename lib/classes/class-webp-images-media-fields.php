<?php

final class WebP_Images_Media_Fields {

	public function __construct() {

		add_filter( 'manage_media_columns', [ $this, 'add_webp_column' ] );

		add_action( 'manage_media_custom_column', [ $this, 'webp_custom_column' ], 10, 2 );

		if ( ! WebP_Images::$cwebp_is_installed ) {

			return;

		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_filter( 'attachment_fields_to_edit', [ $this, 'additional_fields' ], PHP_INT_MAX, 2 );

		add_filter( 'attachment_fields_to_save', [ $this, 'save_additional_fields' ], PHP_INT_MAX, 2 );

		add_filter( 'bulk_actions-upload', [ $this, 'custom_bulk_actions' ] );

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

		wp_enqueue_style( 'test', WEBP_IMAGES_URL . "lib/assets/css/webp-images-media-element{$suffix}.css", [], WEBP_IMAGES_VERSION, 'all' );

		wp_enqueue_script( 'webp-images-media-element', WEBP_IMAGES_URL . "lib/assets/js/webp-images-media-element{$suffix}.js", [ 'jquery' ], WEBP_IMAGES_VERSION, 'all' );

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

		$webp_image_url = self::get_webp_url( $post->ID );

		$form_fields['webp_image_url'] = [
			'label' => esc_html__( 'WebP URL', 'user-downloads' ),
			'input' => 'html',
			'html'  => ! $webp_image_url ? sprintf(
				/* translators: 1. No webp images found error. 2. Generate .webp image link. */
				'<span class="webp-images-url-error">%1$s</span>&nbsp;
				%2$s
				<a href="#" class="button button-primary js-generate-webp" data-attachment="%3$s">%4$s</a>
				<img src="%5$s" class="preloader webp-images hidden">',
				esc_html__( 'No webp image found.', 'webp-images' ),
				wp_nonce_field( 'webp_images', 'generate_webp_image_' . $post->ID, true, false ),
				esc_attr( $post->ID ),
				esc_html__( 'Generate .webp', 'webp-images' ),
				esc_url( admin_url( 'images/wpspin_light.gif' ) )
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

				$webp_image_path = self::get_webp_url( $attachment_id, 'full', 'path' );

				// No webp image was found, regenerate it
				if ( ! $webp_image_path || empty( $webp_image_path ) ) {

					if ( ! WebP_Images::$cwebp_is_installed ) {
						return printf(
							'</small><em><code>cwebp</code> is not installed.</em></small>'
						);
					}

					?>

					<div class="webp-image-results <?php echo $attachment_id; ?>">
						<?php wp_nonce_field( 'webp_images', 'generate_webp_image_' . $attachment_id, true, false ); ?>

						<a href="#" data-attachment="<?php echo esc_attr( $attachment_id ); ?>" class="button button-secondary js-webp-regen-image-link"><?php esc_html_e( 'Generate .wep Images', 'webp-images' ); ?></a>
					</div>

					<?php

					return;

				}

				if ( ! file_exists( $webp_image_path ) ) {

					return printf(
						'<em>%1$s</em>',
						esc_html__( 'No associated .webp image found.', 'webp-images' )
					);

				}

				$original_file_path = $this->get_file_path( $attachment_id );

				self::file_size_diff( $attachment_id );

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

	/**
	 * Return the webp image URL
	 *
	 * @param  integer $post_id Media element post ID.
	 *
	 * @return string          URL to the media element if found, else false.
	 */
	private static function get_webp_url( $post_id, $size = 'full', $type = 'url' ) {

		$attachment_meta = wp_get_attachment_metadata( $post_id );

		if ( ! $attachment_meta ) {

			return false;

		}

		$file_base = $attachment_meta['file'];

		if ( 'full' !== $size ) {

			if ( array_key_exists( $size, $attachment_meta['sizes'] ) ) {

				$file_base = trailingslashit( dirname( $attachment_meta['file'] ) ) . $attachment_meta['sizes'][ $size ]['file'];

			} // @codingStandardsIgnoreLine

		}

		$file_ext  = pathinfo( $file_base, PATHINFO_EXTENSION );
		$webp_file = 'webp/' . str_replace( $file_ext, 'webp', $file_base );

		$upload_dir = wp_upload_dir();
		$webp_path  = trailingslashit( $upload_dir['basedir'] ) . $webp_file;
		$webp_url   = trailingslashit( $upload_dir['baseurl'] ) . $webp_file;

		if ( ! file_exists( $webp_path ) ) {

			return false;

		}

		return ( 'path' === $type ) ? trailingslashit( $upload_dir['basedir'] ) . $webp_file : trailingslashit( $upload_dir['baseurl'] ) . $webp_file;

	}

	/**
	 * Return the original image URL
	 *
	 * @param  integer $post_id Media element post ID.
	 *
	 * @return string          URL to the media element if found, else false.
	 */
	private static  function get_original_image_url( $post_id, $size = 'full', $type = 'url' ) {

		$attachment_meta = wp_get_attachment_metadata( $post_id );

		if ( ! $attachment_meta ) {

			return false;

		}

		$file_base = $attachment_meta['file'];

		if ( 'full' !== $size ) {

			if ( array_key_exists( $size, $attachment_meta['sizes'] ) ) {

				$file_base = trailingslashit( dirname( $attachment_meta['file'] ) ) . $attachment_meta['sizes'][ $size ]['file'];

			} // @codingStandardsIgnoreLine

		}

		$upload_dir = wp_upload_dir();
		$img_path   = trailingslashit( $upload_dir['basedir'] ) . $file_base;
		$img_url    = trailingslashit( $upload_dir['baseurl'] ) . $file_base;

		return ( 'path' === $type ) ? $img_path : $img_url;

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

	public static function file_size_diff( $attachment_id, $img_size = 'all' ) {

		if ( empty( $attachment_id ) || empty( $attachment_id ) ) {

			return;

		}

		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		if ( ! $attachment_meta ) {

			return _e( 'Error retreiving attachment metadata.', 'webp-images' );

		}

		$base = trailingslashit( dirname( $attachment_meta['file'] ) );

		$image_sizes = [
			'full' => $base . basename( $attachment_meta['file'] ),
		];

		foreach ( $attachment_meta['sizes'] as $size => $img_data ) {

			$image_sizes[ $size ] = $base . $img_data['file'];

		}

		$compression_stats = [];

		foreach ( $image_sizes as $size => $img ) {

			$original_image_path = self::get_original_image_url( $attachment_id, $size, 'path' );
			$webp_image_path     = self::get_webp_url( $attachment_id, $size, 'path' );

			if ( ! file_exists( $original_image_path ) || ! file_exists( $webp_image_path ) ) {

				$compression_stats[ $size ] = [
					'file_not_found' => true,
					'original'       => ! file_exists( $original_image_path ),
					'webp'           => ! file_exists( $webp_image_path ),
					'percent_saved'  => 0,
					'kb_saved'       => 0,
				];

				continue;

			}

			// Calculate percent & kb savings
			$percent_saved = round( ( filesize( $original_image_path ) - filesize( $webp_image_path ) ) / filesize( $original_image_path ) * 100, 2 ) . '%';

			$bytes      = filesize( $original_image_path ) - filesize( $webp_image_path );
			$data_sizes = [ 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];
			$factor     = floor( ( strlen( $bytes ) - 1 ) / 3 );
			$kb_saved   = sprintf( '%.2f', $bytes / pow( 1024, $factor ) ) . ' ' . $data_sizes[ $factor ];

			$compression_stats[ $size ] = [
				'percent_saved' => $percent_saved,
				'kb_saved'      => $kb_saved,
			];

		}

		$original_compression_count = count( $compression_stats );
		$compression_count          = $original_compression_count;

		foreach ( $compression_stats as $item ) {
			if ( isset( $item['file_not_found'] ) && $item['file_not_found'] ) {
				$compression_count--;
			}
		}

		$average_percent_saved = self::get_percent_saved( $compression_stats, 'percent_saved' ) . '%';

		ob_start();

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && 'all' !== $img_size ) {

			if ( isset( $compression_stats[ $img_size ]['file_not_found'] ) ) {

				$error = $compression_stats[ $img_size ]['original'] ? __( 'The original file could not be found.', 'webp-images' ) : __( 'The webp file could not be found.', 'webp-images' );

				printf(
					/* translators: 1. Percent saved. */
					'<span class="webp-error-text">%1$s</span>',
					esc_html( $error )
				);

			} else {

				printf(
					/* translators: 1. Percent saved. */
					esc_html__( 'Percent Saved: %1$s', 'webp-images' ),
					esc_html( $compression_stats[ $img_size ]['percent_saved'] )
				);

				echo '<br />';

				printf(
					/* translators: 1. Space saved. ie 123kb */
					esc_html__( 'Space Saved: %1$s', 'webp-images' ),
					esc_html( $compression_stats[ $img_size ]['kb_saved'] )
				);

			}

			$contents = ob_get_contents();

			ob_get_clean();

			return $contents;

		}

		?>

		<div class="webp-image-results <?php echo $attachment_id; ?>">

			<?php

			$icon = ( $original_compression_count > $compression_count ) ? '<span class="dashicons dashicons-warning webp-error-text missing-webp-images"></span>' : '';

			print( $icon );

			printf(
				/* translators: 1. Number of compressed images. */
				_n(
					'%1$s size compressed',
					'%1$s sizes compressed',
					esc_html( $compression_count ),
					'webp-images'
				),
				esc_html( $compression_count )
			);

			echo ' (<a href="#" data-icon="no" data-attachment="' . esc_attr( $attachment_id ) . '" class="js-toggle-image-size-stats"><span class="dashicons dashicons-plus"></span></a>)<br />';

			printf(
				'Reduced by %1$s',
				$average_percent_saved
			);

			?>

			<small><?php sprintf( /* translators: 1. Percent saved 2. KB saved */ esc_html__( 'Reduced by %1$s (%2$s)', 'webp-images' ), esc_html( $percent_saved ), esc_html( $kb_saved ) ); ?></small>

			<div class="compression-stats hidden <?php echo esc_attr( $attachment_id ); ?>">
				<ul class="sizes">
				<?php

				foreach ( $compression_stats as $size => $stats ) {

					$original_size = $size;
					$size          = ucwords( str_replace( '_', ' ', str_replace( '-', ' ', $size ) ) );

					if ( isset( $stats['file_not_found'] ) ) {

						$error = $stats['original'] ? __( 'The original file could not be found.', 'webp-images' ) : __( 'The webp file could not be found.', 'webp-images' );

						printf(
							'<li>
								<strong>
									<a href="#" data-size="%1$s" data-attachment="%2$s" class="js-regenerate-webp-size">
										<span class="dashicons dashicons-update"></span>
									</a>
									%3$s:
								</strong><br />
								<span class="webp-error-text">%4$s</span>
							</li>',
							esc_attr( $original_size ),
							esc_attr( $attachment_id ),
							esc_html( $size ),
							esc_html( $error )
						);

						continue;

					}

					printf(
						'<li>
							<strong>
								<a href="%1$s" title="%2$s" target="_blank">
									<span class="dashicons dashicons-admin-links"></span>
								</a>
								<a href="#" data-size="%3$s" data-attachment="%4$s" class="js-regenerate-webp-size">
									<span class="dashicons dashicons-update"></span>
								</a>
								<a href="#" class="js-delete-webp-size">
									<span class="dashicons dashicons-no-alt webp-error-text"></span>
								</a>
								%5$s:
							</strong><br />
							%6$s<br />
							%7$s
						</li>',
						esc_url( self::get_webp_url( $attachment_id, $original_size ) ),
						esc_html__( 'View webp for this image size.', 'webp-images' ),
						esc_attr( $original_size ),
						esc_attr( $attachment_id ),
						esc_html( $size ),
						sprintf(
							/* translators: 1. Percent saved. */
							esc_html__( 'Percent Saved: %1$s', 'webp-images' ),
							esc_html( $stats['percent_saved'] )
						),
						sprintf(
							/* translators: 1. Space saved. ie 123kb */
							esc_html__( 'Space Saved: %1$s', 'webp-images' ),
							esc_html( $stats['kb_saved'] )
						)
					);

				}

				?>
				</ul>
			</div>

		</div>

		<?php

		$button_text = ( $original_compression_count === $compression_count ) ? __( 'Regenerate All', 'webp-images' ) : sprintf(
			/* translators: Number of missing webp images. */
			_n(
				'Generate %1$s missing image',
				'Generate %1$s missing images',
				esc_html( $original_compression_count - $compression_count ),
				'webp-images'
			),
			esc_html( $original_compression_count - $compression_count )
		);

		?>

		<div class="row-actions">
			<a href="#" title="<?php esc_attr_e( 'Regenerate .webp images', 'webp-images' ); ?>" class="js-webp-regen-image-link" data-attachment="<?php echo esc_attr( $attachment_id ); ?>"><?php echo esc_html( $button_text ); ?></a> |
			<a href="<?php echo esc_url( self::get_webp_url( $attachment_id ) ); ?>" target="_blank" title="<?php esc_attr_e( 'View .webp image', 'webp-images' ); ?>"><?php esc_html_e( 'View', 'webp-images' ); ?></a> |
			<span class="delete"><a href="#" title="<?php esc_attr_e( 'Delete .webp images', 'webp-images' ); ?>" data-attachment="<?php echo esc_attr( $attachment_id ); ?>"><?php esc_html_e( 'Delete', 'webp-images' ); ?></a></span>
		</div>

		<?php

		$contents = ob_get_contents();
		ob_get_clean();

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			return $contents;

		}

		echo $contents;

	}

	private static function get_percent_saved( $compression_stats, $key ) {

		$data = wp_list_pluck( $compression_stats, $key );

		$data = array_map( function( $item ) {

			$item = str_replace( '%', '', $item );
			$item = str_replace( [ 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ], '', $item );

			return trim( $item );

		}, $data );

		return round( array_sum( $data ) / count( $data ), 2 );

	}

}

<?php

/**
 * WebP_Images_Settings
 *
 * @since 1.0.0
 */

class WebP_Images_Settings {

	/**
	 * Register settings
	 *
	 * @since 1.0.0
	 */
	public static function register_settings() {

		register_setting(
			'webp_images',
			'webp_images',
			[ __CLASS__, 'validate_settings' ]
		);

	}


	/**
	 * validation of settings
	 *
	 * @since   0.0.1
	 * @change  1.0.5
	 *
	 * @param   array  $data  array with form data
	 * @return  array         array with validated values
	 */
	public static function validate_settings( $data ) {

		return [
			'dirs'     => esc_attr( $data['dirs'] ),
			'excludes' => esc_attr( $data['excludes'] ),
			'quality'  => (int) esc_attr( $data['quality'] ),
		];

	}


	/**
	 * add settings page
	 *
	 * @since   0.0.1
	 * @change  0.0.1
	 */
	public static function add_settings_page() {

		add_options_page(
			__( 'WebP Images', 'webp-images' ),
			__( 'WebP Images', 'webp-images' ),
			'manage_options',
			'webp_images',
			[ __CLASS__, 'settings_page' ]
		);

	}

	/**
	 * settings page
	 *
	 * @since   0.0.1
	 * @change  1.0.6
	 *
	 * @return  void
	 */
	public static function settings_page() {

		?>

		<div class="wrap">

			<h2><?php esc_html_e( 'WebP Images Settings', 'cdn-enabler' ); ?></h2>

			<?php self::generate_notice(); ?>

			<form method="post" action="options.php">

				<?php settings_fields( 'webp_images' ); ?>

				<?php $options = WebP_Images::get_options(); ?>

				<table class="form-table">

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Included Directories', 'webp-images' ); ?>
						</th>
						<td>
							<fieldset>
								<label for="webp_images_dirs">
									<input type="text" name="webp_images[dirs]" id="webp_images_dirs" value="<?php echo esc_attr( $options['dirs'] ); ?>" size="64" class="regular-text code" />
									<?php esc_html_e( 'Default: <code>uploads</code>', 'webp-images' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Assets in these directories will attempt deliver webp images. Enter the directories separated by', 'webp-images'); ?> <code>,</code>.
								</p>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Exclusions', 'webp-images' ); ?>
						</th>
						<td>
							<fieldset>
								<label for="webp_images_excludes">
									<input type="text" name="webp_images[excludes]" id="webp_images_excludes" value="<?php echo esc_attr( $options['excludes'] ); ?>" size="64" class="regular-text code" />
									<?php esc_html_e( 'Default: <code>empty</code>', 'webp-images' ); ?>
								</label>
									<p class="description">
										<?php esc_html_e( 'Enter the exclusions (directories or extensions) separated by', 'webp-images'); ?> <code>,</code>.
									</p>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Compression Quality', 'webp-images' ); ?>
						</th>
						<td>
							<fieldset>
								<label for="webp_images_excludes">
									<input type="number" min="10" max="100" name="webp_images[quality]" id="webp_images_quality" value="<?php echo esc_attr( $options['quality'] ); ?>" />
									<?php esc_html_e( 'Default:', 'webp-images'); ?> <code>80</code>
								</label>
								<p class="description"><?php esc_html_e( 'Enter the compression quality.', 'webp-images' ); ?> <code>10 - 100</code></p>
								<p class="description"><?php esc_html_e( '100 being the heighest quality (largest file).', 'webp-images' ); ?></p>
							</fieldset>
						</td>
					</tr>

				</table>

				<?php submit_button(); ?>

			</form>

		</div>

		<?php

	}

	/**
	 * Generate settings page notice.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed markup for the notice.
	 */
	public static function generate_notice() {

		$type    = ! WebP_Images::$cwebp_is_installed ? 'error' : 'info';
		$icon    = ! WebP_Images::$cwebp_is_installed ? 'no-alt' : 'yes';
		$message = ! WebP_Images::$cwebp_is_installed ? sprintf(
			/* translators: */
			__( '<strong>Alert:</strong> <code>cwebp</code> is not installed on this server, and is required to compress and serve images in webp format. Please see <b><a href="%1$s" target="_blank">%2$s</a></b> for installation instructions. If you are on a shared host, this will not be possible.', 'webp-images' ),
			'https://developers.google.com/speed/webp/download',
			__( 'Downloading and Installing WebP', 'webp-images' )
		) : sprintf(
			__( '<strong>Success:</strong> <code>cwep</code> %s is installed.', 'webp-images' ),
			WebP_Images::$cwebp_version
		);

		?>

		<div class="notice notice-<?php echo esc_attr( $type ); ?>">

			<p>
				<span class="dashicons dashicons-<?php echo esc_attr( $type ); ?>"></span>
				<?php echo wp_kses_post( $message ); ?>
			</p>

		</div>

		<?php

	} // @codingStandardsIgnoreLine

}

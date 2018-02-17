( function( $ ) {

	var selectedIDs;

	var webPMediaElement = {

		togglePreloader: function( elementClass ) {

			$( elementClass ).toggle();

		},

		regenerateImage: function( e ) {

			e.preventDefault();

			$( '.webp-images-error' ).remove();

			// Show the preloader
			webPMediaElement.togglePreloader( 'img.preloader.webp-images' );

			var data = {
				'action': 'regenerate_webp_images',
				'_wpnonce': $( '#generate_webp_image' ).val(),
				'_referer': $( 'input[name="_wp_http_referer"]' ).val(),
				'attachment_id': $( this ).data( 'attachment' ),
			};

			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post( ajaxurl, data, function( response ) {

				// Hide the preloader
				webPMediaElement.togglePreloader( 'img.preloader.webp-images' );

				if ( ! response.success ) {

					$( '.webp-images-url-error' ).closest( '.field' ).append( '<p class="webp-images-error">' + webpMediaElementData.errorResponse + '</p>' );

					return;

				}

				var $newRow = '<th scope="row" class="label">' +
					'<label for="attachments-' + data.attachment_id + '-webp_image_url">' +
						'<span class="alignleft">WebP URL</span>' +
						'<br class="clear">' +
					'</label>' +
				'</th>' +
				'<td class="field">' +
					'<label for="attachments-' + data.attachment_id + '-webp_image_url">' +
						'<input type="text" readonly="readonly" class="widefat" id="attachments-' + data.attachment_id + '-webp_image_url" name="attachments[' + data.attachment_id + '][webp_image_url]" value="' + response.data.webpImageURL + '">' +
					'</label>' +
				'</td>';

				$( '.compat-field-webp_image_url' ).html( $newRow );

			} );

		},

		bulkRegenerateImages: function( e ) {

			var selection = $( '#bulk-action-selector-top' ).val();

			if ( 'regen_webp_images' === selection ) {

				e.preventDefault();

			}

			selectedIDs = $( 'input[name="media[]"]:checked' ).map( function() {
				return $( this ).val();
			} );

			webPMediaElement.listTableBulkActionRegenImage( 0 );

			return;

		},

		listTableBulkActionRegenImage: function( key ) {

			var attachmentID = selectedIDs[ key ],
			    $preloader   = $( 'img.preloader.generate-webp-' + attachmentID ).clone();

			$( 'img.preloader.generate-webp-' + attachmentID ).closest( 'td.webp_images' ).html( $preloader );

			// Show the preloader
			webPMediaElement.togglePreloader( 'img.preloader.generate-webp-' + attachmentID );

			$( '#cb-select-' + attachmentID ).click();

			var data = {
				'action': 'regenerate_webp_images',
				'return': 'compression_stats',
				'_wpnonce': $( 'a[data-attachment="' + attachmentID +'"]' ).closest( '#generate_webp_image_' + attachmentID ).val(),
				'_referer': $( 'a[data-attachment="' + attachmentID +'"]' ).closest( 'input[name="_wp_http_referer"]' ).val(),
				'attachment_id': attachmentID,
			};

			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post( ajaxurl, data, function( response ) {

				// Hide the preloader
				webPMediaElement.togglePreloader( 'img.preloader.generate-webp-' + attachmentID );

				var compressionResults = ! response.success ? 'Error' : response.data.compressionResults;

				$( 'img.preloader.generate-webp-' + attachmentID ).after( compressionResults );

				// Stop on the last one.
				if ( ( key + 1 ) === selectedIDs.length ) {

					return;

				}

				webPMediaElement.listTableBulkActionRegenImage( key + 1 );

			} );

		},

		listTableButtonRegenerateImage: function( e ) {

			e.preventDefault();

			var attachmentID = $( this ).data( 'attachment' ),
			    $preloader   = $( 'img.preloader.generate-webp-' + attachmentID ).clone();

			// Remove the button
			$( 'img.preloader.generate-webp-' + attachmentID ).closest( 'td.webp_images' ).html( $preloader );

			// Show the preloader
			webPMediaElement.togglePreloader( 'img.preloader.generate-webp-' + attachmentID );

			var data = {
				'action': 'regenerate_webp_images',
				'return': 'compression_stats',
				'_wpnonce': $( this ).closest( '#generate_webp_image_' + attachmentID ).val(),
				'_referer': $( this ).closest( 'input[name="_wp_http_referer"]' ).val(),
				'attachment_id': attachmentID,
			};

			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post( ajaxurl, data, function( response ) {

				// Hide the preloader
				webPMediaElement.togglePreloader( 'img.preloader.generate-webp-' + attachmentID );

				var compressionResults = ! response.success ? 'Error' : response.data.compressionResults;

				$( 'img.preloader.generate-webp-' + attachmentID ).after( compressionResults );

			} );

		},

	};

	$( document ).on( 'click', '.js-generate-webp', webPMediaElement.regenerateImage );

	$( document ).on( 'click', '.js-generate-webp-table', webPMediaElement.listTableButtonRegenerateImage );
	$( document ).on( 'click', '.js-webp-regen-image-link', webPMediaElement.listTableButtonRegenerateImage );

	$( document ).on( 'submit', '#posts-filter', webPMediaElement.bulkRegenerateImages );

} )( jQuery );

/* global ajaxurl, webpMediaElementData */
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

			var attachmentID = selectedIDs[ key ];

			$( '.webp-image-results.' + attachmentID ).html( webpMediaElementData.preloader );

			// Uncheck the checkbox.
			$( '#cb-select-' + attachmentID ).prop( 'checked', false );

			var data = {
				'action': 'regenerate_webp_images',
				'return': 'compression_stats',
				'_wpnonce': $( 'a[data-attachment="' + attachmentID +'"]' ).closest( '#generate_webp_image_' + attachmentID ).val(),
				'_referer': $( 'a[data-attachment="' + attachmentID +'"]' ).closest( 'input[name="_wp_http_referer"]' ).val(),
				'attachment_id': attachmentID,
			};

			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post( ajaxurl, data, function( response ) {

				var compressionResults = ! response.success ? response.data.message : response.data.compressionResults;

				// Remove the original row actions
				$( '.webp-image-results.' + attachmentID ).next().remove();

				$( '.webp-image-results.' + attachmentID ).html( compressionResults );

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
			    isButton     = $( this ).hasClass( 'button-secondary' ),
			    clickedRow   = $( this ).closest( '.row-actions' );

			if ( isButton ) {

				$( this ).remove();

			}

			$( '.webp-image-results.' + attachmentID ).html( webpMediaElementData.preloader );

			var data = {
				'action': 'regenerate_webp_images',
				'return': 'compression_stats',
				'_wpnonce': $( this ).closest( '#generate_webp_image_' + attachmentID ).val(),
				'_referer': $( this ).closest( 'input[name="_wp_http_referer"]' ).val(),
				'attachment_id': attachmentID,
			};

			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post( ajaxurl, data, function( response ) {

				var compressionResults = ! response.success ? 'Error' : response.data.compressionResults;

				clickedRow.remove();

				$( '.webp-image-results.' + attachmentID ).replaceWith( compressionResults );

			} );

		},

		toggleCompressionStatVisibility: function( e ) {

			e.preventDefault();

			var attachmentID = $( this ).data( 'attachment' ),
			    currentIcon  = $( this ).find( 'span.dashicons' ).attr( 'class' ),
			    nextIcon     = $( this ).data( 'icon' );

			currentIcon  = currentIcon.replace( 'dashicons ', '' ).replace( 'dashicons-', '' );

			$( this ).data( 'icon', currentIcon );
			$( this ).find( 'span.dashicons' ).attr( 'class', 'dashicons dashicons-' + nextIcon );

			$( '.compression-stats.' + attachmentID ).stop().slideToggle();

		},

		regenerateWebpSize: function( e ) {

			e.preventDefault();

			var attachmentID = $( this ).data( 'attachment' ),
			    imageSize    = $( this ).data( 'size' ),
			    $parentLI    = $( this ).closest( 'li' ),
			    $clone       = $( this ).closest( 'strong' ).clone();

			$parentLI.html( webpMediaElementData.preloader );

			var data = {
				'action': 'regenerate_webp_image_size',
				'return': 'compression_stats',
				'attachment_id': attachmentID,
				'size': imageSize,
			};

			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post( ajaxurl, data, function( response ) {

				var compressionResults = ! response.success ? response.data.message : response.data.compressionResults;

				$parentLI.html( '<br />' + compressionResults );
				$parentLI.prepend( $clone );

			} );

		}

	};

	$( document ).on( 'click', '.js-generate-webp', webPMediaElement.regenerateImage );

	$( document ).on( 'click', '.js-webp-regen-image-link', webPMediaElement.listTableButtonRegenerateImage );

	$( document ).on( 'click', '.js-toggle-image-size-stats', webPMediaElement.toggleCompressionStatVisibility );

	$( document ).on( 'click', '.js-regenerate-webp-size', webPMediaElement.regenerateWebpSize );

	$( document ).on( 'submit', '#posts-filter', webPMediaElement.bulkRegenerateImages );

} )( jQuery );

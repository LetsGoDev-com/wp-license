(function ($) {

	const LetsgoLicense = {
		/**
		 * Start the engine.
		 *
		 * @since 2.0.0
		 */
		init: function () {

			// Document ready
			$(document).ready(LetsgoLicense.ready);

			// Page load
			$(window).on('load', LetsgoLicense.load);
		},

		/**
		 * Page load.
		 *
		 * @since 2.0.0
		 */
		load: function () {

		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function () {

			// Bind all actions.
			LetsgoLicense.bindUIActions();
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function () {

			// OpenPopup when the user click "Manage License"
			$('.letsgodev_license').on( 'click', function(e) {
				e.preventDefault();
				
				const slug = $(this).data('slug');
				LetsgoLicense.openPopup(slug);
			});

			// Unlink the license from Letsgodev
			$(document).on('click', '#letsgodev_button_unlink', function(e) {
				e.preventDefault();
				const slug = $(this).data('slug');

				LetsgoLicense.unlinkLicense(slug);
			});

			// Link the license from Letsgodev
			$(document).on('click', '#letsgodev_button_link', function(e) {
				e.preventDefault();
				const slug = $(this).data('slug');

				LetsgoLicense.linkLicense(slug);
			});

			// Check if exist some update
			$(document).on('click', '#letsgodev_button_update', function(e) {
				e.preventDefault();
				const slug = $(this).data('slug');

				LetsgoLicense.checkUpdateLicense(slug);
			});

			// ClosePopup when the user click "close popup"
			$('#letsgodev_box_close').on( 'click', function(e) {
				e.preventDefault();

				const slug = $('#letsgodev_box_license').attr('class');
				LetsgoLicense.closePopup(slug);
			});

			// ClosePopup when the user push ESC
			$(document).keyup(function(e) {
				if (e.keyCode == 27) {
					const slug = $('#letsgodev_box_license').attr('class');
					LetsgoLicense.closePopup(slug);
				}
			});
		},
		/**
		 * Open Popup Event
		 * 
		 * @since 1.0.0
		 */
		openPopup: function(slug = '') {
			const loading = '<div style="text-align:center;">' + letsgo.loading_html + '</div>';

			// Remove all the messages
			$('#letsgodev_box_message').removeClass('error').removeClass('success').removeClass('warning').removeClass('info');
			
			// Put the class
			$('#letsgodev_box_license').addClass(slug);

			// Open the popup
			$('#letsgodev_box_license').slideDown('fast');

			// Put loading icon in message section
			$('#letsgodev_box_message').html(loading);
			
			// Empty button content
			$('#letsgodev_box_button').empty();

			// We prepare the information to Ajax event
			const data = {
				action: slug + '_get_status',
				wpnonce: letsgo.wpnonce
			};

			const onSuccess = function (response) {
				
				$('#letsgodev_box_message').addClass(response.data.box_class);
				$('#letsgodev_box_message').html(response.data.box_message);

				if( response.success ) {

					let box_button, box_update = '';

					if( response.data.is_active ) {
						box_button = '<button id="letsgodev_button_unlink" class="letsgodev_primary_button" data-slug="' + slug + '">' + letsgo.unlink_text + '</button>';
						box_update = '<button id="letsgodev_button_update" class="letsgodev_secondary_button" data-slug="' + slug + '" title="'+letsgo.update_text+'"><span class="dashicons dashicons-update"></span></button>';
					}
					
					if( response.data.is_unlink ) {
						box_button = '<button id="letsgodev_button_link" class="letsgodev_primary_button" data-slug="' + slug + '">' + letsgo.link_text + '</button>';
					}	
					
					$('#letsgodev_box_button').html(box_button + box_update );
				}
			};

			const onError = function(jqxhr, textStatus, error) {
				console.log('License Ajax error: ' + textStatus + ' - ' + error);
			};

			if( letsgo && letsgo.ajax_url ) {
				LetsgoLicense.sendAjax(data, onSuccess, onError);
			}
		},
		/**
		 * Close Popup Event
		 * 
		 * @since 1.0.0
		 */
		closePopup : function(slug = '') {
			$('#letsgodev_box_license').removeClass(slug);
			$('#letsgodev_box_license').slideUp('fast');
		},

		/**
		 * Unlink License Event
		 * 
		 * @since 1.0.0
		 */
		unlinkLicense : function(slug = '') {
			const loading = '<div style="text-align:center;">' + letsgo.loading_html + '</div>';

			// Remove all the messages
			$('#letsgodev_box_message').removeClass('error').removeClass('success').removeClass('warning').removeClass('info');
			
			// Put loading icon in message section
			$('#letsgodev_box_message').html(loading);
			
			// Empty button content
			$('#letsgodev_box_button').empty();

			// We prepare the information to Ajax event
			const data = {
				action: slug + '_set_unlink',
				wpnonce: letsgo.wpnonce
			};

			const onSuccess = function (response) {
				
				$('#letsgodev_box_message').addClass(response.data.box_class);
				$('#letsgodev_box_message').html(response.data.box_message);

				if( response.success ) {
					setTimeout(window.location.reload.bind(window.location), 800);
				}
			};

			const onError = function(jqxhr, textStatus, error) {
				console.log('License Ajax unlink error: ' + textStatus + ' - ' + error);
			};

			if( letsgo && letsgo.ajax_url ) {
				LetsgoLicense.sendAjax(data, onSuccess, onError);
			}
		},

		/**
		 * Link License Event
		 * 
		 * @since 1.0.0
		 */
		linkLicense : function(slug = '') {
			const loading = '<div style="text-align:center;">' + letsgo.loading_html + '</div>';

			// Remove all the messages
			$('#letsgodev_box_message').removeClass('error').removeClass('success').removeClass('warning').removeClass('info');
			
			// Put loading icon in message section
			$('#letsgodev_box_message').html(loading);
			
			// Empty button content
			$('#letsgodev_box_button').empty();

			// We prepare the information to Ajax event
			const data = {
				action: slug + '_set_link',
				wpnonce: letsgo.wpnonce
			};

			const onSuccess = function (response) {
				
				$('#letsgodev_box_message').addClass(response.data.box_class);
				$('#letsgodev_box_message').html(response.data.box_message);

				if( response.success ) {
					setTimeout(window.location.reload.bind(window.location), 800);
				}
			};

			const onError = function(jqxhr, textStatus, error) {
				console.log('License Ajax link error: ' + textStatus + ' - ' + error);
			};

			if( letsgo && letsgo.ajax_url ) {
				LetsgoLicense.sendAjax(data, onSuccess, onError);
			}
		},

		/**
		 * Check if exist some update
		 * @param  {String} slug
		 * @return mixed
		 */
		checkUpdateLicense: function( slug = '' ) {
			const loading = '<div style="text-align:center;">' + letsgo.loading_html + '</div>';

			// Remove all the messages
			$('#letsgodev_box_message').removeClass('error').removeClass('success').removeClass('warning').removeClass('info');
			
			// Put loading icon in message section
			$('#letsgodev_box_message').html(loading);
			
			// Empty button content
			$('#letsgodev_box_update').empty();

			// We prepare the information to Ajax event
			const data = {
				action: slug + '_get_update',
				wpnonce: letsgo.wpnonce
			};

			const onSuccess = function (response) {

				$('#letsgodev_box_message').addClass(response.data.box_class);
				$('#letsgodev_box_message').html(response.data.box_message);

				if( response.data.refresh ) {
					setTimeout(window.location.reload.bind(window.location), 1000);
				}
			};

			const onError = function(jqxhr, textStatus, error) {
				console.log('License Ajax update error: ' + textStatus + ' - ' + error);
			};

			if( letsgo && letsgo.ajax_url ) {
				LetsgoLicense.sendAjax(data, onSuccess, onError);
			}
		},
		
		/**
		 * Ajax event
		 * @param  json data
		 * @param  function success_bt
		 * @param  function error_bt
		 * @return mixed
		 */
		sendAjax: function(data, success_bt, error_bt) {

			let ajax = {
					url: letsgo.ajax_url,
					data: data,
					cache: false,
					type: 'POST',
					dataType: 'json',
					timeout: 	30000
				},
				success = success_bt || false,
				error = error_bt || false;
			
			// Set success callback if supplied.
			if (success) {
				ajax.success = success;
			}

			// Set error callback if supplied.
			if (error) {
				ajax.error = error;
			}

			$.ajax(ajax);
		}
	};

	LetsgoLicense.init();
	// Add to global scope.
	window.letsgo_license = LetsgoLicense;
})(jQuery);

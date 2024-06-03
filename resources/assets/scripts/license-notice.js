(function ($) {

	const LetsgoLicenseNotice = {
		/**
		 * Start the engine.
		 *
		 * @since 2.0.0
		 */
		init: function () {

			// Document ready
			$(document).ready(LetsgoLicenseNotice.ready);

			// Page load
			$(window).on('load', LetsgoLicenseNotice.load);
		},

		/**
		 * Page load.
		 *
		 * @since 2.0.0
		 */
		load: function () {
			// Actions
			LetsgoLicenseNotice.executeUIActions();
		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function () {
			// Bind all actions.
			LetsgoLicenseNotice.bindUIActions();
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function () {
		},
		/**
		 * Actions
		 * 
		 * @since 1.0.0
		 */
		executeUIActions: function() {

			// Check if exists some redirect
			$( '.letsgodev_license_redirect' ).each( function() {
				const redirect = $(this).data('redirect');

				if( redirect != null && redirect.length ) {
					window.setTimeout( function() {
						window.location.href = redirect;
					}, 2000 );
				}
			});

		}
	};

	LetsgoLicenseNotice.init();
	// Add to global scope.
	window.letsgo_license_notice = LetsgoLicenseNotice;
})(jQuery);

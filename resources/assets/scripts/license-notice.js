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
			
			// If it has redirect
			if( letsgoNotice.hasRedirect ) {

				// Redirect after 3 seconds
				window.setTimeout(function(){
					window.location.href = letsgoNotice.redirect;
				}, 3000 );
			}
		}
	
	};

	LetsgoLicenseNotice.init();
	// Add to global scope.
	window.letsgo_license_notice = LetsgoLicenseNotice;
})(jQuery);

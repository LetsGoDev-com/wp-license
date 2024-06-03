<div class="notice notice-success is-dismissible">
	<p><?php
		printf(
			'<b>%s</b> : %s %s',
			ucwords( $name ),
			\esc_html__( 'Plugin activated and ready to use.', 'letsgodev' ),
			$hasRedirect ? sprintf( '&nbsp;%s %s', \esc_html__( 'Redirecting...', 'letsgodev' ), $loadingHtml ) : ''
		);
	?></p>

	<?php if( $hasRedirect ) : ?>
		<div class="letsgodev_license_redirect" data-redirect="<?php echo $redirect; ?>"></div>
	<?php endif; ?>
</div>
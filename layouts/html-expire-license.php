<div class="notice notice-warning">
	<p><?php
		printf(
			'<b>%s</b> : %s',
			ucwords( $name ),
			sprintf(
				esc_html__( 'Your license expired, please go to %s to renew it', 'letsgodev' ),
				'<a href="https://www.letsgodev.com" target="_blank">https://www.letsgodev.com</a>'
			)
		);
	?></p>
</div>
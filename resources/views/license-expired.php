<div class="notice notice-warning">
	<p><?php
		printf(
			'<b>%s</b> : %s',
			ucwords( $name ),
			sprintf(
				esc_html__( 'Your license expired on %s, please go to %s to renew it', 'letsgodev' ),
				$expire,
				'<a href="https://www.letsgodev.com" target="_blank">https://www.letsgodev.com</a>'
			)
		);
	?></p>
</div>
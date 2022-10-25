<div class="updated" style="display: block;">

	<h1><?php echo $name; ?></h1>

	<div style="margin-bottom: 0.6em; font-size: 13px;">
		<?php echo esc_html__( 'Please enter your license code', 'letsgodev' ); ?>
	</div>

	<?php if ( ! empty( $error ) ) : ?>
		<code><?php echo $error; ?></code>
	<?php endif; ?>


	<form method="post" style="margin-bottom: 0.6em;">

		<input type="text" name="<?php echo $slug; ?>_license_key" value="" placeholder="<?php echo esc_html__( 'License Code', 'letsgodev' ); ?>" style="width: 50%; margin-right: 10px;">

		<button type="submit" class="button button-primary" title="<?php echo esc_html__( 'Submit', 'letsgodev'); ?>"><?php echo esc_html__( 'Submit', 'letsgodev' ); ?></button>

	</form>

	<p><small><a href="https://www.letsgodev.com/documentation/where-do-i-find-my-license-code/" rel="noopener" target="_blank"><?php echo esc_html__( 'Where do I find my License Code?', 'letsgodev'); ?></a> | <a href="https://www.letsgodev.com/help-pages/manager-license-from-your-site/" target="_blank">
			<?php echo esc_html__( 'Manager License from your site', 'letsgodev' ); ?>
		</a></small></p>

</div>
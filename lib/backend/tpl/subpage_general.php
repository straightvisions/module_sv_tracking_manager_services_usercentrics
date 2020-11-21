<div class="sv_setting_subpage">
	<h2><?php _e('General', 'sv100'); ?></h2>
	<div class="sv_setting_flex">
		<?php
		echo $module->get_setting( 'id' )->form();
		echo $module->get_setting( 'activate' )->form();
		echo $module->get_setting( 'activate_shield' )->form();
		?>
	</div>
	<div class="sv_setting_flex">
		<?php
			echo $module->get_setting( 'api_version' )->form();
		?>
	</div>
</div>
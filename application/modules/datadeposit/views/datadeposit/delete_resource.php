	<h2><?php echo t('js_confirm_delete'); ?></h2>	
	<?php echo form_open(current_url());?>
    <?php 
		echo form_radio('answer', 'No'), t('no'), '<br />';
		echo form_radio('answer', 'Yes'), t('yes'), '<br />';
		echo form_submit('submit', t('submit'));
	
		echo form_close();
	?>


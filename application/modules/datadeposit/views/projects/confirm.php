	<h2>Are you sure?</h2>	
	<?php echo form_open("projects/confirm/{$id}");?>
    <?php 
		echo form_radio('answer', 'No'), 'No, do not delete this project.', '<br />';
		echo form_radio('answer', 'Yes'), 'Yes, delete this project.', '<br />';
		echo form_submit('submit', t('submit'));
	
		echo form_close();
	?>


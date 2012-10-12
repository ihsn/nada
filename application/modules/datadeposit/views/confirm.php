	<h2>Are you sure?</h2>	
	<?php echo form_open("datadeposit/confirm/{$id}");?>
    <?php 
		echo form_radio('answer', 'No'), 'No, do not delete this project.', '<br />';
		echo form_radio('answer', 'Yes'), 'Yes, delete this project.', '<br />';
		echo form_hidden('confirm', t('submit'));
	?>
		   <div class="button">
        <span>Submit</span>
    </div>  
    <?php 
		echo form_close();
	?>


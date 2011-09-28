<?php include dirname(__FILE__).'/../managefiles/tabs.php';?>
<div class="content-container" style="margin-top:20px;">

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php echo form_open_multipart(current_url(), array('class'=>'form') ); ?>
<div class="field">           
	<label for="link_indicator"><img src="images/page_white_database.png" border="0"/> <?php echo t('indicator_database'); ?></label>
    <input class="input-flex" name="link_indicator" type="text" id="link_indicator" value="<?php echo get_form_value('link_indicator',isset($link_indicator) ? $link_indicator : '') ; ?>"/>
</div>

<div class="field">           
    <label for="link_study"><img src="images/page_white_world.png" border="0"/> <?php echo t('study_website'); ?></label>
    <input class="input-flex" name="link_study" type="text" id="link_study" value="<?php echo get_form_value('link_study',isset($link_study) ? $link_study : '') ; ?>"/>
</div>
        
<div class="field">
	<input type="submit" name="submit" id="submit" value="<?php echo t('update'); ?>" />
</div>
<?php echo form_close(); ?>    
</div>
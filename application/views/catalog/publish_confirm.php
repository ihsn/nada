<div class="content-container">
<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php if ($publish==1):?>
<h1 class="page-title"><?php echo t('confirm_publish'); ?></h1>
<?php else:?>
<h1 class="page-title"><?php echo t('confirm_unpublish'); ?></h1>
<?php endif;?>

<?php if ( isset($items)):?>
	<ul class="bullet1">
	<?php foreach ($items as $item):?>
    	<li><?php echo $item;?></li>
    <?php endforeach;?>
    </ul>
<?php endif;?>

<form method="post" class="form">
<div class="field">
	<div><?php echo ($publish==1) ? t('confirm_publish_records') : t('confirm_unpublish_records');?></div>
	<input type="submit" name="submit" id="submit" value="<?php echo t('yes'); ?>" />
	<input type="submit" name="cancel" id="cancel" value="<?php echo t('no'); ?>" />
    <input type="hidden" name="destination"  value="<?php echo form_prep($this->input->get_post('destination')); ?>"/>
</div>
</form>

</div>

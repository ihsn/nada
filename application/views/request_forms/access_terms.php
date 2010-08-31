<?php echo form_open(current_url(), array('class'=>'form'));?>
<h1><?php echo t('title_terms_and_conditions');?></h1>
  <?php echo t('terms_text');?>
  <input type="submit" name="accept" value="<?php echo t('accept');?>"/>
</div>
<?php echo form_close(); ?>
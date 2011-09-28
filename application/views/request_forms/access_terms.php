<?php echo form_open(current_url(), array('class'=>'form'));?>
<div>
<h1><?php echo t('title_terms_and_conditions');?></h1>
<div class="nl"><?php echo t('terms_text');?></div>
  <input type="submit" name="accept" value="<?php echo t('accept');?>"/>
  <?php if (isset($this->ajax)):?>
  <input type="hidden" name="ajax" value="true"/>
  <?php endif;?>
  <?php /* ?>
  <input type="button" name="cancel" value="<?php echo t('cancel');?>" onclick="window.history.go(-1);"/>
  <?php */?>
</div>
<?php echo form_close(); ?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<script type="text/javascript">
var increment =1;
$(function() {
	$('input[name="add_url"]').click(function() {
		urls=$('#urls');
		urls.append("<input type='text' style='width:20%' name='url[]' value='' /><br />");
	});
});
</script>

<div class="container body-container" >
<?php if (!isset($hide_form)):?>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-name"><?php echo ($this->uri->segment(3)=='edit') ? t('edit_permission') : t('add_permission');?></h1>
<?php endif; ?>

<?php echo form_open(current_url(), array('class'=> 'form', 'autocomplete'=>'off'));?>    

      <div class="form-group">
	      <label for="title"><?php echo t('title');?></label>
	      <?php echo form_input('label', get_form_value('label',isset($label) ? $label : '') ,'class="form-control"');?>
      </div>
      
      <div class="form-group">
	      <label for="section"><?php echo t('section');?></label>
	      <?php echo form_input('section', get_form_value('section',isset($section) ? $section : ''),'class="form-control"');?>
      </div>

      <div class="form-group">
	      <label for="description"><?php echo t('description');?></label>
	      <?php echo form_input('description', get_form_value('description',isset($description) ? $description : ''),'class="form-control"');?>
      </div>
      
      <div class="form-group">
	      <label for="weight"><?php echo t('weight');?></label>
	      <?php echo form_input('weight', get_form_value('weight',isset($weight) ? $weight : ''),'class="form-control"');?>
      </div>
      
 	<div id="urls" class="form-group">
		<label for="url"><?php echo t('urls');?></label>
        <?php $urls=get_form_value('url',isset($urls) ? $urls : array());?>
		<?php if (count($urls)==0){$urls=array('');}?>
		<?php $x = true; foreach($urls as $url): ?>
            <?php echo form_input("url[]", $url, 'class="form-control url" '); ?>
            <?php if ($x === true): ?>
                    <input type="button" value="+" name="add_url" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;">
            <?php endif; ?>
            <?php $x = false; ?>
            <br />
       <?php endforeach; ?>
   </div>
   <br />
        <?php echo form_submit('submit', t('submit'), 'class="btn btn-primary"');?>
        <a href="<?php echo site_url('admin/permissions/manage'); ?>" class="button">Cancel</a>

<?php echo form_close(); ?>

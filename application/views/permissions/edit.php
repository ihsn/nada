<script type="text/javascript">
var increment =1;
$(function() {
	$('input[name="add_url"]').click(function() {
		urls=$('#urls');
		urls.append("<input type='text' style='width:20%' name='new["+ (increment++) +"][]' value='' /><br />");
	});
});
</script>
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />

<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<h1 class="page-name"><?php echo t('edit_permission');?></h1>
<?php endif; ?>

<?php echo form_open(current_url(), array('class'=> 'form', 'autocomplete'=>'off'));?>    

      <div class="field">
	      <label for="title"><?php echo t('title');?></label>
	      <?php echo form_input('title', $row[0]->label,'style="width:40%"');?>
      </div>
      
      <div class="field">
	      <label for="section"><?php echo t('section');?></label>
	      <?php echo form_input('section', $row[0]->section,'style="width:40%"');?>
      </div>

      <div class="field">
	      <label for="description"><?php echo t('description');?></label>
	      <?php echo form_input('description', $row[0]->description,'style="width:40%"');?>
      </div>
      
      <div class="field">
	      <label for="weight"><?php echo t('weight');?></label>
	      <?php echo form_input('weight', $row[0]->weight,'style="width:40%"');?>
      </div>
      
 	<div id="urls" class="field">
		<label for="url"><?php echo t('urls');?></label>
	<?php $x = true; foreach($urls as $url): ?>
        <?php echo form_input("url[{$url->id}][]", $url->url, 'class="url" style="width:20%"'); ?>
        <?php if ($x === true): ?>
        		<input type="button" value="+" name="add_url" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;">
        <?php endif; ?>
        <?php $x = false; ?>
        <br />
   <?php endforeach; ?>
   </div>
   <br />
        <?php echo form_submit('submit', t('submit'));?>
        <a href="<?php echo site_url('admin/permissions/admin'); ?>" class="button">Cancel</a>

<?php echo form_close(); ?>

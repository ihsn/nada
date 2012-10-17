<div class="content-container">
<?php include 'catalog_page_links.php'; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('replace_ddi');?></h1>

<div class="note">
Note: DDI Replace won't replace a DDI if another DDI exists with the same ID.
</div>

<div>
	<?php echo form_open_multipart('admin/catalog/replace_ddi/'.(int)$id, array('class'=>'form')	 );?>
    
    <div class="field">
    	<label for="target"><?php echo t('msg_select_source');?> - Select the Study to be replaced</label>
        <?php echo form_dropdown('target', $surveys,$id);?>
    </div>
    
    <div class="field">
    	<label for="userfile"><?php echo t('msg_select_ddi');?></label>
        <input  type="file" name="userfile" id="userfile" size="60"/>
    </div>
    
	<?php echo form_submit('submit',t('submit')); ?>
    <?php echo anchor('admin/catalog',t('cancel'),array('class'=>'button'));?>

    <?php echo form_close();?>
</div>
</div>
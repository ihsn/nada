<div class="content-container">
<?php include 'catalog_page_links.php'; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('replace_ddi');?></h1>
<div>
	<?php echo form_open_multipart('admin/catalog/replace_ddi', array('class'=>'form')	 );?>
    
    <div class="field">
    	<label for="source"><?php echo t('msg_select_source');?> - Select the Study to be replaced</label>
        <?php echo form_dropdown('source', $surveys);?>
    </div>
    
    <div class="field">
    	<label for="target"><?php echo t('msg_select_target');?> - Select the study to replace it with</label>
        <?php echo form_dropdown('target', $surveys);?>
    </div>
    
    <div class="field">
        <label for="overwrite" class="desc"><input type="checkbox" name="overwrite" id="overwrite" checked="checked"  value="yes"/> <?php echo t('move_external_resources');?></label>
        <label for="overwrite" class="desc"><input type="checkbox" name="overwrite" id="overwrite" checked="checked"  value="yes"/> <?php echo t('update_citation_references');?></label>
        <label for="overwrite" class="desc"><input type="checkbox" name="overwrite" id="overwrite" checked="checked"  value="yes"/> <?php echo t('delete_ddi_after_move');?></label>
    </div>

	<?php echo form_submit('submit',t('submit')); ?>
    <?php echo anchor('admin/catalog',t('cancel'),array('class'=>'button'));?>

    <?php echo form_close();?>
</div>
</div>

<pre>
1. Copy source DDI to target DDI's folder
2. Update DB with DDI path
x. Copy resources from Source to Target folder
x. delete source study from db
3. run ddi refresh to update study/variable info in db
x. Delete target DDI
</pre>
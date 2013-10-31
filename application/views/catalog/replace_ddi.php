<div class="content-container">
<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('replace_ddi');?></h1>

<div>
	<?php echo form_open_multipart('admin/catalog/replace_ddi/'.(int)$id, array('class'=>'form')	 );?>
    <input type="hidden" name="id" value="<?php echo $survey['id'];?>"/>
    
    <div class="field" style="background:gainsboro;padding:10px;">
        <?php //echo form_dropdown('target', $surveys,$id);?>
        <div style="font-weight:bold"><?php echo $survey['titl'];?></div>
        <div><?php echo $survey['nation'];?>, <?php echo $survey['data_coll_start'];?></div>
    </div>
    
    <div class="field">
    	<label for="userfile"><?php echo t('msg_select_ddi');?></label>
        <input  type="file" name="userfile" id="userfile" size="60"/>
    </div>
    
	<?php echo form_submit('submit',t('submit')); ?>
    <?php echo anchor('admin/catalog/edit/'.$survey['id'],t('cancel'));?>

    <?php echo form_close();?>
</div>
</div>
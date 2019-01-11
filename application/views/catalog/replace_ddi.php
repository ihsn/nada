<div class="container-fluid">
<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('replace_ddi');?></h1>

<div style="max-width:400px;">
	<?php echo form_open_multipart('admin/catalog/replace_ddi/'.(int)$id, array('class'=>'form')	 );?>
    <input type="hidden" name="id" value="<?php echo $survey['id'];?>"/>
    
    <div class="field" style="background:gainsboro;padding:10px;margin-bottom:15px;">
        <?php //echo form_dropdown('target', $surveys,$id);?>
        <div style="font-weight:bold"><?php echo $survey['title'];?></div>
        <div><?php echo $survey['nation'];?>, <?php echo $survey['year_start'];?></div>
    </div>
    
    <div class="field">
    	<label for="userfile"><?php echo t('msg_select_ddi');?></label>
        <input  type="file" name="userfile" id="userfile" size="60"/>
    </div>
    
    <div style="margin-top:15px;">
	<?php echo form_submit('submit',t('submit'),'class="btn btn-primary"'); ?>
    <?php echo anchor('admin/catalog/edit/'.$survey['id'],t('cancel'));?>
</div>

    <?php echo form_close();?>
</div>
</div>
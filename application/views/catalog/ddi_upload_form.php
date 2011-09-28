<?php
//get repositories list by user access
$user_repositories=$this->ion_auth->get_user_repositories();	
$repositories_list=array();
foreach($user_repositories as $repo)
{
	$repositories_list[$repo["repositoryid"]]=$repo['title'];
}
?>
<div class="content-container">
<?php include 'catalog_page_links.php'; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('upload_ddi');?></h1>
<div>
	<?php echo form_open_multipart('admin/catalog/do_upload', array('class'=>'form')	 );?>
    <div class="field">
    	<label for="repositoryid"><?php echo t('msg_select_repository');?></label>
		<?php echo form_dropdown('repositoryid', $repositories_list); ?>
    </div>    
    <div class="field">
    	<label for="userfile"><?php echo t('msg_select_ddi');?></label>
        <input  type="file" name="userfile" id="userfile" size="60"/>
    </div>
    <div class="field">
        <label for="overwrite" class="desc"><input type="checkbox" name="overwrite" id="overwrite" checked="checked"  value="yes"/> <?php echo t('ddi_overwrite_exist');?></label>
    </div>

	<?php echo form_submit('submit',t('submit')); ?>
    <?php echo anchor('admin/catalog',t('cancel'),array('class'=>'button'));?>

    <?php echo form_close();?>
</div>
</div>
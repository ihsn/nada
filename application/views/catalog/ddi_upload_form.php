<?php
//get repositories list by user access
$user_repositories=$this->acl->get_user_repositories();	
$repositories_list=array();
foreach($user_repositories as $repo)
{
	$repositories_list[$repo["repositoryid"]]=$repo['title'];
}

//active repository
$active_repository='';

//get active repo
if (isset($active_repo) && $active_repo!=NULL)
{
	$active_repository=$active_repo->repositoryid;
}

//get max upload/post limits
$max_upload = (int)(ini_get('upload_max_filesize'));
$max_post = (int)(ini_get('post_max_size'));

$max_limit=$max_upload;

if ($max_upload>$max_post){
	$max_limit=$max_post;
}

?>
<div class="content-container ddi-upload">
<?php //include 'catalog_page_links.php'; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('upload_ddi');?></h1>
<div style="width:500px;">
	<?php echo form_open_multipart('admin/catalog/do_upload', array('class'=>'form')	 );?>
    <div class="field">
    	<label for="repositoryid"><?php echo t('msg_select_repository');?></label>
		<?php echo form_dropdown('repositoryid', $repositories_list,$active_repository); ?>
    </div>    

<fieldset>
<legend><?php echo t('msg_select_ddi');?> <span class="max-file-size">(<?php echo t('max_upload_limit') ." ".$max_limit;?>MB)</span></legend>
    <div class="field">
    	<label for="userfile"></label>
        <input  class="file"  type="file" name="userfile" id="userfile" size="60"/>
        <div class="description"></div>
    </div>
</fieldset>

<fieldset>
<legend><?php echo t('msg_select_rdf');?></legend>
    <div class="field">
        <input class="file" type="file" name="rdf" id="rdf-file" size="60"/>
    </div>
</fieldset>

     <div class="field" style="margin-top:10px;">
        <label for="overwrite" class="desc"><input type="checkbox" name="overwrite" id="overwrite" value="yes"/> <?php echo t('ddi_overwrite_exist');?></label>
    </div>

	<?php echo form_submit('submit',t('submit')); ?>
    <?php echo anchor('admin/catalog',t('cancel'));?>

    <?php echo form_close();?>
</div>
</div>
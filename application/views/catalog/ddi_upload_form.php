<style type="text/css">
.active-repo{background:gainsboro;padding:5px;}
</style>
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
<div class="container-fluid content-container ddi-upload">
<?php //include 'catalog_page_links.php'; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('add_study_to_collection');?> 
    <!--<span class="active-repo"><?php echo $repositories_list[$active_repository];?></span>-->
</h1>

<div style="width:500px;">
	<?php echo form_open_multipart("", array('class'=>'form')	 );?>
    <input type="hidden" name="repositoryid" value="<?php echo $active_repository;?>"/>

  <div class="form-group">
    <label for="userfile"><?php echo t('msg_select_ddi');?></label>
    <input  class="form-control-file"  type="file" name="userfile" id="userfile" size="60"/>
    <small id="ddi-help" class="form-text text-muted"><span class="max-file-size">(<?php echo t('max_upload_limit') ." ".$max_limit;?>MB)</span></small>
  </div>


  <div class="form-group">
    <label for="userfile"><?php echo t('msg_select_rdf');?></label>
    <input class="form-control-file" type="file" name="rdf" id="rdf-file" size="60"/>    
  </div>

<div class="form-group" style="margin-top:10px;">
<label for="overwrite" class="desc">
    <input type="checkbox" name="overwrite" id="overwrite" value="yes"/> <?php echo t('ddi_overwrite_exist');?>
</label>
</div>

	<?php echo form_submit('submit',t('submit'), 'class="btn btn-primary"'); ?>
    <?php echo anchor('admin/catalog',t('cancel'));?>

    <?php echo form_close();?>
</div>
</div>
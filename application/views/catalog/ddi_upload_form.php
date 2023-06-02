<style type="text/css">
.active-repo{background:gainsboro;padding:5px;}
</style>
<?php
//active repository
$active_repository='';

//get active repo
if (isset($active_repo) && $active_repo!=NULL){
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

<?php $error=$this->session->flashdata('error'); ?>
<?php if ($error!=""):?>
        <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                </button><?php echo $error;?>
        </div>
<?php endif;?>

<?php $message=$this->session->flashdata('message');?>
<?php if ($message!=""):?>
        <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                </button><?php echo $message;?>
        </div>
<?php endif;?>

<?php echo validation_errors(); ?>

<div class="col-md-6">

  <h1 class="page-title"><?php echo t('add_study_to_collection');?>
      <!--<span class="active-repo"><?php // echo $repositories_list[$active_repository];?></span>-->
  </h1>

   <?php echo form_open_multipart("", array('class'=>'form')	 );?>

   <input type="hidden" name="repositoryid" value="<?php echo $active_repository;?>"/>

      <div class="form-group">
         <label for="userfile"><?php echo t('msg_select_ddi');?></label>
         <input  class="form-control-file"  type="file" name="userfile" id="userfile" size="60"/>
         <small id="ddi-help" class="form-text text-muted"><span class="max-file-size">(<?php echo t('max_upload_limit') ." ".$max_limit;?>MB)</span></small>
      </div>

      <div class="form-group">
         <label for="rdf-file"><?php echo t('msg_select_rdf');?></label>
         <input class="form-control-file" type="file" name="rdf-file" id="rdf-file" size="60"/>
         <small id="ddi-help" class="form-text text-muted"><span class="max-file-size">(<?php echo t('max_upload_limit') ." ".$max_limit;?>MB)</span></small>
      </div>

      <div class="form-group" style="margin-top:10px;">
         <input type="checkbox" name="overwrite" id="overwrite" value="yes" <?php echo set_checkbox('overwrite','yes'); ?>" />
         <label for="overwrite" class="desc"><?php echo t('ddi_overwrite_exist');?></label>
      </div>

	<?php echo form_submit('submit',t('submit'), 'class="btn btn-primary"'); ?>
    <?php echo anchor('admin/catalog',t('cancel'));?>

    <?php echo form_close();?>
</div>

<div class="col-md-6" style="background:#eeeeee; padding:20px;">
  <h2 style="margin:0px;margin-bottom:15px;"><?php echo t('Create new study');?> </h2>


  <form class="form"  method="get" action="<?php echo site_url('admin/catalog/create');?>">

    <div class="form-group">
      <label for="exampleInputEmail1">Select data type</label>
      <select name="type" class="form-control">
        <option value="survey">Microdata</option>
        <!--<option value="geospatial" >Geospatial</option>-->
        <option value="document">Document</option>
        <option value="table">Table</option>
        <option value="timeseries">Timeseries</option>
        <option value="script">Script</option>
        <option value="image">Image</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Create</button>
</form>

</div>

</div>

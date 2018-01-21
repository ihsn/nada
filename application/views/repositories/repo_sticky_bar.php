<style>
.collection-content-container{margin-left:20px;padding-top:10px;position:relative;}
.collection-content-container .thumb{float:left;width:80px;}
.collection-content-container .thumb img{box-shadow:1px 1px 1px #888;border:1px solid #CCCCCC;padding:2px;}
.collection-content-container .collection-body{margin-top:6px;}
.collection-content-container .collection-body{float:left;}
.collection-content-container .collection-title{font-size: 24px;text-shadow: 0 1px 3px rgba(0, 0, 0, .4), 0 0 30px rgba(0, 0, 0, .075);color: #47475C;}
.collection-content-container .collection-id{font-weight:bold;color:#A5A1A1}
.collection-content-container .page-links{position:absolute;right:50px;top:43px;}
.collection-content-container .page-links .button{
background: #3A87AD;
color: white;
border: 1px solid #59595A;
margin-right: 7px;
-moz-border-radius: 2px;
-webkit-border-radius: 2px;
box-shadow: 1px 1px 1px #888;
font-size:12px;
}
.collection-content-container .page-links .button:hover{background:black;color:white;border:1px solid black;}
</style>
<div class="collection-content-container">
	<div class="thumb">
    	<a href="<?php echo site_url(); ?>/admin/catalog" title="<?php echo t('catalog_home');?>">
    	<img src="<?php echo $thumbnail;?>" alt="Collection" width="60px"/>
    	</a>
    </div>
    <div class="collection-body">
        <div class="collection-title"><?php echo $title;?></div>
        <div class="collection-id"><?php echo $repositoryid;?></div>
    </div>
    
<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/catalog" class="btn btn-primary" title="<?php echo t('catalog_home');?>"><?php echo t('catalog_home');?></a>
	<a href="<?php echo site_url(); ?>/admin/catalog/upload" class="btn btn-primary" title="<?php echo t('upload_ddi_hover');?>"><?php echo t('upload_ddi');?></a> 
    <a href="<?php echo site_url(); ?>/admin/catalog/batch_import" class="btn btn-primary" title="<?php echo t('import_ddi_hover');?>"><?php echo t('import_ddi');?></a>
    <?php if(strtolower($repositoryid)!=='central'):?>
		<a href="<?php echo site_url(); ?>/admin/catalog/copy_study" class="btn btn-primary" title="<?php echo t('copy_studies');?>"><?php echo t('copy_studies');?></a>
    <?php endif;?>
    <a href="<?php echo site_url(); ?>/admin/licensed_requests" class="btn btn-primary" title="<?php echo t('licensed_requests');?>"><?php echo t('licensed_requests');?></a>
</div>    
</div>
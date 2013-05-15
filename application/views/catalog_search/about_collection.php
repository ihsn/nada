<style>
	.about-photo{float:left;margin-right:10px;}
	.repository-container{overflow:auto;}
	.repository-container .photo-big{float:left;margin-right:10px;}
	.visit-catalog{text-align:right;margin:10px;padding-top:10px;}
	a.btn-style-1{color:white;}
	ul.bull,.bull li{list-style-type:disc;margin-left:20px;}
	ul.bull{margin-bottom:10px;}
	.about-collection .repo-thumbnail{width:82px;height:82px;}
	.about-central{border-bottom: 1px solid gainsboro;}
</style>
<div class="about-collection">
<?php if($row->repositoryid!=='central'):?>
<div class="repository-container">
	<div class="body"><?php echo $row->long_text;?></div>
</div>
<div class="visit-catalog">
	<a class="btn-style-1" href="<?php echo site_url(); ?>/catalog/<?php echo $row->repositoryid; ?>"><?php echo t('visit_catalog');?></a>
</div>
<?php elseif($row->repositoryid=='central'):?>
<div class="about-central">
	<p><?php echo t('about_central_catalog');?></p>    
    <div class="visit-catalog">
        <a class="btn-style-1" href="<?php echo site_url(); ?>/catalog/<?php echo $row->repositoryid; ?>"><?php echo t('visit_catalog');?></a>
    </div>
</div>
<?php endif;?>

<?php if($additional):?>
	<?php echo $additional;?>
<?php endif;?>
</div>
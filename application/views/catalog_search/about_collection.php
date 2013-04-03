<style>
	.about-photo{float:left;margin-right:10px;}
	.repository-about{}
	.repository-about .photo-big{float:left;margin-right:10px;}
	.visit-catalog{float:right;}
	a.btn-style-1{color:white;}
</style>
<div class="repository-about">
<?php echo $row->long_text;?>
<div class="visit-catalog">
	<a class="btn-style-1" href="<?php echo site_url(); ?>/catalog/<?php echo $row->repositoryid; ?>"><?php echo t('visit_catalog');?></a>
    </div>
</div>
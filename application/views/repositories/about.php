<style>
	.about-photo{float:left;margin-right:10px;}
	.repository-about{}
	.repository-about .photo-big{float:left;margin-right:10px;}
	.visit-catalog a{background:#A01822;padding:8px;color:white;display:inline-block}
	.visit-catalog a:hover{background:#666666}
	.visit-catalog{float:right;}
</style>
<div class="repository-about">
<?php echo $row->long_text;?>
<div class="visit-catalog"><a href="<?php echo site_url(); ?>/catalog/<?php echo $row->repositoryid; ?>"><?php echo t('visit_catalog');?></a></div>
</div>
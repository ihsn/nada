<style>
	.breadcrumb-container{padding-left:0px;}
	.repo-thumbnail{
		width:120px;
		height:120px;
	}
	.contributing-repos h3{
		margin-top:30px;
	}
</style>
<div class="about-collection">
<?php if($row->repositoryid!=='central'):?>	
	<div class="repository-container">
		<div class="body"><?php echo $row->long_text;?></div>
	</div>
	<div class="visit-catalog">
		<a class="btn btn-primary color-white" href="<?php echo site_url('catalog/'.$row->repositoryid); ?>"><?php echo t('visit_catalog');?></a>
	</div>
<?php elseif($row->repositoryid=='central' && strlen( t('about_central_catalog')) > 50 ):?>
	<div class="about-central">
		<p><?php echo t('about_central_catalog');?></p>    
		<div class="visit-catalog">
			<a class="btn btn-primary color-white" href="<?php echo site_url(); ?>/catalog/<?php echo $row->repositoryid; ?>"><?php echo t('visit_catalog');?></a>
		</div>
	</div>
<?php endif;?>

<?php if($additional):?>
	<h1 class="mb-5"><?php echo t('Collections');?></h1>
	<?php echo $additional;?>
<?php endif;?>
</div>
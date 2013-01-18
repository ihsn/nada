<div class="page-links">
	<?php if ($this->uri->segment(2)=='catalog' && $this->uri->segment(3)!=''):?>
	<a href="<?php echo site_url(); ?>/admin/catalog" class="button" title="<?php echo t('catalog_home');?>"><?php echo t('catalog_home');?></a>
    <?php endif;?>
	<a href="<?php echo site_url(); ?>/admin/catalog/upload" class="button" title="<?php echo t('upload_ddi_hover');?>"><?php echo t('upload_ddi');?></a> 
    <a href="<?php echo site_url(); ?>/admin/catalog/batch_import" class="button" title="<?php echo t('import_ddi_hover');?>"><?php echo t('import_ddi');?></a>
	<a href="<?php echo site_url(); ?>/admin/repositories" class="button" title="<?php echo t('repositories');?>"><?php echo t('repositories');?></a>
	<a href="<?php echo site_url(); ?>/admin/catalog/copy_study" class="button" title="<?php echo t('copy_studies');?>"><?php echo t('copy_studies');?></a>
</div>
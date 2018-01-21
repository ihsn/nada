<div class="page-links">
	<?php if ($this->uri->segment(3)!=''):?>
    	<a href="<?php echo site_url();?>/admin/repositories" class="btn btn-default"><span class="glyphicon glyphicon-home ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('repositories');?></a> 
    <?php endif;?>
    <a href="<?php echo site_url();?>/admin/repositories/add" class="btn btn-default"><span class="glyphicon glyphicon-plus ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('create_repository');?></a> 
    <a href="<?php echo site_url();?>/admin/repository_sections/" class="btn btn-default"><span class="glyphicon glyphicon-th-large ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('manage_repo_sections');?></a> 
</div>

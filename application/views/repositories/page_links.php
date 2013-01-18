<div class="page-links">
	<?php if ($this->uri->segment(3)!=''):?>
    	<a href="<?php echo site_url();?>/admin/repositories" class="button"><img src="images/icon_plus.gif"/><?php echo t('repositories');?></a> 
    <?php endif;?>
    <a href="<?php echo site_url();?>/admin/repositories/add" class="button"><img src="images/icon_plus.gif"/><?php echo t('create_repository');?></a> 
    <?php /* ?><a href="<?php echo site_url();?>/admin/repositories/users" class="button"><img src="images/icon_plus.gif"/><?php echo t('repository_admins');?></a> <?php */ ?>
    <?php /*?><a href="<?php echo site_url();?>/admin/harvester/" class="button"><img src="images/icon_plus.gif"/><?php echo t('harvester_queue');?></a> <?php */?>    
    <a href="<?php echo site_url();?>/admin/repository_sections/" class="button"><img src="images/icon_plus.gif"/><?php echo t('manage_repo_sections');?></a> 
</div>
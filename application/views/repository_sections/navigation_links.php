<?php
/**
* navigation links
*/
?>
<div class="page-links">
	<a href="<?php echo site_url("admin/repositories");?>" class="btn btn-outline-primary btn-sm"> <i class="fas fa-home"></i> <?php echo t('repositories');?></a> 
    <?php if ($this->uri->segment(2)=='repository_sections' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url("admin/repository_sections/add"); ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-plus-circle" aria-hidden="true">&nbsp;</i><?php echo t('repository_section_add');?></a> 
    <?php endif;?>
</div>

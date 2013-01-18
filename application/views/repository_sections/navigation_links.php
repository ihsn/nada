<?php
/**
* navigation links
*/
?>
<div class="page-links">
	<a href="<?php echo site_url();?>/admin/repositories" class="button"><img src="images/icon_plus.gif"/><?php echo t('repositories');?></a> 
    <?php if ($this->uri->segment(2)=='repository_sections' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/repository_sections/add" class="button"><img src="images/page_white.png"/><?php echo t('repository_section_add');?></a> 
    <?php endif;?>
</div>

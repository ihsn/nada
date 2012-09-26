<?php
/**
* navigation links
*/
?>
<div class="page-links">
	<?php if ($this->uri->segment(2)=='repository_sections' && $this->uri->segment(3)!=''):?>
		<a href="<?php echo site_url(); ?>/admin/repository_sections" class="button"><img src="images/house.png"/><?php echo t('repository_sections_home');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='repository_sections' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/repository_sections/add" class="button"><img src="images/page_white.png"/><?php echo t('repository_sections_add_page');?></a> 
    <?php endif;?>
</div>

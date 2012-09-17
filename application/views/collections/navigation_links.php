<?php
/**
* navigation links
*/
?>
<div class="page-links">
	<?php if ($this->uri->segment(2)=='collections' && $this->uri->segment(3)!=''):?>
		<a href="<?php echo site_url(); ?>/admin/collections" class="button"><img src="images/house.png"/><?php echo t('collections_home');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='collections' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/collections/add" class="button"><img src="images/page_white.png"/><?php echo t('collections_add_page');?></a> 
    <?php endif;?>
</div>

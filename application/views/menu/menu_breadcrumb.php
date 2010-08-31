<?php
/**
* Menu breadcrumb
*/
?>
<div class="page-links">
	<?php if ($this->uri->segment(2)=='menu' && $this->uri->segment(3)!=''):?>
		<a href="<?php echo site_url(); ?>/admin/menu" class="button"><img src="images/house.png"/><?php echo t('menu_home');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='menu' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/menu/add" class="button"><img src="images/page_white.png"/><?php echo t('menu_add_page');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='menu' && $this->uri->segment(3)!='add_link'):?>    
	   	<a href="<?php echo site_url(); ?>/admin/menu/add_link" class="button"><img src="images/link.png"/><?php echo t('menu_add_link');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='menu' && $this->uri->segment(3)!='menu_sort'):?>    
   	<a href="<?php echo site_url(); ?>/admin/menu/menu_sort" class="button"><img src="images/arrow_reorder.png"/><?php echo t('menu_reorder');?></a> 
    <?php endif;?>
</div>

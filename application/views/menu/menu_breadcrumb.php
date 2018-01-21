<?php
/**
* Menu breadcrumb
*/
?>
<div class="text-right page-links">
	<?php if ($this->uri->segment(2)=='menu' && $this->uri->segment(3)!=''):?>
		<a href="<?php echo site_url(); ?>/admin/menu" class="btn btn-default"><span class="glyphicon glyphicon-home right-margin-5"></span> <?php echo t('menu_home');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='menu' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/menu/add" class="btn btn-default"><span class="glyphicon glyphicon-file right-margin-5"></span> <?php echo t('menu_add_page');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='menu' && $this->uri->segment(3)!='add_link'):?>    
	   	<a href="<?php echo site_url(); ?>/admin/menu/add_link" class="btn btn-default"><span class="glyphicon glyphicon-link right-margin-5"></span> <?php echo t('menu_add_link');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='menu' && $this->uri->segment(3)!='menu_sort'):?>    
   	<a href="<?php echo site_url(); ?>/admin/menu/menu_sort" class="btn btn-default"><span class="glyphicon glyphicon-refresh right-margin-5"></span> <?php echo t('menu_reorder');?></a> 
    <?php endif;?>
</div>

<?php
/**
* navigation links
*/
?>
<div class="page-links">
	<a href="<?php echo site_url();?>/admin/da_collections" class="button"><img src="images/icon_plus.gif"/><?php echo t('da_collections');?></a> 
    <?php if ($this->uri->segment(2)=='da_collections' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/da_collections/add" class="button"><img src="images/page_white.png"/><?php echo t('da_collection_create');?></a> 
    <?php endif;?>
</div>

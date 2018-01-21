<?php
/**
* navigation links
*/
?>
<div class="page-links">
	<a href="<?php echo site_url();?>/admin/da_collections" class="btn btn-default"><span class="glyphicon glyphicon-home ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('da_collections');?></a> 
    <?php if ($this->uri->segment(2)=='da_collections' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/da_collections/add" class="btn btn-default"><span class="glyphicon glyphicon-file ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('da_collection_create');?></a> 
    <?php endif;?>
</div>

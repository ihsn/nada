<?php
/**
* navigation links
*/
?>
<div class="page-links">
	<?php if ($this->uri->segment(2)=='user_groups' && $this->uri->segment(3)!=''):?>
		<a href="<?php echo site_url(); ?>/admin/user_groups" class="button"><img src="images/house.png"/><?php echo t('user_groups_home');?></a> 
    <?php endif;?>
	<?php if ($this->uri->segment(2)=='user_groups' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/user_groups" class="button"><img src="images/page_white.png"/><?php echo t('user_by_groups');?></a> 
    <?php endif;?>
    <?php if ($this->uri->segment(2)=='user_groups' && $this->uri->segment(3)!='add'):?>
		<a href="<?php echo site_url(); ?>/admin/user_groups/add" class="button"><img src="images/page_white.png"/><?php echo t('user_groups_add_page');?></a> 
    <?php endif;?>
</div>

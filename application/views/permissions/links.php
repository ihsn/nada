<?php
/**
* permissions navigation bar
*/
?>
<div class="page-links">
    <a href="<?php echo site_url(); ?>/admin/user_groups" class="btn btn-default">
        <i class="fa fa-users" aria-hidden="true"></i> <?php echo t('user_groups');?>
    </a> 
    <a href="<?php echo site_url(); ?>/admin/permissions/add" class="btn btn-default">
        <i class="fa fa-user-plus" aria-hidden="true"></i>
        <?php echo t('add_permission_url');?>
    </a>
    <a href="<?php echo site_url(); ?>/admin/permissions/manage" class="btn btn-default">        
        <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
        <?php echo t('manage_permissions');?>
    </a>     
</div>
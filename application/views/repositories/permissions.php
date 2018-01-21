<style>
#btnupdate{padding:3px;}
.subtitle{color:gray;}
.email {color:gray;}
.no-access{color:red;}
</style>

<?php include 'page_links.php'; ?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>


<h1 class="page-title"><?php echo t('repository_permissions');?> <span class="subtitle">[<?php echo $repo['title'];?>]</span>  </h1>

<div class="contributing-repos" >
<?php if ($users_by_repo):?>
<form method="post">
	<table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
    	<th><?php echo t('user');?></th>
        <th><?php echo t('permissions');?></th>
        <th></th>
    </tr> 
    <?php foreach($limited_users as $user):?>
    <tr valign="top">
    	<td>
			<div><?php echo $user['first_name'].' '.$user['last_name'];?></div>
			<div class="email"><?php echo $user['email'];?></div>
        </td>
        <td>
        	<?php if (count($user['permissions'])>0):?>
			<?php foreach($user['permissions'] as $permissions):?>
				<div><?php echo $permissions['group_title'];?></div>
            <?php endforeach;?>
            <?php else:?>
            	<div class="no-access"><?php echo t('none');?></div>
            <?php endif;?>
        </td>
        <td><a href="<?php echo site_url('admin/users/permissions/'.$user['id'].'?destination=admin/repositories/permissions/'.$this->uri->segment(4). '&collection='.$this->uri->segment(4));?>"><?php echo t('permissions');?></a></td>
    </tr>    
    <?php endforeach;?>
    </table>
    
</form>    
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>


</div>

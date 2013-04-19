<style>
#btnupdate{padding:3px;}
.subtitle{color:gray;}
</style>

<?php include 'page_links.php'; ?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<h1 class="page-title"><?php echo t('repository_permissions');?> <span class="subtitle">[<?php echo $repo['title'];?>]</span>  </h1>

<div class="contributing-repos" >
<?php if ($user_groups):?>
<form method="post">
	<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
    	<th><?php echo t('group_name');?></th>
        <th><?php echo t('group_description');?></th>
        <th><?php echo t('group_type');?></th>
        <th><?php echo t('group_access');?></th>
    </tr> 
    <?php foreach($user_groups as $group):?>
    <?php if ($group['group_type']=='user'){continue;}?>
    <tr>
    	<td><?php echo $group['name'];?></td>
        <td><?php echo $group['description'];?></td>
        <td><?php echo strtoupper($group['group_type']);?></td>
        <td><input type="checkbox" name="group_id[]" id="group_id" value="<?php echo $group['id'];?>" <?php echo in_array($group['id'],$repo_user_groups) ? 'checked="checked"' : '';?>  /></td>
    </tr>    
    <?php endforeach;?>
    </table>
    
    <div style="margin-top:20px;">    	
    	<?php 
			echo form_submit('submit',t('save_permissions'),'id="btnupdate"'); 
 			echo anchor('admin/repositories',t('cancel'));
		?>        
    </div>
</form>    
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>


</div>
<?php
/*
* Manage permissions for a user group
*/
?>
<style>
.caption{
font-weight:bold;padding-left:20px;
}
.description{
	color:#666666;padding-left:20px;
}
.header{background:#C1DAD7}
.grid-table .br td{border:0px;}
.h1{margin-top:20px;}
</style>

<?php $this->load->view('permissions/links');?>
<h1>Manage Group Permissions - <?php echo $group['name'];?></h1>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<form method="post">
<input type="hidden" name="group_id" value="<?php echo $group_id;?>"/>
<table class="grid-table">
<tr>
<th><h3>Permission</h3></th>
<th style="text-align:center"><h3><?php echo $group['name'];?></h3></th>
</tr>
<?php foreach ($permissions as $group_name=>$perm_group):?>
	<tr>
        <td colspan="2" class="header">
            <h3><?php echo $group_name;?></h3>
        </td>
    </tr>
    <?php $x = 0; ?>    
	<?php foreach($perm_group as $perm):?>
    	<?php 
			//check/uncheck checkbox
			$perm_selected=(in_array($perm['id'],$assigned_perms) ) ? 'checked="checked"' : '';
		?>	
    	<tr class="<?php echo ($x++%2==1) ? '' : 'alternate' ?>">
    	<td>
			<div class="caption"><?php echo $perm['label'];?></div>
            <div class="description"><?php echo $perm['description'];?></div>
        </td>
        <td style="width:200px;text-align:center" >
        	<input type="checkbox" name="pid[]" value="<?php echo $perm['id'];?>" <?php echo $perm_selected;?>/>
        </td>
        </tr>
    <?php endforeach;?> 
    <tr class="br"><td colspan="2">&nbsp;</td></tr>
<?php endforeach;?>

	<?php //list repositories ?>
    <tr class="header">
    <td><h3><?php echo t('manage_repo_permissions');?></h3></td>
    <td style="text-align:center"></td>
    </tr>

    <?php foreach($repos as $repo):?>
    	<?php $group_has_access=(in_array($repo['id'],$repo_group_perms) ) ? 'checked="checked"' : ''; ?>
    <tr class="repo">
    	<td><?php echo $repo['title'];?>  (<?php echo $repo['repositoryid'];?>) </td>
        <td style="width:200px;text-align:center"><input type="checkbox" name="repo[]" value="<?php echo $repo['id'];?>" <?php echo $group_has_access;?>/></td>
    </tr>
    <?php endforeach;?>
</table>

<div style="text-align:right;">
<input type="submit" value="Apply changes" name="submit"/>
</div>
</form>
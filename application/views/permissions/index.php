<style type="text/css">
.caption{
	font-weight:bold;padding-left:20px;
}
.description{
	color:#666666;padding-left:20px;
}
.header{background:#C1DAD7}
.grid-table .br td{border:0px;}
.h1{margin-top:20px;}
.group-name{font-weight:bold;}
</style>

<div class="container-fluid">
<?php $this->load->view('permissions/links');?>
<h1><?php echo t('manage_permissions');?></h1>

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

<table class="grid-table table trable-striped">
<?php foreach ($permissions as $group_name=>$perm_group):?>
	<tr>
        <td colspan="3" class="header">
            <div class="group-name"><?php echo t($group_name);?></div>
        </td>
    </tr>
    <?php $x = 0; ?>    
	<?php foreach($perm_group as $perm):?>
    	<tr class="<?php echo ($x++%2==1) ? '' : 'alternate' ?>">
    	<td>
			<div class="caption"><?php echo $perm['label'];?></div>
            <div class="description"><?php echo $perm['description'];?></div>
        </td>
        <td>
        <?php 
			///var_dump($perm);exit;
		//urls
			if (array_key_exists($perm['id'],$permission_urls))
			{
				foreach($permission_urls[$perm['id']] as $url)
				{
					echo $url;
					echo '<br/>';
				}
			}
		?>
        </td>
        <td><?php echo anchor('admin/permissions/edit/'.$perm['id'],t('edit'));?> | <?php echo anchor('admin/permissions/delete/'.$perm['id'],t('delete'));?></td>
        </tr>
    <?php endforeach;?> 
    <tr class="br"><td colspan="2">&nbsp;</td></tr>
<?php endforeach;?>
</table>

</form>
		</div>
<style type="text/css">
.permissions-role-page .caption{
	padding-left:20px;text-transform:capitalize;
}
.permissions-role-page .description{
	color:#666;padding-left:20px;font-size:13px;
}
.permissions-role-page .group-description{
	color:#666;font-size:13px;
}
.permissions-role-page .perm-global-header{
	border-bottom:2px solid #333;padding:10px 0 6px;font-size:16px;font-weight:600;
	margin-top:12px;
}
.permissions-role-page .group-name{font-weight:600;}
.permissions-role-page .permission-caption label{
	font-weight:normal;text-transform:capitalize;
	margin:0;
}
</style>

<div class="container-fluid permissions-role-page">
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

<div class="row">
<div class="col-md-3 mb-3">
	<ul class="list-group">
		<?php foreach($roles as $role):?>
			<a class="list-group-item list-group-item-action <?php echo $role['id']==$active_id ? 'active' : '';?>"  href="<?php echo site_url('admin/permissions/manage/'.$role['id']);?>"><?php echo htmlspecialchars($role['name']);?></a>
		<?php endforeach;?>
	</ul>
</div>

<div class="col-md-9">
<?php echo form_open(); ?>
	<h2 class="h4 mb-3 perm-global-header"><?php echo t('Site-wide permissions'); ?></h2>
	<p class="text-muted small mb-3"><?php echo t('permissions_global_intro'); ?></p>
	<table class="table table-sm">
	<?php foreach ($permissions as $resource=>$rule):?>
		<tr>
			<td colspan="3" class="perm-global-header pt-4">
				<div class="group-name">
					<?php echo htmlspecialchars(t($rule['title']));?>
				</div>
				<?php if(isset($rule['description'])):?>
					<div class="group-description"><?php echo $rule['description'];?></div>
				<?php endif;?>
			</td>
		</tr>
		<?php $x = 0; ?>
		<?php foreach($rule['permissions'] as $perm):?>
			<tr class="<?php echo ($x++%2==1) ? '' : 'table-light' ?>">
			<td class="text-right" style="width:48px;">
				<?php
					$is_checked='';
					if (isset($post_values[$resource])  && in_array($perm['permission'],$post_values[$resource] )){
						$is_checked='checked="checked"';
					}
					$fid = htmlspecialchars($resource.'.'.$perm['permission'], ENT_QUOTES, 'UTF-8');
				?>
				<input
					<?php echo $is_checked;?>
					type="checkbox"
					id="<?php echo $fid;?>"
					name="resource[<?php echo htmlspecialchars($resource, ENT_QUOTES, 'UTF-8');?>][]"
					value="<?php echo htmlspecialchars($perm['permission'], ENT_QUOTES, 'UTF-8');?>"/>
			</td>
			<td>
				<div class="caption">
					<label class="mb-0" for="<?php echo $fid;?>">
						<?php echo htmlspecialchars($perm['permission']);?>
					</label>
				</div>
				<div class="description"><?php echo isset($perm['description']) ? htmlspecialchars($perm['description']) : '';?></div>
			</td>
			</tr>
		<?php endforeach;?>
	<?php endforeach;?>
	</table>

	<button type="submit" class="btn btn-primary mt-3"><?php echo t('submit');?></button>
<?php echo form_close();?>
</div>
</div>
</div>

<style type="text/css">
.permissions-role-page .caption{
	padding-left:20px;text-transform:capitalize;
}
.permissions-role-page .description{
	color:#666;padding-left:20px;font-size:13px;
}
.permissions-role-page .group-description{
	color:#666;font-size:13px;margin-top:4px;
}
.permissions-role-page .group-name{font-weight:600;font-size:15px;}
.permissions-role-page .permission-caption label{
	font-weight:normal;text-transform:capitalize;
	margin:0;
}
.permissions-role-page .perm-site-wide-section{
	background:#f8f9fa;
	border:1px solid #dee2e6;
	border-radius:6px;
	padding:0;
	margin-bottom:24px;
	overflow:hidden;
}
.permissions-role-page .perm-site-wide-intro{
	background:#fff;
	padding:20px 24px 0;
}
.permissions-role-page .perm-site-wide-title{
	margin:0 0 12px;
	font-size:22px;
	font-weight:600;
	line-height:1.25;
	color:#212529;
}
.permissions-role-page .perm-site-wide-note{
	margin:0;
	font-size:inherit;
	line-height:1.5;
	color:#495057;
}
.permissions-role-page .perm-site-wide-body{
	padding:20px 16px 20px;
	margin-top:20px;
}
.permissions-role-page .perm-resource-header{
	border-bottom:1px solid #e9ecef;
	padding:24px 8px 10px;
	background:transparent;
}
.permissions-role-page .perm-resource-header:first-child{
	padding-top:16px;
}
</style>

<div class="container-fluid permissions-role-page">
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

<?php
$active_role_name = '';
foreach ($roles as $role) {
	if ((int) $role['id'] === (int) $active_id) {
		$active_role_name = $role['name'];
		break;
	}
}
?>

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
	<section class="perm-site-wide-section" aria-labelledby="perm-site-wide-title">
		<header class="perm-site-wide-intro">
			<h2 id="perm-site-wide-title" class="perm-site-wide-title"><?php
				$role_label = $active_role_name !== '' ? $active_role_name : t('role');
				echo htmlspecialchars(sprintf(t('permissions_global_permissions_title'), $role_label));
			?></h2>
			<p class="perm-site-wide-note"><?php
				echo sprintf(
					t('permissions_site_wide_collection_note'),
					anchor(site_url('admin/collections'), t('permissions_collections_page_link'))
				);
			?></p>
		</header>

		<div class="perm-site-wide-body">
			<table class="table table-sm mb-0">
			<?php foreach ($permissions as $resource=>$rule):?>
				<tr>
					<td colspan="2" class="perm-resource-header">
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
		</div>
	</section>

	<button type="submit" class="btn btn-primary"><?php echo t('submit');?></button>
<?php echo form_close();?>
</div>
</div>
</div>

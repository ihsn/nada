<style>
.repo-update{width:50px;cursor:pointer;text-align:center;}
</style>
<?php
$repo_types=array(
	'0'=>'Internal',
	'1'=>'External'
);

$repo_roles=array(
	'0'=>'--NO-ACCESS--',
	'1'=>'Catalog admin',
	'2'=>'Catalog licensed requests admin'	
);

$repositories=array();
foreach($repos as $entry)
{
	$repositories[$entry['id']]=$entry['title'];
}
?>
<div class="body-container" style="padding:10px;">
<?php include 'page_links.php'; ?>
<h1><?php echo $this->page_title;?></h1>
	
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
	//form action url
	$uri_arr=$this->uri->segment_array();

	$form_action_url=site_url().'/admin/repositories/'.$this->uri->segment(3).'/';
	if ($this->uri->segment(3)=='add')
	{
		$form_action_url.='/add';
	}
	else
	{
		$form_action_url.=$this->uri->segment(4);
	}
?>

<?php if ($repo):?>
	Repository:
	<?php echo form_dropdown('repositryid', $repositories,$repo['id'], 'id="repositoryid"');?>
<?php endif;?>

<!--    <div class="field">
        <label for="pid"><?php echo t('select_repo_type');?></label>
        <?php echo form_dropdown('type', $repo_types,get_form_value('type',isset($type) ? $type : ''));?>
    </div>  
-->


    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('username'); ?></th>
            <th><?php echo t('email'); ?></th>  
            <th><?php echo t('select_access_type'); ?></th>          
			<th><?php echo t('status'); ?></th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($users as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>" id="<?php echo $row->id; ?>">
            <td><a href="<?php echo site_url().'/admin/repositories/edit/'.$row->id; ?>"><?php echo $row->username; ?></a></td>
            <td><?php echo $row->email; ?>&nbsp;</td>
       		<!--<td><?php echo strtoupper($row->group_name); ?></td>-->
            <td><?php echo form_dropdown('repo_role', $repo_roles,(array_key_exists($row->id,$repo_admins)) ? $repo_admins[$row->id]['roleid'] : 0,'class="repo_role_id"');?></td>
			<td><?php echo ((int)$row->active)==1 ? t('ACTIVE') : t('DISABLED'); ?></td>
            <td>
            	<span  class="button repo-update" id="<?php echo $row->id;?>"><?php echo t('update');?></span>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function(){
	
	$(".repo-update").click(
			function (e) 
			{
				userid=$(this).parent().parent("tr").attr("id");
				repoid=$("#repositoryid").val();
				roleid=$(".repo_role_id",$(this).parent().parent("tr")).val();
				$(e.target).parent().append('<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>');
				$.ajax({
					timeout:1000*120,
					cache:false,
					dataType: "json",
					data:{ submit: "submit"},
					type:'POST', 
					url: CI.base_url+'/admin/repositories/assign_role/'+repoid+'/'+userid+'/'+roleid+'/?ajax=true',
					success: function(data) {
						if (data.success){
							$(".loading",$(e.target).parent()).delay(1000).fadeOut();
						}
						else{
							alert(data.error);
						}
					},
					error: function(XHR, textStatus, thrownError) {
						alert("Error occured " + XHR.status);
					}
				});	
			}
	);
	
	//change repo
	$("#repositoryid").change(function(){
		window.location = CI.base_url+'/admin/repositories/users/'+$("#repositoryid").val();
	});

});
</script>
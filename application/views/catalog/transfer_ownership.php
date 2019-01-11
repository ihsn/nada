<style>
.field{margin-top:15px;}
</style>
<?php
//get repositories list by user access
$user_repositories=$this->acl->get_user_repositories();
$repositories_list=array('0'=>'--SELECT--');
foreach($user_repositories as $repo)
{
	$repositories_list[$repo["repositoryid"]]=$repo['title'];
}
?>
<div style="padding:10px;">
<h1><?php echo t('transfer_study_ownership');?></h1>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php if (isset($surveys) && count($surveys)>0):?>
	<?php echo form_open_multipart('admin/catalog/transfer', array('class'=>'form'));?>
    
    <p><?php echo t('select_the_repository_from_the_list_below');?></p>
    <div class="field">
        <label for="repositoryid"><?php echo t('msg_select_repository');?></label>
        <?php echo form_dropdown('repositoryid', $repositories_list); ?>
    </div>

	<div class="field">
	    <label for=""><?php echo t('msg_studies_to_transfer');?></label>
        <ul>
        <?php foreach($surveys as $survey):?>
            <li><?php echo $survey['title'];?><input type="hidden" name="sid[]" value="<?php echo $survey['id'];?>"/></li>
        <?php endforeach;?>
        </ul>
	</div>
    
    <div class="field">
    	<?php echo form_submit('submit',t('transfer')); ?>
    <?php echo anchor('admin/catalog',t('cancel'));?>
    </div>
    <?php echo form_close();?>
    
<?php endif;?>

</div>
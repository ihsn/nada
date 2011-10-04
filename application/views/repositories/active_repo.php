<style>
.repo{font-size:14px;
	font-weight:bold;
	background:gainsboro;
	padding:5px;
	margin-bottom:5px;
	-moz-border-radius: 5px;	
	border-radius: 5px;
	text-decoration:none;
	display:block;
	color:black;
	}
	
p.msg{font-size:14px;margin-bottom:10px;}
</style>
<div class="body-container" style="padding:10px;">
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

<?php if ($repos):?>
<p class="msg"><?php echo t('msg_select_active_repo'); ?></p>
<?php else:?>
<p class="msg"><?php echo t('msg_no_repo_access'); ?></p>
<?php endif;?>
<ul>
<?php foreach($repos as $repo):?>
	<li><a class="repo" href="<?php echo site_url();?>/admin/repositories/active/<?php echo $repo['repositoryid']; ?>"><?php echo $repo['title']; ?></a></li>
<?php endforeach;?>
</ul>
</div>
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
.select-repository .thumbnail{float:left;width:82px;height:82px;overflow:hidden;margin:0px;padding:0px;margin-right:10px;border:0px;}
.select-repository .thumbnail img{width:60px;}
.select-repository .body{font-weight:normal;overflow:auto;}
.select-repository h2{font-size:16px; text-transform:uppercase;margin:0px;padding:0px}
.select-repository .repository-id{color:gray;font-size:smaller;}
.select-repository .repo-row{overflow:auto;padding:10px;border-bottom:1px solid gainsboro;}
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
<div class="select-repository">
<?php foreach($repos as $repo):?>
	<div class="repo-row" data-url="<?php echo site_url('/admin/repositories/active/'.$repo['id']);?>" >
			<div class="thumbnail">
            	<a href="<?php echo site_url();?>/admin/repositories/active/<?php echo $repo['id']; ?>">
                <img src="<?php echo $repo['thumbnail']; ?>" alt="Collection"/>
            	</a>
            </div>
            <div class="body">
			<h2 class="repo-title">
            	<a href="<?php echo site_url();?>/admin/repositories/active/<?php echo $repo['id']; ?>"><?php echo $repo['title']; ?></a> 
                <span class="repository-id"> - <?php echo $repo['repositoryid']; ?></span>
            </h2>
            <div class="repo-text"><?php echo $repo['short_text']; ?></div>
            </div>
	</div>
<?php endforeach;?>
</div>

</div>
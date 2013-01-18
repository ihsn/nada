<style>
.repo-table td{padding-top:10px;padding-bottom:0px;border-bottom:1px solid gainsboro;font-size:12px;line-height:140%;}
.thumb{padding-right:10px;padding-bottom:5px;width:90px;}
.thumb img{padding-bottom:10px;}
.page-title{border-bottom:1px solid gainsboro;}
.contributing-repos h2 {border-bottom:0px solid gainsboro;font-size:18px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; font-weight:bold; word-spacing:110%;}
.contributing-repos p a, .central-repo p a{color:black;}
.contributing-repos p a:hover, .central-repo p a:hover{text-decoration:underline}
.repositoryid{color:#999999;font-size:smaller;font-weight:normal;}
</style>

<?php include 'page_links.php'; ?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<h1 class="page-title"><?php echo t('repositories');?></h1>

<div class="contributing-repos" >
<?php if ($rows):?>
    <div style="height:20px;">&nbsp;</div>	
	<?php foreach($sections as $id=>$section):?>	
	<?php
		$data=array(
					'rows'=>$rows,
					'section'=>$id,
					'section_title'=>$section,
					'show_unpublished'=>TRUE
					);
		$this->load->view("repositories/admin_repos_by_section",$data);
    ?>
    <?php endforeach;?>

<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>


</div>
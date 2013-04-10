<style>
.repo-table td{padding-top:10px;padding-bottom:0px;border-bottom:1px solid gainsboro;font-size:12px;line-height:140%;}
.thumb{padding-right:10px;padding-bottom:5px;}
.thumb img{padding-bottom:10px;}
.page-title{border-bottom:1px solid gainsboro;}
.contributing-repos h2 {border-bottom:0px solid gainsboro;font-size:18px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; font-weight:bold; word-spacing:110%;margin-top:20px;}
.contributing-repos p a, .central-repo p a{color:black;}
.contributing-repos p a:hover, .central-repo p a:hover{text-decoration:underline}

</style>
<div class="contributing-repos" >
<?php if ($sections):?>
	<?php foreach($sections as $section_id=>$section): ?>    
    	<?php 
			$data=array(
						'rows'=>$rows,
						'section'=>$section_id,
						'section_title'=>$section,
						'show_unpublished'=>$show_unpublished
						);
			$output=$this->load->view("repositories/repos_by_section",$data,TRUE);
			
		?>
		<?php if (trim($output)!=''):?>
        <div>
            <h2 class="page-title"><?php echo $section;?></h2>
            <?php echo $output;?>
        </div>
		<?php endif;?>
        
    <?php endforeach;?>

<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>


</div>
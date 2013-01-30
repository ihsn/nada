<style>
.repo-table td{padding-top:10px;padding-bottom:0px;border-bottom:1px solid gainsboro;font-size:12px;line-height:140%;}
.thumb{padding-right:10px;padding-bottom:5px;}
.thumb img{padding-bottom:10px;}
.page-title{border-bottom:1px solid gainsboro;}

.contributing-repos h2 {border-bottom:0px solid gainsboro;font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; font-weight:bold; word-spacing:110%;}
.contributing-repos p a, .central-repo p a{color:black;}
.contributing-repos p a:hover, .central-repo p a:hover{text-decoration:underline}

</style>
<div class="contributing-repos" >
<?php if ($rows):?>
	<?php foreach($rows as $row): ?>
    	<?php 
			$row=(object)$row;
			$repo_sections[$row->section]=$row->section;
		?>
    <?php endforeach;?>

	<?php //show repositories divided by sections
			$data=array(
						'rows'=>$rows,
						'section'=>$row->section,
						'section_title'=>t('repositories_regional') 
						);
			$this->load->view("repositories/repos_by_section",$data);
	?>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>


</div>
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
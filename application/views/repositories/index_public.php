
<?php if ($sections):?>
<div class="contributing-repos" >
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
</div>
<?php endif; ?>
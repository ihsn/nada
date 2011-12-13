<style>
.repo-table td{padding-top:10px;padding-bottom:10px;border-bottom:1px solid gainsboro;}
.thumb{padding-right:10px;padding-bottom:5px;}
.page-title{border-bottom:1px solid gainsboro;}
</style>
<div class="contributing-repos" style="padding:10px;">
<div style="padding-bottom:30px;">
<a title="Central Catalog" href="../index.php/catalog"><img style="float: left; display: block; margin-right: 10px;" src="files/logo-network.gif" alt="Central Catalog"></a>
<h3 style="font-size:larger;font-weight:bold;"><a href="<?php echo site_url();?>/catalog/central">Central Microdata Catalog</a></h3>
<p>
The Central Microdata Catalog is a portal for all datasets held in catalogs maintained by the World Bank and a number of contributing external repositories. As of <?php echo date("F d, Y",date("U")); ?>, our central catalog contains <?php echo $survey_count;?> surveys. Users who wish to go directly to a specific catalog can visit the specific contributing repository through the links below.</p>
</div>
<br style="clear:both;margin-top:10px;"/>

<?php if ($rows):?>
	<?php foreach($rows as $row): ?>
    	<?php 
			$row=(object)$row;
			$repo_sections[$row->section]=$row->section;
		?>        
    <?php endforeach;?>

	<?php //show repositories divided by sections
		
		//internal catalogs
		if (in_array('internal',$repo_sections))
		{
			$data=array(
						'rows'=>$rows,
						'section'=>'internal',
						'section_title'=>t('repositories_internal') 
						);
			$this->load->view("repositories/repos_by_section",$data);
		}	
	?>
    <div style="height:20px;">&nbsp;</div>	
	<?php	
		//external catalogs
		if (in_array('external',$repo_sections))
		{
			$data=array(
						'rows'=>$rows,
						'section'=>'external',
						'section_title'=>t('repositories_external') 
						);

			$this->load->view("repositories/repos_by_section",$data);
		}
    ?>

<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>


</div>
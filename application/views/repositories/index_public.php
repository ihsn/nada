<style>
.repo-table td{padding-top:10px;padding-bottom:0px;border-bottom:1px solid gainsboro;font-size:12px;line-height:140%;}
.thumb{padding-right:10px;padding-bottom:5px;}
.thumb img{padding-bottom:10px;}
.page-title{border-bottom:1px solid gainsboro;}
<<<<<<< HEAD

.contributing-repos h2 {border-bottom:0px solid gainsboro;font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; font-weight:bold; word-spacing:110%;}
.contributing-repos p a, .central-repo p a{color:black;}
.contributing-repos p a:hover, .central-repo p a:hover{text-decoration:underline}

</style>
<div class="contributing-repos" >
<?php /*?>
<div>
<a title="Microdata Library" href="<?php echo site_url();?>/catalog/central"><img style="float: left; display: block; margin-right: 10px;" src="files/logo-central.gif" alt="Microdata Library"></a>
<h3 style="font-size:larger;font-weight:bold;"><a href="<?php echo site_url();?>/catalog/central"><?php echo t('central_data_catalog');?></a></h3>
<p>
<a href="<?php echo site_url();?>/catalog/central">The <?php echo t('central_data_catalog');?> is a portal for all datasets held in repositories maintained by the World Bank and a number of contributing external repositories. Click here to search all repositories.</a></p>
</div>
<br style="clear:both;"/>
<?php */?>
=======
</style>
<div class="contributing-repos" style="padding:10px;">
<div style="padding-bottom:30px;">
<a title="Central Catalog" href="<?php echo site_url();?>/catalog/central"><img style="float: left; display: block; margin-right: 10px;" src="files/logo-network.gif" alt="Central Catalog"></a>
<h3 style="font-size:larger;font-weight:bold;"><a href="<?php echo site_url();?>/catalog/central">Central Microdata Catalog</a></h3>
<p>
The Central Microdata Catalog is a portal for all datasets held in catalogs maintained by the World Bank and a number of contributing external repositories. As of <?php echo date("F d, Y",date("U")); ?>, our central catalog contains <?php echo $survey_count;?> surveys. Users who wish to go directly to a specific catalog can visit the specific contributing repository through the links below.</p>
</div>
<br style="clear:both;margin-top:10px;"/>

>>>>>>> 0df80238506a3fa904ffbc982da373dfec446f9c
<?php if ($rows):?>
	<?php foreach($rows as $row): ?>
    	<?php 
			$row=(object)$row;
			$repo_sections[$row->section]=$row->section;
		?>        
    <?php endforeach;?>

	<?php //show repositories divided by sections
		
		//internal catalogs
<<<<<<< HEAD
		if (in_array('regional',$repo_sections))
		{
			$data=array(
						'rows'=>$rows,
						'section'=>'regional',
						'section_title'=>t('repositories_regional') 
=======
		if (in_array('internal',$repo_sections))
		{
			$data=array(
						'rows'=>$rows,
						'section'=>'internal',
						'section_title'=>t('repositories_internal') 
>>>>>>> 0df80238506a3fa904ffbc982da373dfec446f9c
						);
			$this->load->view("repositories/repos_by_section",$data);
		}	
	?>
    <div style="height:20px;">&nbsp;</div>	
	<?php	
		//external catalogs
<<<<<<< HEAD
		if (in_array('specialized',$repo_sections))
		{
			$data=array(
						'rows'=>$rows,
						'section'=>'specialized',
						'section_title'=>t('repositories_specialized') 
=======
		if (in_array('external',$repo_sections))
		{
			$data=array(
						'rows'=>$rows,
						'section'=>'external',
						'section_title'=>t('repositories_external') 
>>>>>>> 0df80238506a3fa904ffbc982da373dfec446f9c
						);

			$this->load->view("repositories/repos_by_section",$data);
		}
    ?>

<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>


</div>
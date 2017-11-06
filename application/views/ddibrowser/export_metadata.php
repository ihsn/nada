<div class="survey-export_metadata">
<h2><?php echo $title;?></h2>
<ul class="bullet-list">
	<li><a class="download" data-title="DDI-<?php echo $id;?>.XML" href="<?php echo site_url('catalog/ddi').'/'.$id;?>"><?php echo t('download_study_ddi');?></a> </li>
    <li><a class="download" data-title="RDF-<?php echo $id;?>.RDF" href="<?php echo site_url('catalog/rdf').'/'.$id;?>"><?php echo t('download_study_rdf');?></a></li>
    
    	<?php $report_file=unix_path($this->survey_folder.'/ddi-documentation-'.$this->config->item("language").'-'.$id.'.pdf');?>
   		<?php if (file_exists($report_file)):?>
        <li><a class="download" data-title="STUDY-DOCUMENTATION-<?php echo $id;?>.PDF" href="<?php echo site_url()."/ddibrowser/$id/export/?format=pdf&generate=yes";?>" title="<?php echo t('pdf');?>" rel="nofollow"><?php echo t('documentation_in_pdf');?></a> </li>
    	<?php endif;?>

</ul>
</div>
<?php
/**
* Prints information about the remote repository
*
**/
?>
<div id="repo-info-box" style="background-color:#F7F7F7;font-size:14px;padding:10px;margin-top:10px;margin-bottom:20px;border:1px solid gainsboro;">
<?php if (isset($harvested_survey)): ?>
<h2>Metadata information</h2>
<p style="font-size:14px;">The metadata are provided by <b><?php echo anchor($harvested_survey['repo_url'],$harvested_survey['repo_title'],'target="blank_"');?></b>, 
and do not include any documentation such as the questionnaires, reports, data files, etc. To access complete metadata and documentation, 
please visit the <em><?php echo $harvested_survey['repo_title']; ?></em> data catalog at <?php echo anchor($harvested_survey['repo_url']); ?> or directly access the study page
at <?php echo anchor($harvested_survey['survey_url'],$harvested_survey['survey_url'],'target="blank_"'); ?></p>
<?php endif;?>
</div>
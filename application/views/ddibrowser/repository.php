<?php
/**
* Prints information about the remote repository
*
**/
?>
<div id="repo-info-box" style="background-color:#F7F7F7;font-size:14px;padding:10px;margin-top:10px;margin-bottom:20px;border:1px solid gainsboro;">
<?php if (isset($repository)): ?>
<h2>Metadata information</h2>
<p style="font-size:14px;">The metadata is provided by <b><?php echo anchor($repository['url'],$repository['title'],'target="blank_"');?></b>, 
and does not include any documentation such as the questionnaires, reports, data files, etc. To access complete metadata and documentation, 
please visit the <em><?php echo $repository['title']; ?></em> data catalog at <?php echo anchor($repository['url']); ?> or directly access the study 
at <?php echo anchor($repository['url'].'index.php/catalog/'.$repository['surveyid'],$repository['url'].'index.php/catalog/'.$repository['surveyid'],'target="blank_"'); ?></p>
<?php endif;?>
</div>
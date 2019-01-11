<div class="content-container study-metadata page-type page-data-study"
     style="overflow:auto;margin-bottom:10px;"
     data-page-type="study"
     data-repo-owner="<?php echo $owner_repo['repositoryid'];?>"
     data-study-id="<?php echo $id;?>"
>

	<?php if ($this->input->get("print")) :?>
    <div style="padding-bottom:20px;">
        <h1><?php echo $nation;?> - <?php echo $titl;?></h1>
    </div>
    <?php endif;?>

<?php if ($owner_repo['thumbnail']!=''):?>
<div class="collection-thumb-container">
	<a href="<?php echo site_url('catalog/'.$owner_repo['repositoryid']);?>">
    <img src="<?php echo $owner_repo['thumbnail'];?>" width="100%;" alt="<?php echo $owner_repo['repositoryid'];?>" title="<?php echo $owner_repo['title'];?>"/>
    </a>
</div>
<?php endif;?>

<table class="grid-table survey-info" cellspacing="0">
	<tr>
    	<td class="label"><?php echo t('refno');?></td>
        <td class="value"><?php echo $surveyid;?></td>
    </tr>
	<tr>
    	<td class="label"><?php echo t('year');?></td>
        <td class="value" itemprop="temporal"><?php
				if ($year_start==$year_end)
				{
					echo $year_start;
				}
				else
				{
					if ($year_start!='')
					{
						$dates[]=$year_start;
					}
					if ($year_end!='')
					{
						$dates[]=$year_end;
					}						
					echo implode(" - ", $dates);
				}?>
        </td>
    </tr>
	<?php if ($nation!=''):?>
	<tr itemprop="spatial" itemscope="itemscope" itemtype="http://schema.org/Country">
    	<td class="label"><?php echo t('country');?></td>
        <td class="value"  itemprop="name"><?php echo $nation;?></td>
    </tr>
	<?php endif;?>
	<tr valign="top" itemprop="producer" itemscope="itemscope" itemtype="http://schema.org/Person">
    	<td class="label"><?php echo t('producers');?></td>
        <td class="value" itemprop="name" >
        	<?php if (isset($authoring_entity)):?>
                    <?php echo $authoring_entity;?>
            <?php endif;?>    
        </td>
    </tr>
    <?php if (strlen($sponsor)>5):?>
	<tr valign="top"  >
    	<td class="label"><?php echo t('sponsors');?></td>
        <td class="value" ><?php echo $sponsor;?></td>
    </tr>
    <?php endif;?>

	<?php if (isset($repositories) && is_array($repositories) && count($repositories)>0): ?>
	<tr valign="top">
    	<td class="label"><?php echo t('collections');?></td>
        <td class="value">
		<?php foreach($repositories as $repository):?>
			<div class="collection"><?php echo anchor('catalog/'.$repository['repositoryid'],$repository['title']);?></div>
		<?php endforeach;?>
        </td>
    </tr>
	<?php endif;?>

	<?php $report_file=unix_path($this->survey_folder.'/ddi-documentation-'.$this->config->item("language").'-'.$id.'.pdf');?>
    <?php if (file_exists($report_file)):?>
    <tr>    
    	<td class="label"><?php echo t('metadata');?></td>
        <td class="value links">            
            <span class="link-col sep">
                <a class="download" href="<?php echo site_url()."/ddibrowser/$id/export/?format=pdf&generate=yes";?>" data-title="STUDY-DOCUMENTATION-<?php echo $id;?>.PDF" title="<?php echo $titl.' '.t('pdf');?>" rel="nofollow">
                <img border="0" title="<?php echo t('link_pdf');?>" alt="PDF" src="images/pdf.gif" /> <?php echo t('documentation_in_pdf');?>
                </a> 
            </span>            
        </td>
    </tr>
    <?php endif;?>
    
    <?php if($link_indicator!='' || $link_study!=''): ?>
    <tr>
    <td></td>
    <td class="study-links">
			<!-- indicators -->
            <span class="link-col">
			 <?php if($link_indicator!=''): ?>
                <a class="link" data-title="STUDY-IND-LINK-<?php echo $id;?>" target="_blank"  href="<?php echo site_url("/catalog/$id/link/interactive");?>" title="<?php echo t('link_indicators_hover');?>">
                    <img border="0" alt="<?php echo t('link_indicators');?>" src="images/page_white_database.png" /> <?php echo t('link_indicators_hover');?>
                </a>
            <?php endif; ?>
	        </span>
            
            <span class="link-col">
            <?php if($link_study!=''): ?>
                    <a  class="link" data-title="STUDY-LINK-<?php echo $id;?>" target="_blank" href="<?php echo site_url("/catalog/$id/link/study-website");?>" title="<?php echo t('link_study_website_hover');?>">
                        <img border="0" title="<?php echo t('link_study_website_hover');?>" alt="<?php echo t('link_study_website');?>" src="images/page_white_world.png" /> <?php echo t('link_study_website');?>
                    </a>
            <?php endif; ?>
        	</span>
        </td>
    </tr>
    <?php endif;?>
    
</table>

<div class="study-statistics-box">
<?php /*
<table class="grid-table">
 <tr>
    <td class="label"><?php echo t('created_on');?></td>
    <td class="value"><?php echo date("M d, Y",$created);?></td>
    </tr>
    <tr>
    <td class="label"><?php echo t('last_modified');?></td>
    <td class="value"><?php echo date("M d, Y",$changed);?></td>
    </tr>
<tr>
	<td class="label"><?php echo t('page_views');?></td>
    <td class="value"><?php echo $total_views;?></td>
</tr>
<tr>
	<td class="label"><?php echo t('downloads');?></td>
    <td class="value"><?php echo $total_downloads;?></td>
</tr>
</table>
*/ ?>

<div>
    <div class="label"><?php echo t('created_on');?></div>
    <div class="value" itemprop="dateCreated"><?php echo date("M d, Y",$created);?></div>
</div>

<div>
    <div class="label"><?php echo t('last_modified');?></div>
    <div class="value" itemprop="dateModified"><?php echo date("M d, Y",$changed);?></div>
</div>
<div>
	<div class="label"><?php echo t('page_views');?></div>
    <div class="value"><?php echo $total_views;?></div>
</div>

</div>


</div>
<div class="content-container study-metadata" style="overflow:auto;margin-bottom:10px;">
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
    	<td><?php echo t('refno');?></td>
        <td><?php echo $refno;?></td>
    </tr>
	<tr>
    	<td style="width:100px;"><?php echo t('year');?></td>
        <td><?php 
				if ($data_coll_start==$data_coll_end)
				{
					echo $data_coll_start;
				}
				else
				{
					if ($data_coll_start!='')
					{
						$dates[]=$data_coll_start;
					}
					if ($data_coll_end!='')
					{
						$dates[]=$data_coll_end;
					}						
					echo implode(" - ", $dates);
				}?>
        </td>
    </tr>
	<?php if ($nation!=''):?>
	<tr>
    	<td><?php echo t('country');?></td>
        <td><?php echo $nation;?></td>
    </tr>
	<?php endif;?>
	<tr valign="top">
    	<td><?php echo t('producers');?></td>
        <td>
        	<?php if (isset($authenty)):?>
				<?php $authenty_arr=json_decode($authenty);?>
                <?php if (is_array($authenty_arr)):?>
                    <?php echo implode("<BR>",$authenty_arr);?>
                <?php else:?>
                    <?php echo $authenty;?>
                <?php endif;?>
            <?php endif;?>    
        </td>
    </tr>
    <?php if (strlen($sponsor)>5):?>
	<tr valign="top">
    	<td><?php echo t('sponsors');?></td>
        <td><?php echo $sponsor;?></td>
    </tr>
    <?php endif;?>

	<?php if (isset($repositories) && is_array($repositories) && count($repositories)>0): ?>
	<tr valign="top">
    	<td><?php echo t('collections');?></td>
        <td>
		<?php foreach($repositories as $repository):?>
			<div class="collection"><?php echo anchor('catalog/'.$repository['repositoryid'],$repository['title']);?></div>
		<?php endforeach;?>
        </td>
    </tr>
	<?php endif;?>

	<?php $report_file=unix_path($this->survey_folder.'/ddi-documentation-'.$this->config->item("language").'-'.$id.'.pdf');?>
    <tr>
    	<td><?php echo t('metadata');?></td>
        <td class="links">
            <?php if (file_exists($report_file)):?>
            <span class="link-col sep">
                <a href="<?php echo site_url()."/ddibrowser/$id/export/?format=pdf&generate=yes";?>" title="<?php echo t('pdf');?>" rel="nofollow">
                <img border="0" title="<?php echo t('link_pdf');?>" alt="PDF" src="images/pdf.gif" /> <?php echo t('documentation_in_pdf');?>
                </a> 
            </span>
            <?php endif;?>
            <span class="link-col sep"><a href="<?php echo site_url('catalog/ddi').'/'.$id;?>"><?php echo t('download_ddi');?></a></span>
            <?php if ($has_resources):?>
            <span class="link-col"><a href="<?php echo site_url('catalog/rdf').'/'.$id;?>"><?php echo t('download_rdf');?></a></span>    
            <?php endif;?>
        </td>
    </tr>
    
    <?php if($link_indicator!='' || $link_study!=''): ?>
    <tr>
    <td></td>
    <td>
			<!-- indicators -->
            <span class="link-col">
			 <?php if($link_indicator!=''): ?>
                <a target="_blank"  href="<?php echo site_url("/catalog/$id/link/interactive");?>" title="<?php echo t('link_indicators_hover');?>">
                    <img border="0" alt="<?php echo t('link_indicators');?>" src="images/page_white_database.png" /> <?php echo t('link_indicators_hover');?>
                </a>
            <?php endif; ?>
	        </span>
            
            <span class="link-col">
            <?php if($link_study!=''): ?>
                    <a  target="_blank" href="<?php echo site_url("/catalog/$id/link/study-website");?>" title="<?php echo t('link_study_website_hover');?>">
                        <img border="0" title="<?php echo t('link_study_website_hover');?>" alt="<?php echo t('link_study_website');?>" src="images/page_white_world.png" /> <?php echo t('link_study_website');?>
                    </a>
            <?php endif; ?>
        	</span>
        </td>
    </tr>
    <?php endif;?>
    
</table>

<div class="study-statistics-box">
<table class="grid-table">
 <tr>
    <td><?php echo t('created_on');?></td>
    <td><?php echo date("M d, Y",$created);?></td>
    </tr>
    <tr>
    <td><?php echo t('last_modified');?></td>
    <td><?php echo date("M d, Y",$changed);?></td>
    </tr>
<tr>
	<td><?php echo t('page_views');?></td>
    <td><?php echo $total_views;?></td>
</tr>
<tr>
	<td><?php echo t('downloads');?></td>
    <td><?php echo $total_downloads;?></td>
</tr>
</table>
</div>


</div>
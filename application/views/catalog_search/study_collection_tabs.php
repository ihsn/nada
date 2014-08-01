<?php
//active tab=active_tab
$active_class="ui-tabs-active ui-state-active";
if(!isset($active_tab))
{
	$active_tab='catalog';
}

if (isset($repo) && isset($repo['repositoryid'])){
}
else{
	$repo=array(
			'repositoryid'	=>'central',
			'title'			=>t('central_data_catalog')
			);			
}
?>

<?php if(isset($repo['ispublished']) && intval($repo['ispublished'])===0):?>
	<div class="content-unpublished"><?php echo t('content_is_not_published');?></div>
<?php endif;?>



<h1><?php echo $repo['title'];?></h1>
<div class="tab-style-1">
<div id="collection-tabs" class="ui-tabs ui-widget ui-widget-content ui-corner-all study-tabs ">
  <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all study-info-tabs" role="tablist" >
    <?php if (isset($repo) && isset($repo['repositoryid']) && $repo['repositoryid']=='central'):?>
    <li class="ui-state-default ui-corner-top <?php echo ($active_tab=='about') ? $active_class : '' ?>" role="tab" tabindex="0" aria-controls="tabs-1" aria-labelledby="ui-id-1" aria-selected="false"><a href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>/about" class="ui-tabs-anchor" role="presentation" tabindex="-1" ><?php echo t('tab_collections');?></a></li>
	<?php else:?>
    <li class="ui-state-default ui-corner-top <?php echo ($active_tab=='about') ? $active_class : '' ?>" role="tab" tabindex="0" aria-controls="tabs-1" aria-labelledby="ui-id-1" aria-selected="false"><a href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>/about" class="ui-tabs-anchor" role="presentation" tabindex="-1" ><?php echo t('tab_about');?></a></li>
    <?php endif;?>
    <li class="ui-state-default ui-corner-top <?php echo ($active_tab=='catalog') ? $active_class : '' ?>" role="tab" tabindex="-1" aria-controls="tabs-23" aria-labelledby="ui-id-23" aria-selected="true"><a href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>" class="ui-tabs-anchor" role="presentation" tabindex="-1" ><?php echo t('tab_datasets');?></a></li>
    <?php if (isset($repo_citations_count) && $repo_citations_count>0):?>
    <li class="ui-state-default ui-corner-top <?php echo ($active_tab=='citations') ? $active_class : '' ?>" role="tab" tabindex="-1" aria-controls="tabs-3" aria-labelledby="ui-id-4" aria-selected="false"><a href="<?php echo site_url('citations/?collection='.$repo['repositoryid']);?>" class="ui-tabs-anchor" role="presentation" tabindex="-1" ><?php echo t('tab_citations');?></a></li>
    <?php endif;?>
  </ul>
  <div id="tabs-1" aria-labelledby="ui-id-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom" role="tabpanel" aria-expanded="true" aria-hidden="false">  	
	  <?php echo $content;?>  
  </div>
  
</div>
</div>
<table style="display:none;" width="100%" class="catalog-page-title" cellpadding="0" cellspacing="0" border="0">
<tr valign="baseline">
<td><h2><?php echo $this->page_title;?></h2></td>
<td align="right">
<div class="page-links">
	<a id="link_export" title="<?php echo t('link_export_search');?>" href="<?php echo site_url();?>/catalog/export"><img src="images/export.gif" border="0" alt="Export"/></a>
    <a title="<?php echo t('rss_feed');?>" href="<?php echo site_url();?>/catalog/rss" target="_blank"><img src="images/rss_icon.png" border="0" alt="RSS"/></a>
</div>
</td>
</tr>
</table>

<form name="search_form" id="search_form" method="get" autocomplete = "off">
<input type="hidden" id="view" name="view" value="<?php echo (isset($this->view) && $this->view=='v') ? 'v': 's'; ?>"/>
<input type="hidden" id="ps" name="ps" value="<?php echo $this->limit; ?>"/>
<input type="hidden" id="page" name="page" value="<?php echo $current_page; ?>"/>
<input type="hidden" id="repo" name="repo" value="<?php echo $active_repo; ?>"/>
<input type="hidden" id="_r" name="_r" value=""/>
<div> 


<?php
$fac_filters=array();
?>

	<!--keywords filter-->
	<?php  $this->load->view("catalog_search/filter_keywords",array('repoid'=>$active_repo)); ?>
    
    <!--year filter-->
    <?php if ($this->config->item("year_search")=='yes'):?>
		<?php $fac_filters[(int)$this->config->item("year_search_weight")]['year']= $this->load->view("catalog_search/filter_years",array('repoid'=>$active_repo),TRUE); ?>
    <?php endif;?>
    
	<!-- country filter-->
	<?php if ($this->regional_search=='yes'):?>
    	<?php  $fac_filters[(int)$this->config->item("regional_search_weight")]['country']=$this->load->view("catalog_search/filter_countries",array('active_repo',$active_repo),TRUE); ?>
	<?php endif;?>

	<!-- da filter -->
    <?php if ($this->config->item("da_search")=='yes' && is_array($da_types) && count($da_types)>0):?>
    	<?php  $fac_filters[(int)$this->config->item("da_search_weight")]['da']=$this->load->view("catalog_search/filter_da",NULL,TRUE); ?>
    <?php endif;?>    
    <!-- end da filter -->

	<?php if ($this->center_search=='yes'):?>
        <!-- center filter-->
        <?php  $this->load->view("catalog_search/filter_centers"); ?>
	<?php endif;?>    
    
    <?php if ($this->collection_search=='yes' && $active_repo==''):?>
        <?php  $fac_filters[(int)$this->config->item("collection_search_weight")]['collection']=$this->load->view("catalog_search/filter_collections",NULL,TRUE); ?>
	<?php endif;?>

    <?php if($this->topic_search==='yes'):?>
	    <?php  $fac_filters[(int)$this->config->item("topic_search_weight")]['topic']=$this->load->view("catalog_search/filter_topics",NULL,TRUE); ?>
    <?php endif;?>
    
    <?php ksort($fac_filters);?>
    <?php foreach($fac_filters as $key=>$filter):?>
    	<?php if(is_array($filter)):?>
        	<?php echo implode("",$filter);?>
        <?php else:?>
    	<?php echo $filter;?>
        <?php endif;?>
    <?php endforeach;?>    
    
</div>
</form>



<script type="text/javascript">
//translations	
var i18n=
{
'searching':"<?php echo t('js_searching');?>",
'loading':"<?php echo t('js_loading');?>",
'invalid_year_range_selected':"<?php echo t('js_invalid_year_range_selected');?>",
'topic_selected':"<?php echo t('js_topic_selected');?>",
'topics_selected':"<?php echo t('js_topics_selected');?>",
'collection_selected':"<?php echo t('js_collection_selected');?>",
'collections_selected':"<?php echo t('js_collections_selected');?>",
'country_selected':"<?php echo t('js_country_selected');?>",
'countries_selected':"<?php echo t('js_countries_selected');?>",
'center_selected':"<?php echo t('js_center_selected');?>",
'centers_selected':"<?php echo t('js_centers_selected');?>",
'collection_selected':"<?php echo t('js_collection_selected');?>",
'collections_selected':"<?php echo t('js_collections_selected');?>",
'cancel':"<?php echo t('cancel');?>",
'js_compare_variables_selected':"<?php echo t('variables selected from');?>",
'js_compare_studies_selected':"<?php echo t('studies');?>",
'js_compare_variable_select_atleast_2':"<?php echo t('Select two or more variables to compare');?>"
};

//min/max years
var years = {'from': '<?php reset($years);echo current($years); ?>', 'to': '<?php echo end($years); ?>'}; 
</script>
<?php $this->load->view("catalog_search/active_filter_tokens");?>

<?php if (isset($rows)): ?>
<?php if ($rows): ?>

<?php
	//sort
	$sort_by=$search_options->sort_by;
	$sort_order=$search_options->sort_order;

	//set default sort
	if(!$sort_by)
	{
		$sort_by='name';
	}
	
	//current page url with query strings
	$page_url=site_url().'/catalog/';		

	//page querystring for variable sub-search
	$variable_querystring=get_sess_querystring( array('sk', 'vk', 'vf','view'),'search');
	
	//variables selected for compare
	$compare_items=explode(",",$this->input->cookie('variable-compare', TRUE));
?>

<input type="hidden"  id="sort_order" value="<?php echo $sort_order;?>"/>
<input type="hidden" id="sort_by" value="<?php echo $sort_by;?>"/>

<table style="width:100%;" border="0" cellpadding="0" cellspacing="0">
<tr>
<td>
<div class="catalog-sort-links">
<?php echo t('sort_results_by');?>:
<?php 
	//name
	echo create_sort_link($sort_by,$sort_order,'name',t('name'),$page_url,array('sk','vk','vf','view') );
  echo "| "; 	
	//label	
	echo create_sort_link($sort_by,$sort_order,'labl',t('label'),$page_url,array('sk','vk','vf', 'view') );
  echo "| ";  
	//titl
	echo create_sort_link($sort_by,$sort_order,'titl',t('field_survey_title'),$page_url,array('sk','vk','vf','view') );
    	
	//nation	
	if ($this->config->item("regional_search")=='yes')
	{
		echo "| ";  
		echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('sk','vk','vf','view') ); 
	}	
?>
</div>
</td>
<td align="right">
	<a class="" href="#" onclick="change_view('s');return false;"><?php echo t('switch_to_study_view');?></a> | 
	<a class="btn-compare-var" target="_blank" title="<?php echo t('compare_hover');?>" target="_blank" href="<?php echo site_url(); ?>/catalog/compare"><?php echo t('compare');?></a>
    </td>
</tr>
</table>
<?php 
	//current page url
	$page_url=site_url().$this->uri->uri_string();
	
	//total pages
	$pages=ceil($found/$limit);	
?>

<div class="pagination">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr valign="middle">
	<td>
         <?php echo sprintf(t('showing_variables'),
							(($limit*$current_page)-$limit+1),
							($limit*($current_page-1))+ count($rows),
							$found);?>
     </td>
    <td align="right"><?php $pager_bar=(pager($found,$limit,$current_page,5));echo $pager_bar;?></td>
</tr>
</table>
</div>

<?php $tr_class=""; ?>
<div class="variable-list-container">
	<table class="grid-table variable-list" cellpadding="0" cellspacing="0" width="100%">
        	<tr class="header">
        	<td><?php echo anchor('catalog/compare',t('compare'), array('class'=>'btn-compare-var','title'=>t('compare_selected_variables'),'target'=>'_blank'));?></td>
            <td><?php echo t('name');?></td>
            <td><?php echo t('label');?></td>
        </tr>	

	<?php foreach($rows as $row):?>
  		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <?php 
			$compare='';	
			//compare items selected
			if (in_array($row['surveyid_FK'].'/'.$row['varID'], $compare_items) )
			{  
				$compare=' checked="checked" ';
			} 
		?>
    	<tr  class="vrow <?php echo $tr_class; ?>" valign="top" data-url="<?php echo site_url('catalog/'.$row['surveyid_FK'].'/variable/'.$row['varID']); ?>" data-url-target="_blank" data-title="<?php echo $row['labl'];?>" title="<?php echo t('variable_info');?>">
	        <td title="<?php echo t('mark_for_variable_comparison');?>">
            	<input type="checkbox" class="compare" value="<?php echo $row['surveyid_FK'].'/'.$row['varID'] ?>" <?php echo $compare; ?>/>
             </td>
            <td><?php echo anchor('catalog/'.$row['surveyid_FK'].'/variable/'.$row['varID'],$row['name'],array('target'=>'blank_','class'=>'dlg','title'=>t('variable_info')));?></td>
            <td>
				<div class="labl" ><?php echo ($row['labl']!=='') ? $row['labl'] : $row['name']; ?></div>
				<div class="var-subtitle"><?php echo $row['nation']. ' - '.$row['titl']; ?></div>
            </td>
        </tr>
    <?php endforeach;?>
	</table>
</div>

<div class="pagination">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr valign="middle">
	<td>
         <?php echo sprintf(t('showing_variables'),
							(($limit*$current_page)-$limit+1),
							($limit*($current_page-1))+ count($rows),
							$found);?>
     </td>
    <td align="right">
        <span>
        <?php echo $pager_bar;?>
        </span>
    </td>
</tr>
</table>
</div>

<div class="light switch-page-size">
    <?php echo t('select_number_of_records_per_page');?>:
    <span class="btn btn-mini">15</span>
    <span class="btn btn-mini">30</span>
    <span class="btn btn-mini">50</span>
    <span class="btn btn-mini">100</span>
</div>
<script type="text/javascript">
	var sort_info = {'sort_by': '<?php echo $sort_by;?>', 'sort_order': '<?php echo $sort_order;?>'};
</script>
    
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
<?php endif; ?>
<?php $this->load->view('tracker/tracker');?>
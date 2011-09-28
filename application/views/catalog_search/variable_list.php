<?php if (isset($rows)): ?>
<?php if ($rows): ?>

<?php		
	//sort
	$sort_by=$this->sort_by;
	$sort_order=$this->sort_order;

	//set default sort
	if(!$sort_by)
	{
		$sort_by='name';
	}
	
	//current page url with query strings
	$page_url=site_url().'/catalog/';		

	//page querystring for variable sub-search
	$variable_querystring=get_sess_querystring( array('sk', 'vk', 'vf','view'),'search');
	
	$compare_items=$this->session->userdata('compare');
?>
<table style="width:100%;" border="0" cellpadding="0" cellspacing="0">
<tr>
<td>
<div class="catalog-sort-links">
<?php echo t('sort_results_by');?>:
<?php 
	//name
	echo create_sess_sort_link('search',$sort_by,$sort_order,'name',t('name'),$page_url,array('sk','vk','vf','view') );
  echo "| "; 	
	//label	
	echo create_sess_sort_link('search',$sort_by,$sort_order,'labl',t('label'),$page_url,array('sk','vk','vf', 'view') );
  echo "| ";  
	//titl
	echo create_sess_sort_link('search',$sort_by,$sort_order,'titl',t('field_survey_title'),$page_url,array('sk','vk','vf','view') );
    	
	//nation	
	if ($this->config->item("regional_search")=='yes')
	{
		echo "| ";  
		echo create_sess_sort_link('search',$sort_by,$sort_order,'nation',t('country'),$page_url,array('sk','vk','vf','view') ); 
	}	
?>
</div>
</td>
<td align="right">
	<a class="" href="#" onclick="change_view('s');return false;"><?php echo t('switch_to_study_view');?></a> | 
	<a class="dlg" title="<?php echo t('compare_hover');?>" target="_blank" href="<?php echo site_url(); ?>/catalog/compare"><?php echo t('compare');?></a>
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
    <td align="right">
        <span class="page-link">
        <?php if ($current_page>1):?>
        	<a title="Prev page" href="#" onclick="search_page(<?php echo $current_page-1; ?>);return false;">&laquo;</a>
        <?php else:?>
	       <?php //&laquo;?>
        <?php endif; ?>
        </span>  

		<?php 
			$page_dropdown='<select name="page" id="page" onchange="advanced_search()">';
			for($i=1;$i<=$pages;$i++)
			{
                $page_dropdown.='<option '. (($current_page==$i) ? 'selected="selected"' : '').'>'.$i.'</option>';
            }
        	$page_dropdown.='</select>';
		?>        
		<?php echo sprintf(t('showing_pages'),$page_dropdown,$pages);?>

		<span class="page-link">
        <?php if ($current_page<$pages):?>
        	<a title="Next page" href="#" onclick="search_page(<?php echo $current_page+1; ?>);return false;">&raquo;</a>
        <?php else:?>
	        <?php //&raquo;?>
        <?php endif; ?>
        </span>
    </td>
</tr>
</table>
</div>

<?php $tr_class=""; ?>
	<table class="grid-table" cellpadding="0" cellspacing="0" width="100%">
        	<tr class="header">
        	<td><?php echo anchor('catalog/compare',t('compare'), array('class'=>'dlg','title'=>t('compare_selected_variables')));?></td>
            <td><?php echo t('name');?></td>
            <td><?php echo t('label');?></td>
            <td>&nbsp;</td>
        </tr>	

	<?php foreach($rows as $row):?>
  		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <?php 
			$compare='';	
			//compare items selected
			if (isset($compare_items[$row['surveyid_FK'].':'.$row['varID']]) )
			{  
				$compare=' checked="checked" ';
			} 
		?>
    	<tr  class="<?php echo $tr_class; ?>" valign="top">
	        <td style="color:gray;" title="<?php echo t('mark_for_variable_comparison');?>"><input type="checkbox" class="compare" value="<?php echo $row['surveyid_FK'].'/'.$row['varID'] ?>" <?php echo $compare; ?>/></td>
            <td><?php echo anchor('catalog/'.$row['surveyid_FK'].'/variable/'.$row['varID'],$row['name'],array('target'=>'blank_','class'=>'dlg','title'=>t('variable_info')));?></td>
            <td>
				<div class="labl" ><?php echo ($row['labl']!=='') ? $row['labl'] : $row['name']; ?></div>
				<div style="color:#666666"><?php echo $row['nation']. ' - '.$row['titl']; ?></div>
            </td>
            <td><?php echo anchor('catalog/'.$row['surveyid_FK'].'/variable/'.$row['varID'],'<img src="images/icon_question.gif" border="0"/>',array('target'=>'blank_','class'=>'dlg','title'=>$row['labl']));?></td>
        </tr>
    <?php endforeach;?>
	</table>

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
        <span class="page-link">
        <?php if ($current_page>1):?>
        	<a title="Prev page" href="#" onclick="search_page(<?php echo $current_page-1; ?>);return false;">&laquo;</a>
        <?php else:?>
	       <?php //&laquo;?>
        <?php endif; ?>
        </span>  

		<?php 
			$page_dropdown='<select name="page2" id="page2" onchange="navigate_page()">';
			for($i=1;$i<=$pages;$i++)
			{
                $page_dropdown.='<option '. (($current_page==$i) ? 'selected="selected"' : '').'>'.$i.'</option>';
            }
        	$page_dropdown.='</select>';
		?>        
		<?php echo sprintf(t('showing_pages'),$page_dropdown,$pages);?>

		<span class="page-link">
        <?php if ($current_page<$pages):?>
        	<a title="Next page" href="#" onclick="search_page(<?php echo $current_page+1; ?>);return false;">&raquo;</a>
        <?php else:?>
	        <?php //&raquo;?>
        <?php endif; ?>
        </span>
    </td>
</tr>
</table>
</div>

<span class="light switch-page-size">
    <?php echo t('select_number_of_records_per_page');?>:
    <span class="button">15</span>
    <span class="button">30</span>
    <span class="button light">50</span>
    <span class="button light">100</span>
</span>
<script type="text/javascript">
	var sort_info = {'sort_by': '<?php echo $sort_by;?>', 'sort_order': '<?php echo $sort_order;?>'};
</script>
    
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
<?php endif; ?>
<?php $this->load->view('tracker/tracker');?>
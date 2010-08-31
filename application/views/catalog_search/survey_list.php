<?php if (count($rows)>0): ?>
<?php		
	//citations
	if ($citations===FALSE)
	{
		$citations=array();
	}
		
	//sorting
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");	
	
	//current page url with query strings
	$page_url=site_url().'/catalog/';		

	//page querystring for variable sub-search
	$variable_querystring=get_querystring( array('sk', 'vk', 'vf'));
	
	//page querystring for variable sub-search
	$search_querystring='?'.get_querystring( array('sk', 'vk', 'vf','view','topic','country'));	
?>
<table style="width:100%;" border="0" cellpadding="0" cellspacing="0">
<tr>
<td>
<div class="catalog-sort-links">
<?php echo t('sort_results_by');?>:
<?php
  //nation  
  if ($this->config->item("regional_search")=='yes')
  {
    echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('sk','vk','vf') );
    echo "| "; 
  }
   
  //year  
  echo create_sort_link($sort_by,$sort_order,'proddate',t('year'),$page_url,array('sk','vk','vf') ); 
  echo "| ";
   
	//titl
	echo create_sort_link($sort_by,$sort_order,'titl',t('title'),$page_url,array('sk','vk','vf') );
?>
</div>
</td>
<td align="right">
	<?php if ($this->input->get('vk')!=''):?>
     <a href="#" onclick="change_view('v');return false;"><?php echo t('switch_to_variable_view');?></a> |
     <a class="dlg" title="<?php echo t('compare_hover_text');?>" target="_blank" href="<?php echo site_url(); ?>/catalog/compare"><?php echo t('compare');?></a>
    <?php endif;?> 
</td>
</tr>
</table>
<?php 
	//sort
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	
	//current page url
	$page_url=site_url().$this->uri->uri_string();
	
	//total pages
	$pages=ceil($found/$limit);	
?>

<div class="pagination">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr valign="middle">
	<td><?php 			
			if ($found==1)
			{
				echo sprintf(t('found_study'),$found,$total);
			}
			else
			{
				echo sprintf(t('found_studies'),$found,$total);
			}	
		?>
   </td>
    <td align="right">
        <span class="page-link">
        <?php if ($current_page>1):?>
        	<a title="Prev page" href="<?php echo site_url().'/catalog/'.$search_querystring.'&page='.($current_page-1); ?>" 
            		onclick="search_page(<?php echo $current_page-1; ?>);return false;">&laquo;</a>
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
        	<a title="Next page" href="<?php echo site_url().'/catalog/'.$search_querystring.'&page='.($current_page+1); ?>" onclick="search_page(<?php echo $current_page+1; ?>);return false;">&raquo;</a>
        <?php else:?>
	        <?php //&raquo;?>
        <?php endif; ?>
        </span>
    </td>
</tr>
</table>
</div>

<?php foreach($rows as $row): ?>
	<div class="survey-row">
        <div class="title">
        <a href="<?php echo site_url(); ?>/catalog/<?php echo $row['id']; ?>" class="dlg" title="<?php echo $row['titl']; ?>">
		<?php if ($this->regional_search=='yes'):?>
	        <?php echo $row['nation']. ' - ';?>
        <?php endif;?>
		<?php echo $row['titl'];?>
        </a>
        </div>
        <div class="sub-title"><?php echo $row['authenty'];?>, <em><?php echo $row['refno'];?></em></div>

		<?php if ( isset($row['var_found']) ): ?>
        <div class="variables-found">
        		<a class="vsearch" style="outline:none;" href="<?php echo site_url(); ?>/catalog/vsearch/<?php echo $row['id']; ?>/?<?php echo $variable_querystring; ?>">
        			<?php echo sprintf(t('variables_keywords_found'),$row['var_found'],$row['varcount']);?>
                	<img class="open-close" src="images/next.gif"/>
                </a>
                <span class="vsearch-result"></span>
        </div>
        <?php endif; ?>
        <div class="survey-icons">
            <div style="float: left;">                

                <a target="_blank" title="<?php echo t('link_browse_metadata_hover');?>" href="<?php echo site_url().'/ddibrowser/'.$row['id'];?>">
                <span><img src="images/page_white_cd.png" /><?php echo t('link_browse_metadata');?></span>
                </a>
                
                <a target="_blank" id="ap-<?php echo $row['id'];?>" class="accesspolicy"  title="<?php echo t('link_access_policy_hover');?>" href="<?php echo site_url().'/ddibrowser/'.$row['id'].'/accesspolicy/?ajax=1&css=1';?>">
                	<span><img src="images/page_white_key.png" /><?php echo t('link_access_policy');?></span>
                </a>
                                
                <?php if($row['form_model']=='direct'): ?>
                    <a href="<?php echo site_url().'/access_direct/'.$row['id'];?>" class="accessform" title="<?php echo t('link_data_direct_hover');?>">
                    <span><img src="images/form_direct.gif" /><?php echo t('link_data');?></span>
                    </a>                    
                <?php elseif($row['form_model']=='public'): ?>                    
                    <a href="<?php echo site_url().'/access_public/'.$row['id'];?>" class="accessform"  title="<?php echo t('link_data_public_hover');?>">
                    <span><img src="images/form_public.gif" /><?php echo t('link_data');?></span>
                    </a>                    
                <?php elseif($row['form_model']=='licensed'): ?>
                    <a href="<?php echo site_url().'/access_licensed/'.$row['id'];?>" class="accessform"  title="<?php echo t('link_data_licensed_hover');?>">
                    <span><img src="images/form_licensed.gif" /><?php echo t('link_data');?></span>
                    </a>                    
                <?php elseif($row['form_model']=='data_enclave'): ?>
                    <a href="<?php echo site_url().'/access_enclave/'.$row['id'];?>" class="accessform"  title="<?php echo t('link_data_enclave_hover');?>">
                    <span><img src="images/form_enclave.gif" /><?php echo t('link_data');?></span>
                    </a>                    
                <?php endif; ?>

                <?php if (in_array($row['id'],$citations)): ?>
                    <a href="<?php echo site_url().'/catalog/citations/'.$row['id'];?>" title="<?php echo t('link_citations_hover');?>" class="dlg">
                    <span><img src="images/book_open.png" /><?php echo t('link_citations');?></span>
                    </a>                    
                <?php endif;?>
                
            </div>
            <div style="float: right;">
				<?php if($row['link_report']!=''): ?>
                    <a target="_blank" href="<?php echo site_url().'/catalog/download/'.$row['id'].'/'.base64_encode($row['link_report']);?>" title="<?php echo t('link_reports_hover');?>">
                        <img border="0" title="<?php echo t('link_reports');?>" alt="<?php echo t('link_reports');?>" src="images/report.png" />
                    </a>
                <?php endif; ?>

                <?php if($row['link_indicator']!=''): ?>
                    <a target="_blank"  href="<?php echo site_url().'/catalog/download/'.$row['id'].'/'.base64_encode($row['link_indicator']);?>" title="<?php echo t('link_indicators_hover');?>">
                        <img border="0" alt="<?php echo t('link_indicators');?>" src="images/page_white_database.png" />
                    </a>
                <?php endif; ?>

                <?php if($row['link_questionnaire']!=''): ?>
                    <a target="_blank"  href="<?php echo site_url().'/catalog/download/'.$row['id'].'/'.base64_encode($row['link_questionnaire']);?>" title="<?php echo t('link_questionnaires_hover');?>">
                        <img border="0" alt="<?php echo t('link_questionnaires');?>" title="<?php echo t('link_questionnaires');?>" src="images/page_question.png" />
                    </a>
                <?php endif; ?>

                <?php if($row['link_technical']!=''): ?>
                    <a  target="_blank" href="<?php echo site_url().'/catalog/download/'.$row['id'].'/'.base64_encode($row['link_technical']);?>" title="<?php echo t('link_technical_hover');?>">
                        <img border="0" alt="<?php echo t('link_technical');?>" title="<?php echo t('link_technical_hover');?>" src="images/page_white_compressed.png" />
                    </a>
                <?php endif; ?>

                <?php if($row['link_study']!=''): ?>
                    <a  target="_blank" href="<?php echo site_url().'/catalog/download/'.$row['id'].'/'.base64_encode($row['link_study']);?>" title="<?php echo t('link_study_website_hover');?>">
                        <img border="0" title="<?php echo t('link_study_website_hover');?>" alt="<?php echo t('link_study_website');?>" src="images/page_white_world.png" />
                    </a>
                <?php endif; ?>

				 <?php //if($row['isshared']!=''): ?>
                    <a href="<?php echo site_url().'/catalog/ddi/'.$row['id'];?>" title="<?php echo t('link_ddi_hover');?>">
                        <img border="0" title="<?php echo t('link_ddi');?>" alt="DDI" src="images/ddi2.gif" />
                    </a>
                <?php //endif; ?>
            </div>
            <br/>
        </div>      		
    </div>    
<?php endforeach;?>

<div class="pagination">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr valign="middle">
	<td>
		<?php echo sprintf(t('showing_studies'),
            (($limit*$current_page)-$limit+1),
            ($limit*($current_page-1))+ count($rows),
            $total);
		?>
   </td>
    <td align="right">
        <span class="page-link">
        <?php if ($current_page>1):?>
        	<a title="Prev page" href="<?php echo site_url().'/catalog/'.$search_querystring.'&page='.($current_page-1); ?>" 
            		onclick="search_page(<?php echo $current_page-1; ?>);return false;">&laquo;</a>
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
        	<a title="Next page" href="<?php echo site_url().'/catalog/'.$search_querystring.'&page='.($current_page+1); ?>" onclick="search_page(<?php echo $current_page+1; ?>);return false;">&raquo;</a>
        <?php else:?>
	        <?php //&raquo;?>
        <?php endif; ?>
        </span>
    </td>
</tr>
</table>
</div>
    
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
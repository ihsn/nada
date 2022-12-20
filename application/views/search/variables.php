
<?php if (isset($variables['rows']) && count($variables['rows'])<1): ?>
    <?php $this->load->view("search/var_search_nav_bar");?>

    <div id="variables">
        <span class="result-types-summary">
            <span class="type-summary" data-types='<?php //echo htmlentities(json_encode($surveys['search_counts_by_type']), ENT_QUOTES, 'UTF-8'); ?>'>
                <?php //echo json_encode($surveys['search_counts_by_type']);?>
            </span>        
        </span>

        <div class="nada-search-no-result"><?php echo t('search_no_results');?></div>
        <div><span class="clear-search"><a href="<?php echo site_url('catalog');?>"><?php echo t('reset_search');?></a></span></div>
    </div>
    <?php return;?>
<?php endif; ?>


<?php 
	//current page url
	$page_url=site_url().$this->uri->uri_string();
	
	//total pages
	$pages=ceil($variables['found']/$variables['limit']);	
?>

<?php $this->load->view("search/var_search_nav_bar");?>
<?php //$this->load->view("search/active_filter_tokens");?>


<?php		
	
	//sorting
	$sort_by=$search_options->sort_by;
	$sort_order=$search_options->sort_order;

    /*
    //set default sort
	if(!$sort_by)
	{
		if ($this->config->item("regional_search")=='yes'){
			$sort_by='nation';
		}
		else
		{
			$sort_by='title';
		}
    }
    */

	//current page url with query strings
	$page_url=site_url().'/catalog/';		
	
	//page querystring for variable sub-search
	$variable_querystring=get_querystring( array('sk', 'vk', 'vf'));
	
	//page querystring for variable sub-search
    $search_querystring='?'.get_querystring( array('sk', 'vk', 'vf','view','topic','country'));
    
    $compare_items=explode(",",(string)$this->input->cookie('variable-compare', TRUE));
?>

<input type="hidden" name="sort_by" id="sort_by" value="<?php echo $sort_by;?>"/>
<input type="hidden" name="sort_order" id="sort_order" value="<?php echo $sort_order;?>"/>
<input type="hidden" name="ps" id="ps" value="<?php echo $search_options->ps;?>"/>
<input type="hidden" name="repo" id="repo" value="<?php echo html_escape($active_repo_id);?>"/>
    
<div id="variables">
    <span class="result-types-summary">
        <span class="type-summary" data-types='<?php //echo htmlentities(json_encode($surveys['search_counts_by_type']), ENT_QUOTES, 'UTF-8'); ?>'>
            <?php //echo json_encode($surveys['search_counts_by_type']);?>
        </span>        
    </span>


    <div class="variable-list-container variable-search">
            <div class="table-hover grid-table variable-list">
                
                <div><?php echo anchor('catalog/compare',t('compare'), array('class'=>'btn-compare-var','title'=>t('compare_selected_variables'),'target'=>'_blank'));?></div>

                <div class="variables-container border-top">
                <?php foreach($variables['rows'] as $row):?>
                    <?php
                    $compare='';
                    //compare items selected
                    if (in_array($row['sid'].'/'.$row['vid'], $compare_items) ){
                        $compare=' checked="checked" ';
                    }

                    $var_country_info=array_filter(array($row['nation'], $row['idno']));
                    $var_country_info=implode(" - ", $var_country_info);
                    ?>
                    <div class="row vrow pb-2 mb-2 border-bottom" valign="top" data-url="<?php echo site_url('catalog/'.$row['sid'].'/variable/'.$row['vid']); ?>" data-url-target="_blank" data-title="<?php echo $row['labl'];?>" title="<?php echo t('variable_info');?>">
                        <div class="col-md-1" title="<?php echo t('mark_for_variable_comparison');?>">
                            <input type="checkbox" class="nada-form-check-input compare" value="<?php echo $row['sid'].'/'
                                .$row['vid']
                            ?>" <?php echo $compare; ?>/>
                        </div>
						<div class="col">
                            <?php $title=array_unique(array_filter(array($row['name'],$row['labl'])));?>
                            <?php $title=implode(" - ", $title);?>
                            <div class="title font-weight-bold">
                                <a href="<?php echo site_url('catalog/'.$row['sid'].'/variable/'.$row['vid']);?>" target="_blank" class="dlg link" title="<?php t('variable_info');?>"><?php echo $title;?></a>
                            </div>
                            <?php if(isset($row['qstn'])):?>
                            <div class="qstn" ><?php echo (isset($row['qstn']) && $row['qstn']!=='') ? $row['qstn'] : 'n/a'; ?></div>
                            <?php endif;?>
                            <div class="var-subtitle var-study-link mt-2">
                                <a target="_blank" href="<?php echo site_url('catalog/'.$row['sid']);?>"><?php echo $row['title']; ?> <i class="fa fa-external-link" aria-hidden="true"></i></a>
                                <div><?php echo $var_country_info; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach;?>

                </div>
            </div>
        </div>


</div>
    <div class="nada-pagination border-top-none">
        <div class="row mt-3 mb-3 d-flex align-items-lg-center">
            <div class="col-12 col-md-5 col-lg-5 text-center text-md-left mb-2 mb-md-0">
                <?php echo sprintf(t('showing_variables'),
                    number_format(($variables['limit']*$current_page)-$variables['limit']+1),
                    number_format(($variables['limit']*($current_page-1))+ count($variables['rows'])),
                    number_format($variables['found']));
                ?>
            </div>

            <div class="col-12 col-md-8 col-lg-7 d-flex justify-content-center justify-content-lg-end text-center">
                <nav aria-label="Page navigation">
                    <?php
                    $catalog_url='catalog';
                    if(isset($active_repo) && isset($active_repo['repositoryid'])){
                        $catalog_url='catalog/'.$active_repo['repositoryid'];
                    }
                    $pager_bar=(pager($variables['found'],$variables['limit'],$current_page,5,$catalog_url));
                    echo $pager_bar;
                    ?>
                </nav>
            </div>
        </div>

    </div>

    <!-- set per page items size-->
    <div id="items-per-page" class="items-per-page light switch-page-size">
        <small>
            <?php echo t('select_number_of_records_per_page');?>:
            <span class="nada-btn change-page-size" data-value="15">15</span>
            <span class="nada-btn change-page-size" data-value="30">30</span>
            <span class="nada-btn change-page-size" data-value="50">50</span>
            <span class="nada-btn change-page-size" data-value="100">100</span>
        </small>
    </div>

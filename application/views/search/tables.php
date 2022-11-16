<?php if (isset($surveys['rows']) && count($surveys['rows'])<1): ?>
    <?php $this->load->view("search/search_nav_bar");?>

    <div id="surveys">
        <span class="result-types-summary">
            <span class="type-summary" data-types='<?php echo htmlentities(json_encode($surveys['search_counts_by_type']), ENT_QUOTES, 'UTF-8'); ?>'>
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
	$pages=ceil($surveys['found']/$surveys['limit']);	
?>

<?php 
if ($this->input->get("view")=="v"){
	if($found==1) {
		$items_found=t('found_variable');
	}
	else{
		$items_found=t('found_variables');
	}
}	
else{
	$found=$surveys['found'];
	$total=$surveys['total'];
    
    if($found==1) {
		$items_found=t('found_study');
	}
	else{
		$items_found=t('found_studies');
	}
}
?>

<?php $this->load->view("search/search_nav_bar");?>
<?php //$this->load->view("search/active_filter_tokens");?>



<hr/>

<?php		
	//citations
	if ($surveys['citations']===FALSE){
		$citations=array();
	}
	
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
?>

<input type="hidden" name="sort_by" id="sort_by" value="<?php echo $sort_by;?>"/>
<input type="hidden" name="sort_order" id="sort_order" value="<?php echo $sort_order;?>"/>
<input type="hidden" name="ps" id="ps" value="<?php echo $search_options->ps;?>"/>
<input type="hidden" name="repo" id="repo" value="<?php echo html_escape($active_repo_id);?>"/>

<?php if(isset($featured_studies) && $featured_studies!==FALSE ):?>
        <!-- survey-row -->
        <div class="survey-row featured-study">
            <span class="badge badge-warning featured-study-tag"><?php echo t('featured_study');?></span>
            <div class="row" data-url="<?php echo site_url('catalog/'.$featured_studies['id']);?>" title="View study">
                <div class="col-2 col-lg-1">
                    <i class="icon-da icon-da-<?php echo $featured_studies['model'];?>" title="<?php echo t("legend_data_".$featured_studies['model']);?>"></i>
                </div>
                <div class="col-10 col-lg-11">
                    <h4 class="pr-5">
                        <a href="<?php echo site_url('catalog/'.$featured_studies['id']); ?>"  title="<?php echo $featured_studies['title']; ?>"><?php echo $featured_studies['title'];?>
                        </a>
                    </h4>
                    <div class="study-country">
                        <?php echo $featured_studies['nation']. ',';?>
                        <?php
                        $survey_year=array();
                        $survey_year[$featured_studies['year_start']]=$featured_studies['year_start'];
                        $survey_year[$featured_studies['year_end']]=$featured_studies['year_end'];
                        $survey_year=implode('-',$survey_year);
                        ?>
                        <?php echo $survey_year!=0 ? $survey_year : '';?>
                    </div>
                    <div class="sub-title">                                                 
                        <?php echo $featured_studies['authoring_entity'];?>
                        
                        <?php if (isset($row['repo_title']) && $row['repo_title']!=''):?>
                            <div class="owner-collection">
                                <?php echo t('catalog_owned_by')?>: <a href="<?php echo site_url('catalog/'.$row['repositoryid']);?>"><?php echo $row['repo_title'];?></a>
                            </div>
                        <?php endif;?>
                    </div>
                    <div class="survey-stats">
                        <span><?php echo t('created_on');?>: <?php echo date('M d, Y',$featured_studies['created']);?></span>
                        <span><?php echo t('last_modified');?>: <?php echo date('M d, Y',$featured_studies['changed']);?></span>
                        <span><?php echo t('views');?>: <?php echo (int)$featured_studies['total_views'];?></span>                        
                    </div>
                </div>
            </div>
        </div>
        <!-- /survey-row -->
    <?php endif;?>

    
<?php 
    $type_icons=array(
        'survey'=>'fa-database',
        'microdata'=>'fa-database',
        'geospatial'=>'fa-globe-americas',
        'timeseries'=>'fa-clock-o',
        'document'=>'fa-file-text-o',
        'table'=>'fa-table',
        'visualization'=>'fa-pie-chart',
        'script'=>'fa-file-code-o',
        'image'=>'fa-camera',
    );
?>
    
<div id="surveys">
    <span class="result-types-summary">
        <span class="type-summary" data-types='<?php echo htmlentities(json_encode($surveys['search_counts_by_type']), ENT_QUOTES, 'UTF-8'); ?>'>
            <?php //echo json_encode($surveys['search_counts_by_type']);?>
        </span>        
    </span>
<?php foreach($surveys['rows'] as $row): ?>    
    <?php if(!isset($row['form_model'])){
        $row['form_model']='data_na';
    }
    ?>
    <div class="survey-row" data-url="<?php echo site_url('catalog/'.$row['id']); ?>" title="<?php echo t('View study');?>">
    <div class="row">
        <div class="col-2 col-lg-1">            
            <?php /* <i class="icon-da icon-da-<?php echo $row['form_model'];?>" title="<?php echo t("legend_data_".$row['form_model']);?>"></i> */?>
            <span class="fa-stack fa-lg fa-nada-<?php echo $row['type'];?>">
            <i class="fa fa-circle fa-stack-2x"></i>
            <i class="fa <?php echo $type_icons[$row['type']];?> fa-stack-1x fa-inverse fa-nada-icon"></i>
            </span> 
        </div>        
        
        <div class="col-10 col-lg-11">            
            <h5 class="title">
                <a href="<?php echo site_url('catalog/'.$row['id']); ?>"  title="<?php echo $row['title']; ?>" >                
                    <?php echo $row['title'];?> 
                    <?php /*?>
                    <?php if(isset($licenses) && !empty($row['license_id'])):?>
                    <span class="dataset-license-label ">
                        <?php //echo $row['type'];?>                        
                        <?php echo $licenses[$row['license_id']];?>
                    </span>
                    <?php endif;?>
                    <?php */ ?>
                </a>
            </h5>
            
            <div class="study-country">
                <?php if (isset($row['nation']) && $row['nation']!=''):?>
                        <?php echo $row['nation']. ',';?>
                <?php endif;?>
                <?php 
                    $survey_year=array();
                    $survey_year[$row['year_start']]=$row['year_start'];
                    $survey_year[$row['year_end']]=$row['year_end'];
                    $survey_year=implode('-',$survey_year);
                ?>
                <?php echo $survey_year!=0 ? $survey_year : '';?>                
            </div>
            <div class="sub-title">
                <?php if (isset($row['authoring_entity'])):?>
                <div>
                    <span class="study-by"><?php echo $row['authoring_entity'];?></span>
                </div>
                <?php endif;?>
                <?php if (isset($row['repo_title']) && $row['repo_title']!=''):?>
                    <div class="owner-collection"><?php echo t('catalog_owned_by')?>: <a href="<?php echo site_url('catalog/'.$row['repositoryid']);?>"><?php echo $row['repo_title'];?></a></div>
                <?php endif;?>
            </div>
            <div class="survey-stats">                
                <span><?php echo t('created_on');?>: <?php echo date('M d, Y',$row['created']);?></span>
                <span><?php echo t('last_modified');?>: <?php echo date('M d, Y',$row['changed']);?></span>
                <?php if ((int)$row['total_views']>0):?>
                    <span><?php echo t('views');?>: <?php echo (int)$row['total_views'];?></span>
                <?php endif;?>
                <?php if(isset($row['rank_'])):?>
                    <span> Score: <?php echo round($row['rank_'],2);?></span>
                <?php endif;?>

                <?php if(isset($licenses) && !empty($row['license_id'])):?>
                    <span class="dataset-license-labelz ">
                        <i class="fa fa-cog"></i>
                        <?php echo $licenses[$row['license_id']];?>
                    </span>
                <?php endif;?>
                
                <?php /* ?>
                <span><?php echo t('downloads');?>: <?php echo (int)$row['total_downloads'];?></span>
                <?php */?>
                <?php if (array_key_exists($row['id'],$surveys['citations'])): ?>
                    <span>
                    <a title="<?php echo t('related_citations');?>" href="<?php echo site_url('catalog/'.$row['id'].'/related_citations');?>"><?php echo t('citations');?>: <?php echo $surveys['citations'][$row['id']];?></a>
                    </span>                    
                <?php endif;?> 
            </div>
            
            <?php if ( isset($row['var_found']) ): ?>            
                <div class="variables-found" style="clear:both;">

                        <a class="vsearch" href="<?php echo site_url(); ?>/catalog/vsearch/<?php echo $row['id']; ?>/?<?php echo $variable_querystring; ?>">
                            
                        <div class="d-flex">                      
                            <div class="flex-grow-1">
                                <div class="heading-text"><?php echo sprintf(t('variables_keywords_found'),$row['var_found'],$row['varcount']);?></div>
                            </div>
                            <div class="toggle-arrow-bg">
                                <span class="toggle-arrow">
                                    <i class="toggle-arrow-right fa fa-caret-right" aria-hidden="true"></i>
                                    <i class="toggle-arrow-down fa fa-caret-down" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>
                            
                        </a>
                        <span class="vsearch-result"></span>
                        <div class="variable-footer">
                            <input class="btn btn btn-outline-primary btn-sm wb-btn-outline btn-style-1 btn-compare-var" type="button" name="compare-variable" value="Compare variables"/> 
                            <span class="var-compare-summary"><small><?php echo t('To compare, select two or more variables');?></small></span>
                        </div>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>

<?php endforeach;?>
</div>
    <div class="nada-pagination border-top-none">
        <div class="row mt-3 mb-3 d-flex align-items-lg-center">

            <div class="col-12 col-md-3 col-lg-4 text-center text-md-left mb-2 mb-md-0">
                <?php echo sprintf(t('showing_studies'),
                    (($surveys['limit']*$current_page)-$surveys['limit']+1),
                    ($surveys['limit']*($current_page-1))+ count($surveys['rows']),
                    $surveys['found']);
                ?>
            </div>

            <div class="col-12 col-md-9 col-lg-8 d-flex justify-content-center justify-content-lg-end text-center">
                <nav aria-label="Page navigation">
                    <?php
                    $catalog_url='catalog';
                    if(isset($active_repo) && isset($active_repo['repositoryid'])){
                        $catalog_url='catalog/'.$active_repo['repositoryid'];
                    }
                    $pager_bar=(pager($surveys['found'],$surveys['limit'],$current_page,5,$catalog_url));
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

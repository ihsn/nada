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
<?php //if($search_options->ps>15):?>
<input type="hidden" name="ps" id="ps" value="<?php echo $search_options->ps;?>"/>
<?php //endif;?>
<input type="hidden" name="repo" id="repo" value="<?php echo html_escape($active_repo_id);?>"/>
<input type="hidden" name="sid" id="sid" value="<?php echo $search_options->sid;?>"/>
    
<?php 
    $type_icons=array(
        'survey'=>'fa-database',
        'microdata'=>'fa-database',
        'geospatial'=>'fa-globe-americas',
        'timeseries'=>'fa-chart-line',
        'document'=>'fa-file-alt',
        'table'=>'fa-table',
        'visualization'=>'fa-pie-chart',
        'script'=>'fa-file-code',
        'image'=>'fa-image', 
        'video'=>'fa-video',
    );


    //default view with inline icon
    $row_col1_class="";
    $row_col2_class="col-md-12";
    $row_col1_type="";//type_icon, thumbnail, empty(none)

    switch ($tab_type){
        /*case ''://all
            $row_col1_type="type_icon_inline";
            $row_col1_class="col-md-2";
            $row_col2_class="col-md-10";
            break;*/
        case ''://all
        case 'microdata':
        case 'survey':
        case 'table':
        case 'timeseries':
        case 'document':
        case 'script':
        case 'geospatial':
            $row_col1_type="thumbnail";
            $row_col1_class="col-md-2";
            $row_col2_class="col-md-10";
            break;
    }

    $survey_rows_count=count($surveys['rows']);
    if(isset($featured_studies) && $featured_studies!==FALSE ){
        foreach($featured_studies as $feature_study){
            $feature_study['featured']=true;
            array_unshift($surveys['rows'],$feature_study);
        }        
    }
?>


<?php 
//IDs for all featured studies
$featured_studies_id_list=array();

if (isset($featured_studies) && is_array($featured_studies) ){

    foreach($featured_studies as $feature_study){
        $featured_studies_id_list[]=$feature_study['id'];
    }
}
?>

    
<div id="surveys">
    <span class="result-types-summary">
        <span class="type-summary" data-types='<?php echo htmlentities(json_encode($surveys['search_counts_by_type']), ENT_QUOTES, 'UTF-8'); ?>'>
            <?php //echo json_encode($surveys['search_counts_by_type']);?>
        </span>        
    </span>
<?php $is_featured_count=0;?>
<?php foreach($surveys['rows'] as $key=>$row): ?>    
    <?php     
        if(!isset($row['form_model'])){
            $row['form_model']='data_na';
        }
        
        $is_featured=isset($row['featured']) ? $row['featured'] : false;        

        if ($is_featured){
            $is_featured_count++;
        }

        //hide featured study duplicate entry
        if (!$is_featured && in_array($row['id'],$featured_studies_id_list)){
            continue;
        }

        if(isset($row['thumbnail']) && is_array($row['thumbnail'])){
            $row['thumbnail']=implode("",$row['thumbnail']);
        }

        if (empty($row['thumbnail'])){
            $row_col2_class="col-md-12";
        }else{
            $row_col2_class="col-md-10";
        }

        $row_collections=array();
        //var_dump($row);
        if(isset($row['repo_title'])){
            
            $row_collections[]=array(
                'repositoryid'=>$row['repositoryid'],
                'title'=>$row['repo_title'],
                'type'=>'owner'
            );
        }
        
        if(isset($related_collections) && array_key_exists($row['id'],$related_collections)){
            foreach($related_collections[$row['id']] as $collection_){
                $row_collections[]=$collection_;
            }
        }

        $collection_links=array();
        foreach($row_collections as $collection_){
            $collection_links[]='<a href="'.site_url('catalog/'.$collection_['repositoryid']).'">'.$collection_['title'].'</a>';
        }
        $collection_links=implode(" <span class=\"coll-sep\">|</span> ",$collection_links);
    ?>

    <div class="survey-row border-bottom pb-3 mb-2 <?php echo ($is_featured == true ? 'xwb-featured xfeatured-study': '');?>" data-url="<?php echo site_url('catalog/'.$row['id']); ?>" >
        
        <?php if ($is_featured): ?>
                    <?php $row['form_model']=$row['model'];?>
                    <div class="row mb-2">
                    <span class="badge badge-featured wb-featured-mark" >
                        <i class="fas fa-star"></i> <?php echo t('Featured');?>
                    </span>
                    </div>      
        <?php endif; ?>
        <div class="row">            
            <div class="<?php echo $row_col2_class;?>">                
                <h5 class="wb-card-title title">
                    <a href="<?php echo site_url('catalog/'.$row['id']); ?>"  title="<?php echo $row['title']; ?>" class="d-flex" >   
                        <i class="fa <?php echo $type_icons[$row['type']];?> fa-nada-icon wb-title-icon"></i>             
                        <span>
                            <?php echo $row['title'];?>
                            <?php if(isset($row['subtitle'])):?>
                                <div class="study-subtitle"><?php echo $row['subtitle'];?></div>
                            <?php endif;?>
                        </span>
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

                    <?php if ($row_collections):?>
                        <span class="owner-collection collection-link mr-3"><?php echo t('catalog_owned_by')?>:
                        <?php /*
                        <?php if (isset($row['repo_title']) && $row['repo_title']!=''):?>
                             <span><a href="<?php echo site_url('catalog/'.$row['repositoryid']);?>"><?php echo $row['repo_title'];?></a></span>
                        <?php endif;?>
                        
                            <?php foreach($row_collections as $related_collection):?>
                                <span>
                                <a href="<?php echo site_url('catalog/'.$related_collection['repositoryid']);?>"><?php echo $related_collection['title'];?></a>
                                </span>
                            <?php endforeach;?>
                            */?>
                            <?php echo $collection_links;?>
                        </span>
                    <?php endif;?>
                </div>
                <div class="survey-stats">
                <span class="study-idno">
                        <span class="wb-label"><?php echo t('ID')?>:</span> <span class="text-dark wb-value"><?php echo $row['idno'];?></span>
                    </span>

                    <?php /*<span><?php echo t('created_on');?>: <?php echo date('M d, Y',$row['created']);?></span> */ ?>
                    <span><span class="wb-label"><?php echo t('last_modified');?>:</span> <span class="wb-value"><?php echo date('M d, Y',$row['changed']);?></span></span>
                    <?php if ((int)$row['total_views']>0):?>
                        <span><span class="wb-label"><?php echo t('views');?>:</span> <span class="wb-value"><?php echo (int)$row['total_views'];?></span></span>
                    <?php endif;?>
                    <?php if(isset($row['rank_'])):?>
                        <span> <span class="wb-label">Score:</span> <span class="wb-value"><?php echo round($row['rank_'],2);?></span></span>
                    <?php endif;?>

                    
                    <?php /* ?>
                    <span><?php echo t('downloads');?>: <?php echo (int)$row['total_downloads'];?></span>
                    <?php */?>
                    <?php if (array_key_exists($row['id'],$surveys['citations'])): ?>
                        <span>
                            <span class="wb-label"><?php echo t('citations');?>:</span> <a title="<?php echo t('related_citations');?>" href="<?php echo site_url('catalog/'.$row['id'].'/related_citations');?>"><?php echo $surveys['citations'][$row['id']];?></a>
                        </span>                    
                    <?php endif;?>
                </div>

                <?php //Data license + data classification icons ?>
                <?php if($row['type']=='survey'):?>
                <div class="wb-license-classification">
                    <a href="<?php echo site_url('catalog/'.$row['id'].'/get-microdata');?>">
                    <span class="badge wb-data-access wb-badge btn-data-license-<?php echo $row['form_model'];?>" title="<?php echo t("link_data_".$row['form_model'].'_hover');?>">
                        <i class="icon-da-sm icon-da-<?php echo $row['form_model'];?>" ></i> <span class=""><?php echo t("legend_data_".$row['form_model']);?></span>
                    </span>
                    </a>
        
                    <?php if(isset($data_classifications) && !empty($row['data_class_id'])):?>
                        <?php if(isset($data_classifications[$row['data_class_id']]['code'])):?>
                            <span class="badge wb-badge-outline wb-badge-data-class wb-badge-data-class-<?php echo $data_classifications[$row['data_class_id']]['code'];?>">                       
                                <?php echo $data_classifications[$row['data_class_id']]['title'];?>
                            </span>
                        <?php endif;?>
                    <?php endif;?>
                    
                    <?php /* //TODO
                    <span class="text-secondary entry-flags" >
                        <span class="badge wb-badge-outline badge-secondary">API</span>
                        <span class="badge wb-badge-outline "><i class="fas fa-chart-area"></i></span>
                    </span>
                    */?>
                </div>
                <?php endif;?>
                
                <?php if ( isset($row['var_found']) ): ?>            
                    <div class="mt-3 variables-found" style="clear:both;">

                            <a class="vsearch" href="<?php echo site_url(); ?>/catalog/vsearch/<?php echo $row['id']; ?>/?<?php echo $variable_querystring; ?>">
                                
                            <div class="d-flex">                      
                                <div class="flex-grow-1">                                    
                                    <div class="heading-text"><?php echo sprintf(t('variables_keywords_found'),$row['var_found'],isset($row['varcount']) ? $row['varcount'] : 'N');?></div>
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

            <?php if($row_col1_class!=""):?>

                <?php //type icon ?>                
                <?php //type thumbnail ?>
                <?php if (!empty($row['thumbnail'])):?>
                    <div class="<?php echo $row_col1_class;?>  wb-col-media" >
                        <a href="<?php echo site_url('catalog/'.$row['id']); ?>">                        
                            <img src="<?php echo base_url();?>files/thumbnails/<?php echo basename($row['thumbnail']);?>?v=<?php echo $row['changed'];?>" alt="" class="img-fluid img-thumbnail rounded shadow-sm study-thumbnail"/>                        
                        </a>
                    </div>
                <?php endif;?>

            <?php endif;?>


        </div>
    </div> <!-- /.    row -->

<?php endforeach;?>
</div>
    <div class="nada-pagination border-top-none">
        <div class="row mt-3 mb-3 d-flex align-items-lg-center">

            <div class="col-12 col-md-3 col-lg-4 text-center text-md-left mb-2 mb-md-0">
                <?php echo sprintf(t('showing_studies'),
                    number_format(($surveys['limit']*$current_page)-$surveys['limit']+1),
                    number_format(($surveys['limit']*($current_page-1))+ $survey_rows_count),
                    number_format($surveys['found']));
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

    <!-- TODO: Enable the tooltips in this page. Move it in a common place -->
    <script type="text/javascript">
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
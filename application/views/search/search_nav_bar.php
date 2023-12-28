<?php 

	$found=$surveys['found'];
	$total=$surveys['total'];
    
    if($found==1) {
		$items_found=t('found_study');
	}
	else{
		$items_found=t('found_studies');
	}


    $qs_params=$this->input->get();
    $variable_view=$qs_params;

    $variable_view['view']="v";
    $variable_view=http_build_query($variable_view);

    $show_variable_toggle=false;
    if (isset($search_options->tab_type) && in_array($search_options->tab_type,array("","survey","geospatial"))){
        $show_variable_toggle=true;
    }

    $image_thumbnail_view=$qs_params;
    $image_thumbnail_view['image_view']="thumb";    
    $image_thumbnail_view=http_build_query($image_thumbnail_view);

    $image_default_view=$qs_params;
    if (isset($image_default_view)){
        unset($image_default_view['image_view']);
    }
    $image_default_view=http_build_query($image_default_view);

    $image_view=isset($search_options->image_view) ? $search_options->image_view :'';
?>

<div class="row mb-3">
    <div class="col-12 col-md-12 mt-2 mt-md-0 ">
        <div class="filter-action-bar row">
                <?php if($found>0):?>
                <?php 
                /* <div class="search-count mt-1 font-weight-bold col">
                    <?php echo number_format($found). ' '. t('results');?>
                </div>
                */ ?>

                <div class="search-count col-md-5 text-md-left mb-2 mb-md-0 pt-2">
                    <?php echo sprintf(t('showing_studies'),
                        number_format(($surveys['limit']*$current_page)-$surveys['limit']+1),
                        number_format(($surveys['limit']*($current_page-1))+ count($surveys['rows'])),
                        number_format($surveys['found']));
                    ?>
                </div>

                <?php if($show_variable_toggle && (isset($surveys['search_counts_by_type']) && array_key_exists('survey',$surveys['search_counts_by_type']))):?>
                <div class="col mt-1 wb-search-toggle">               
                    <div class="btn-group btn-group-toggle study-view-toggle" >
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-left active toggle_view" data-value="s" ><?php echo t('Study view');?></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-right toggle_view" data-value="v"><a href="<?php echo site_url('catalog/'.@$active_repo['repositoryid'].'?'.$variable_view);?>"><?php echo t('Variable view');?></a></button>
                    </div>
                </div>
                <?php endif;?>

                <?php if ($tab_type=='image'):?>
                    <div class="col mt-1 wb-search-image">                        
                        <div class="btn-group btn-group-toggle image-view-toggle" >
                            
                            <button type="button" class="btn btn-sm btn-outline-primary <?php echo $image_view!=='thumbnail' ? 'active' :'';?> rounded-left toggle_view" >
                                <a title="Detail view" href="<?php echo site_url('catalog?'.$image_default_view);?>" >
                                    <i class="fa fa-th-list" aria-hidden="true"></i>
                                    <?php echo t('Details view');?>
                                </a>                                
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-right <?php echo $image_view=='thumbnail' ? 'active' :'';?> toggle_view">
                                <a href="<?php echo site_url('catalog?image_view=thumbnail&'.$image_default_view);?>">
                                    <i class="fa fa-th-large" aria-hidden="true"></i>
                                    <?php echo t('Gallery view');?>
                                </a>
                            </button>

                        </div>
                    </div>
                <?php endif;?>

                <div class="col mt-1 wb-search-sort">
                    <div class="form-inline float-right ">
                        <label for="sort-by-select" class="sort-by-label">
                            <span class="sort-by-title d-none d-sm-block"></span>
                            <select class="form-control form-control-sm sort-dropdown" id="sort-by-select">
                                <option value="relevance"  data-sort="desc" <?php  echo ($search_options->sort_by=='relevance' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?> ><?php echo t('Relevance');?></option>
                                <option value="popularity"  data-sort="desc" <?php  echo ($search_options->sort_by=='popularity' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?>><?php echo t('Popularity');?></option>
                                <option value="year" data-sort="desc" <?php  echo ($search_options->sort_by=='year' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?>><?php echo t('Year (Recent &uarr;)');?></option>
                                <option value="year" data-sort="asc" <?php  echo ($search_options->sort_by=='year' && $search_options->sort_order=='asc') ? 'selected="selected"' : '' ; ?>><?php echo t('Year (Oldest &darr;)');?></option>
                                <option value="title" data-sort="asc" <?php  echo ($search_options->sort_by=='title' && $search_options->sort_order=='asc') ? 'selected="selected"' : '' ; ?>><?php echo t('Title (A-Z)');?></option>
                                <option value="title" data-sort="desc" <?php  echo ($search_options->sort_by=='title' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?>><?php echo t('Title (Z-A)');?></option>
                                <option value="country" data-sort="asc" <?php  echo ($search_options->sort_by=='country' && $search_options->sort_order=='asc') ? 'selected="selected"' : '' ; ?>><?php echo t('Country (A-Z)');?></option>
                                <option value="country" data-sort="desc" <?php  echo ($search_options->sort_by=='country' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?>><?php echo t('Country (Z-A)');?></option>                           
                            </select>
                        </label>
                        <?php /*
                        <a target="_blank" href="<?php echo site_url('catalog/export/csv').'?ps=5000&'.get_querystring( array('sort_by','sort_order','collection', 'country','sk','vk','dtype','topic','view','repo','from','to'));?>" class="btn btn btn-outline-primary btn-sm d-none d-sm-block ml-2"><i class="fas fa-file-export"></i> <?php echo t('Export');?></a>
                        */?>
                    </div>
                </div>                  
                <?php endif;?>
        </div>

    </div>    
</div>

<div class="active-filters-container">
    <?php $active_filters=$this->load->view("search/active_filter_tokens",null,true);?>    
    <?php if (!empty(trim($active_filters))):?>
        <div class="active-filters">
            <?php echo $active_filters;?>
            <a href="<?php echo site_url('catalog');?>?tab_type=<?php echo $search_options->tab_type; ?>" class="btn-reset-search btn btn-outline-danger btn-sm"><?php echo t('reset_search');?></a>
        </div>        
    <?php endif;?>
</div>    

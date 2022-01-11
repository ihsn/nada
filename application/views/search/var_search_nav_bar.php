<?php 

	$found=$variables['found'];
	$total=$variables['total'];
    
    if($found==1) {
		$items_found=t('found_variable');
	}
	else{
		$items_found=t('found_variables');
	}

    $study_view=$this->input->get();
    unset($study_view['view']);
    $study_view=http_build_query($study_view);

    
?>

<div class="row mb-3">

    
    <div class="col-12 col-md-12 mt-2 mt-md-0 ">
    <?php /* ?>
    <ul class="nav nav-tabs mb-3 font-weight-bold">
        <li class="nav-item">
            <span class="nav-link" ><a href="<?php echo site_url('catalog?'.$study_view);?>">Studies</a></span>
        </li>
        <li class="nav-item">
            <span class="nav-link active" >Variables</span>
        </li>
    </ul>
    <?php */ ?>
        
        <div class="filter-action-bar row">
                <?php if($found>0):?>
                <?php /*
                <div class="search-count mt-1 font-weight-bold col">
                    <?php echo number_format($found). ' '. t('results');?>
                </div>
                */?>
                <div class="search-count mt-1 col-5">
                    <?php echo sprintf(t('showing_variables'),
                        number_format(($variables['limit']*$current_page)-$variables['limit']+1),
                        number_format(($variables['limit']*($current_page-1))+ count($variables['rows'])),
                        number_format($variables['found']));
                    ?>
                </div>

                <div class="col mt-1 wb-search-toggle">
                    <div class="btn-group btn-group-toggle study-view-toggle" >
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-left toggle_view" data-value="s" ><a href="<?php echo site_url('catalog/'.@$active_repo['repositoryid'].'?'.$study_view);?>"><?php echo t('Study view');?></a></button>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-right active toggle_view" data-value="v"><?php echo t('Variable view');?></button>
                    </div>
                </div>
                <div class="col mt-1 wb-search-sort">
                    <div class="form-inline float-right ">
                        <label for="sort-by-select" class="sort-by-label">
                            <span class="sort-by-title d-none d-sm-block"></span>
                            <select class="form-control form-control-sm sort-dropdown" id="sort-by-select">
                                <option value="relevance"  data-sort="desc" <?php  echo ($search_options->sort_by=='relevance' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?> >Relevance</option>
                                <option value="popularity"  data-sort="desc" <?php  echo ($search_options->sort_by=='popularity' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?>>Popularity</option>
                                <option value="year" data-sort="desc" <?php  echo ($search_options->sort_by=='year' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?>>Year (Recent &uarr;)</option>
                                <option value="year" data-sort="asc" <?php  echo ($search_options->sort_by=='year' && $search_options->sort_order=='asc') ? 'selected="selected"' : '' ; ?>>Year (Oldest &darr;)</option>
                                <option value="title" data-sort="asc" <?php  echo ($search_options->sort_by=='title' && $search_options->sort_order=='asc') ? 'selected="selected"' : '' ; ?>>Title (A-Z)</option>
                                <option value="title" data-sort="desc" <?php  echo ($search_options->sort_by=='title' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?>>Title (Z-A)</option>
                                <option value="country" data-sort="asc" <?php  echo ($search_options->sort_by=='country' && $search_options->sort_order=='asc') ? 'selected="selected"' : '' ; ?>>Country (A-Z)</option>
                                <option value="country" data-sort="desc" <?php  echo ($search_options->sort_by=='country' && $search_options->sort_order=='desc') ? 'selected="selected"' : '' ; ?>>Country (Z-A)</option>                           
                            </select>
                        </label>
                        <?php /* 
                        <a target="_blank" href="<?php echo site_url('catalog/export/print').'?ps=5000&'.get_querystring( array('sort_by','sort_order','collection', 'country','sk','vk','dtype','topic','view','repo','from','to'));?>" class="btn btn btn-outline-success btn-sm ml-2 mr-2 d-none d-sm-block"><i class="fa fa-print"></i></a>
                        <a target="_blank" href="<?php echo site_url('catalog/export/csv').'?ps=5000&'.get_querystring( array('sort_by','sort_order','collection', 'country','sk','vk','dtype','topic','view','repo','from','to'));?>" class="btn btn btn-outline-primary btn-sm d-none d-sm-block ml-2"><i class="fas fa-file-export"></i> Export</a>  
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

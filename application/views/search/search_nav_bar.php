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

<div class="row mb-3">
    <div class="col-12 col-md-12 mt-2 mt-md-0 ">
        <div class="filter-action-bar">
            <span>
                <?php if($found>0):?>
                <div class="search-count font-weight-bold float-left"><?php echo number_format($found). ' '. t('results');//sprintf($items_found,$found,$total);?></div>
                <div class="form-inline float-right">                    
                    <label for="sort-by-select" class="sort-by-label">
                        <span class="sort-by-title d-none d-sm-block">Sort By:&nbsp;</span>
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
                    <a target="_blank" href="<?php echo site_url('catalog/export/print').'?ps=5000&'.get_querystring( array('sort_by','sort_order','collection', 'country','sk','vk','dtype','topic','view','repo','from','to'));?>" class="btn btn btn-outline-success btn-sm ml-2 mr-2 d-none d-sm-block"><i class="fa fa-print"></i></a>
                    <a target="_blank" href="<?php echo site_url('catalog/export/csv').'?ps=5000&'.get_querystring( array('sort_by','sort_order','collection', 'country','sk','vk','dtype','topic','view','repo','from','to'));?>" class="btn btn btn-outline-success btn-sm d-none d-sm-block"><i class="fa fa-file-excel-o"></i></a>
                </div>                  
                <?php endif;?>
            </span>                
        </div>            
    </div>    
</div>

<div class="active-filters-container">
    <?php $active_filters=$this->load->view("search/active_filter_tokens",null,true);?>    
    <?php if (!empty(trim($active_filters))):?>
        <div class="active-filters">
            <?php echo $active_filters;?>
            <a href="<?php echo site_url('catalog');?>?tab_type=<?php echo $search_options->tab_type; ?>" class="btn-reset-search btn btn-outline-primary btn-sm">Reset search</a>
        </div>        
    <?php endif;?>
</div>    

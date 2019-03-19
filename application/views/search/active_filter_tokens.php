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

if (!isset($_GET['collection']))
{
	$current_repo=@$search_options->filter->repo;
	$_GET['collection'][]=$current_repo;
}
?>
<div class="active-filters-container">
    <div class="row mb-3">

        <div class="col-12 col-md-6 col-lg-4 text-center text-md-left">
            <div class="search-count font-weight-bold"><?php echo number_format($found). ' '. t('results');//sprintf($items_found,$found,$total);?></div>
        </div>

        <div class="col-12 col-md-6 col-lg-8 mt-2 mt-md-0 text-center text-md-right">
            <div class="filter-action-bar">
                <span>
                    <?php if($found>0):?>
                    <div class="form-inline float-right">
                     <label for="sort-by-select" class="sort-by-label pr-2">
                        <span class="sort-by-title">Sort By:&nbsp;</span>
                        <select class="form-control form-control-sm sort-dropdown" id="sort-by-select">
                            <option value="relevance"  data-sort="desc">Relevance</option>
                            <option value="popularity"  data-sort="desc">Popularity</option>
                            <option value="year" data-sort="desc">Year (Recent &uarr;)</option>
                            <option value="year" data-sort="asc">Year (Oldest &darr;)</option>
                            <option value="title" data-sort="asc">Title (A-Z)</option>
                            <option value="title" data-sort="desc">Title (Z-A)</option>
                            <option value="country" data-sort="asc" >Country (A-Z)</option>
                            <option value="country" data-sort="desc">Country (Z-A)</option>                           
                        </select>
                    </label>

                    <a target="_blank" href="<?php echo site_url('catalog/export/print').'?ps=5000&'.get_querystring( array('sort_by','sort_order','collection', 'country','sk','vk','dtype','topic','view','repo','from','to'));?>" class="btn btn btn-outline-success btn-sm mr-2"><i class="fa fa-print"></i></a>
                    <a target="_blank" href="<?php echo site_url('catalog/export/csv').'?ps=5000&'.get_querystring( array('sort_by','sort_order','collection', 'country','sk','vk','dtype','topic','view','repo','from','to'));?>" class="btn btn btn-outline-success btn-sm"><i class="fa fa-file-excel-o"></i></a>
                    </div>

                  
                  <?php endif;?>
                </span>
            </div>
        </div>
    </div>

    <div class="active-filters">
        <?php if (is_array($search_options->type)):?>
            <?php foreach($search_options->type as $type):?>                
                    <span class="badge badge-default badge-secondary wb-badge-close remove-filter type" data-type="type" data-value="<?php echo $type;?>"><?php echo $type;?><i class="fa fa-close"></i></span>
            <?php endforeach;?>
        <?php endif;?>
        <?php if (is_array($search_options->country)):?>
            <?php foreach($search_options->country as $country):?>
                <?php if (array_key_exists($country,$countries)):?>
                    <span class="badge badge-default wb-badge-close remove-filter country" data-type="country" data-value="<?php echo $country;?>"><?php echo $countries[$country]['nation'];?><i class="fa fa-close"></i></span>
                <?php endif;?>
            <?php endforeach;?>
        <?php endif;?>

        <?php if (is_array($search_options->topic)):?>
            <?php foreach($search_options->topic as $topic):?>
                <?php if (array_key_exists($topic,$topics)):?>
                    <span class="badge badge-default wb-badge-close country remove-filter topic" data-type="topic" data-value="<?php echo $topic;?>">
                <?php $brac_pos=strpos($topics[$topic]['title'],'[',0);?>
                        <?php if ($brac_pos):?>
                            <?php echo substr($topics[$topic]['title'],0,strpos($topics[$topic]['title'],'[',0)); ?>
                        <?php else: ?>
                            <?php echo $topics[$topic]['title']; ?>
                        <?php endif;?>
                        <i class="fa fa-close"></i></span>
                <?php endif;?>
            <?php endforeach;?>
        <?php endif;?>

        <?php if (is_array($search_options->collection)):?>
            <?php foreach($search_options->collection as $collection):?>
                <?php if (array_key_exists($collection,$repositories)):?>
                    <span class="badge badge-default wb-badge-close country remove-filter collection" data-type="collection" data-value="<?php echo $collection;?>"><?php echo $repositories[$collection]['title'];?><i class="fa fa-close"></i></span>
                <?php endif;?>
            <?php endforeach;?>
        <?php endif;?>

        <?php if (isset($search_options->dtype) && is_array($search_options->dtype)):?>
            <?php foreach($search_options->dtype as $dtype):?>
                <?php if (array_key_exists($dtype,$data_access_types)):?>
                    <span class="badge badge-default wb-badge-close remove-filter dtype" data-type="dtype" data-value="<?php echo $dtype;?>"><?php echo $data_access_types[$dtype];?><i class="fa fa-close"></i></span>
                <?php endif;?>
            <?php endforeach;?>
        <?php endif;?>

        <?php if ($search_options->from!='' && $search_options->to!=''):?>
            <?php if ( $search_options->from!=$min_year || $search_options->to!=$max_year ):?>
                <span class="badge badge-default wb-badge-close remove-filter years" data-type="years" data-value="0"><?php echo t('between');?> <?php echo $search_options->from;?>-<?php echo $search_options->to;?><i class="fa fa-close"></i></span>
            <?php endif;?>
        <?php endif;?>

        <?php if (isset($search_options->sk) && $search_options->sk!=''):?>
            <span class="badge badge-default wb-badge-close country remove-filter sk" data-type="sk" data-value=""><?php echo substr($search_options->sk,0,50);?><i class="fa fa-close"></i></span>
        <?php endif;?>

        <?php if (isset($search_options->vk) && $search_options->vk!=''):?>
            <span class="badge badge-default wb-badge-close country remove-filter vk" data-type="vk" data-value=""><?php echo substr($search_options->vk,0,50);?><i class="fa fa-close"></i></span>
        <?php endif;?>

        <?php if (isset($search_options->sid) && $search_options->sid!=''):?>
            <span class="badge badge-default wb-badge-close country remove-filter sk" data-type="sid" data-value=""><?php echo substr($search_options->sid,0,50).'... ';?><i class="fa fa-close"></i></span>
        <?php endif;?>
    </div>

</div>

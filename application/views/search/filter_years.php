<?php 
$years_dropdown=array();
$years_dropdown['']=' ';
$filter_collapse='show';
$filter_id='year';

if (isset($years['min_year']) && isset($years['max_year'])){
    foreach (range($years['max_year'], $years['min_year']) as $year){
        $years_dropdown[$year]=$year;
    }
}
?>
<div id="filter-by-year" class="sidebar-filter wb-ihsn-sidebar-filter filter-by-year filter-box <?php echo isset($is_enabled) && $is_enabled===false ? 'disabled-facet' :'' ;?>">
    <?php /*<h6 class="togglable"> <i class="fa fa-search pr-2"></i><?php echo t('filter_by_year');?></h6>*/?>

    <h6 class="togglable"> 
        <div 
        class="wb-filter-title <?php echo $filter_collapse ? 'collapsed' :'';?>" 
        data-toggle="collapse" 
        href="#facet-<?php echo $filter_id;?>" role="button" aria-expanded="false" aria-controls="facet-<?php echo $filter_id;?>">
            <i class="fa fa-filter pr-2"></i>
            <span class="text-capitalize"><?php echo isset($title) ? t($title) : t('filter_by_'.$filter_id);?></span>
            <span class="float-right" >
            <i class="icon-toggle icon-collapsed float-right fa fa-chevron-down"></i>
            <i class="icon-toggle icon-expanded float-right fa fa-chevron-up"></i>
            </span>
        </div>

        <div class="wb-filter-subtitle clear-button-container clear-disabled" >
            <span><span class="selected-items"></span> <?php echo t('selected');?> <?php /*/<span class="total-items"></span> */?> </span>
            <a class="btn btn-link btn-sm rounded clear lnk-filter-reset">Clear</a>
        </div>
    </h6> 

    <div id="facet-<?php echo $filter_id;?>" class="sidebar-filter-entries <?php echo $filter_collapse ? 'collapse' :'';?>">        
        <form>
            <div class="row">
            <div class="col">
                <p class="mb-0 pb-0"><?php echo t('from');?></p>
                <div class="form-group mb-0">
                    <?php echo form_dropdown('from', $years_dropdown, ((isset($search_options->from) && $search_options->from!='') ? $search_options->from : current($years_dropdown)), 'id="from"  class="form-control"'); ?>
                </div>
            </div>
            <div class="col">
                <p class="mb-0 pb-0"><?php echo t('to');?></p>
                <div class="form-group">
                    <?php echo form_dropdown('to', $years_dropdown, (isset($search_options->to) && $search_options->to!='') ? $search_options->to: '','id="to" class="form-control"'); ?>
                </div>
            </div>
            </div>
        </form>
    </div>

</div>

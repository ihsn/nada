<?php

if(!isset($items) || empty($items)){
    return false;
}

$item_limit=0;
$filter_collapse=true;

if (isset($collapse)){
    $filter_collapse=$collapse;
}
/*
$filter_id='license';
$filter_name='license';
$filter_total=100;
$filter_use_code=false;


*/

//items format
/*
items=array(
    0=>array(
        'id'=>1,
        'code'=>'code',
        'title'=>'title'
    )
)
*/

?>
<div id="filter-by-<?php echo $filter_id;?>" class="sidebar-filter wb-ihsn-sidebar-filter filter-box filter-by-<?php echo $filter_id;?>">
    <h6 class="togglable"> <i class="fa fa-filter pr-2"></i><?php echo t('filter_by_'.$filter_id);?></h6>
    
    <?php if(isset($filter_total) && $filter_total>0):?> 
    <div class="sidebar-filter-index selected-items-count" data-toggle="tooltip" data-placement="top" title="Tooltip for Help"><?php echo $filter_total;?></div>
    <?php endif;?>

    <div class="sidebar-filter-entries <?php echo $filter_collapse ? 'wb-sidebar-filter-collapse' :'';?> <?php echo $filter_id;?>-container items-container">
        <div class="lnk-filter-reset text-right"><?php echo t('clear');?></div>
        <div class="items-container  collection-items <?php //echo (count($repositories)>10) ? 'scrollable' : ''; ?>">
            <?php if($items):?>
                <?php $k=0;foreach($items as $item_key=>$item):$k++; ?>
                    <div class="form-check item-<?php echo $filter_id;?> <?php echo $k;?> item inactive">
                        <label class="form-check-label" for="<?php echo $filter_id;?>-<?php echo form_prep($item_key); ?>" <?php echo form_prep($item_key); ?>>
                            <input class="form-check-input chk chk-<?php echo $filter_id;?>" type="checkbox" name="<?php echo $filter_id;?>[]"
                                   value="<?php echo form_prep($item_key); ?>"
                                   id="<?php echo $filter_id;?>-<?php echo form_prep($item_key); ?>"
                                <?php if($search_options->{$filter_id}!='' && in_array($item_key,$search_options->{$filter_id})):?>
                                    checked="checked"
                                <?php endif;?>>                                
                                <?php echo $item['title']; ?> <span> <span class="count">(<?php echo $item['found']; ?>)</span></span>
                        </label>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
        </div>

    </div>

</div>

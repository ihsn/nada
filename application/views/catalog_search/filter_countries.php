<?php 
	$item_limit=4;	
?>
<div class="filter-box filter-by-country">
<h3><?php echo t('filter_by_country');?></h3> 

<span class="selected-items-count" ><?php echo count($countries);?></span>

<div id="countries-container" >
    <div class="country any country-any">    	
        <input type="checkbox" class="chk-country-any chk-any" id="country-any"  <?php echo $search_options->country!="" ? '' : 'checked="checked"';?> />
        <label for="country-any"><?php echo t('any');?></label>
    </div>
	<div class="country-items items-container <?php //echo (count($countries)>10) ? 'scrollable' : ''; ?>">
	<?php if($countries):?>
	<?php $k=0;foreach($countries as $country):$k++; ?>
        <div class="country item inactive <?php echo ($k>$item_limit) ? 'less' : 'less'; ?>">
            <input class="chk-country chk" type="checkbox" name="country[]" 
                value="<?php echo form_prep($country['cid']); ?>" 
                id="c-<?php echo form_prep($country['cid']); ?>"
                <?php if($search_options->country!='' && in_array($country['cid'],$search_options->country)):?>
                checked="checked"
                <?php endif;?>
             />
            <label for="c-<?php echo form_prep($country['cid']); ?>">
                <?php echo $country['nation']; ?> <span class="count">(<?php echo $country['surveys_found']; ?>)</span>
            </label>
        </div>
    <?php endforeach;?>
    <?php endif;?>
    </div>
    
    <?php /* if($k>$item_limit):?>
    <div>+<?php echo $k-$item_limit; ?> more...</div>
    <?php endif; */ ?>
    
    <div class="filter-footer">
    <input type="button" class="btn-select" value="<?php echo t('view_select_more');?>" id="btn-country-selection" data-dialog-id="dialog-countries" data-dialog-title="<?php echo t('select_countries');?>" data-url="index.php/catalog/country_selection/<?php echo $active_repo;?>"/>
    </div>
    
</div>
</div>
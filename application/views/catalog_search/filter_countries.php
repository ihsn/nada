<?php 
	$item_limit=4;	
?>
<div class="filter-box">
<h3><?php echo t('filter_by_country');?></h3> 

<a class="clear-filter" href="#"><?php echo t('reset');?></a>

<div id="countries-container" >
    <div class="country any">    	
        <input type="checkbox" class="chk-country-any chk-any" id="country-any"  <?php echo $search_options->country!="" ? '' : 'checked="checked"';?> />
        <label for="country-any">Any</label>
    </div>
    <div class="select-specific">Or select specific:</div>
	<div class="country-items items-container <?php echo (count($countries)>10) ? 'scrollable' : ''; ?>">
	<?php $k=0;foreach($countries as $country):$k++; ?>
        <div class="country item <?php echo ($k>$item_limit) ? 'more' : 'less'; ?>">
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
    </div>
    
    <?php if($k>$item_limit):?>
    <div>+<?php echo $k-$item_limit; ?> more...</div>
    <?php endif;?>
    
    <div class="filter-footer">
    <input type="button" class="btn-select" value="View / Select More" id="btn-country-selection" data-dialog-id="dialog-countries" data-dialog-title="Select Countries" data-url="index.php/catalog/country_selection"/>
    </div>
    
</div>
</div>
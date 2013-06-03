<?php 
	$item_limit=0;
?>

<div class="filter-box filter-by-collection">
	<h3><?php echo t('filter_by_collection');?></h3> 
	<span class="selected-items-count" ><?php echo count($repositories);?></span>
    
<div id="collections-container">
    <div class="any">    	
        <input type="checkbox" class="chk-any" id="collection-any"  <?php echo $search_options->collection!="" ? '' : 'checked="checked"';?> />
        <label for="collection-any"><?php echo t('any');?></label>
    </div>

	<div class="items-container  collection-items <?php //echo (count($repositories)>10) ? 'scrollable' : ''; ?>">
	<?php if($repositories):?>
	<?php $k=0;foreach($repositories as $repo):$k++; ?>
        <div class="collection <?php echo $k;?> item inactive">
            <input class="chk chk-collection" type="checkbox" name="collection[]" 
                value="<?php echo form_prep($repo['repositoryid']); ?>" 
                id="repo-<?php echo form_prep($repo['id']); ?>"
                <?php if($search_options->collection!='' && in_array($repo['repositoryid'],$search_options->collection)):?>
                checked="checked"
                <?php endif;?>
             />
            <label for="repo-<?php echo form_prep($repo['id']); ?>">
                <?php echo $repo['title']; ?> <span class="count">(<?php echo $repo['surveys_found']; ?>)</span>
            </label>
        </div>
    <?php endforeach;?>
    <?php endif;?>
    </div>
    
<?php if(count($repositories)>$item_limit):?>    
    <div class="filter-footer">
    <input type="button" class="btn-select" value="<?php echo t('view_select_more');?>" id="btn-collection-selection" data-dialog-id="dialog-collections" data-dialog-title="<?php echo t('select_collections');?>" data-url="index.php/catalog/collection_selection"/>
    </div>
<?php endif;?>    
</div>
</div>

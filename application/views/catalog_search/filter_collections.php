<?php 
	$item_limit=5;
?>

<div class="filter-box">
	<h3><?php echo t('filter_by_collection');?></h3> 
	<a class="clear-filter" href="#"><?php echo t('reset');?></a>

<div id="collections-container">
    <div class="any">    	
        <input type="checkbox" class="chk-any" id="collection-any"  <?php echo $search_options->collection!="" ? '' : 'checked="checked"';?> />
        <label for="collection-any">Any</label>
    </div>
    <div class="select-specific">Or select specific:</div>
	<div class="items-container <?php echo (count($repositories)>10) ? 'scrollable' : ''; ?>">
	<?php $k=0;foreach($repositories as $repo):$k++; ?>
        <div class="collection <?php echo $k;?> item <?php echo ($k>$item_limit) ? 'more' : 'less'; ?>">
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
    </div>
    
    <?php if($k>$item_limit):?>
    <div>+<?php echo $k-$item_limit; ?> more...</div>
    <?php endif;?>

<?php if(count($repositories)>$item_limit):?>    
    <div class="filter-footer">
    <input type="button" class="btn-select" value="View / Select More" id="btn-collection-selection" data-dialog-id="dialog-collections" data-dialog-title="Select Collections" data-url="index.php/catalog/collection_selection"/>
    </div>
<?php endif;?>    
</div>
</div>

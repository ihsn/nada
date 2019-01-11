<?php
$item_limit=0;
?>
<div id="filter-by-collection" class="sidebar-filter wb-ihsn-sidebar-filter filter-box filter-by-collection">
    <h6 class="togglable"> <i class="fa fa-filter pr-2"></i><?php echo t('filter_by_collection');?></h6>
    <div class="sidebar-filter-index selected-items-count" data-toggle="tooltip" data-placement="top" title="Tooltip for Help"><?php echo count($repositories);?></div>

    <div class="sidebar-filter-entries wb-sidebar-filter-collapse collections-container">
        <div class="form-check any">
            <label class="form-check-label" for="collection-any" <?php echo t('any');?> >
                <input class="form-check-input chk-any" id="collection-any" type="checkbox" <?php echo $search_options->collection!="" ? '' : 'checked="checked"';?>>
                <small><strong><?php echo t('any');?></strong></small>
            </label>
        </div>
        <div class="items-container  collection-items <?php //echo (count($repositories)>10) ? 'scrollable' : ''; ?>">
            <?php if($repositories):?>
                <?php $k=0;foreach($repositories as $repo):$k++; ?>
                    <div class="form-check collection <?php echo $k;?> item inactive">
                        <label class="form-check-label" for="repo-<?php echo form_prep($repo['id']); ?>" <?php echo form_prep($repo['id']); ?>>
                            <input class="form-check-input chk chk-collection" type="checkbox" name="collection[]"
                                   value="<?php echo form_prep($repo['repositoryid']); ?>"
                                   id="repo-<?php echo form_prep($repo['id']); ?>"
                                <?php if($search_options->collection!='' && in_array($repo['repositoryid'],$search_options->collection)):?>
                                    checked="checked"
                                <?php endif;?>>
                            <small><?php echo $repo['title']; ?> <span>(<?php echo $repo['surveys_found']; ?>)</span></small>
                        </label>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
        </div>

    </div>

</div>

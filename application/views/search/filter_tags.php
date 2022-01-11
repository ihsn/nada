<?php
$item_limit=0;
?>
<div id="filter-by-type" class="sidebar-filter wb-ihsn-sidebar-filter filter-box filter-by-tag">
    <h6 class="togglable"> <i class="fa fa-filter pr-2"></i><?php echo t('filter_by_tag');?></h6>
    <div class="sidebar-filter-index selected-items-count" data-toggle="tooltip" data-placement="top" ><?php echo count($tags);?></div>

    <div class="sidebar-filter-entries wb-sidebar-filter-collapse tags-container items-container">
        <!--
            <div class="form-check any">
            <label class="form-check-label" for="type-any" <?php echo t('any');?> >
                <input class="form-check-input chk-any" id="type-any" type="checkbox" <?php echo $search_options->tag!="" ? '' : 'checked="checked"';?>>
                <span><strong><?php echo t('any');?></strong></span>
            </label>
        </div>
        -->
        <div class="lnk-filter-reset text-right"><?php echo t('clear');?></div>
        <div class="items-container  types-items <?php //echo (count($repositories)>10) ? 'scrollable' : ''; ?>">
            <?php if($tags):?>
                <?php $k=0;foreach($tags as $tag):$k++; ?>
                    <div class="form-check tag dataset-tag <?php echo $k;?> item inactive">
                        <label class="form-check-label" for="type-<?php echo form_prep($tag['tag']); ?>" >
                            <input class="form-check-input chk chk-type" type="checkbox" name="tag[]"
                                   value="<?php echo form_prep($tag['tag']); ?>"
                                   id="type-<?php echo form_prep($tag['tag']); ?>"
                                <?php if($search_options->tag!='' && in_array($tag['tag'],$search_options->tag)):?>
                                    checked="checked"
                                <?php endif;?>>
                            <span><?php echo $tag['tag']; ?> <span>(<?php echo $tag['total']; ?>)</span></span>
                        </label>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
        </div>

    </div>

</div>

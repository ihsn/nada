<?php
$item_limit=0;
?>
<div id="filter-by-type" class="sidebar-filter wb-ihsn-sidebar-filter filter-box filter-by-type">
    <h6 class="togglable"> <i class="fa fa-filter pr-2"></i><?php echo t('filter_by_type');?></h6>
    <div class="sidebar-filter-index selected-items-count" data-toggle="tooltip" data-placement="top" title="Tooltip for Help"><?php echo count($types);?></div>

    <div class="sidebar-filter-entries wb-sidebar-filter-collapse types-container">
        <!--<div class="form-check any">
            <label class="form-check-label" for="type-any" <?php echo t('any');?> >
                <input class="form-check-input chk-any" id="type-any" type="checkbox" <?php echo $search_options->type!="" ? '' : 'checked="checked"';?>>
                <span><strong><?php echo t('any');?></strong></span>
            </label>
        </div>-->
        <div class="items-container  types-items <?php //echo (count($repositories)>10) ? 'scrollable' : ''; ?>">
            <?php if($types):?>
                <?php $k=0;foreach($types as $type):$k++; ?>
                    <div class="form-check type dataset-type <?php echo $k;?> item inactive">
                        <label class="form-check-label" for="type-<?php echo form_prep($type['code']); ?>" <?php echo form_prep($type['title']); ?>>
                            <input class="form-check-input chk chk-type" type="checkbox" name="type[]"
                                   value="<?php echo form_prep($type['code']); ?>"
                                   id="type-<?php echo form_prep($type['code']); ?>"
                                <?php if($search_options->type!='' && in_array($type['code'],$search_options->type)):?>
                                    checked="checked"
                                <?php endif;?>>
                            <span><?php echo $type['title']; ?> <span class="count">(<?php echo $type['found']; ?>)</span></span>
                        </label>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
        </div>

    </div>

</div>

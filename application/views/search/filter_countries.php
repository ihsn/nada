<?php 
	$item_limit=4;	
?>
    <div id="filter-by-country" class="sidebar-filter wb-ihsn-sidebar-filter filter-box filter-by-country">
        <h6 class="togglable"> <i class="fa fa-filter pr-2"></i><?php echo t('filter_by_country');?></h6>
        <div class="sidebar-filter-index selected-items-count" data-toggle="tooltip" data-placement="top" title="Countries">
            <?php echo count($countries);?>
        </div>
        <div class="sidebar-filter-entries wb-sidebar-filter-collapse country-items items-container">
        <div class="lnk-filter-reset text-right"><?php echo t('clear');?></div>
            <!--
            <div class="form-check">
                <label class="form-check-label country any country-any form-check-any">
                    <input 
                            class="form-check-input chk-country-any chk-any"  
                            id="country-any"  <?php echo $search_options->country!="" ? '' : 'checked="checked"';?> 
                            type="checkbox" value="">
                       
                    <span class="lnk-filter-all"><?php echo t('all');?></span>                    
                </label>
            </div>
            -->
            <?php if($countries):?>
            <?php $k=0;foreach($countries as $country):$k++; ?>
                    <div class="form-check">
                        <label class="form-check-label country item inactive <?php echo ($k>$item_limit) ? 'less' : 'less'; ?>">
                            <input class="form-check-input chk-country chk" type="checkbox" name="country[]"
                                   value="<?php echo form_prep($country['cid']); ?>"
                                   id="c-<?php echo form_prep($country['cid']); ?>"
                                <?php if($search_options->country!='' && in_array($country['cid'],$search_options->country)):?>
                                    checked="checked"
                                <?php endif;?>>
                            <span><?php echo $country['nation']; ?><span class="count"> (<?php echo $country['surveys_found']; ?>)
                                </span></span>
                        </label>
                    </div>
                <?php endforeach;?>
            <?php endif;?>

        </div>
    </div>

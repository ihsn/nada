<?php $bootstrap_theme = 'themes/'.$this->template->theme();?>
<div id="filter-by-access" class="sidebar-filter wb-ihsn-sidebar-filter filter-by-access filter-box filter-by-dtype">
    <h6 class="togglable"> <i class="fa fa-filter pr-2"></i> <?php echo t('filter_by_data');?></h6>

<!--
    <div class="sidebar-filter-index search-help da-help" data-toggle="tooltip" data-placement="top" title="Tooltip for Help">
        <img src="images/icon_question.png" alt="help" title="Help" data-url="<?php echo site_url('catalog/help_da');?>">
    </div>
-->
        <div class="sidebar-filter-entries filter-da items-container">
            <div class="form-check filter-da any">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input chk-da-any" id="chk-da-any"  <?php echo $search_options->dtype!="" ? '' : 'checked="checked"';?>>
                    <small><?php echo t('any');?></small>
                </label>
            </div>
            <?php if (in_array('open',$da_types)):?>
                <div class="form-check">
                    <label class="form-check-label item">
                        <input class="form-check-input chk chk-da" type="checkbox"  <?php if(isset($search_options->dtype) && is_array($search_options->dtype) && in_array('7',$search_options->dtype) ){echo 'checked="checked"'; }?> value="7" name="dtype[]" id="da_open">
                        <span class="filter-icon-da-open">
                            <small><?php echo t('legend_data_open');?></small>
                        </span>
                    </label>
                </div>
            <?php endif;?>

            <?php if (in_array('direct',$da_types)):?>
                <div class="form-check">
                    <label class="form-check-label item">
                        <input class="form-check-input chk chk-da" type="checkbox"  <?php if(isset($search_options->dtype) && is_array($search_options->dtype) && in_array('1',$search_options->dtype) ){echo 'checked="checked"'; }?> value="1" name="dtype[]" id="da_direct">                         
                        <span class="filter-icon-da-direct">
                            <small><?php echo t('legend_data_direct');?></small>
                        </span> 
                    </label>
                </div>
            <?php endif;?>

            <?php if (in_array('public',$da_types)):?>
                <div class="form-check">
                    <label class="form-check-label item">
                        <input class="form-check-input chk chk-da public" type="checkbox"  <?php if(isset($search_options->dtype) && is_array($search_options->dtype) && in_array('2',$search_options->dtype) ){echo 'checked="checked"'; }?> value="2" name="dtype[]" id="da_public">                        
                        <span class="filter-icon-da-public">
                            <small><?php echo t('legend_data_public');?></small>
                        </span> 
                    </label>
                </div>
            <?php  endif;?>

            <?php  if (in_array('licensed',$da_types)):?>
            <div class="form-check">
                <label class="form-check-label item">
                    <input class="form-check-input chk chk-da licensed" type="checkbox"  <?php if(isset($search_options->dtype) && is_array($search_options->dtype) && in_array('3',$search_options->dtype) ){echo 'checked="checked"'; }?> value="3" name="dtype[]" id="da_licensed">
                    <span class="filter-icon-da-licensed">
                            <small><?php echo t('legend_data_licensed');?></small>
                        </span> 
                </label>
            </div>
            <?php endif;?>

            <?php if (in_array('data_enclave',$da_types)):?>
                <div class="form-check">
                    <label class="form-check-label item">
                        <input class="form-check-input chk chk-da enclave" type="checkbox" <?php if(isset($search_options->dtype) && is_array($search_options->dtype) && in_array('4',$search_options->dtype) ){echo 'checked="checked"'; }?> value="4" name="dtype[]" id="da_enclave">                        
                        <span class="filter-icon-da-enclave">
                            <small><?php echo t('legend_data_enclave');?></small>
                        </span> 
                    </label>
                </div>
            <?php endif;?>

            <?php  if (in_array('remote',$da_types)):?>
                <div class="form-check">
                    <label class="form-check-label item">
                        <input class="form-check-input chk chk-da remote" type="checkbox" <?php if(isset($search_options->dtype) && is_array($search_options->dtype) && in_array('5',$search_options->dtype) ){echo 'checked="checked"'; }?> value="5" name="dtype[]" id="da_remote">                        
                        <span class="filter-icon-da-remote">
                            <small><?php echo t('legend_data_remote');?></small>
                        </span> 
                    </label>
                </div>
            <?php  endif;?>

            <?php if (in_array('data_na',$da_types)):?>
                <div class="form-check">
                    <label class="form-check-label item">
                        <input class="form-check-input chk chk-da no_access" type="checkbox" <?php if(isset($search_options->dtype) && is_array($search_options->dtype) && in_array('6',$search_options->dtype) ){echo 'checked="checked"'; }?> value="6" name="dtype[]" id="da_na">
                        <span class="filter-icon-da-no_access">
                            <small><?php echo t('legend_na_access');?></small>
                        </span> 
                    </label>
                </div>
            <?php endif; ?>
        </div>
    </div>


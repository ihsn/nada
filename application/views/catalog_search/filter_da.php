	<h3 class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
    	<a href="#"><?php echo t('filter_by_data');?><span id="selected-countries" style="font-size:11px;padding-left:10px;"></span></a>
    </h3> 
	<div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" id="datatype-list" style="font-size:11px;">
    	<div class="flash">
            <div style="text-align:right;">
                <a  href="#" onclick="select_da('all');return false;"><?php echo t('link_select_all');?></a> | 
                <a  href="#" onclick="select_da('none');return false;"><?php echo t('link_clear');?></a> | 
                <a  href="#" onclick="select_da('toggle');return false;"><?php echo t('link_toggle');?></a>
            </div>

            <div class="filter-da">
				<?php if (in_array('direct',$this->da_types)):?>
                <label title="<?php echo t('link_data_direct');?>" for="da_direct">
                    <input class="chk" type="checkbox"  checked="checked" value="1" name="dtype[]" id="da_direct"/><span class="title"><?php echo t('legend_direct_access');?></span><br/>
                    <span class="da-desc"><?php echo t('data_direct_description');?></span>
                </label>
                <?php endif;?>
                
                <?php if (in_array('public',$this->da_types)):?>
                <label title="<?php echo t('link_data_public_hover');?>" for="da_public">
                    <input class="chk" type="checkbox"   checked="checked" value="2" name="dtype[]" id="da_public"/><span class="title"><?php echo t('legend_data_public');?></span><br/>
                    <span class="da-desc"><?php echo t('data_public_description');?></span>
                </label>
                <?php endif;?>
                
                <?php if (in_array('licensed',$this->da_types)):?>
                <label title="<?php echo t('link_data_licensed_hover');?>" for="da_licensed">
                    <input class="chk" type="checkbox"   checked="checked" value="3" name="dtype[]" id="da_licensed"/><span class="title"><?php echo t('legend_data_licensed');?></span><br/>
                    <span class="da-desc"><?php echo t('data_licensed_description');?></span>
                </label>
                <?php endif;?>
                
                <?php if (in_array('enclave',$this->da_types)):?>
                <label title="<?php echo t('link_data_enclave_hover');?>" for="da_enclave">
                    <input class="chk" type="checkbox"   checked="checked" value="4" name="dtype[]" id="da_enclave"/><span class="title"><?php echo t('legend_data_enclave');?></span><br/>
                    <span class="da-desc"><?php echo t('data_enclave_description');?></span>
                </label>
                <?php endif;?>
                
                <?php if (in_array('remote',$this->da_types)):?>
                <label title="<?php echo t('link_data_remote_hover');?>" for="da_remote">
                    <input class="chk" type="checkbox"   checked="checked" value="5" name="dtype[]" id="da_remote"/><span class="title"><?php echo t('legend_data_remote');?></span><br/>
                    <span class="da-desc"><?php echo t('data_remote_description');?></span>
                </label>
                <?php endif;?>
                
                <?php if (in_array('data_na',$this->da_types)):?>
                <label title="<?php echo t('link_data_na');?>" for="da_na">
                    <input class="chk" type="checkbox"  checked="checked" value="6" name="dtype[]" id="da_na"/><span class="title"><?php echo t('legend_na_access');?></span><br/>
                    <span class="da-desc"><?php echo t('data_da_description');?></span>
                </label>
                <?php endif;?>
            </div>

        </div>
	</div>
<div class="filter-box filter-by-dtype">
<h3><?php echo t('filter_by_data');?></h3>

<span class="search-help da-help">
    <img src="images/icon_question.gif" alt="help" title="Help" data-url="<?php echo site_url('catalog/help_da');?>">
</span>

<div id="datatype-list" >

    <div class="filter-da any" >
        <input type="checkbox" class="chk-da-any" id="chk-da-any"  <?php echo $search_options->dtype!="" ? '' : 'checked="checked"';?> />
        <label for="chk-da-any">Any</label>
    </div>
    
    <div class="filter-da items-container">
    	<table>
            <?php if (in_array('direct',$da_types)):?>
            <tr class="item">
                <td><input class="chk chk-da" type="checkbox"  <?php if($search_options->dtype!='' && in_array($dtype[1],$da_types) ){ echo 'checked="checked"'; }?> value="1" name="dtype[]" id="da_direct"/></td>
                <td><span class="da-icon-small da-direct"></span></td>
                <td class="nopad"> <label title="<?php echo t('data_direct_description');?>" for="da_direct"> <span class="title"><?php echo t('legend_data_direct');?></span> </label></td>
            </tr>
            <?php endif;?>
            
            <?php if (in_array('public',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da public" type="checkbox"   <?php if($search_options->dtype!='' && in_array($dtype[2],$da_types) ){ echo 'checked="checked"'; }?> value="2" name="dtype[]" id="da_public"/></td>
            <td><span class="da-icon-small da-public"></span></td>
            <td class="nopad"> <label title="<?php echo t('data_public_description');?>" for="da_public"><span class="title"> <?php echo t('legend_data_public');?></span></label></td>
            </tr>
            <?php endif;?>
            
            
			<?php if (in_array('licensed',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da licensed" type="checkbox"   <?php if($search_options->dtype!='' && in_array($dtype[3],$da_types) ){ echo 'checked="checked"'; }?> value="3" name="dtype[]" id="da_licensed"/></td>
            <td><span class="da-icon-small da-licensed"></span></td>
            <td class="nopad">
               <label title="<?php echo t('data_licensed_description');?>" for="da_licensed">
            		<span class="title"> <?php echo t('legend_data_licensed');?></span>
	            </label>
            </td>
            </tr>
            <?php endif;?>
            
            <?php if (in_array('data_enclave',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da enclave" type="checkbox"   <?php if($search_options->dtype!='' && in_array($dtype[4],$da_types) ){ echo 'checked="checked"'; }?> value="4" name="dtype[]" id="da_enclave"/></td>
            <td><span class="da-icon-small da-enclave"></span></td>
            <td class="nopad"><label title="<?php echo t('data_enclave_description');?>" for="da_enclave"><span class="title"> <?php echo t('legend_data_enclave');?></span></label></td>
            </tr>
            <?php endif;?>
            
            <?php if (in_array('remote',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da remote" type="checkbox"  <?php if($search_options->dtype!='' && in_array($dtype[5],$da_types) ){ echo 'checked="checked"'; }?> value="5" name="dtype[]" id="da_remote"/></td>
            <td><span class="da-icon-small da-remote"></span></td>
            <td class="nopad"><label title="<?php echo t('data_remote_description');?>" for="da_remote"><span class="title"><?php echo t('legend_data_remote');?></span></label></td>
            </tr>
            <?php endif;?>
            
            <?php if (in_array('data_na',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da no_access" type="checkbox" <?php if($search_options->dtype!='' && in_array($dtype[6],$da_types) ){ echo 'checked="checked"'; }?> value="6" name="dtype[]" id="da_na"/></td>
            <td><span class="da-icon-small da-no_access"></span></td>
            <td class="nopad"><label title="<?php echo t('data_na_description');?>" for="da_na"><span class="title"> <?php echo t('legend_na_access');?></span></label></td>
            </tr>
            <?php endif;?>
		</table>
    </div>
</div>
</div>
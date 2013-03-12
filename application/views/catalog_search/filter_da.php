<div class="filter-box">
<h3><?php echo t('filter_by_data');?></h3>

<a class="clear-filter" href="#"><?php echo t('reset');?></a>
     
<div id="datatype-list" >

    <div class="filter-da any">
        <input type="checkbox" class="chk-da-any" id="chk-da-any"  <?php echo $search_options->dtype!="" ? '' : 'checked="checked"';?> />
        <label for="chk-da-any">Any</label>
    </div>
    
    <div class="select-specific">Or select specific:</div>

    <div class="filter-da items-container">
    	<table>
            <?php if (in_array('direct',$da_types)):?>
            <tr class="item">
                <td><input class="chk chk-da" type="checkbox"  checked="checked" value="1" name="dtype[]" id="da_direct"/></td>
                </td>
                <td><span class="da-direct"></span></td>
                <td class="nopad"><span class="title"><?php echo t('legend_direct_access');?></span></td>
            </tr>
            <?php endif;?>
            
            <?php if (in_array('public',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da public" type="checkbox"   checked="checked" value="2" name="dtype[]" id="da_public"/></td>
            <td><span class="da-public"></span></td>
            <td class="nopad"> <label title="<?php echo t('link_data_public_hover');?>" for="da_public"><span class="title"> <?php echo t('legend_data_public');?></span></label></td>
            </tr>
            <?php endif;?>
            
            
			<?php if (in_array('licensed',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da licensed" type="checkbox"   checked="checked" value="3" name="dtype[]" id="da_licensed"/></td>
            <td><span class="da-licensed"></span></td>
            <td class="nopad">
               <label title="<?php echo t('link_data_licensed_hover');?>" for="da_licensed">
            		<span class="title"> <?php echo t('legend_data_licensed');?></span>
	            </label>
            </td>
            </tr>
            <?php endif;?>
            
            <?php if (in_array('enclave',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da enclave" type="checkbox"   checked="checked" value="4" name="dtype[]" id="da_enclave"/></td>
            <td><span class="da-enclave"></span></td>
            <td class="nopad"><label title="<?php echo t('link_data_enclave_hover');?>" for="da_enclave"><span class="title"> <?php echo t('legend_data_enclave');?></span></label></td>
            </tr>
            <?php endif;?>
            
            <?php if (in_array('remote',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da remote" type="checkbox"   checked="checked" value="5" name="dtype[]" id="da_remote"/></td>
            <td><span class="da-remote"></span></td>
            <td class="nopad"><label title="<?php echo t('link_data_remote_hover');?>" for="da_remote"><span class="title"><?php echo t('legend_data_remote');?></span></label></td>
            </tr>
            <?php endif;?>
            
            <?php if (in_array('data_na',$da_types)):?>
            <tr class="item">
            <td><input class="chk chk-da no_access" type="checkbox"  checked="checked" value="6" name="dtype[]" id="da_na"/></td>
            <td><span class="da-no_access"></span></td>
            <td class="nopad"><label title="<?php echo t('link_data_na');?>" for="da_na"><span class="title"> <?php echo t('legend_na_access');?></span></label></td>
            </tr>
            <?php endif;?>
		</table>
    </div>
</div>
</div>
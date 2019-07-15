<?php 
$da_types=(array)$this->input->get('dtype');
?>
<div id="datatype-list" >

    <div class="filter-da items-container">
    	<table>
            <tr class="item">
                <td style="width:20px;"><input class="chk chk-da" type="checkbox"  <?php if(in_array(7,$da_types) ){ echo 'checked="checked"'; }?> value="7" name="dtype[]" id="da_open"/></td>
                <td><span class="da-icon-small da-open"></span></td>
                <td class="nopad"> <label title="<?php echo t('link_data_open_hover');?>" for="da_open"> <span class="title"><?php echo t('legend_data_open');?></span> </label></td>
            </tr>
            
            <tr class="item">
                <td><input class="chk chk-da" type="checkbox"  <?php if(in_array(1,$da_types) ){ echo 'checked="checked"'; }?> value="1" name="dtype[]" id="da_direct"/></td>
                <td><span class="da-icon-small da-direct"></span></td>
                <td class="nopad"> <label title="<?php echo t('link_data_direct_hover');?>" for="da_direct"> <span class="title"><?php echo t('legend_data_direct');?></span> </label></td>
            </tr>

            <tr class="item">
            <td><input class="chk chk-da public" type="checkbox"   <?php if(in_array(2,$da_types) ){ echo 'checked="checked"'; }?> value="2" name="dtype[]" id="da_public"/></td>
            <td><span class="da-icon-small da-public"></span></td>
            <td class="nopad"> <label title="<?php echo t('link_data_public_hover');?>" for="da_public"><span class="title"> <?php echo t('legend_data_public');?></span></label></td>
            </tr>

            <tr class="item">
            <td><input class="chk chk-da licensed" type="checkbox"   <?php if(in_array(3,$da_types) ){ echo 'checked="checked"'; }?> value="3" name="dtype[]" id="da_licensed"/></td>
            <td><span class="da-icon-small da-licensed"></span></td>
            <td class="nopad">
               <label title="<?php echo t('link_data_licensed_hover');?>" for="da_licensed">
            		<span class="title"> <?php echo t('legend_data_licensed');?></span>
	            </label>
            </td>
            </tr>

            <tr class="item">
            <td><input class="chk chk-da enclave" type="checkbox"   <?php if(in_array(4,$da_types) ){ echo 'checked="checked"'; }?> value="4" name="dtype[]" id="da_enclave"/></td>
            <td><span class="da-icon-small da-enclave"></span></td>
            <td class="nopad"><label title="<?php echo t('link_data_enclave_hover');?>" for="da_enclave"><span class="title"> <?php echo t('legend_data_enclave');?></span></label></td>
            </tr>

            <tr class="item" valign="top">
            <td><input class="chk chk-da remote" type="checkbox"  <?php if(in_array(5,$da_types) ){ echo 'checked="checked"'; }?> value="5" name="dtype[]" id="da_remote"/></td>
            <td><span class="da-icon-small da-remote"></span></td>
            <td class="nopad"><label title="<?php echo t('link_data_remote_hover');?>" for="da_remote"><span class="title"><?php echo t('legend_data_remote');?></span></label></td>
            </tr>

            <tr class="item">
            <td><input class="chk chk-da no_access" type="checkbox" <?php if(in_array(6,$da_types) ){ echo 'checked="checked"'; }?> value="6" name="dtype[]" id="da_na"/></td>
            <td><span class="da-icon-small da-no_access"></span></td>
            <td class="nopad"><label title="<?php echo t('link_data_na');?>" for="da_na"><span class="title"> <?php echo t('legend_na_access');?></span></label></td>
            </tr>
		</table>
    </div>
</div>

<?php 
$da_types=(array)$this->input->get('dtype');
?>
<div id="datatype-list" >

    <div class="filter-da items-container">
    	<table>
            

            <?php foreach($this->data_access_types as $da_type):?>
                <tr class="item">
                    <td><input class="chk chk-da" type="checkbox"  <?php if(in_array($da_type['formid'],$da_types) ){ echo 'checked="checked"'; }?> value="<?php echo $da_type['formid'];?>" name="dtype[]" id="da_<?php echo $da_type['model'];?>"/></td>
                    <td><span class="da-icon-small da-<?php echo $da_type['model'];?>"></span> </td>
                    <td class="nopad"> 
                        <label title="<?php echo t('link_data_'.$da_type['model'].'_hover');?>" for="da_<?php echo $da_type['model'];?>">    
                                <?php echo t('legend_data_'.$da_type['model']);?>
                        </label>
                    </td>
                </tr>
            <?php endforeach;?>
            
           
		</table>
    </div>
</div>

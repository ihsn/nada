<table>
	<tr>
    	<td style="width:160px;"><?php echo t('ref_no');?></td>
        <td><?php echo $refno;?></td>
    </tr>
	<tr>
    	<td><?php echo t('country');?></td>
        <td><?php echo $nation;?></td>
    </tr>
	<tr>
    	<td><?php echo t('primary_investigator');?></td>
        <td><?php echo $authenty;?></td>
    </tr>
	<tr>
    	<td><?php echo t('repository');?></td>
        <td><?php echo $repositoryid;?></td>
    </tr>
	<tr>
    	<td><?php echo t('metadata_by');?></td>
        <td><?php echo $producer;?></td>
    </tr>
	<tr>
    	<td><?php echo t('folder');?></td>
        <td><?php echo $dirpath;?></td>
    </tr>    
</table>
<div class="action-bar" >
<span style="float:right;padding-right:10px;">
	<?php if($model=='direct'): ?>
        <a href="<?php echo site_url().'/access_direct/'.$id;?>" class="accessform" title="<?php echo t('link_data_direct_hover');?>">
        <span><img src="images/form_direct.gif" /></span>
        </a>                    
    <?php elseif($model=='public'): ?>                    
        <a href="<?php echo site_url().'/access_public/'.$id;?>" class="accessform"  title="<?php echo t('link_data_public_hover');?>">
        <span><img src="images/form_public.gif" /></span>
        </a>                    
    <?php elseif($model=='licensed'): ?>
        <a href="<?php echo site_url().'/access_licensed/'.$id;?>" class="accessform"  title="<?php echo t('link_data_licensed_hover');?>">
        <span><img src="images/form_licensed.gif" /></span>
        </a>                    
    <?php elseif($model=='data_enclave'): ?>
        <a href="<?php echo site_url().'/access_enclave/'.$id;?>" class="accessform"  title="<?php echo t('link_data_enclave_hover');?>">
        <span><img src="images/form_enclave.gif" /></span>
        </a>                    
    <?php endif; ?>    
        <a href="<?php echo site_url().'/admin/catalog/export_rdf/'.$id;?>"  alt="<?php echo t('rdf_export');?>" title="<?php echo t('rdf_export');?>">
        <span><img src="images/rdf_metadata_button.32.gif"/></span>
        </a>                    
</span>
    <ul>
        <li>                	
        	<a title="<?php echo t('resource_manager');?> - <?php echo $nation.' - ' .$titl;?>" href="<?php echo site_url()."/admin/managefiles/$id/"; ?>" onclick="IFrameDialog(this);return false;">
        		<img border="0" align="absbottom" src="images/page_attach.png"/> <?php echo t('resource_manager');?>
        	</a>
        </li>  
        <li>
        	<a title="<?php echo t('browse_metadata');?>" target="_blank" href="<?php echo site_url();?>/ddibrowser/<?php echo $id;?>" >
        		<img border="0" align="absbottom" src="images/page_white_cd.png"/> <?php echo t('browse_metadata');?> 
        	</a>
        </li>                        
        <li>
            <div class="field" style="display:inline;">
                <label><input type="checkbox" name="isshared" id="isshared" value="1" onclick="share_ddi(this,<?php echo $id;?>);" <?php echo (get_form_value('isshared',isset($isshared) ? $isshared: '') ? 'checked="checked"' : '') ; ?> /> <?php echo t('share_ddi_w_harvester'); ?></label>
            </div>        
        </li>                  
        <li class="float-right">
        	<a title="<?php echo t('delete');?>" style="cursor: pointer;" href="<?php echo site_url().'/admin/catalog/delete/'.$id; ?>">
        		<img border="0" align="absbottom" src="images/bin_closed.png"/> <?php echo t('delete');?>
            </a>
        </li>
    </ul> 
</div>
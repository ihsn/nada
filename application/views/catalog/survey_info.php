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
    <ul>
        <li>                	
        	<a title="<?php echo t('resource_manager');?> - <?php echo $nation.' - ' .$titl;?>" href="<?php echo site_url()."/admin/managefiles/$id/"; ?>" onclick="IFrameDialog(this);return false;">
        		<img border="0" align="absbottom" src="images/page_attach.png"/> <?php echo t('resource_manager');?>
        	</a>
        </li>  
<!--
        <li>                	
        	<a title="<?php echo t('file_manager');?>" href="<?php echo site_url()."/filemanager/datasets/$dirpath/"; ?>" onclick="IFrameDialog(this);return false;">
        		<img border="0" align="absbottom" src="images/folder_page_white.png"/> <?php echo t('file_manager');?>
        	</a>
        </li>  
-->        
        <li>
        	<a title="<?php echo t('browse_metadata');?>" target="_blank" href="<?php echo site_url();?>/ddibrowser/<?php echo $id;?>" >
        		<img border="0" align="absbottom" src="images/page_white_cd.png"/> <?php echo t('browse_metadata');?> 
        	</a>
        </li>                        
        <li class="float-right">    	
        	<a title="<?php echo t('delete');?>" style="cursor: pointer;" href="<?php echo site_url().'/admin/catalog/delete/'.$id; ?>">
        		<img border="0" align="absbottom" src="images/bin_closed.png"/> <?php echo t('delete');?>
            </a>
        </li>
        <li>
            <div class="field" style="display:inline;">
                <label><input type="checkbox" name="isshared" id="isshared" value="1" onclick="share_ddi(this,<?php echo $id;?>);" <?php echo (get_form_value('isshared',isset($isshared) ? $isshared: '') ? 'checked="checked"' : '') ; ?> /> <?php echo t('share_ddi_w_harvester'); ?></label>
            </div>        
        </li>                  
    </ul> 
</div>
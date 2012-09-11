<?php
if ( !function_exists('decode_json_string'))
{ 
    function decode_json_string($json_str)
    {
    	$decoded_str=json_decode($json_str);
        
        if (is_array($decoded_str) && count($decoded_str)>0)
        {
        	return implode("<BR />", $decoded_str);
        }
        
        return $json_str;
    }
}

//get study ownership type [linked/owned]
$study_ownership_type='owned';
foreach($repo as $repo_row)
{
	if ($repo_row['isadmin']==1)
	{
		$study_ownership_type='owned';
	}
	else
	{
		$study_ownership_type='linked';
	}
}
?>
<table class="tbl-survey-info">
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
        <td><?php echo decode_json_string($authenty);?></td>
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
    
    <!--
	<tr valign="top" class="inline-edit">
    	<td><?php echo t('admin_notes');?></td>
        <td>
        	<textarea rows="3" id="admin_note_<?php echo $id; ?>" style="width:100%;"><?php echo $admin_notes;?></textarea>
            <a class="mini-button" href="#" onclick="attach_note(<?php echo $id;?>,'admin');return false;">Update</a>
        </td>
    </tr>
	<tr valign="top" class="inline-edit alternate">
    	<td><?php echo t('reviewer_notes');?></td>
        <td>
        	<textarea rows="3" id="reviewer_note_<?php echo $id; ?>" style="width:100%;"><?php echo isset($reviewer_notes) ? $reviewer_notes : '';?></textarea>
            <a class="mini-button" href="#" onclick="attach_note(<?php echo $id;?>,'reviewer');return false;">Update</a>
        </td>
    </tr>
    -->
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
		<?php if (isset($has_citations) && (integer)$has_citations>0):?>
        <a target="_blank" href="<?php echo site_url().'/admin/catalog/export_citations/'.$id;?>"  alt="<?php echo t('export_study_citations');?>" title="<?php echo t('export_study_citations')." ($has_citations)";?>">
        <span><img src="images/book_open.png"/></span>        
        </a>
        <?php endif;?>
        <a href="<?php echo site_url().'/admin/catalog/export_rdf/'.$id;?>"  alt="<?php echo t('rdf_export');?>" title="<?php echo t('rdf_export');?>">
        <span><img src="images/rdf_metadata_button.32.gif"/></span>
        </a> 
        <a href="<?php echo site_url().'/admin/catalog/ddi/'.$id;?>"  alt="<?php echo t('download_ddi');?>" title="<?php echo t('download_ddi');?>">
        <span><img src="images/ddi2.gif"/></span>
        </a>                            
</span>
    <ul>
        <li>                    
            <a target="_blank" title="<?php echo t('edit');?> - <?php echo $nation.' - ' .$titl;?>" href="<?php echo site_url()."/admin/catalog/edit/$id/"; ?>" id="<?php echo 'survey-'.$id;?>">
                <img border="0" align="absbottom" src="images/page_attach.png"/> <?php echo t('edit');?>
            </a>
        </li>

        <li>                	
        	<a target="_blank" title="<?php echo t('resource_manager');?> - <?php echo $nation.' - ' .$titl;?>" href="<?php echo site_url()."/admin/managefiles/$id/"; ?>" onclick="popup_dialog(this);return false;" id="<?php echo 'survey-'.$id;?>">
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
        <?php if ($study_ownership_type=='linked'):?>
        <li class="unlink-study"><span class="icon linked"><a href="<?php echo site_url();?>/admin/catalog/unlink/<?php echo $this->active_repo->repositoryid;?>/<?php echo $id;?>">Unlink</a></span></li>
    	<?php endif;?>
        <li class="transfer-study"><span class="icon transfer"></span><a href="<?php echo site_url();?>/admin/catalog/transfer/<?php echo $id;?>"><?php echo t('transfer_study_ownership');?></a></li>
        <li class="replace-ddi"><span class="icon replace_ddi"></span><a href="<?php echo site_url();?>/admin/catalog/replace_ddi/<?php echo $id;?>"><?php echo t('replace_ddi');?></a></li>
    </ul> 
</div>

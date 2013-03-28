<?php
/*
* A list of related studies attached to a study
*
*/

//add choice when no relationship type is set
array_unshift($relationship_types, '-- Select Relationship Type --');
//$relationship_types=array_unique($relationship_types);
?>

<?php if (isset($related_studies) && count($related_studies)>0 ): ?>

<table class="grid-table table-related-studies" cellpadding="0" cellspacing="0" id="tab-content-related-studies">
<tbody>
	<tr align="left" valign="top">
    	<td colspan="3"><span class="info">Click on <i class="icon-remove-sign"></i> to remove citations</span>
        <div class="page-links" style="float:right"><a class="related_studies_attach_studies" href="javascript:void(0);">Attach studies</a></div>
        </td>
    </tr>    
	<tr class="header">
    	<td>Relationship type</td>
        <td>Related study</td>
		<td>&nbsp;</td>
    </tr>
<?php foreach ($related_studies as $study):?>
	<tr class="item" align="left" valign="top" data-sid_1="<?php echo $survey_id;?>" data-sid_2="<?php echo $study['sid_2'];?>" >
        <td><?php echo form_dropdown('relation_id', $relationship_types, $study['relationship_id'],'class="rel-type"'); ?></td>
		<td>
			<div><?php echo anchor('admin/catalog/edit/'.$study['sid'].'/related_studies',$study['titl']);?></div>
            <div class="sub-text"><?php echo $study['nation'];?>, <?php echo $study['data_coll_start'];?> </div> 
            </td>
    	<td nowrap="nowrap">
        	<a href="<?php echo site_url('admin/catalog/remove_related_study/'.$survey_id.'/'.$study['sid_2'].'/'.$study['relationship_id']);?>" class="icon-remove-sign link remove remove-related-study " title="Remove">&nbsp;</a>
        </td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>    

<?php else:?>
<div><a class="related_studies_attach_studies" href="javascript:void(0);"><?php echo t('no_related_studies_click_here_to_add');?></div></div>
<?php endif;?>
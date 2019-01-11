<?php
/*
* A list of related studies attached to a study
*
*/

//add choice when no relationship type is set
//array_unshift($relationship_types, '-- Select Relationship Type --');
//$relationship_types=array_unique($relationship_types);
?>

<div class="row">
<?php if (isset($related_studies) && count($related_studies)>0 ): ?>

<div class="page-links pull-right" style="margin-bottom:15px;">
	<a class="btn btn-default related_studies_attach_studies" href="<?php echo site_url('admin/catalog/attach_related_data/'.$survey_id);?>">
		<span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
		<?php echo t('attach_related_data');?>
	</a>
</div>



<table class="table table-striped table-related-studies" cellpadding="0" cellspacing="0" id="tab-content-related-studies">
<tbody>
	<tr class="header">
    <td><?php echo t('title');?></td>
		<td><?php echo t('relationship_type');?></td>
		<td>&nbsp;</td>
    </tr>
<?php foreach ($related_studies as $study):?>
	<tr class="item" align="left" valign="top" data-sid_1="<?php echo $survey_id;?>" data-sid_2="<?php echo $study['sid_2'];?>" >
		<td>
			<div><?php echo anchor('admin/catalog/edit/'.$study['sid'].'/related_studies',$study['title']);?></div>
      <div class="sub-text"><?php echo $study['nation'];?>, <?php echo $study['year_start'];?> </div>
    </td>
		<td><?php echo form_dropdown('relation_id', $relationship_types, $study['relationship_id'],'class="rel-type"'); ?></td>
    <td nowrap="nowrap">
      <a href="<?php echo site_url('admin/catalog/remove_related_study/'.$survey_id.'/'.$study['sid_2'].'/'.$study['relationship_id']);?>" class="btn btn-default remove remove-related-study " title="Remove">
				<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
			</a>
  	</td>

	</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else:?>
<div class="col-md-12">
	<?php echo t('no_related_studies_click_here_to_add');?>	
	<a class="btn btn-default related_studies_attach_studies" href="<?php echo site_url('admin/catalog/attach_related_data/'.$survey_id);?>">
		<span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
		<?php echo t('attach_related_data');?>
	</a>	
</div>
<?php endif;?>
</div>

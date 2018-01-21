<?php
/*
* A list of citations attached to a survey
*
*/
?>

<?php if (isset($related_citations) && ($related_citations!==FALSE)): ?>

<table class="table table-striped grid-table" cellpadding="0" cellspacing="0" id="related-citations-table">
<tbody>
	<tr align="left" valign="top">
    	<td colspan="2">
				<span class="info">
					<?php echo sprintf(t('click_on_icon_to_remove_citation'),'  <span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>');?>
				</span>
				<div class="pull-right">
					<a class="btn btn-default" href="<?php echo site_url('admin/related_citations/index/'.$survey_id);?>"><?php echo t('attach_citation');?></a>
				</div>
			</td>
    </tr>

<?php foreach ($related_citations as $citation):?>
	<tr align="left" valign="top">
    	<td nowrap="nowrap">
        	<a href="<?php echo site_url('admin/related_citations/remove/'.$survey_id.'/'.$citation['id']);?>" class="icon-trash link remove" title="Remove"><span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span></a>
        </td>
		<td><?php echo $this->chicago_citation->format($citation,'journal');?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else:?>
<div>
	<?php echo t('no_related_citations_click_here_to_add');?>
	<a class="btn btn-default" href="<?php echo site_url('admin/related_citations/index/'.$survey_id);?>"><?php echo t('attach_citation');?></a>
</div>
<?php endif;?>

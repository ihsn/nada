<?php
/*
*
* Show repos by section
*/
if (!isset($show_unpublished))
{
	$show_unpublished=FALSE;
}
?>
<?php if ($rows):?>
    <table class="repo-table" width="100%" cellspacing="0" cellpadding="4">
	<?php $tr_class=""; ?>
	<?php foreach($rows as $key=>$row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=" "; } ?>
        <?php if ($row->section!=$section){continue;}?>
        <?php if (!$row->ispublished && $show_unpublished==FALSE){continue;}?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">
            <td class="thumb"><a href="<?php echo site_url('catalog/'.$row->repositoryid.'/about');?>"><img class="repo-thumbnail" src="<?php echo base_url();?><?php echo $row->thumbnail; ?>"/></a></td>
            <td class="text">
			<h3><a href="<?php echo site_url('catalog/'.$row->repositoryid);?>/about"><?php echo $row->title; ?></a></h3>
			<p><?php echo $row->short_text; ?></p>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
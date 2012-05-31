<?php
/*
*
* Show repos by section
*/
?>
<div>
<h2 class="page-title"><?php echo $section_title;?></h2>
<?php if ($rows):?>
    <table class="repo-table" width="100%" cellspacing="0" cellpadding="4">
	<?php $tr_class=""; ?>
	<?php foreach($rows as $key=>$row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=" "; } ?>
        <?php if (!$row->ispublished || $row->section!==$section){continue;} //skip unpublished?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">
            <td class="thumb"><a href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>/about"><img src="<?php echo base_url();?>/<?php echo $row->thumbnail; ?>"/></a></td>
            <td class="text">
			<h3><a href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>/about"><?php echo $row->title; ?></a></h3>
			<p><a href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>/about"><?php echo $row->short_text; ?></a></p>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>
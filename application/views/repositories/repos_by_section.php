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
<<<<<<< HEAD
	<?php foreach($rows as $key=>$row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=" "; } ?>
        <?php if (!$row->ispublished || $row->section!==$section){continue;} //skip unpublished?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">
            <td class="thumb"><a href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>/about"><img src="<?php echo base_url();?><?php echo $row->thumbnail; ?>"/></a></td>
            <td class="text">
			<h3><a href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>/about"><?php echo $row->title; ?></a></h3>
			<p><a href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>/about"><?php echo $row->short_text; ?></a></p>
=======
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <?php if (!$row->ispublished || $row->section!==$section){continue;} //skip unpublished?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">
            <td class="thumb"><img src="<?php echo base_url();?>/<?php echo $row->thumbnail; ?>"/></td>
            <td>
			<h3><a href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>/about"><?php echo $row->title; ?></a></h3>
			<?php echo $row->short_text; ?>
>>>>>>> 0df80238506a3fa904ffbc982da373dfec446f9c
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>
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
<div>
<h2><?php echo $section_title;?></h2>
<?php if ($rows):?>
    <table class="repo-table" width="100%" cellspacing="0" cellpadding="4">
	<?php $tr_class="";$found=false; ?>
	<?php foreach($rows as $key=>$row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=" "; } ?>
        <?php if ($row->section!=$section){continue;}?>
        <?php if (!$row->ispublished && $show_unpublished==FALSE){continue;}?>
        <?php $found=true;?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">
            <td class="thumb"><a href="<?php echo site_url();?>/admin/repositories/edit/<?php echo $row->id;?>"><img alt="<?php echo $row->repositoryid; ?>" src="<?php echo base_url();?><?php echo $row->thumbnail; ?>"/></a></td>
            <td class="text">
			<h3><a href="<?php echo site_url();?>/admin/repositories/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a> <span class="repositoryid">[<?php echo $row->repositoryid;?>]</span></h3>
			<p><?php echo $row->short_text; ?></p>
            <div class="options">
            	<?php if($row->ispublished):?>
                	Published
                <?php else:?>
                	Draft    
				<?php endif;?>
                
                <?php echo $row->type?>
            </div>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
    <?php if (!$found):?>
    	<?php echo t('no_records_found'); ?>
    <?php endif;?>
        <div style="margin-bottom:20px;">&nbsp;</div>

<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>
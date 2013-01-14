<?php
/*
* list comments history
*
*/
?>

<?php if (!isset($comments_history) || !$comments_history): ?>
	<?php return;?>
<?php endif; ?>

<!-- grid -->
<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
	    <th><?php echo t('dated');?></th>
	    <th><?php echo t('comment_by');?></th>        			
        <th><?php echo t('comment');?></th>                    
    </tr>
<?php $tr_class=""; ?>
<?php foreach($comments_history as $row): ?>
    <?php $row=(object)$row; ?>
    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    <tr class="<?php echo $tr_class; ?>">
        <td><?php echo date("m/d/Y",$row->created); ?></td>
        <td><?php echo $row->user_id;?></td>        
        <td><?php echo $row->description;?></td>
    </tr>
<?php endforeach;?>
</table>

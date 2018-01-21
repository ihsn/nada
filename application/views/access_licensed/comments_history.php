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
<table class="table table-striped comments-history-table" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
	    <th><?php echo t('dated');?></th>
	    <th><?php echo t('status');?></th>        
	    <th><?php echo t('comment_by');?></th>        			
        <th><?php echo t('comment');?></th>                    
    </tr>
<?php $tr_class=""; ?>
<?php foreach($comments_history as $row): ?>
    <?php $row=(object)$row; ?>
    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    <tr class="<?php echo $tr_class; ?>" style="vertical-align:top">
	    <td><?php echo date("m/d/Y",$row->created); ?></td>
        <td><?php echo $row->request_status;?></td>        
        <td nowrap="nowrap"><?php echo $row->first_name;?> <?php echo $row->last_name;?></td>
        <td><div class="admin-comment"><?php echo nl2br(form_prep($row->description));?></div></td>
    </tr>
<?php endforeach;?>
</table>

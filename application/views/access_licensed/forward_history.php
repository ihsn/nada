<?php
/*
* list email history
*
*/
?>

<?php if (!isset($forward_history) || !$forward_history): ?>
	<?php return;?>
<?php endif; ?>

<!-- grid -->
<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
	    <th><?php echo t('dated');?></th>
	    <th><?php echo t('sent_by');?></th>        			
        <th><?php echo t('email');?></th>
    </tr>
<?php $tr_class=""; ?>
<?php foreach($forward_history as $row): ?>
    <?php $row=(object)$row; ?>
    <?php $email=unserialize($row->description);?>
    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    <tr class="<?php echo $tr_class; ?>" valign="top">
        <td><?php echo date("m/d/Y",$row->created); ?></td>
        <td><?php echo $row->user_id;?></td>
        <td>
        	<div class="view-email-forward" title="<?php echo t("show_hide");?>" > 
            	<a class="subject"><?php echo $email['subject'];?></a>
                <div class="message-body">
                    <div><span class="email-field"><?php echo t('to');?>:</span> <?php echo $email['to'];?></div>
                    <div><span class="email-field"><?php echo t('cc');?>:</span> <?php echo $email['cc'];?></div>
                    <div><span class="email-field"><?php echo t('subject');?>:</span> <?php echo $email['subject'];?></div>
                    <div class="email_body" ><?php echo nl2br($email['body']);?></div>
                </div>
            </div>
        </td>
    </tr>
<?php endforeach;?>
</table>

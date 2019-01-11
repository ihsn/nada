<div class="container-fluid" >

<h4><?php echo t('download_summary');?></h4>
<?php if ($summary_rows): ?>
    <!-- grid -->
    <table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('file');?></th>
            <th><?php echo t('downloaded');?></th>
            <th><?php echo t('download_limit');?></th>			
			<th><?php echo t('last_accessed');?></th>
            <th><?php echo t('expiry_date');?></th>
            <th><?php echo t('status');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($summary_rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
	        <td><?php echo form_prep(basename($row->filepath)); ?></td>
            <td><?php echo $row->downloads; ?></td>
            <td><?php echo $row->download_limit; ?>&nbsp;</td>			
			<td><?php echo date("m-d-Y H:i:s",$row->lastdownloaded); ?></td>
            <td><?php echo date("m-d-Y H:i:s",$row->expiry); ?></td>
            <td>
            	<?php
					if ($row->expiry < date("U") || $row->downloads>=$row->download_limit)
					{
						echo '<img src="images/icon_cancel.gif" alt="EXPIRED"/>';
					}
					else
					{
						echo '<img src="images/tick.png" alt="ACTIVE"/>';
					}
				?>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>

<h4><?php echo t('download_log');?></h4>
<?php if ($log_rows): ?>
    <!-- grid -->
    <table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('file');?></th>
            <th><?php echo t('ip_address');?></th>
            <th><?php echo t('username');?></th>
			<th><?php echo t('dated');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($log_rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
	        <td><?php echo basename($row->filepath); ?></td>
            <td><?php echo $row->ip; ?></td>
            <td><?php echo $row->username; ?>&nbsp;</td>			
			<td><?php echo date("m-d-Y H:i:s",$row->created); ?></td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>

</div>

<?php
/*
* Shows the licensed request (files) download log
*
*/
?>
<div class="body-container" style="padding:10px;">

<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/menu/add" class="button"><img src="images/icon_plus.gif"/>Add new</a> 
    <a href="<?php echo site_url(); ?>/admin/menu/add/external" class="button"><img src="images/icon_plus.gif"/>Add external page</a> 
</div>

<h1 class="page-title">Licensed data files</h1>

<?php if ($files): ?>

    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th>File</th>
            <th>Downloaded</th>			
            <th>Download limit</th>			
			<th>Last downloaded</th>
            <th>Expiry</th>
            
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($files as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
            <td><?php echo $row->file_path; ?></td>
            <td><?php echo $row->downloads; ?>&nbsp;</td>			
            <td><?php echo $row->download_limit; ?>&nbsp;</td>			
            <td><?php echo date("m-d-Y H:i:s",$row->lastdownloaded); ?></td>
			<td><?php echo date("m-d-Y H:i:s",$row->expiry); ?></td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
No records found
<?php endif; ?>
</div>
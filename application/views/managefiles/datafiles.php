<div>
<?php if ($files): ?>
	<h2><?php echo t('data_selection_apply_to_files');?></h2>
    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('title');?></th>
            <th><?php echo t('filename');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($files as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
            <td><?php echo $row->title; ?></td>
            <td><?php echo basename($row->filename);?></td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
<div style="margin-bottom:20px;font-size:16px;"><?php echo sprintf(t('study_no_data_files_assigned'),anchor('admin/managefiles/'.$this->uri->segment(3),t('manage_files')));?>.</div>
<?php endif; ?>
</div>

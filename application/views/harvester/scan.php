<style>
.completed{background-color:#00CC00; }
.pending{background-color:#FFCC33}
.error{background-color:#FF0000}
</style>
<div class="body-container" style="padding:10px;">
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo count($rows);?> surveys were found</h1>
<?php if ($rows): ?>
    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header" align="left">
            <th>Country</th>
            <th>Title</th>
            <th>Year</th>
            <th>Created</th>
            <th>Status</th>
            <th>Last updated</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
	        <td><?php echo $row->country?></td>
            <td><a href="<?php echo $row->survey_url?>"><?php echo $row->title?></a></td>
            <td><?php echo $row->survey_year?></td>
			<td><?php echo date("j F Y, g:i a",$row->survey_timestamp)?></td>
            <td><?php echo $row->status?></td>
            <td><?php echo date("j F Y, g:i a",$row->changed)?></td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
<div><?php echo anchor('admin/repositories',t('click_here_to_return_to_repositories'));?></div>
</div>
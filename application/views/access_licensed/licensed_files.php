<h2 style="margin-top:20px;">Data files</h2>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
<?php //var_dump($files);?>

<?php if ($files) :?>
<!-- grid -->
<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
        <th><?php echo 'File'; ?></th>
        <th><?php echo 'Size'; ?></th>
        <th>Download</th>
    </tr>
<?php $tr_class=""; ?>
<?php foreach($files as $file): ?>
	<?php $file=(object)$file; ?>
    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    <tr class="<?php echo $tr_class; ?>">
    	<?php
		$file_path=str_replace('\\','/',$file->file_path);
		$file_path=end(explode('/',$file_path));
		?>
        <td><?php echo anchor('access_licensed/download/'.$this->uri->segment(3).'/'.$file->id,$file_path); ?></td>
        <td><?php echo $file->id; ?> kb&nbsp;</td>
        <td><a href="<?php echo current_url();?>/download/<?php echo ($file->id);?>">download</a></td>
    </tr>
<?php endforeach;?>
</table>
<?php else: ?>
No files are available for download.
<?php endif;?>
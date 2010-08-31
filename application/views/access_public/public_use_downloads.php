<div class="content-container">
<h1 class="page-title">Download survey files</h1>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<!-- grid -->
<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
        <th><?php echo 'File'; ?></th>
        <th><?php echo 'Size'; ?></th>
        <th>Download</th>
    </tr>
<?php $tr_class=""; ?>
<?php foreach($files as $file): ?>
    <?php $file=(object)$file;?>
    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    <tr class="<?php echo $tr_class; ?>">
        <td><a href="<?php echo current_url();?>/download/<?php echo base64_encode($file->server_path);?>"><?php echo $file->name; ?></a></td>
        <td><?php echo round($file->size/(1024),2); ?> kb&nbsp;</td>
        <td><a href="<?php echo current_url();?>/download/<?php echo base64_encode($file->server_path);?>">download</a></td>
    </tr>
<?php endforeach;?>
</table>
</div>
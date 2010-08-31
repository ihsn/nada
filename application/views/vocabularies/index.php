<div class="body-container" style="padding:10px;">

<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/vocabularies/add" class="button"><img src="images/icon_plus.gif"/><?php echo t('add_vocabulary');?></a> 
</div>
        
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('title_vocabulary');?></h1>

<?php if($rows):?>
	 <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('vocabulary');?></th>
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
            <td><a href="<?php echo site_url();?>/admin/terms/<?php echo $row->vid;?>""><?php echo $row->title; ?></a></td>
			<td>
            	<a href="<?php echo site_url();?>/admin/terms/<?php echo $row->vid;?>"><?php echo t('terms');?></a> | 
                <a href="<?php echo current_url();?>/edit/<?php echo $row->vid;?>"><?php echo t('edit');?></a> | 
                <a href="<?php echo current_url();?>/delete/<?php echo $row->vid;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
<?php else:?>
	<?php echo t('no_records_found');?>
<?php endif;?>    
</div>
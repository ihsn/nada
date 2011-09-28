<div class="body-container" style="padding:10px;">

<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/vocabularies/" class="button"><img src="images/house.png"/><?php echo t('home');?></a>
    <a href="<?php echo site_url(); ?>/admin/terms/<?php echo $this->uri->segment(3);?>/add" class="button"><img src="images/icon_plus.gif"/><?php echo t('add_term');?></a> 
</div>
        
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo $page_title;?></h1>

<?php if ($rows):?>
	 <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('term');?></th>
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $key=>$value): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
            <td><a href="<?php echo current_url();?>/edit/<?php echo $key;?>""><?php echo $value; ?></a></td>
			<td>
                <a href="<?php echo current_url();?>/edit/<?php echo $key;?>"><?php echo t('edit');?></a> | 
                <a href="<?php echo current_url();?>/delete/<?php echo $key;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
<?php else:?>
	<?php echo t('no_records_found');?>
<?php endif;?>    
</div>
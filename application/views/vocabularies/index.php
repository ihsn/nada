<div class="content-container container">

<div class="text-right page-links">
	<a href="<?php echo site_url(); ?>/admin/vocabularies/add" class="btn btn-default">
    	<span class="glyphicon glyphicon-plus ico-add-color right-margin-5" aria-hidden="true"></span> <?php echo t('add_vocabulary');?></a> 
</div>
        
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('title_vocabulary');?></h1>

<?php if($rows):?>
	 <!-- grid -->
    <table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
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

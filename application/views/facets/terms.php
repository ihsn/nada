<div class="container-fluid">

<div class="text-right page-links">
	<a href="<?php echo site_url('admin/facets'); ?>" class="btn btn-outline-primary btn-sm">
        <span class="fas fa-home ico-add-color right-margin-5" aria-hidden="true"></span> 
        <?php echo t('home');?>
    </a>
</div>


<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h3 class="page-title mt-3"><a href="<?php echo site_url('admin/facets'); ?>"><?php echo t('Facets');?></a> / <?php echo $facet['name'];?></h3>

<?php if($rows):?>
	<div><?php echo t('Found');?>: <?php echo count($rows);?></div>
	 <!-- grid -->
    <table class="table table-striped table-sm" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><?php echo t('ID');?></th>            
            <th><?php echo t('Terms');?></th>            
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>        
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">            
            <td><?php echo $row->id;?></td>            
            <td><?php echo $row->value;?></td>			
        </tr>
    <?php endforeach;?>
<?php else:?>
	<?php echo t('no_records_found');?>
<?php endif;?>    
</div>

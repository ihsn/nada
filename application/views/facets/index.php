<div class="container-fluid">


<div class="text-right page-links">
	<a href="<?php echo site_url('admin/facets'); ?>" class="btn btn-outline-primary btn-sm">
        <span class="fas fa-home ico-add-color right-margin-5" aria-hidden="true"></span> 
        <?php echo t('home');?>
    </a>

    <a href="<?php echo site_url('admin/facets/indexer'); ?>" class="btn btn-outline-primary btn-sm">
        <span class="fas fa-coins ico-add-color right-margin-5" aria-hidden="true"></span> 
        <?php echo t('Indexer');?>
    </a>
</div>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h3 class="page-title mt-3"><?php echo t('Facets');?></h3>

<?php if($rows):?>
	
	 <!-- grid -->
    <table class="table table-striped table-sm" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">        	
            <th>#</th>
            <th><?php echo t('Name');?></th>
            <th><?php echo t('Terms');?></th>            
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class="";$k=0; ?>
	<?php foreach($rows as $row):$k++; ?>
    	<?php $row=(object)$row;?>        
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">
            <td><?php echo $k;?></td>                        
            <td><a href="<?php echo site_url('admin/facets/terms/'.$row->id);?>"><?php echo $row->name; ?></a></td>            
            <td><?php echo $row->total;?></td>
			<td>                
                <a href="<?php echo site_url('admin/facets/delete/'.$row->id);?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
<?php else:?>
	<?php echo t('no_records_found');?>
<?php endif;?>    
</div>

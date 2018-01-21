<div class="container-fluid">

<div class="text-right page-links">
	<a href="<?php echo site_url('admin/countries/add'); ?>" class="btn btn-default">
        <span class="glyphicon glyphicon-plus ico-add-color right-margin-5" aria-hidden="true"></span> 
        <?php echo t('add_country');?>
    </a>
    <a href="<?php echo site_url('admin/countries/mappings'); ?>" class="btn btn-default"> 
        <span class="glyphicon glyphicon-random ico-add-color right-margin-5" aria-hidden="true"></span> 
        <?php echo t('country_mappings');?>
    </a> 
</div>


<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('Countries');?></h1>

<?php if($rows):?>
	<div><?php echo t('Countries');?>: <?php echo count($rows);?></div>
	 <!-- grid -->
    <table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><?php echo t('ID');?></th>
            <th><?php echo t('iso');?></th>
            <th><?php echo t('country');?></th>
            <th><?php echo t('aliases');?></th>
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>        
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">            
            <td><?php echo $row->countryid;?></td>
            <td><?php echo $row->iso;?></td>
            <td><a href="<?php echo site_url();?>/admin/countries/edit/<?php echo $row->countryid;?>"><?php echo $row->name; ?></a></td>
            <td>
            	<?php if(array_key_exists($row->countryid,$aliases) ):?>
                	<div><?php echo implode("<br/>",$aliases[$row->countryid]);?></div>
                <?php endif;?>
            </td>
			<td>
                <a href="<?php echo site_url();?>/admin/countries/edit/<?php echo $row->countryid;?>"><?php echo t('edit');?></a> | 
                <a href="<?php echo site_url();?>/admin/countries/delete/<?php echo $row->countryid;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
<?php else:?>
	<?php echo t('no_records_found');?>
<?php endif;?>    
</div>

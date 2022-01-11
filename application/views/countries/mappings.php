<style>
.form-control-custom{
	width:auto;
	display:inline;
}
</style>
<div class="container-fluid">

<div class="text-right page-links">
	<a href="<?php echo site_url(); ?>/admin/countries" class="btn btn-outline-primary btn-sm">
    	<span class="fas fa-home ico-add-color right-margin-5" aria-hidden="true"></span> <?php echo t('countries');?></a>
</div>


<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h3 class="page-title mt-3"><?php echo t('Fix Country Mapping');?></h3>

<div class="country-dropdown" style="display:none;">
<?php echo form_dropdown('cid', $country_list,NULL,'class="form-control-sm form-control-custom"'); ?>
</div>

<?php if($rows):?>
	<div><?php echo t('Total rows');?>: <?php echo count($rows);?></div>
	 <!-- grid -->
    <table class="table table-striped table-sm" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><?php echo t('Country');?></th>
            <th><?php echo t('Assign Country');?></th>
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>        
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
       
    	<tr class="<?php echo $tr_class; ?>" valign="top">
            <td>
				<a href="<?php echo site_url();?>/admin/countries/edit/<?php echo $row->country_name;?>">
				<?php echo $row->country_name; ?>
			</a> (<?php echo $row->total;?>)
			</td>
            <td> 
            	<form method="get" action="<?php echo site_url('admin/countries/fix_mappings');?>">
                    <input type="hidden" name="name" value="<?php echo $row->country_name;?>"/>
                    <span class="country-list"></span>
                    <input type="submit" name="Submit" value="Update" class="btn btn-primary btn-sm"/>
            	</form>
            </td>
			<td>
                
            </td>
        </tr>
        
    <?php endforeach;?>
<?php else:?>
	<?php echo t('no_records_found');?>
<?php endif;?>    
</div>


<script>
$(document).ready(function() 
{	
	$(".country-list").html($(".country-dropdown select"));
	
});

</script>

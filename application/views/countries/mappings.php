<div class="body-container" style="padding:10px;">

<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/countries" class="button"><img src="images/house.png"/><?php echo t('home');?></a>
</div>


<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('Fix Country Mapping');?></h1>

<div class="country-dropdown" style="display:none;">
<?php echo form_dropdown('cid', $country_list,NULL); ?>
</div>

<?php if($rows):?>
	<div><?php echo t('Total rows');?>: <?php echo count($rows);?></div>
	 <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
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
            <td><a href="<?php echo site_url();?>/admin/countries/edit/<?php echo $row->country_name;?>"><?php echo $row->country_name; ?></a> (<?php echo $row->total;?>)</td>
            <td> 
            	<form method="get" action="<?php echo site_url('admin/countries/fix_mappings');?>">
                    <input type="hidden" name="name" value="<?php echo $row->country_name;?>"/>
                    <span class="country-list"></span>
                    <input type="submit" name="Submit" value="Update"/>
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
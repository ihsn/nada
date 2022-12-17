<div class="container-fluid menu-index-page">

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>


<h3 class="page-title mt-5 mb-5"><?php echo t('Whitelist data access');?></h3>



	<?php echo form_open_multipart(site_url('admin/dataaccess_whitelist/create'), array('class'=>'form mt-4') ); ?>
	<div class="form-row align-items-center">
		<div class="col-md-3">
			<div class="form-group" >
				<label for="username"><?php echo t('Email'); ?><span class="required">*</span></label>
				<input class="form-control"  name="email" type="text" id="email"  value="<?php echo get_form_value('email', isset($email) ? $email : ''); ?>"/>			
			</div>
		</div>		
		<div class="col-md-3">
			<div class="form-group">
				<label for="collection">Collection</label>
				<select class="form-control" name="repository_id">
					<option value="0">Select</option>
					<?php foreach($collections as $collection):?>
						<option value="<?php echo $collection['id'];?>"><?php echo $collection['title'];?> (<?php echo $collection['repositoryid'];?>)</option>
					<?php endforeach;?>
				</select>
			</div>			
		</div>
		<div class="col-md-1">
		<button type="submit" class="btn btn-primary mt-3">Whitelist</button>
		</div>
	</div>
	<?php echo form_close();?>

<?php if ($rows): ?>
    <!-- grid -->
    <table class="table table-striped table-sm table-border-bottom" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
			<th>Email</th>
			<th>Collection</th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">        	
            <td><?php echo $row['email'];?></td>
            <td><?php echo $row['collection_name'];?></td>
			<td>
                <a href="<?php echo current_url();?>/delete/<?php echo $row['id'];?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
    </table>

<?php else: ?>
	<?php echo t('no_records_found'); ?>
<?php endif;?>
</div>
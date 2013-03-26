<style>
.parent{font-weight:bold;}
.child{margin-left:25px;}
</style>
<div class="body-container" style="padding:10px;">

<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/regions/add" class="button"><img src="images/icon_plus.gif"/><?php echo t('create_region');?></a> 
</div>
        
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('Regions');?></h1>

<?php if($tree):?>
	 <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('ID');?></th>
            <th><?php echo t('PID');?></th>
            <th><?php echo t('Region');?></th>            
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php /* ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>        
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
	        <td><?php echo $row->id;?></td>
            <td><?php echo $row->pid;?></td>
            <td><a href="<?php echo site_url();?>/admin/regins/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a></td>            
			<td>
                <a href="<?php echo site_url();?>/admin/regions/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> | 
                <a href="<?php echo site_url();?>/admin/regions/delete/<?php echo $row->id;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
	<?php */ ?>


	<?php foreach($tree as $row): ?>
    	<?php $row=(object)$row;?>        
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
	        <td><?php echo $row->id;?></td>
            <td><?php echo $row->pid;?></td>
            <td><a class="parent" href="<?php echo site_url();?>/admin/regions/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a></td>            
			<td>
                <a href="<?php echo site_url();?>/admin/regions/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> | 
                <a href="<?php echo site_url();?>/admin/regions/delete/<?php echo $row->id;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
        <?php if (isset($row->children)):?>
        	<?php $this->load->view('regions/region_children',array('children'=>$row->children, 'tr_class'=>$tr_class));?>
        <?php endif;?>
    <?php endforeach;?>
<?php else:?>
	<?php echo t('no_records_found');?>
<?php endif;?>    
</div>
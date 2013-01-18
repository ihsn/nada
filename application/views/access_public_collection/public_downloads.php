<div class="body-container" style="padding:10px;margin-bottom:20px;">
<?php if (!isset($hide_form)):?>
	<?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>
    
    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>
    
    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
<?php endif; ?>
    
<h1 style="margin-bottom:30px;"><?php echo t('collection_data_files'). ' - ' . $collection_title;?></h1>

<?php if ($surveys): ?>

<?php foreach($surveys as $survey):?>
	
    <h3><?php echo $survey['nation'].' - '.$survey['titl'];?></h3>

    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0" >
    	<tr class="header">
	        <th><?php echo t('title');?></th>            
            <th><?php echo t('filename');?></th>
            <th><?php echo t('size');?></th>
            <th><?php echo t('date');?></th>            
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($survey['resources'] as $row): ?>
    	<?php $survey_folder=$survey['survey_folder'];?>
		<?php $row=(object)$row; ?>
        <?php $filepath=unix_path($survey_folder.'/'.$row->filename);?>
        <?php $file_exists=file_exists($filepath);?>
        <?php if ($file_exists):?>
			<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
            <tr class="<?php echo $tr_class; ?>">
                <td><a href="<?php echo site_url();?>/access_public/download/<?php echo $survey['id'];?>/<?php echo $row->resource_id;?>"><?php echo $row->title; ?></a></td>            
                <td><a href="<?php echo site_url();?>/access_public/download/<?php echo $survey['id'];?>/<?php echo $row->resource_id;?>"><?php echo basename($row->filename); ?></a></td>
                <td><?php echo format_bytes(@filesize(unix_path($survey_folder.'/'.$row->filename)),2);?></td>
                <td><?php echo date($this->config->item("date_format_long"),$row->changed); ?></td>            
                <td>
                    <a href="<?php echo site_url();?>/access_public/download/<?php echo $survey['id'];?>/<?php echo $row->resource_id;?>"><?php t('download');?></a>
                </td>
            </tr>
			<?php endif;?>
    <?php endforeach;?>
    </table>
    <div style="padding-bottom:20px;"></div>
	
<?php endforeach;?>

<?php else: ?>
<?php t('no_records_found');?>
<?php endif; ?>
</div>
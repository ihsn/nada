<?php
	$surveyid=$this->uri->segment(2);
?>
<div class="body-container" style="padding:10px;">
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
    
<h1 style="margin:0px;"><?php echo t('survey_data_files');?></h1>
<?php //$this->load->view('datafiles/upload_file');?>

<?php if ($rows): ?>
<?php		
		$sort_by=$this->input->get("sort_by");
		$sort_order=$this->input->get("sort_order");			
?>
<?php 
	//sort
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	
	//current page url
	$page_url=site_url().$this->uri->uri_string();
?>

<form autocomplete="off">
    
    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
	        <th><?php echo t('title');?></th>
            <th><?php echo t('filename');?></th>
            <th><?php echo t('size');?></th>
            <th><?php echo t('date');?></th>            
			<th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	
		<?php $row=(object)$row;?>
        <?php $filepath=unix_path($this->survey_folder.'/'.$row->filename);?>
        <?php $file_exists=file_exists($filepath);?>
        <?php if ($file_exists):?>
			<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
            <tr class="<?php echo $tr_class; ?>">
                <td><a href="<?php echo site_url();?>/access_direct/download/<?php echo $surveyid;?>/<?php echo $row->resource_id;?>"><?php echo $row->title; ?></a></td>            
                <td><a href="<?php echo site_url();?>/access_direct/download/<?php echo $surveyid;?>/<?php echo $row->resource_id;?>"><?php echo basename($row->filename); ?></a></td>
                <td><?php echo format_bytes(@filesize(unix_path($this->survey_folder.'/'.$row->filename)),2);?></td>
                <td><?php echo date($this->config->item("date_format_long"),$row->changed); ?></td>            
                <td>
                    <a href="<?php echo site_url();?>/access_direct/download/<?php echo $surveyid;?>/<?php echo $row->resource_id;?>"><?php echo t('download');?></a>
                </td>
            </tr>
			<?php endif;?>
    <?php endforeach;?>
    </table>
</form>
<?php else: ?>
<?php echo t('no_records_found');?>
<?php endif; ?>
</div>
<?php
/*
List of broken external resources
*/
?>
<style>
.small{font-size:10px;}
</style>
<table style="width:100%;">
<tr>
	<td><h1><?php echo t('broken_resources');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>

<?php if ($rows):?>
	<?php $k=1;?>
    <table class="report-table" style="width:100%;">
    	<tr>
	        <th><?php echo t('no#');?></th>
            <th><?php echo t('survey_id');?></th>
            <th><?php echo t('resource_id');?></th>
            <th><?php echo t('title');?></th>
            <th>folder</th>
            <th><?php echo t('filename');?></th>
        </tr>
    <?php $prev_study='';?>    
    <?php foreach($rows as $row):?>
        <tr>
            <td><?php echo $k++;?></td>
            <td><a href="<?php echo site_url();?>/admin/catalog/<?php echo $row['survey_id'];?>/resources/"><?php echo $row['survey_id'];?></a></td>
            <td><?php echo $row['resource_id'];?></td>
            <td><a href="<?php echo site_url();?>/admin/catalog/<?php echo $row['survey_id'];?>/resources/edit/<?php echo $row['resource_id'];?>"><?php echo $row['title'];?></a></td>
            <td class="small"><?php echo $this->Catalog_model->get_survey_path_full($row['survey_id']);?></td>
            <td  class="small"><?php echo $row['filename'];?></td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else:?>    
<?php echo t('no_records_found');?>
<?php endif;?>
<table style="width:100%;">
<tr>
	<td><h1><?php echo t('most_viewed_studies_summary');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>

<?php if ($rows):?>
    <table class="report-table" style="width:100%;">
    	<tr>
        	<th><?php echo t('study_id');?></th>
            <th><?php echo t('study_title');?></th>
            <th><?php echo t('hits');?></th>
        </tr>
	<?php foreach($rows as $row):?>
    	<tr>
        	<td><?php echo $row['idno'];?></td>
            <td><?php echo $row['title'];?></td>
            <td><?php echo $row['visits'];?></td>
        </tr>
    <?php endforeach;?>    
    </table>
<?php else:?>    
<?php echo t('no_records_found');?>
<?php endif;?>
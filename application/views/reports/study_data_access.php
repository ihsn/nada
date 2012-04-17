<?php
/*
Public use and Licensed Studies with no data files attached
*/
?>
<table style="width:100%;">
<tr>
	<td><h1><?php echo t('missing_data');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>

<?php if ($rows):?>
	<?php $k=1;?>
    <table class="report-table" style="width:100%;">
    	<tr>
	        <th><?php echo t('no#');?></th>
            <th><?php echo t('repositoryid');?></th>
            <th><?php echo t('titl');?></th>
        </tr>
    <?php $prev_study='';?>    
    <?php foreach($rows as $row):?>
        <tr>
            <td><?php echo $k++;?></td>
            <td><?php echo strtoupper($row['repositoryid']);?></td>
            <td><a href="<?php echo site_url();?>/catalog/<?php echo $row['id'];?>"><?php echo $row['nation'].' - '.$row['titl'];?></a></td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else:?>    
<?php echo t('no_records_found');?>
<?php endif;?>
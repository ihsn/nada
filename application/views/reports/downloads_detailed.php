<table style="width:100%;">
<tr>
	<td><h1><?php echo t('downloads_detailed');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>

<?php if ($rows):?>
    <table class="report-table" style="width:100%;">
    	<tr>
            <th><?php echo t('filename');?></th>
            <th><?php echo t('date');?></th>
            <th><?php echo t('username');?></th>
            <th><?php echo t('email');?></th>
            <th><?php echo t('country');?></th>
            <th><?php echo t('ip_address');?></th>
        </tr>
    <?php $prev_study='';?>    
    <?php foreach($rows as $row):?>
        	<?php if ($prev_study!=$row['survey_title']):?>
            	<?php $prev_study=$row['survey_title'];?>
                <tr style="background-color:#F2F2F2">
	            <td colspan="6"><b><?php echo $row['survey_title'];?></b> (<?php echo t($row['form_type']);?>)</td>
                </tr>
            <?php endif;?>
        <tr>
            <td style="padding-left:20px;">
				<?php echo $row['download_filename'];?>                
            </td>            
            <td><?php echo date("m/d/y",$row['logtime']);?></td>
            <td><?php echo $row['username'];?></td>
            <td><?php echo $row['email'];?></td>
            <td><?php echo $row['country'];?></td>
            <td><?php echo $row['ip'];?></td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else:?>    
<?php echo t('no_records_found');?>
<?php endif;?>
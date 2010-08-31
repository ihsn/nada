<table style="width:100%;">
<tr>
	<td><h1><?php echo t('public_requests');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>

<?php if ($rows):?>
    <table class="report-table" style="width:100%;">
    	<tr>
            <th><?php echo t('username');?></th>
            <th width="150px"><?php echo t('organization');?></th>
            <th><?php echo t('country');?></th>                       	
            <th><?php echo t('dated');?></th>
            <th><?php echo t('intended_use');?></th>
        </tr>
    <?php $prev_study='';?>    
    <?php foreach($rows as $row):?>
        	<?php if ($prev_study!=$row['survey_title']):?>
            	<?php $prev_study=$row['survey_title'];?>
                <tr style="background-color:#F2F2F2">
	            <td colspan="8"><b><?php echo $row['survey_title'];?></b></td>
                </tr>
            <?php endif;?>
        <tr valign="top">
            <td style="padding-left:20px;"><?php echo $row['username'];?></td>
            <td><?php echo $row['company'];?></td>
            <td><?php echo $row['country'];?></td>
            <td><?php echo date("m/d/y",$row['posted']);?></td>
            <td style="width:100%;">
				<?php echo substr($row['abstract'],0,250);?> 
                <a target="_blank" href="<?php echo site_url();?>/admin/public_requests/<?php echo $row['id'];?>"><?php echo t('details');?></a>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else:?>    
<?php echo t('no_records_found');?>
<?php endif;?>
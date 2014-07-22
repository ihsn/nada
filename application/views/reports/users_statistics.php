<table style="width:100%;">
<tr>
	<td><h1><?php echo t('users_statistics');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>

<?php if ($rows):?>
    <table class="report-table" style="width:100%;">
    	<tr>
            <th><?php echo t('username'); ?></th>
            <th><?php echo t('email'); ?></th>
            <th><?php echo t('status'); ?></th>
            <th><?php echo t('organization'); ?></th>
            <th><?php echo t('country'); ?></th>
            <th><?php echo t('phone'); ?></th>            
            <th><?php echo t('date_joined'); ?></th>
            <th><?php echo t('last_online'); ?></th>
        </tr>
    <?php foreach($rows as $row):?>
        <tr>
            <td><?php echo $row['username'];?></td>
            <td><?php echo $row['email'];?></td>
			<td><?php echo ((int)$row['active'])==1 ? t('ACTIVE') : t('DISABLED'); ?></td>
            <td><?php echo $row['company'];?></td>
            <td><?php echo $row['country'];?></td>
            <td><?php echo $row['phone'];?></td>
            <td><?php echo date("m-d-Y",$row['created_on']);?></td>
			<?php if ($row['last_login']>$row['created_on']): ?>
				<td><?php echo date("m-d-Y",$row['last_login']); ?></td>
            <?php else: ?>                    
	            <td>-</td>
            <?php endif; ?>
        </tr>
    <?php endforeach;?>
    </table>
<?php else:?>    
<?php echo t('no_records_found');?>
<?php endif;?>
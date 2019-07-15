<style>
.red{background-color:red;}
</style>
<table style="width:100%;">
<tr>
	<td><h1><?php echo t('study_statistics');?></h1></td>
    <td style="text-align:right;"><?php $this->load->view('reports/download_options'); ?></td>
</tr>
</table>
<?php 
	if ($this->input->get("format")=='excel')
	{
		$img_no='NO';
	}
	else
	{
		$img_no='&#10006;';
		$img_yes='&#10003;';
	}	
?>
<?php if ($rows):?>
    <table class="report-table" style="width:100%;">
    	<tr>
            <th><?php echo t('title');?></th>
            <th><?php echo t('updated');?></th>
            <th><?php echo t('variables');?></th>
            <th><?php echo t('access_type');?></th>
            <th><?php echo t('year');?></th>
            <th><?php echo t('data_files');?></th>
            <th><?php echo t('citations');?></th>
            <th><?php echo t('reports');?></th>
            <th><?php echo t('questionnaires');?></th>
        </tr>
        <?php 
			$variable_count=0;
			$datafiles_count=0;	
			$citations_count=0;
		?>
    <?php foreach($rows as $row):?>
    	<?php $variable_count+=$row['varcount']; ?>
        <tr>
            <td style="background-color:#F5F5F5"><?php echo $row['title'];?></td>
            <td><?php echo date("m/d/y",$row['changed']);?></td>
            <td><?php echo $row['varcount'];?></td>            
            <td><?php echo ($row['form_type'])=='' ? $img_no : $row['form_type'];?></td>
            <td><?php echo (integer)($row['year_start']) >0 ? $row['year_start']: $img_no;?></td>
            <?php if (isset($data[$row['id']])): ?>
	            <td><?php echo $data[$row['id']];?></td>
            <?php else:?>
                <td><?php echo $img_no;?></td>
            <?php endif;?>
            <td><?php echo (isset($citations[$row['id']])) ?  $citations[$row['id']] : $img_no; ?></td>
            <td><?php echo (isset($reports[$row['id']])) ?  $reports[$row['id']] : $img_no; ?></td>
            <td><?php echo (isset($questionnaires[$row['id']])) ?  $questionnaires[$row['id']] : $img_no; ?></td>
        </tr>
    <?php endforeach;?>
    	<tr style="background-color:#EEEEEE">
        	<td>Total studies: <?php echo count($rows);?></td>
            <td></td>
            <td><?php echo $variable_count;?></td>
            <td></td>
            <td></td>
            <td><?php echo array_sum($data);?></td>
            <td><?php echo array_sum($citations);?></td>
            <td><?php echo array_sum($reports);?></td>
            <td><?php echo array_sum($questionnaires);?></td>
        </tr>
    </table>
<?php else:?>    
<?php echo t('no_records_found');?>
<?php endif;?>
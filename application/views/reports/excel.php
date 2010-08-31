<?php
//	echo survey_details($rows);
//return;
//echo '<pre>';
//var_dump($rows);
//exit;
?>

<?php if ($rows):?>
    <table class="report-table" style="width:100%;">
	    <tr>
		<?php foreach($rows[0] as $key=>$value):?>
        	<th><?php echo $key;?></th>
	    <?php endforeach;?>	
        </tr>
        
		<?php foreach($rows as $row):?>
            <tr>
				<?php foreach($row as $key=>$value):?>
                    <td><?php echo $value;?></td>
                <?php endforeach;?>	
            </tr>
        <?php endforeach;?>
    </table>
<?php else:?>    
No results found
<?php endif;?>
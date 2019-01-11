<?php
/*
* A list of surveys attached to a citation
*
*/
?>
<?php if ($selected_surveys): ?>
<table class="table table-stripped grid-table custom-short-font" cellpadding="0" cellspacing="0" id="related-surveys-table" style="background:white;">
<thead>
	<tr class="header">        
	    <th>&nbsp;</th>
		<th><?php echo t('title');?> <span>&nbsp;</span></th>
		<th><?php echo t('country');?> <span>&nbsp;</span></th>
        <th><?php echo t('year');?> <span>&nbsp;</span></th>
	</tr>
</thead>
<tbody>    
<?php foreach ($selected_surveys as $survey):?>
	<tr align="left">
    	<td><input class="chk chk-sid" type="checkbox" name="sid[]" value="<?php echo $survey['id'];?>" checked="checked" /></td>
		<td><?php echo $survey['title'];?></td>
		<td><?php echo $survey['nation'];?></td>
        <td><?php 
				$years=array_unique(array($survey['year_start'],$survey['year_end']));
				echo implode(" - ",$years);
		 	?>
        </td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>    

<?php else:?>
No records found.
<?php endif;?>

<?php
/*
* A list of surveys attached to a citation
*
*/
?>
<?php if ($selected_surveys): ?>
<script type="text/javascript">
	$(function() {
		$('#related-surveys .chk').live('click', function() {
		$(this).parent().parent().remove();
		});
	});
</script>
<table class="grid-table custom-short-font" cellpadding="0" cellspacing="0" id="related-surveys-table" style="background:white;">
<thead>
	<tr class="header">        
	    <th>&nbsp;</th>
		<th>Title <span>&nbsp;</span></th>
		<th>Country <span>&nbsp;</span></th>
	</tr>
</thead>
<tbody>    
<?php foreach ($selected_surveys as $survey):?>
	<tr align="left">
    	<td><input class="chk" type="checkbox" name="sid[]" value="<?php echo $survey['id'];?>" checked="checked" /></td>
		<td><?php echo $survey['titl'];?></td>
		<td><?php echo $survey['nation'];?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>    

<?php else:?>
No records found.
<?php endif;?>
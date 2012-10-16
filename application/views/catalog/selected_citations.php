<?php
/*
* A list of surveys attached to a citation
*
*/
?>
<style>
.custom-short-font{font-size:11px;}
</style>

<?php if ($selected_citations): ?>

<script type="text/javascript">
	$(function() {
		$('#related-citations .chk').live('click', function() {
		$(this).parent().parent().remove();
		});
	});
</script>


<table class="grid-table custom-short-font" cellpadding="0" cellspacing="0" id="related-surveys-table">
<thead>
	<tr class="header">        
	    <th style="width:40px">&nbsp;</th>
		<th>Title <span>&nbsp;</span></th>
	</tr>
</thead>
<tbody>    
<?php foreach ($selected_citations as $citation):?>
	<tr align="left">
    	<td><input class="chk" type="checkbox" name="sid[]" value="<?php echo $citation['id'];?>" checked="checked" /></td>
		<td><?php echo $citation['title'];?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>    

<?php else:?>
No records found.
<?php endif;?>
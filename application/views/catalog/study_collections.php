<div class="study-attached-collections">
<table>
<?php foreach($collections as $coll):?>	
	<?php 
		$checked='';
		if (in_array($coll['repositoryid'],$selected))
		{
			$checked='checked="checked"';
		}
	?>
	<tr valign="top">
    <td><input class="chk" type="checkbox" id="t-<?php echo $coll['repositoryid'];?>" value="<?php echo $coll['repositoryid'];?>" <?php echo $checked;?>/></td>
	<td><label for="t-<?php echo $coll['repositoryid'];?>"><?php echo $coll['title'];?></label></td>
	</tr>
<?php endforeach;?>
</table>
</div>
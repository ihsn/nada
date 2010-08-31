<table border="0" width="100%" id="project-list">
	<tr class="table-th" align="left">
        <th><input type="checkbox" class="chk-prj-hd" /></th>    
    	<th>ID</th>
        <th>Title</th>
        <th>Country</th>
    </tr>
<?php foreach($rows as $row):?>	
	<tr valign="top" align="left">
    	<td><input type="checkbox" class="chk-prj" name="pid[]" value="<?php echo $row['projectid']; ?>"/></td>
    	<td><?php echo $row['projectid']; ?></td>
        <td><?php echo $row['projectname']; ?></td>
        <td><?php echo $row['country']; ?></td>
    </tr>
<?php endforeach;?>
</table>

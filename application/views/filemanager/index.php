
<h1 class="page-title">Directory:</h1>
<table border="0" width="100%">
<tr>
	<td>Up | Home | Upload | Delete</td>
    <td align="right">
    	<select name="fd">
        	<option value="file">File</option>
            <option value="dir">Directory</option>
        </select>
        <input type="text"/><input type="submit" name="submit" value="Create"/>
    </td>
</tr>
</table>
<table width="100%">
	<tr class="th">
    	<td><input type="checkbox"/></td>
        <td>Name</td>
        <td>Perm's</td>
        <td>Size</td>
    </tr>
<?php foreach($files as $file): ?>
	<tr>
	    <td><input type="checkbox"/></td>
    	<td><?php echo anchor('admin/filemanager/?folder='.$file['name'],$file['name']); ?></td>
       	<td><?php echo $file['perms']; ?></td>
    	<td><?php echo $file['size']; ?></td>
    </tr>
<?php endforeach;?>
</table>

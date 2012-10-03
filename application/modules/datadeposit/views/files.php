<h2 style="margin-bottom:15px">Files in project folder</h2>
<?php if ($folder !== false) $folder = array_diff($folder, array('.', '..')); ?>
<?php if ($folder !== false && !empty($folder)): ?>
<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">

		<tr valign="top" align="left" style="height:5px" class="header">


						<th >File Name</th>
       </tr>
       <?php foreach($folder as $file): ?>
       <tr>
	   <td><?php echo $file; ?></td>
       </tr>
       <?php endforeach; ?>
</table>
<?php else: ?>
<p><?php echo t('no_files'); ?></p>
<?php endif; ?>
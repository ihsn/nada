<?php
	//other settings
	echo '<h1>'.t('other_php_settings').'</h1>';
	echo '<table cellpadding="3" cellspacing="0" class="grid-table">';
	echo '<tr class="header">';
	echo '<th>'.t('setting').'</th>';
	echo '<th>'.t('value').'</th>';
	echo '<th>'.t('recommended').'</th>';
	echo '</tr>';
	
	echo '<tr>';
	echo '<td>file_uploads</td>';
	echo '<td>';
	echo ini_get('file_uploads')==1 ? t('enabled') : t('disabled');
	echo '</td>';
	echo '<td>'.t('enabled').'</td>';
	echo '</tr>';
	
	echo '<tr>';
	echo '<td>post_max_size</td>';
	echo '<td>';
	echo ini_get('post_max_size');
	echo '</td>';
	echo '<td>20M</td>';
	echo '</tr>';
	
	echo '<tr>';
	echo '<td>upload_max_filesize</td>';
	echo '<td>';
	echo ini_get('upload_max_filesize');
	echo '</td>';
	echo '<td>15M</td>';
	echo '</tr>';
	echo '</table>';
?>
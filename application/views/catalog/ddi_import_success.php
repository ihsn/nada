<h3><?php echo t('ddi_import_success');?></h3>
<table style="color:#666666">
<tr>
	<td style="width:100px"><?php echo t('survey_id');?></td>
	<td><?php echo $info['id']; ?></td>
</tr>
<tr>
	<td><?php echo t('title');?></td>
	<td><?php echo $info['title']; ?></td>
</tr>
<tr>
	<td><?php echo t('collection_date');?></td>
    <td>
    	<?php if ( !is_numeric($info['year_start'])  || !is_numeric($info['year_end']) ):?>
			<span style="color:red"><?php echo t('missing');?></span>
    	<?php else:?>
			<?php echo $info['year_start']; ?> - <?php echo $info['year_end']; ?>
		<?php endif;?>        
    </td>
</tr>
</table>

<div style="padding:10px;">
	<a href="<?php echo site_url(); ?>/admin/catalog" title="<?php echo t('catalog_home');?>"><?php echo t('click_to_return_to_catalog_page');?></a> 
</div>
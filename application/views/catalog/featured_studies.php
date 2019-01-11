<h1 class="page-title">
	<?php echo t('featured_studies');?>
</h1>


<?php if (!$featured_studies): ?>
	<?php echo t('no_records_found');return;?>
<?php endif;?>

<table class="grid-table" >
<?php foreach($featured_studies as $study):?>
	<tr>
    	<td><?php echo $study['repositoryid'];?></td>
        <td><a href="<?php echo site_url('admin/catalog/edit/'.$study['id']);?>"><?php echo $study['title'];?></a></td>        
        <td><?php echo $study['nation'];?></td>
        <td><?php echo $study['year_start'];?></td>
        <td><a href="<?php echo site_url('admin/catalog/set_featured_study/'.$study['repositoryid'].'/'.$study['id'].'/0');?>?destination=<?php echo site_url('admin/catalog/featured_studies');?>"><?php echo t('remove');?></a></td>
    </tr>
<?php endforeach;?>
</table>
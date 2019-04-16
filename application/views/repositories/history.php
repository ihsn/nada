<div class="body-container" style="padding:10px;">

<?php include 'page_links.php'; ?>

<h1 class="page-title"><?php echo t('collection_history');?></h1>

<?php if ($rows): ?>
	<div><?php echo t('total_studies_in_collection');?>: <?php echo count($rows);?></div>
    <!-- grid -->
    <table class="grid-table table table-striped" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th>#</th>
            <th><?php echo t('study_title');?></th>
			<th><?php echo t('country');?></th>
            <th><?php echo t('year');?></th>
            <th><?php echo t('created');?></th>
            <th><?php echo t('changed');?></th>
        </tr>
		
	<?php $tr_class=""; ?>
	<?php $k=0;foreach($rows as $row):$k++; ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="study-row <?php echo $tr_class; ?>">
            <td><?php echo $k;?></td>
            <td><a href="<?php echo site_url('admin/catalog/edit/'.$row->id);?>"><?php echo $row->title;?></a></td>
            <td><?php echo $row->nation; ?></td>
            <td>
				<?php 
					$years=array($row->year_start,$row->year_end);
					$years=array_unique($years);
					echo implode(" - ",$years);
				?>
            </td>
            <td><?php echo date("m/d/Y",$row->created);?></td>
            <td><?php echo date("m/d/Y",$row->changed);?></td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>
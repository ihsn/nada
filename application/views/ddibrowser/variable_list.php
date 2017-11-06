<div id="variable-list" >
<?php if ($variables): ?>
<h4><?php echo sprintf(t('variable_search_match_found'),count($variables));?></h4>
<?php $tr_class="row-color1"; ?>
	<table class="table-variable-list" cellpadding="4" cellspacing="0" width="100%" border="1" style="border-collapse:collapse">
    	<tr class="var-th">
        	<td><?php echo t('name');?></td>
            <td><?php echo t('label');?></td>
            <td><?php echo t('question');?></td>
        </tr>
	<?php foreach($variables as $row):?>
  		<?php if($tr_class=="row-color1") {$tr_class="row-color2";} else{ $tr_class="row-color1"; } ?>
    	<tr  class="<?php echo $tr_class; ?>" id="<?php echo $row['vid'];?>">
            <td><?php echo anchor('ddibrowser/'.$this->uri->segment(2).'/variable/'.$row['vid'],$row['name'], array('class'=>'xajax'));?></td>
            <td><?php echo $row['labl']?> </td>
            <td><?php echo substr($row['qstn'],0,100); if (strlen($row['qstn'])>100){echo '...';}?> </td>
        </tr>
          <tr class="var-info-panel" style="display:none;">
        <td colspan="3" id="pnl-<?php echo $row['vid'];?>"></td>
    </tr>
    <?php endforeach;?>
	</table>
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
</div>
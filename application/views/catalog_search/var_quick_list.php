<?php 
if ($variables): ?>
<?php $compare_items=explode(",",$this->input->cookie('variable-compare', TRUE));?>
<?php $surveyid=$this->uri->segment(3);?>
<?php $tr_class=""; ?>
<div class="var-quick-list<?php echo count($variables)>10 ? '-scroll' : '';?>">
	<table class="grid-table variable-list" cellpadding="0" cellspacing="0" width="100%">
    	<tr class="header">
        	<td><?php echo anchor('catalog/compare',t('compare'), array('class'=>'btn-compare-var','title'=>t('compare_selected_variables'),'target'=>'_blank'));?></td>
            <td><?php echo t('name');?></td>
            <td><?php echo t('label');?></td>
        </tr>
	<?php foreach($variables as $row):?>
  		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <?php 
			$compare='';	
			//compare items selected
			if (in_array($surveyid.'/'.$row['varID'],$compare_items) )
			{  
				$compare=' checked="checked" ';
			}
		?>        
    	<tr  class="vrow <?php echo $tr_class; ?>" data-url="<?php echo site_url('catalog/'.$surveyid.'/variable/'.$row['varID']); ?>" data-url-target="_blank" data-title="<?php echo $row['labl'];?>">
	        <td style="color:gray;" title="<?php echo t('mark_for_variable_comparison');?>">
            		<input type="checkbox" class="compare" value="<?php echo $surveyid.'/'.$row['varID'] ?>" <?php echo $compare; ?>/>
            </td>
            <td><?php echo anchor('catalog/'.$surveyid.'/variable/'.$row['varID'],$row['name'],array('target'=>'blank_','title'=>$row['labl'],'class'=>'link'));?></td>
            <td><?php echo $row['labl']?></td>
        </tr>
    <?php endforeach;?>
	</table>
</div>    
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
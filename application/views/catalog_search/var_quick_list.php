<?php 
if ($variables): ?>
<?php $compare_items=$this->session->userdata('compare');?>
<?php $surveyid=$this->uri->segment(3);?>
<?php $tr_class=""; ?>
	<table class="grid-table" cellpadding="0" cellspacing="0" width="100%">
    	<tr class="header">
        	<td><?php echo anchor('catalog/compare',t('compare'), array('class'=>'dlg','title'=>t('compare_selected_variables')));?></td>
            <td><?php echo t('name');?></td>
            <td><?php echo t('label');?></td>
            <td>&nbsp;</td>
        </tr>	
	<?php foreach($variables as $row):?>
  		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <?php 
			$compare='';	
			//compare items selected
			if (isset($compare_items[$surveyid.':'.$row['varID']]) )
			{  
				$compare=' checked="checked" ';
			} 
		?>        
    	<tr  class="<?php echo $tr_class; ?>">
	        <td style="color:gray;" title="<?php echo t('mark_for_variable_comparison');?>">
            		<input type="checkbox" class="compare" value="<?php echo $surveyid.'/'.$row['varID'] ?>" <?php echo $compare; ?>/>
            </td>
            <td><?php echo anchor('catalog/'.$surveyid.'/variable/'.$row['varID'],$row['name'],array('target'=>'blank_','title'=>$row['labl']));?></td>
            <td><?php echo $row['labl']?></td>
            <td><?php echo anchor('catalog/'.$surveyid.'/variable/'.$row['varID'],'<img src="images/icon_question.gif" border="0"/>',array('target'=>'blank_','title'=>$row['labl']));?></td>
        </tr>
    <?php endforeach;?>
	</table>
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
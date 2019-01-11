<?php 
if ($variables): ?>
<?php $compare_items=explode(",",$this->input->cookie('variable-compare', TRUE));?>
<?php $surveyid=$this->uri->segment(3);?>
<div class="list-inline var-quick-list var-quick-list<?php echo count($variables)>10 ? '-scroll' : '';?>"">
    <table class="table table-striped table-hover grid-table variable-list">
        <thead>
            <tr>
                <th><?php echo anchor('catalog/compare',t('compare'), array('class'=>'btn-compare-var','title'=>t('compare_selected_variables'),'target'=>'_blank'));?></th>
                <th><?php echo t('name');?></th>
                <th><?php echo t('label');?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($variables as $row):?>
                <?php
                $compare='';
                //compare items selected
                if (in_array($surveyid.'/'.$row['vid'],$compare_items) )
                {
                    $compare=' checked="checked" ';
                }
                ?>
                <tr  class="vrow" data-url="<?php echo site_url('catalog/'.$surveyid.'/variable/'.$row['vid']); ?>" data-url-target="_blank" data-title="<?php echo $row['labl'];?>">
                    <td title="<?php echo t('mark_for_variable_comparison');?>">
                        <input type="checkbox" class="nada-form-check-input compare" value="<?php echo $surveyid.'/'
                            .$row['vid'] ?>" <?php echo $compare; ?>/>
                    </td>
                    <td><?php echo anchor('catalog/'.$surveyid.'/variable/'.$row['vid'],$row['name'],array('target'=>'blank_','title'=>$row['labl'],'class'=>'link'));?></td>
                    <td><?php echo $row['labl']?></td>
                </tr>
            <?php endforeach;?>
        </tbody>
    </table>
</div>    
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>

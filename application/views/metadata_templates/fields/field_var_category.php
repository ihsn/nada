<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">

            <?php
            $show_stats=false;
            $stats_col=array();
            //TODO - categry level statistics
            
            foreach($data as $item){
                //ignore non-numeric value
                if(is_numeric($item['value'])){
                    $stats_col[]=$item['stats'];
                }
            }
            if (count($stats_col)>0){
                $show_stats=true;
                $sum_cases=array_sum($stats_col);
                $cat_count=count($stats_col);
                $last_cat=$data[$cat_count-1];
                $max_value=max($stats_col);



                //remove the sysmiss from sum
                if (trim(strtolower($last_cat['value']))=='sysmiss'){
                    $sum_cases=(int)$sum_cases - (int)$last_cat['stats'];
                    //$max_value=max($stats_col);
                }
            }
            ?>

            <table class="table table-stripped xsl-table">
                <tr>
                    <th><?php echo t('value');?></th>
                    <th><?php echo t('category');?></th>
                    <?php if($show_stats):?>
                        <th><?php echo t('cases');?></th>                    
                    <th></th>
                    <?php endif;?>
                </tr>
                <?php foreach($data as $cat):?>
                    <?php
                    $cat=(object)$cat;

                    if($show_stats){
                        $percent=@round($cat->stats/$sum_cases * 100,1);
                        $width=@round($cat->stats/$max_value * 100,1);
                    }
                    //$width=round(100*($cat->stats / $max_value));
                    //round($maxLength*($value div max($nodes)))
                    ?>
                    <tr>
                        <td><?php echo isset($cat->value) ? $cat->value : '';?></td>
                        <td><?php echo isset($cat->label) ? $cat->label : '';?></td>
                        <?php if($show_stats):?>
                        <td><?php echo (int)$cat->stats;?></td>                        
                        <td class="bar-container">
                            <?php if(is_numeric($cat->value)):?>
                                <div class="bar" style="margin-right:5px; float:left;background:#000;height:13px;width:<?php echo 1*$width;?>px;"></div><?php echo $percent;?>%
                            <?php endif;?>
                        </td>
                        <?php endif;?>
                    </tr>
                <?php endforeach;?>
            </table>
            <div class="xsl-warning"><?php echo t('warning_figures_indicate_number_of_cases_found');?></div>
    </div>
</div>
<?php endif;?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="table-responsive field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">

            <?php
            $show_stats=false;
            $stats_col=array();
            $stats_col_wgtd=array();

            $show_stats=true;
            $sum_cases=0;
            $sum_cases_wgtd=0;
            $cat_count=0;
            $last_cat=0;
            $max_value=0;
            $max_value_wgtd=0;

            foreach($data as $data_idx=>$item){
                
                //create wgtd and non-wgtd stats values
                $data[$data_idx]['stats_wgtd_value']=null;
                $data[$data_idx]['stats_non_wgtd_value']=null;

                if(!is_array($item['stats'])){
                    continue;
                }

                foreach($item['stats'] as $stat_row){
                    //non-weighted stats
                    if(!$stat_row['wgtd']){
                        $data[$data_idx]['stats_non_wgtd_value']=$stat_row['value'];
                        if(!$item['is_missing']){
                            $stats_col[]=$stat_row['value'];
                        }
                    }//weighted stats
                    else if ($stat_row['wgtd']=='wgtd'){
                        $data[$data_idx]['stats_wgtd_value']=$stat_row['value'];
                        if(!$item['is_missing']){
                            $stats_col_wgtd[]=$stat_row['value'];
                        }    
                    }
                }
            }
            if (count($stats_col)>0){
                $show_stats=true;
                $sum_cases=array_sum($stats_col);
                
                $cat_count=count($stats_col);
                $last_cat=$data[$cat_count-1];
                $max_value=max($stats_col);
                
            }
            if(count($stats_col_wgtd)>0){
                $max_value_wgtd=max($stats_col_wgtd);
                $sum_cases_wgtd=array_sum($stats_col_wgtd);
            }

            ?>

            <table class="table table-stripped xsl-table">
                <tr>
                    <th><?php echo t('value');?></th>
                    <th><?php echo t('category');?></th>
                    <?php if($show_stats && $sum_cases>0):?>
                        <th><?php echo t('cases');?></th>                    
                    <?php endif;?>
                    <?php if($show_stats && $sum_cases_wgtd>0):?>
                        <th><?php echo t('weighted');?></th>                                            
                    <?php endif;?>
                    <?php if($show_stats && ($sum_cases>0) ):?>
                        <th></th>
                    <?php endif;?>
                </tr>
                <?php foreach($data as $cat):?>
                    <?php
                    $cat=(object)$cat;

                    if($show_stats && $sum_cases>0){
                        $percent=@round($cat->stats_non_wgtd_value/$sum_cases * 100,1);
                        $width=@round($cat->stats_non_wgtd_value/$max_value * 100,1);

                        $percent_wgtd=@round($cat->stats_wgtd_value/$sum_cases_wgtd * 100,1);
                        $width_wgtd=@round($cat->stats_wgtd_value/$max_value_wgtd * 100,1);
                    }
                    ?>

                    <tr>
                        <td><?php echo isset($cat->value) ? $cat->value : '';?></td>
                        <td><?php echo isset($cat->labl) ? $cat->labl : '';?></td>
                        
                        <?php if($show_stats && $sum_cases>0):?>
                        <td><?php echo (int)$cat->stats_non_wgtd_value;?></td>
                        <?php endif;?>    
                        
                        <!--weighted-->
                        <?php if($show_stats && $sum_cases_wgtd>0 ):?>
                            <td><?php echo round($cat->stats_wgtd_value);?></td>
                            <?php if (empty($cat->is_missing)):?>
                            <td class="bar-container">
                                <?php if(is_numeric($cat->stats_wgtd_value)):?>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percent_wgtd;?>%;" aria-valuenow="<?php echo $percent_wgtd;?>;" aria-valuemin="0" aria-valuemax="100"></div>
                                    <span class="progress-text"><?php echo $percent_wgtd;?>%</span>
                                </div>
                                <?php endif;?>
                            </td>
                            <?php else:?>
                            <td></td>
                            <?php endif?>

                        <?php endif;?>

                        <!--non-weighted-->
                        <?php if($show_stats && $sum_cases>0 && (int)$sum_cases_wgtd<1):?>
                            <?php if(empty($cat->is_missing)):?>
                            <td class="bar-container">
                                <?php if(is_numeric($cat->stats_non_wgtd_value)):?>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $width;?>%;" aria-valuenow="<?php echo $width;?>;" aria-valuemin="0" aria-valuemax="100"></div>
                                    <span class="progress-text"><?php echo $percent;?>%</span>
                                </div>
                                <?php endif;?>
                            </td>
                            <?php else:?>
                            <td></td>
                            <?php endif;?>
                        <?php endif;?>    
                    </tr>
                <?php endforeach;?>
            </table>
            <div class="xsl-warning"><?php echo t('warning_figures_indicate_number_of_cases_found');?></div>
    </div>
</div>
<?php endif;?>
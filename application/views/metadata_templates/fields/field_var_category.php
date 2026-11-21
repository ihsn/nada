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

                if(!isset($item['stats']) || !is_array($item['stats'])){
                    continue;
                }

                foreach($item['stats'] as $stat_row){
                    if (!array_key_exists('value', $stat_row) || $stat_row['value'] === '' || $stat_row['value'] === null) {
                        continue;
                    }

                    // Consider missing only when explicitly set to something truthy (e.g. "1", "Y", "true"); "0" and empty mean not missing
                    $ismissing_ = isset($item['is_missing']) ? $item['is_missing'] : '';
                    $is_missing_val = ($ismissing_ !== '' && $ismissing_ !== '0' && $ismissing_ !== 0);

                    $stat_value = $stat_row['value'];
                    $stat_is_numeric = is_numeric($stat_value);

                    //non-weighted stats
                    $wgtd_=isset($stat_row['wgtd']) ? $stat_row['wgtd'] : '';
                    if($wgtd_!=='wgtd'){
                        if ($stat_is_numeric) {
                            $data[$data_idx]['stats_non_wgtd_value'] = $stat_value + 0;
                        }
                        if(!$is_missing_val && $stat_is_numeric){
                            $stats_col[] = $stat_value + 0;
                        }
                    }//weighted stats
                    else if ($stat_row['wgtd']==='wgtd'){
                        if ($stat_is_numeric) {
                            $data[$data_idx]['stats_wgtd_value'] = $stat_value + 0;
                        }
                        if(!$is_missing_val && $stat_is_numeric){
                            $stats_col_wgtd[] = $stat_value + 0;
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

            <?php $cat_scroll=!empty($options['scroll']) && count($data)>15; ?>
            <div class="var-cat<?php echo $cat_scroll ? ' var-cat-scroll' : '';?>">
            <?php
            $has_cases=$show_stats && $sum_cases>0;
            $has_wgtd=$show_stats && $sum_cases_wgtd>0;
            $has_bar=$has_cases || $has_wgtd;
            //percent label; tiny non-zero shares are shown as <0.1%
            $fmt_pct=function($count,$total){
                if (!is_numeric($count) || $total<=0){
                    return '';
                }
                $pct=$count/$total*100;
                if ($pct>0 && $pct<0.05){
                    return '&lt;0.1%';
                }
                return round($pct,1).'%';
            };
            ?>
            <table class="table table-stripped xsl-table var-cat-table<?php echo $has_bar ? ' var-cat-has-stats' : '';?>">
                <tr class="var-cat-head">
                    <th class="var-cat-value"><?php echo t('value');?></th>
                    <th><?php echo t('category');?></th>
                    <?php if($has_cases):?>
                        <th class="var-cat-num"><?php echo t('cases');?></th>
                    <?php endif;?>
                    <?php if($has_wgtd):?>
                        <th class="var-cat-num"><?php echo t('weighted');?></th>
                    <?php endif;?>
                    <?php if($has_bar):?>
                        <th class="var-cat-pct">%</th>
                    <?php endif;?>
                </tr>
                <?php foreach($data as $cat):?>
                    <?php
                    $cat=(object)$cat;
                    //bar and percent follow the weighted figures when present
                    $bar_count=$has_wgtd ? $cat->stats_wgtd_value : $cat->stats_non_wgtd_value;
                    $bar_total=$has_wgtd ? $sum_cases_wgtd : $sum_cases;
                    $bar_max=$has_wgtd ? $max_value_wgtd : $max_value;
                    $bar_width=(is_numeric($bar_count) && $bar_max>0) ? round($bar_count/$bar_max*100,1) : 0;
                    ?>

                    <tr>
                        <td class="var-cat-value"><?php echo isset($cat->value) ? $cat->value : '';?></td>
                        <td><?php echo isset($cat->labl) ? $cat->labl : '';?> <?php echo isset($cat->label) ? $cat->label : '';?></td>

                        <?php if($has_cases):?>
                        <td class="var-cat-num"><?php echo is_numeric($cat->stats_non_wgtd_value) ? (int)$cat->stats_non_wgtd_value : '';?></td>
                        <?php endif;?>

                        <?php if($has_wgtd):?>
                        <td class="var-cat-num"><?php echo is_numeric($cat->stats_wgtd_value) ? round($cat->stats_wgtd_value) : '';?></td>
                        <?php endif;?>

                        <?php if($has_bar):?>
                        <td class="var-cat-pct bar-container">
                            <?php if(empty($cat->is_missing) && is_numeric($bar_count)):?>
                            <div class="var-cat-pct-wrap">
                                <div class="var-cat-bar" aria-hidden="true"><span style="width: <?php echo $bar_width;?>%;"></span></div>
                                <span class="var-cat-pct-text"><?php echo $fmt_pct($bar_count,$bar_total);?></span>
                            </div>
                            <?php endif;?>
                        </td>
                        <?php endif;?>
                    </tr>
                <?php endforeach;?>
            </table>
            </div>
            <?php if ($has_bar):?>
            <div class="xsl-warning var-cat-note"><i class="fas fa-info-circle" aria-hidden="true"></i> <?php echo t('warning_figures_indicate_number_of_cases_found');?></div>
            <?php endif;?>
    </div>
</div>
<?php endif;?>
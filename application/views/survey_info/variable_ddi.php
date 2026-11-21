<?php
/**
 * Variable detail (DDI). Used for the full variable page and, with $in_panel,
 * for the expandable panel in the data file variable list.
 */
$in_panel = !empty($in_panel);
$var_meta = (isset($variable['metadata']) && is_array($variable['metadata'])) ? $variable['metadata'] : array();

//overview items: array(css class, label, value html)
$overview = array();

//descriptive fields first (type, format, decimals), then statistics, then file position
$add_meta_item=function($key, $skip_zero=false) use (&$overview, $var_meta){
    if (!array_key_exists($key,$var_meta) || $var_meta[$key]===null || $var_meta[$key]===''){
        return;
    }
    if ($skip_zero && is_numeric($var_meta[$key]) && (float)$var_meta[$key]==0){
        return;
    }
    $overview[]=array('sum-stat-'.$key, t($key), html_escape(t($var_meta[$key])));
};

$add_meta_item('var_intrvl');
if (isset($var_meta['var_format']['type']) && $var_meta['var_format']['type']!==''){
    $overview[]=array('var-format', t('var_format'), html_escape(t($var_meta['var_format']['type'])));
}

$add_meta_item('var_dcml');

if (!empty($var_meta['var_sumstat']) && is_array($var_meta['var_sumstat'])){
    foreach($var_meta['var_sumstat'] as $sumstat){
        $sumstat=(object)$sumstat;
        if (!isset($sumstat->type)){
            continue;
        }
        $wgtd=isset($sumstat->wgtd) && $sumstat->wgtd=='wgtd' ? '_wgtd' : '';
        $value='-';
        if (isset($sumstat->value) && $sumstat->value!==''){
            $raw=trim((string)$sumstat->value);
            if (is_numeric($raw) && preg_match('/[.eE]/',$raw)){
                //display: at most 2 decimals (more if the variable declares more), trailing zeros dropped; exact value on hover
                $dcml=isset($var_meta['var_dcml']) && is_numeric($var_meta['var_dcml']) ? (int)$var_meta['var_dcml'] : 0;
                $shown=rtrim(rtrim(number_format((float)$raw, max(2,min($dcml,6)), '.', ''),'0'),'.');
                if ($shown==='-0'){
                    $shown='0';
                }
                $value=$shown===$raw ? html_escape($raw) : '<span title="'.html_escape($raw).'">'.html_escape($shown).'</span>';
            }
            else{
                $value=html_escape($raw);
            }
        }
        $overview[]=array('sum-stat-'.html_escape($sumstat->type).'-'.$wgtd, t('var_'.$sumstat->type.$wgtd), $value);
    }
}

if (isset($var_meta['var_val_range']) && is_array($var_meta['var_val_range'])){
    $range=$var_meta['var_val_range'];
    $range_min=isset($range['min']) ? trim((string)$range['min']) : '';
    $range_max=isset($range['max']) ? trim((string)$range['max']) : '';
    if ($range_min!=='' || $range_max!==''){
        $overview[]=array('sum-stat-range', t('var_range'), html_escape($range_min).' - '.html_escape($range_max));
    }
}

//start/end/width of 0 mean no fixed-width position was recorded
foreach(array("loc_start_pos","loc_end_pos","loc_width") as $loc_key){
    $add_meta_item($loc_key, true);
}

$wgt_info = variable_weight_info($var_meta);
if ($wgt_info['is_weight']){
    $overview[]=array('var_is_wgt', t('var_is_wgt'), t('yes'));
}
if ($wgt_info['weight_vid'] !== null){
    $wgt_url=site_url('catalog/'.$file['sid'].'/variable/'.$file['file_id'].'/'.rawurlencode($wgt_info['weight_vid']));
    $overview[]=array('var_wgt', t('var_wgt'), '<a href="'.html_escape($wgt_url).'">'.html_escape($wgt_info['weight_vid']).'</a>');
}

$variable_metadata_display = $var_meta;
if (isset($variable_metadata_display['var_catgry']) && is_array($variable_metadata_display['var_catgry'])) {
    $variable_metadata_display['var_catgry'] = merge_var_catgry_invd_sysmiss_display(
        $variable_metadata_display['var_catgry'],
        isset($variable_metadata_display['var_sumstat']) ? $variable_metadata_display['var_sumstat'] : array()
    );
}

//question fields; categories are shown under this heading only when there is question text
$question_fields=array("var_qstn_preqtxt","var_qstn_qstnlit","var_qstn_postqtxt","var_qstn_ivulnstr","var_qstn_ivuinstr");
$has_question_text=false;
foreach($question_fields as $qf){
    $qv=get_field_value($qf,$variable_metadata_display);
    if (is_array($qv) ? !empty($qv) : trim((string)$qv)!==''){
        $has_question_text=true;
        break;
    }
}
$category_field=array('var_category','options'=>array('scroll'=>true));
?>

<div class="variable-container<?php echo $in_panel ? ' variable-in-panel' : '';?>">
    <?php if (!$in_panel):?>
    <h2><?php echo html_escape($variable['labl']) . ' (' . html_escape($variable['name']) . ')';?></h2>
    <h5 class="var-file"><?php echo t('data_file');?>: <a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/'.$file['file_id']);?>"><?php echo html_escape($file['file_name']);?></a></h5>
    <?php endif;?>

    <?php if (!empty($overview)):?>
    <h3 class="xsl-subtitle"><?php echo t('overview');?></h3>
    <dl class="var-overview">
        <?php foreach($overview as $item):?>
        <div class="fld-inline sum-stat <?php echo $item[0];?>">
            <dt class="fld-name sum-stat-type"><?php echo $item[1];?></dt>
            <dd class="fld-value sum-stat-value"><?php echo $item[2];?></dd>
        </div>
        <?php endforeach;?>
    </dl>
    <?php endif;?>

    <!-- data_collection -->
    <?php if ($has_question_text):?>
        <?php echo render_group('questions_n_instructions',
            $fields=array(
                "var_qstn_preqtxt"=>'text',
                "var_qstn_qstnlit"=> 'text',
                "var_catgry"=>$category_field,
                "var_qstn_postqtxt"=>'text',
                "var_qstn_ivulnstr"=>'text',
                "var_qstn_ivuinstr"=>'text'
            ),
            $variable_metadata_display);
        ?>
    <?php else:?>
        <?php echo render_group('',
            $fields=array("var_catgry"=>$category_field),
            $variable_metadata_display);
        ?>
    <?php endif;?>


    <!-- description -->
    <?php echo render_group('description',
        $fields=array(
            "var_txt"=>'text',
            "var_universe"=>'text',
            "var_resp_unit"=>'text'
        ),
        $variable_metadata_display);
    ?>

    <?php echo render_group('concept',
        $fields=array(
            "var_concept"=>'array'
        ),
        $variable_metadata_display);
    ?>


    <?php echo render_group('imputation_n_derivation',
        $fields=array(
            "var_imputation"=>'text',
            "var_codinstr"=>'text'
        ),
        $variable_metadata_display);
    ?>

    <?php echo render_group('others',
        $fields=array(
            "var_security"=>'text',
            "var_notes"=>'text'
        ),
        $variable_metadata_display);
    ?>

<!--end-container-->
</div>

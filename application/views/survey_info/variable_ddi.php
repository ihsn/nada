<style>
    .fld-inline .fld-name{color:gray;}
    .float-left {width:35%;float:left;}
    .fld-container,.clear{clear:both;}

    .var-breadcrumb{
        list-style:none;
        clear:both;
        margin-bottom:25px;
        color:gray;
    }

    .var-breadcrumb li{display:inline;}
</style>

<?php /* ?>
<div class="var-breadcrumb">
    <ul>
        <li  class="var-breadcrumb-item">
        <a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/');?>"><?php echo t('data_dictionary');?></a>
        </li>
        <li  class="var-breadcrumb-item">/</li>
        <li  class="var-breadcrumb-item"><a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/'.$file['file_id']);?>"><?php echo $file['file_name'];?></a></li>
        <li  class="var-breadcrumb-item">/</li>
        <li  class="var-breadcrumb-item"><?php echo $variable['labl'] . ' ('. $variable['name'].')';?></li>
    </ul>
</div>
<?php */ ?>


<div class="variable-container">
    <h2><?php echo $variable['labl'] . ' ('. $variable['name'].')';?></h2>
    <h5 class="var-file">Data File: <a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/'.$file['file_id']);?>"><?php echo $file['file_name'];?></a></h5>

    <h2 class="xsl-subtitle">Overview</h2>

    <?php if(isset($variable['metadata']['var_sumstat'])):?>
    <div class="float-left">
        <?php foreach($variable['metadata']['var_sumstat'] as $sumstat): $sumstat=(object)$sumstat; ?>
            <div class="fld-inline sum-stat sum-stat-<?php echo $sumstat->type;?>">
                <span class="fld-name sum-stat-type"><?php echo t($sumstat->type);?></span>
                <span class="fld-value sum-stat-value"><?php echo $sumstat->value;?></span>
            </div>
        <?php endforeach;?>
    </div>
    <?php endif;?>
    <!--other stats-->
    <?php
    $stat_keys=array("var_intrvl","var_dcml","var_loc_start_pos","var_loc_end_pos");
    ?>

    <?php foreach($stat_keys as $stat_key):?>
        <?php if (array_key_exists($stat_key,$variable['metadata'])):?>
        <?php $stat=$variable['metadata'][$stat_key];?>
        <div class="fld-inline sum-stat sum-stat-<?php echo $stat_key;?>">
            <span class="fld-name sum-stat-type"><?php echo t($stat_key);?></span>
            <span class="fld-value sum-stat-value"><?php echo $stat;?></span>
        </div>
        <?php endif;?>
    <?php endforeach;?>

    <?php if (isset($variable['metadata']['var_val_range'])):?>
    <div class="fld-inline sum-stat sum-stat-range">
        <span class="fld-name sum-stat-type"><?php echo t('range');?></span>
        <?php foreach($variable['metadata']['var_val_range'] as $range):?>
            <?php  $range=(object)$range;?>
            <span class="fld-value sum-stat-value">
            <?php echo $range->min;?> - <?php echo $range->max;?>
        </span>
        <?php endforeach;?>
        <?php endif;?>
    </div>
    <div class="clear"></div>

    <!--<h2 class="xsl-subtitle">Questions and instructions</h2>
    <div><?php echo render_text('var_qstn_qstnlit', get_field_value('var_qstn_qstnlit',$variable['metadata']));?></div>
    <div><?php echo render_var_category('var_catgry', get_field_value('var_catgry',$variable['metadata']));?></div>
-->

    <!-- data_collection -->
    <?php echo render_group('questions_n_instructions',
        $fields=array(
            "var_qstn_qstnlit"=> 'text',
            "var_catgry"=>'var_category',
            "var_qstn_ivuinstr"=>'text',
            "var_qstn_preqtxt"=>'text',
            "var_qstn_postqtxt"=>'text',
            "var_qstn_ivulnstr"=>'text'
        ),
        $variable['metadata']);
    ?>


    <!-- description -->
    <?php echo render_group('description',
        $fields=array(
            "var_txt"=>'text',
            "var_universe"=>'text',
            "var_resp_unit"=>'text'
        ),
        $variable['metadata']);
    ?>

    <?php echo render_group('concept',
        $fields=array(
            "var_concept"=>'array'
        ),
        $variable['metadata']);
    ?>


    <?php echo render_group('imputation_n_derivation',
        $fields=array(
            "var_imputation"=>'text',
            "var_codeinstr"=>'text'
        ),
        $variable['metadata']);
    ?>

    <?php echo render_group('others',
        $fields=array(
            "var_security"=>'text',
            "var_notes"=>'text'
        ),
        $variable['metadata']);
    ?>

<!--end-container-->
</div>



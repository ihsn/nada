<style>
    .fld-inline .fld-name{color:gray;}
    .fld-container,.clear{clear:both;}

    .var-breadcrumb{
        list-style:none;
        clear:both;
        margin-bottom:25px;
        color:gray;
    }

    .var-breadcrumb li{display:inline;}
    .variables-container .bar-container {min-width:150px;}
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
    <h2><?php echo html_escape($variable['labl']) . ' (' . html_escape($variable['name']) . ')';?></h2>
    <h5 class="var-file"><?php echo t('data_file');?>: <a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/'.$file['file_id']);?>"><?php echo $file['file_name'];?></a></h5>

    <?php /* ?>
    <div class="wb-variables-export">
        <a href="<?php echo site_url('metadata/export_variable/'.$file['sid'].'/'.$variable['vid'].'/csv'); ?>"  class="badge">
            <i class="far fa-file-excel"></i>
		</a> 

		<a href="<?php echo site_url('metadata/export_variable/'.$file['sid'].'/'.$variable['vid'].'/json'); ?>"  class="badge">
            <i class="fas fa-file-csv"></i>
		</a> 
    </div>
    <?php */ ?>
    
    <h3 class="xsl-subtitle"><?php echo t('overview');?></h3>

    <div class="row">
    <?php if(isset($variable['metadata']['var_sumstat'])):?>
        <div class="col-md-6">
            <?php foreach($variable['metadata']['var_sumstat'] as $sumstat): $sumstat=(object)$sumstat; ?>
                <?php $wgtd=isset($sumstat->wgtd) && $sumstat->wgtd=='wgtd' ? '_wgtd' : '';?>
                <div class="fld-inline sum-stat sum-stat-<?php echo html_escape($sumstat->type);?>-<?php echo $wgtd;?>">
                    <span class="fld-name sum-stat-type"><?php echo t('var_'.$sumstat->type. $wgtd);?>: </span>
                    <span class="fld-value sum-stat-value"><?php echo isset($sumstat->value) ? html_escape($sumstat->value) : '-';?></span>
                </div>
            <?php endforeach;?>
        </div>
    <?php endif;?>

    <!--other stats-->
    <?php
    $stat_keys=array("var_intrvl","var_dcml","loc_start_pos","loc_end_pos","loc_width");
    ?>
        
    <div class="col-md-6">
        <?php foreach($stat_keys as $stat_key):?>
            <?php if (array_key_exists($stat_key,$variable['metadata']) && $variable['metadata'][$stat_key]!==null ):?>
            <?php $stat=$variable['metadata'][$stat_key];?>
            <div class="fld-inline sum-stat sum-stat-<?php echo $stat_key;?>">
                <span class="fld-name sum-stat-type"><?php echo t($stat_key);?>: </span>
                <span class="fld-value sum-stat-value"><?php echo t($stat);?></span>
            </div>
            <?php endif;?>
        <?php endforeach;?>

        <?php if (isset($variable['metadata']['var_val_range'])):?>
        <div class="fld-inline sum-stat sum-stat-range">
            <span class="fld-name sum-stat-type"><?php echo t('var_range');?>: </span>
                <?php $range=$variable['metadata']['var_val_range'];?>
                <?php  $range=(object)$range; ?>
                <span class="fld-value sum-stat-value">
                <?php echo html_escape((string)@$range->min);?> - <?php echo html_escape((string)@$range->max);?>
            </span>
        </div>
        <?php endif;?>
        
        <?php if (isset($variable['metadata']['var_format'])):?>
        <div class="fld-inline sum-stat var-format">
            <span class="fld-name var-format-fld"><?php echo t('var_format');?>: </span>
            <?php $format=$variable['metadata']['var_format'];?>
            <?php  $format=(object)$format; ?>
            <span class="fld-value format-value"><?php echo t(@$format->type);?></span>
        </div>
        <?php endif;?>

        <?php if (isset($variable['metadata']['var_is_wgt'])  && $variable['metadata']['var_is_wgt']=='wgt' ):?>
        <div class="fld-inline sum-stat var_is_wgt">
            <span class="fld-name var-fld-var_is_wgt"><?php echo t('var_is_wgt');?>: </span>
            <?php $var_is_wgt=$variable['metadata']['var_is_wgt'];?>
            <span class="fld-value var_is_wgt-value"><?php echo t('yes');?></span>
        </div>
        <?php endif;?>

        <?php if (isset($variable['metadata']['var_wgt'])):?>
        <div class="fld-inline sum-stat var_wgt">
            <span class="fld-name var-fld-var_wgt"><?php echo t('var_wgt');?>: </span>
            <?php $var_wgt=$variable['metadata']['var_wgt'];?>
            <span class="fld-value var_wgt-value"><a href="<?php echo html_escape(site_url('catalog/'.$file['sid'].'/variable/'.$file['file_id'].'/'.$var_wgt));?>"><?php echo html_escape($var_wgt);?></a></span>
        </div>
        <?php endif;?>

    </div>
    </div>

    
    <div class="clear"></div>

    <?php
    $variable_metadata_display = (isset($variable['metadata']) && is_array($variable['metadata']))
        ? $variable['metadata']
        : array();
    if (isset($variable_metadata_display['var_catgry']) && is_array($variable_metadata_display['var_catgry'])) {
        $variable_metadata_display['var_catgry'] = merge_var_catgry_invd_sysmiss_display(
            $variable_metadata_display['var_catgry'],
            isset($variable_metadata_display['var_sumstat']) ? $variable_metadata_display['var_sumstat'] : array()
        );
    }
    ?>

    <!-- data_collection -->
    <?php echo render_group('questions_n_instructions',
        $fields=array(
            "var_qstn_preqtxt"=>'text',
            "var_qstn_qstnlit"=> 'text',
            "var_catgry"=>'var_category',
            "var_qstn_ivuinstr"=>'text',            
            "var_qstn_postqtxt"=>'text',
            "var_qstn_ivulnstr"=>'text'
        ),
        $variable_metadata_display);
    ?>


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



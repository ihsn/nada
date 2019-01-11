<?php
/*
 * DDI template - display metadata for DDI fields
 *
 * @metadata - array containing all metadata
 *
 * @id - survey id
 * @surveyid - IDNO
 * @ all survey table fields
 *
 *
 **/
?>

<?php 
    //rendered html for all sections
    $output=array();
?>

<!-- identification section -->
<?php $output['identification']= render_group('identification',
    $fields=array(
            "codebook_stdydscr_citation_titlstmt_titl"=>'text',
            "codebook_stdydscr_citation_titlstmt_subtitl"=>'text',
            "codebook_stdydscr_citation_titlstmt_alttitl"=>'text',
            "codebook_stdydscr_citation_titlstmt_partitl"=>'text',
            "codebook_stdydscr_stdyinfo_sumdscr_nation"=>'nation',
            "codebook_stdydscr_citation_serstmt_sername"=>'text',
            "codebook_stdydscr_citation_serstmt_serinfo"=>'text',
            "codebook_stdydscr_citation_titlstmt_idno"=>'text',
            "codebook_stdydscr_stdyinfo_abstract"=>'text',
            "codebook_stdydscr_stdyinfo_sumdscr_datakind"=>'text',
            "stdy_anlyunit"=>'text',
            "codebook_stdydscr_citation_diststmt_contact"=>'array'
            ),
    $metadata);
?>


<!-- version -->
<?php $output['version']= render_group('version',
    $fields=array(
            "codebook_stdydscr_citation_verstmt_version"=>'text',
            "codebook_stdydscr_citation_verstmt_version_@date"=>'text',
            "codebook_stdydscr_citation_verstmt_notes"=>'text'
            ),
    $metadata);
?>


<!-- scope -->
<?php $output['scope']= render_group('scope',
    $fields=array(
            "codebook_stdydscr_stdyinfo_notes"=>'text',
            "codebook_stdydscr_stdyinfo_subject_topcclas"=>'array',
            "codebook_stdydscr_stdyinfo_subject_keyword"=>'array'
            ),
    $metadata);
?>


<!-- coverage -->
<?php $output['coverage']= render_group('coverage',
    $fields=array(
            "codebook_stdydscr_stdyinfo_sumdscr_geogcover"=>'text',
            "codebook_stdydscr_stdyinfo_sumdscr_geogunit"=>'text',
            "codebook_stdydscr_stdyinfo_sumdscr_anlyunit"=>'text',
            "codebook_stdydscr_stdyinfo_sumdscr_universe"=>'text'
            ),
    $metadata);
?>


<!-- producers_sponsors -->
<?php $output['producers_sponsors']= render_group('producers_sponsors',
    $fields=array(
            "codebook_stdydscr_citation_rspstmt_authenty"=>'array',
            "codebook_stdydscr_citation_prodstmt_producer"=>'array',
            "codebook_stdydscr_citation_prodstmt_fundag"=>'array',
            "codebook_stdydscr_citation_rspstmt_othid"=>'array'
            ),
    $metadata);
?>


<!-- sampling -->
<?php $output['sampling']= render_group('sampling',
    $fields=array(
            "codebook_stdydscr_method_datacoll_sampproc"=>'text',
            "codebook_stdydscr_method_datacoll_deviat"=>'text',
            "codebook_stdydscr_method_anlyinfo_resprate"=>'text',
            'stdy_weight'=>'text'
            ),
    $metadata);
?>


<!-- data_collection -->
<?php $output['data_collection']= render_group('data_collection',
    $fields=array(
            "codebook_stdydscr_stdyinfo_sumdscr_colldate"=>'array',
            "codebook_stdydscr_method_datacoll_frequenc"=>'text',
            "codebook_stdydscr_stdyinfo_sumdscr_timeprd"=>'array',
            "codebook_stdydscr_method_datacoll_sources_datasrc"=>'text',
            "codebook_stdydscr_method_datacoll_collmode"=>'text',
            "codebook_stdydscr_method_datacoll_collsitu"=>'text',
            "codebook_stdydscr_method_datacoll_actmin"=>'text',
            "codebook_stdydscr_method_datacoll_weight"=>'text',
            "codebook_stdydscr_method_datacoll_resinstru"=>'text',
            "codebook_stdydscr_method_datacoll_datacollector"=>'array',
            ),
    $metadata);
?>


<!-- data_processing -->
<?php $output['data_processing']= render_group('data_processing',
    $fields=array(
            "codebook_stdydscr_method_datacoll_cleanops"=>'text',
            "codebook_stdydscr_method_notes"=>'text'
            ),
    $metadata);
?>


<!-- data_appraisal -->
<?php $output['data_appraisal']= render_group('data_appraisal',
    $fields=array(
            "codebook_stdydscr_method_anlyinfo_estsmperr"=>'text',
            "codebook_stdydscr_method_anlyinfo_dataappr"=>'text'
            ),
    $metadata);
?>


<!-- data_access -->
<?php $output['data_access']= render_group('data_access',
    $fields=array(
            "codebook_stdydscr_dataaccs_usestmt_contact"=>'array',
            "stdy_dataaccs_confdec"=>'text',
            "codebook_stdydscr_dataaccs_usestmt_conditions"=>'text',
            "codebook_stdydscr_dataaccs_usestmt_citreq"=>'text',
            "codebook_stdydscr_dataaccs_usestmt_deposreq">'text',
            "codebook_stdydscr_dataaccs_setavail_accsplac"=>'array', //parsing issue
            "codebook_stdydscr_dataaccs_setavail_origarch"=>'array', //parsing issue
            "codebook_stdydscr_dataaccs_setavail_avlstatus"=>'text',
            "codebook_stdydscr_dataaccs_usestmt_confdec"=>'text',//parsing issue

            ),
    $metadata);
?>


<!-- disclaimer_copyright -->
<?php $output['disclaimer_copyright']= render_group('disclaimer_copyright',
    $fields=array(
            "codebook_stdydscr_dataaccs_usestmt_disclaimer"=>'text',
            "codebook_stdydscr_citation_prodstmt_copyright"=>'text'
            ),
    $metadata);
?>


<!-- contacts -->
<?php $output['contacts']= render_group('contacts',
    $fields=array(
            "stdy_contact"=>'array'
            ),
    $metadata);
?>

<!-- metadata_production -->
<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
            "stdy_producer"=>'array',
            "doc_producer"=>'array',
            "doc_version"=>'text',
            "doc_idno"=>'text'
            ),
    $metadata);
?>

<div style="overflow:hidden;clear:both">
<div style="float: left;
    width: 170px;
    font-size: 14px;
    margin: 0;
    padding: 0;
    padding-top:15px;">

    <?php foreach($output as $key=>$value):?>            
        <?php if(trim($value)!==""):?>    
        <div style="padding:5px;font-size:smaller;">
            <a href="<?php echo current_url();?>#metadata-<?php echo $key;?>"><?php echo t($key);?></a>
        </div>
        <?php endif;?>
    <?php endforeach;?>
</div>
<div style="margin-left: 170px;
    border-left: 1px solid #e8e8e8;
    padding: 0 20px 20px 20px;">
<?php echo implode('',$output);?>
</div>
</div>
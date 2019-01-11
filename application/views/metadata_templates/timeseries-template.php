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

<?php /* ?>
<pre>
<?php
 var_dump($metadata);
?>
</pre>
<?php */?>

<!-- identification section -->
<?php $output['identification']= render_group('identification',
    $fields=array(
            "titl"=>'text',
            "abbreviation"=>'text',
            "metadata.ts_geog_units"=>"geog_units",
            "metadata.titlstmt.alttitl"=>'text',
            "metadata.titlstmt.partitl"=>'text',
            "nation"=>'text',
            "metadata.serStmt.serName"=>'text',
            "metadata.serStmt.serInfo"=>'text',
            "surveyid"=>'text',
            "metadata.stdyInfo.abstract"=>'text',
            "metadata.stdyInfo.dataKind"=>'text',
            "metadata.stdyInfo.anlyUnit"=>'text'            
    ),
    $metadata);
?>


<!-- version -->
<?php $output['version']= render_group('version',
    $fields=array(
            "metadata.verStmt.version"=>'text',
            "metadata.verStmt.date"=>'text',
            "metadata.verStmt.notes"=>'text'
            ),
    $metadata);
?>


<?php
$all_fields=array();
foreach($metadata['metadata'] as $key=>$value){
    $all_fields["metadata.".$key]=is_array($value) ? 'array' : 'text';
}
?>

<!-- scope -->
<?php $output['scope']= render_group('scope',
    $fields=$all_fields,
    $metadata);
?>


<!-- coverage -->
<?php $output['coverage']= render_group('coverage',
    $fields=array(
            "metadata.stdyInfo.geogCover"=>'text',
            "metadata.stdyInfo.geogUnit"=>'text',
            "metadata.stdyInfo.anlyUnit"=>'text',
            "metadata.stdyInfo.universe"=>'text'
            ),
    $metadata);
?>


<!-- producers_sponsors -->
<?php $output['producers_sponsors']= render_group('producers_sponsors',
    $fields=array(
            "authenty"=>'array',
            "producer"=>'array',
            "fundag"=>'array',
            "metadata.othId"=>'array'
            ),
    $metadata);
?>


<!-- sampling -->
<?php $output['sampling']= render_group('sampling',
    $fields=array(
            "metadata.method.sampProc"=>'text',
            "metadata.method.deviat"=>'text',
            "metadata.method.respRate"=>'text',
            'metadata.method.weight'=>'text'
            ),
    $metadata);
?>


<!-- data_collection -->
<?php $output['data_collection']= render_group('data_collection',
    $fields=array(
            "metadata.stdyInfo.collDate"=>'array',
            "metadata.method.frequenc"=>'text',
            "metadata.stdyInfo.timePrd"=>'array',
            "metadata.method.dataSrc"=>'text',
            "metadata.method.collMode"=>'text',
            "metadata.method.collSitu"=>'text',
            "metadata.method.actMin"=>'text',
            "metadata.method.resInstru"=>'text',
            "metadata.method.dataCollector"=>'array',
            ),
    $metadata);
?>


<!-- data_processing -->
<?php $output['data_processing']= render_group('data_processing',
    $fields=array(
            "metadata.method.cleanOps"=>'text',
            "metadata.method.notes"=>'text'
            ),
    $metadata);
?>


<!-- data_appraisal -->
<?php $output['data_appraisal']= render_group('data_appraisal',
    $fields=array(
            "metadata.method.estSmpErr"=>'text',
            "metadata.method.dataAppr"=>'text'
            ),
    $metadata);
?>


<!-- data_access -->
<?php $output['data_access']= render_group('data_access',
    $fields=array(
            "metadata.dataAccess.contact"=>'array',
            "metadata.dataAccess.confDec"=>'text',
            "metadata.dataAccess.conditions"=>'text',
            "metadata.dataAccess.citReq"=>'text',
            "metadata.dataAccess.deposReq">'text',
            "metadata.dataAccess.accsPlac"=>'text', 
            "metadata.dataAccess.origArch"=>'text', 
            "metadata.dataAccess.avlStatus"=>'text'
            ),
    $metadata);
?>


<!-- disclaimer_copyright -->
<?php $output['disclaimer_copyright']= render_group('disclaimer_copyright',
    $fields=array(
            "metadata.dataAccess.disclaimer"=>'text',
            "metadata.prodStmt.copyright"=>'text'
            ),
    $metadata);
?>


<!-- contacts -->
<?php $output['contacts']= render_group('contacts',
    $fields=array(
            "metadata.distStmt.contact"=>'array'
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
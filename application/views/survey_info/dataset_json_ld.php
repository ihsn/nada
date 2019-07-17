<?php 

$abstract_fields=array(
    t('Abstract')=>'study_desc/study_info/abstract',
    t('Geographic coverage')=>'study_desc/study_info/geog_coverage',
    t('Geographic coverage notes')=>'study_desc/study_info/geog_coverage_notes',
    t('Analysis unit')=>'study_desc/study_info/analysis_unit',
    t('Universe')=>'study_desc/study_info/universe',
    t('Kind of data')=>'study_desc/study_info/data_kind',
    t('Frequency of data collection')=>'study_desc/method/data_collection/frequency',
    t('Sampling procedure')=>'study_desc/method/data_collection/sampling_procedure',
    t('Sampling deviation')=>'study_desc/method/data_collection/sampling_deviation',
    ('Mode of data collection')=>'study_desc/method/data_collection/coll_mode',
    t('Research instrument')=>'study_desc/method/data_collection/research_instrument',
    t('Cleaning operations')=>'study_desc/method/data_collection/cleaning_operations',
    t('Response rate')=>'study_desc/method/analysis_info/response_rate',
    t('Sampling error estimates')=>'study_desc/method/analysis_info/sampling_error_estimates',
    t('Data appraisal')=>'study_desc/method/analysis_info/data_appraisal',    
);

$creators=array();
$authoring_entities=get_array_nested_value($metadata,'study_desc/authoring_entity');
if(!empty($authoring_entities)){
    $authoring_entities=array_column($authoring_entities, 'name');

    foreach($authoring_entities as $auth_entity){
        $creators[]=array(
            "@type"=>"Organization",
            "name"=>$auth_entity
        );
    }
}

$producers=array();
$producers_=get_array_nested_value($metadata,'study_desc/production_statement/producers');
if(!empty($producers_)){
    $producers_=array_column($producers_, 'name');
    
    foreach($producers_ as $producer){
        $producers[]=array(
            "@type"=>"Organization",
            "name"=>$producer
        );
    }
}

$abstract=array();
foreach($abstract_fields as $fld_name=>$fld_path){
    $value=get_array_nested_value($metadata,$fld_path);
    if(!empty($value)){
        $abstract[]=$fld_name."\r\n---------------------------\r\n\r\n".$value;
    }
}
$abstract=implode("\r\n\r\n",$abstract);

$keywords=get_array_nested_value($metadata,'study_desc/study_info/keywords');
if($keywords){
    $keywords=array_column($keywords,'keyword');
}

$years=implode("-",array_filter(array_unique(array($year_start, $year_end))));


$json_ld=array(
    '@context'=>'https://schema.org/',
    "@type"=>"Dataset",
    "name"=> implode(" - ", array_filter(array($title,$nation))),
    "description"=>$abstract,
    "url"=>site_url('catalog/'.$id),
    "sameAs"=> site_url('catalog/study/'.$idno),
    "identifier" => $idno,
    "includedInDataCatalog"=> array(
        "@type"=>"DataCatalog",
        "name"=>base_url()
    ),
    "temporalCoverage" => $years,
    "dateCreated" => date('c',$created),
  "dateModified" => date('c',$changed),
  "spatialCoverage"=>array(
     "@type"=>"Place",
     "name"=>$nation
  )
);

if (!empty($keywords)){
    $json_ld["keywords"]=$keywords;
}

if (is_array($creators) && count($creators)>0){
    $json_ld["creator"]=$creators;
}

if (is_array($producers) && count($producers)>0){
    $json_ld["producer"]=$producers;
}

?>
<script type="application/ld+json">
    <?php echo json_encode($json_ld,JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);?>
</script>
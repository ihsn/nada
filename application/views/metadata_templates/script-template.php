<?php
/*
 * Reproducable research template
 *
 * @metadata - array containing all metadata
 *
 *
 **/
?>


<style>
    .accordion-title{
        cursor:pointer;
        font-size:1.2em;
        color:#0275d8;
        font-weight:normal;
    }
    .accordion-title:hover{
        color:#000;
    }

    #accordion-script-files .card{
        margin-top:5px;
    }

    [data-toggle="collapse"] i:before{
        content: "\f139";
    }
    
    [data-toggle="collapse"].collapsed i:before{
        content: "\f13a";
    }
    
    .metadata-content-body{
        border-left:1px solid gainsboro;
    }
    
</style>





<?php 


    //rendered html for all sections
    $output=array();
?>

<?php

$template=array(
    'identification'=>array(
    "metadata.project_desc.title_statement.title"=>'text',
    "metadata.project_desc.title_statement.sub_title" =>'text',
    "metadata.project_desc.title_statement..alternate_title"=>'text',
    "metadata.project_desc.title_statement.translated_title"=>'text',
    "metadata.project_desc.geographic_coverage"=>'nation',    
	"metadata.project_desc.abstract" =>'text',
	"metadata.project_desc.output_type" =>'text',
	"metadata.project_desc.publication_url" =>'text',    
    "metadata.project_desc.authoring_entity"=>'array',
    "metadata.project_desc.contributor"=>'array',
    "metadata.project_desc.curator" =>'array',
	"metadata.project_desc.doi" =>'text',
	"metadata.project_desc.repository_url" =>'array',
	"metadata.project_desc.project_website" =>'text',
    "idno"=>'text',
	"metadata.project_desc.language" =>'array'
    ),
    'version'=>array(
        "metadata.project_desc.version_statement.version"=>'text',
        "metadata.project_desc.version_statement.version_date"=>'text',
        "metadata.project_desc.version_statement.version_resp"=>'text',
	"metadata.project_desc.version_statement.version_notes"=>'text'
    ),
    'methods'=>array(
        "metadata.project_desc.methods"=>'array',
    ),
    'software'=>array(
        "metadata.project_desc.software"=>'script_software',
        "metadata.project_desc.technology_environment"=>'text',
        "metadata.project_desc.technology_requirements"=>'text',
        "metadata.project_desc.license"=>'text',
        "metadata.project_desc.pub_research"=>'text',
        "metadata.project_desc.data_included"=>'text',
        "metadata.project_desc.data_url"=>'text',
        "metadata.project_desc.contact"=>'array',

        "metadata.project_desc.disclaimer"=>'text',
        "metadata.project_desc.confidentiality"=>'text',
        "metadata.project_desc.citation_requirement"=>'text',
        "metadata.project_desc.data_desc"=>'array',
        "metadata.project_desc.review_process"=>'array',
        "metadata.project_desc.sponsor"=>'array',
        "metadata.project_desc.acknowledgements"=>'array',
        "metadata.project_desc.related_proejcts"=>'array',
        "metadata.project_desc.contacts"=>'array',
    ),

    'scripts'=>array(
	    "metadata.project_desc.script_files"=>'script_file'
    ),
    
    'metadata_production'=>array(
        "metadata_production.copyright"=>'array',
        "doc_producer"=>'array',
        "doc_version"=>'text',
        "doc_idno"=>'text'
    )
);
?>

<?php
$output=array(); 
foreach($template as $section=>$fields){
    $output[$section]=render_group($section,$fields,$metadata);
}
?>



<!-- sidebar with section links -->
<div class="col-sm-2 col-lg-2  d-none d-sm-block">
<div class="navbar-collapse sticky-top">

    <ul class="navbar-nav flex-column wb--full-width">
    <?php foreach($output as $key=>$value):?>            
        <?php if(trim($value)!==""):?>    
        <li class="nav-item">
            <a href="<?php echo current_url();?>#metadata-<?php echo $key;?>"><?php echo t($key);?></a>
        </li>
        <?php endif;?>
    <?php endforeach;?>
    </ul>
</div>
</div>
<!--metadata content-->
<div class="col-12 col-sm-10 col-lg-10 wb-border-left">
    <?php echo implode('',$output);?>
</div>



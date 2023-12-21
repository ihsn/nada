<?php
/*
 * Image template 
 *
 * @metadata - array containing all metadata
 *
 **/
?>

<?php
if(isset($metadata['resources']) ){    
    foreach($metadata['resources'] as $resource_filename => $resource){
        if($this->form_validation->valid_url($resource['filename'])){
            $metadata['resources'][$resource_filename]['download_link']=$resource['filename'];
            $metadata['resources'][$resource_filename]['extension']=pathinfo($resource['filename'],PATHINFO_EXTENSION);
        }else{
            $metadata['resources'][$resource_filename]['download_link']=site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
            $metadata['resources'][$resource_filename]['extension']=pathinfo($resource['filename'],PATHINFO_EXTENSION);
        }  
    }
} 
?>


<?php 
    //rendered html for all sections
    $output=array();
?>

<!-- identification section -->
<?php $output['identification']= render_group('identification',
    $fields=array(
            "title"=>'text',
            "resources"=>'photo',
            "metadata.image_description.description.description"=>'text',            
            "metadata.image_description.description.albums"=>'array',
            "idno"=>'text',
    ),
    $metadata    
);

//change the format of the gps field
if (isset($metadata['metadata']['image_description']['dcmi']['gps'])){
    $tmp_=$metadata['metadata']['image_description']['dcmi']['gps'];
    unset($metadata['metadata']['image_description']['dcmi']['gps']);
    $metadata['metadata']['image_description']['dcmi']['gps'][]=$tmp_;
}
?>

<!-- metadata_production -->
<?php $output['image_description']= render_group('image_description',
    $fields=array(

        "metadata.image_description.dcmi.date"=>"text",
        "metadata.image_description.dcmi.caption"=>"text",
        "metadata.image_description.dcmi.description"=>"text",

        "metadata.image_description.dcmi.keywords"=>"array",
        "metadata.image_description.dcmi.topics"=>"array",
        "metadata.image_description.dcmi.country"=>"array",

        "metadata.image_description.dcmi.coverage"=>"text",
        "metadata.image_description.dcmi.format"=>"text",
        "metadata.image_description.dcmi.languages"=>"array",

        "metadata.image_description.dcmi.source"=>"text",
        "metadata.image_description.dcmi.note"=>"text",
        "metadata.image_description.dcmi.creator"=>"text",
        "metadata.image_description.dcmi.contributor"=>"text",
        "metadata.image_description.dcmi.publisher"=>"text",
        "metadata.image_description.dcmi.rights"=>"text",

        "metadata.image_description.dcmi.relations"=>"array",

        "metadata.image_description.dcmi.license"=>"array",
        "metadata.image_description.dcmi.album"=>"array",
        "metadata.image_description.dcmi.gps"=>"map_leaflet",
        //"metadata.image_description.dcmi.gps"=>"array",

        
            ),
    $metadata,
    $options=array(
        'metadata.image_description.dcmi.gps'=> array(
            'latitude'=>'latitude',
            'longitude'=>'longitude',
            'loc_info'=>'latitude',
            'api_key'=>$this->config->item("google_maps_api_key"),
            //'show_data_table'=>false
        )
    )
);
?>


<?php $output['album']= render_group('album',
    $fields=array(
        "metadata.image_description.album"=>"array"
    ),
    $metadata);
?>        


<?php $output['files']= render_group('files',
    $fields=array(
        "metadata.image_description.files"=>"array",
        "metadata.image_description.files.filename"=>"text",
        "metadata.image_description.files.format"=>"text",
        "metadata.image_description.files.note"=>"text",
    ),
    $metadata);
?>        

        


<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
        "metadata.metadata_information.title"=>"text",
        "metadata.metadata_information.idno"=>"text",
        "metadata.metadata_information.producers"=>"array",
        "metadata.metadata_information.producers.name"=>"text",
        "metadata.metadata_information.producers.abbr"=>"text",
        "metadata.metadata_information.producers.affiliation"=>"text",
        "metadata.metadata_information.producers.role"=>"text",
        "metadata.metadata_information.production_date"=>"text",
        "metadata.metadata_information.version"=>"text"
    ),
    $metadata);
?>        


<!-- dump -->
<?php 
    //$output['metadata_dump']= render_field('dump',$field_name='dump',$metadata,true);
?>


<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>
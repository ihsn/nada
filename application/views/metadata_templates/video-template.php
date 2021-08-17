<?php
/*
 * Video template
 *
 * @metadata - array containing all metadata
 *
 * @id - id
 * @surveyid - IDNO
 * @ all fields
 *
 *
 **/
?>


<?php 
    //rendered html for all sections
    $output=array();
?>


<?php $output['']= render_group('',
    $fields=array(
            "metadata.resources"=>'resources_download_buttons'),
    $metadata);
?>


<?php
$video_options['video']=array(
    'video_provider'=> get_field_value('metadata.video_description.video_provider',$metadata),
    'video_url'=> get_field_value('metadata.video_description.video_url',$metadata),
    'embed_url'=> get_field_value('metadata.video_description.embed_url',$metadata),
);

$output["video"]=render_group ("",
    $fields=array(
        "video"=>"video",
    ),
    $video_options);
?>


<!-- identification section -->
<?php $output['description']= render_group('',
    $fields=array(        
        "metadata.video_description.title"=>"text",
        "metadata.video_description.description"=>"text",
        "metadata.video_description.video_url"=>"text",
        "metadata.video_description.idno"=>"text",
    ),
    $metadata);
    ?>



<?php     
    //render html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output, 'hide_sidebar'=>true));
?>
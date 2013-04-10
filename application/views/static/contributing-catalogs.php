<div style="background:white;overflow:auto;">

<h1><?php echo t('contributing_catalogs');?></h1>
<p><?php echo sprintf(t('msg_about_contributing_catalogs'),anchor('catalog/central',t('central_data_catalog'))); ?></p>

<?php
$this->load->model("repository_model");
$this->load->model("repository_sections_model");

$collections=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);
$sections=array();

foreach($collections as $key=>$collection)
{
	$sections[$collection['section']]=$collection['section_title'];
}

$data['sections']=$sections;		
$data['rows']=$collections;
$data['show_unpublished']=TRUE;
$content=$this->load->view("repositories/index_public",$data);
?>

</div>
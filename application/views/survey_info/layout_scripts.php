<?php
/*
 * survey info page template
 *
 **/?>
<?php
$country_name='';
if(isset($survey['nation']) &&  trim($survey['nation']) !='' ){
	$country_name=$survey['nation']. ' - ';
}
?>


<div class="page-body-full study-metadata-page">
	<span 
		id="dataset-metadata-info" 
		data-repositoryid="<?php echo html_escape($survey['owner_repo']['repositoryid']);?>"
		data-id="<?php echo $survey['id'];?>"
		data-idno="<?php echo html_escape($survey['idno']);?>"
	></span>

<div class="container-fluid page-header">
<div class="container">
<?php if(intval($published)===0):?>
	<?php
		$_review_uid = $this->session->userdata('user_id');
		if (empty($sid) || !$this->acl_manager->dataset_is_publicly_readable($sid, $_review_uid ? (int) $_review_uid : null)) {
			show_404();
		}
	?>
	<div class="content-unpublished"><?php echo t('content_is_not_published');?></div>
<?php endif;?>


<?php $this->load->view('survey_info/info',null); ?>


<?php 
if(!isset($active_tab)){
    $active_tab=$this->uri->segment(3);
}

$survey_id=$this->uri->segment(2);
$active_tab_class="active";

$tab_urls=array();
$tab_urls['data_dictionary']=array(
			'data_dictionary',
			'data-dictionary',
			'vargrp',
			'datafile',
			'datafiles',
			'search'
			);

$tab_urls['get_microdata']=array(
			'get_microdata',
			'get-microdata'
			);

$tab_urls['related_materials']=array(
		'related_materials',
		'related-materials',
		'documentation',
		'home',
		''
);

$tab_urls['related_citations']=array(
		'related_citations',
		'related_publications'
);

$tab_urls['description']=array(
		'study-description',
		'description',
		'overview',
		'sampling',
		'datacollection',
		'accesspolicy',
		'export-metadata'
);

if (empty($page_tabs['related_materials']['show_tab']) && isset($tab) && in_array($tab, $tab_urls['related_materials'])){
	$active_tab='description';
}

//enable disable right side bar for related studies
if($related_studies_count>0){
    $right_sidebar='with-right-side-bar';
}
else{
    $right_sidebar='';
}

//show microdata tab only for logged-in users
if($this->config->item("guests_hide_microdata_tab")=='yes' && !$this->ion_auth->logged_in()){
	unset($page_tabs['get_microdata']);
}
?>


<?php $this->load->view('survey_info/study_tabs', array('page_tabs'=>$page_tabs, 'active_tab'=>$active_tab, 'active_tab_class'=>$active_tab_class, 'data_access_type'=>isset($data_access_type) ? $data_access_type : '')); ?>
</div>
</div>



<div class="container study-metadata-body-content <?php echo $right_sidebar;?>" >


<!-- tabs -->
<div id="tabs" class="study-metadata ui-tabs ui-widget ui-widget-content ui-corner-all study-tabs" >	
  
  <div id="tabs-1" aria-labelledby="ui-id-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom" role="tabpanel" >
  	
    <?php if(in_array($active_tab,array('data-dictionary'))):?>
    <?php 
    	$section='test';
    	$sidebar='side bar content';
    ?>
    <div style="overflow:hidden;clear:both">
        <div class="tab-sidebar sidebar-<?php echo $section;?>"><?php echo isset($sidebar) ? $sidebar : ''; ?></div>
        <div class="tab-body body-<?php echo $section;?>"><?php echo $body;?></div>
    </div>
    <?php else:?>
    <div class="tab-body-no-sidebar-x"><?php echo $body;?></div>
    <?php endif;?>

	<div class="mt-5">                
            <a class="btn btn-sm btn-secondary" href="<?php echo site_url('catalog');?>"><i class="fas fa-arrow-circle-left"></i> <?php echo t('Back to Catalog');?></a>
        </div>
  </div>
</div>
<!-- end-tabs-->    
   </div> 
</div>


<!--survey summary resources-->
<script type="text/javascript">
	function toggle_resource(element_id){
		$("#"+element_id).parent(".resource").toggleClass("active");
		$("#"+element_id).toggle();
	}
	
	function bind_pdf_preview_behaviours() {
		if (!$("#pdf-preview-modal").length) {
			return;
		}

		$(document).off("click.nadaPdfPreview", ".pdf-preview-btn").on("click.nadaPdfPreview", ".pdf-preview-btn", function(e) {
			e.preventDefault();

			var previewUrl = $(this).attr("data-preview-url");
			var title = $(this).attr("data-title") || "PDF preview";
			var $modal = $("#pdf-preview-modal");
			var $frame = $("#pdf-preview-frame");

			$("#pdf-preview-modal-title").text(title);
			$frame.attr("src", "about:blank");

			$modal.off("shown.bs.modal.nadaPdfPreview").one("shown.bs.modal.nadaPdfPreview", function() {
				$frame.attr("src", previewUrl);
			}).modal("show");
		});

		$("#pdf-preview-modal").off("hidden.bs.modal.nadaPdfPreview").on("hidden.bs.modal.nadaPdfPreview", function() {
			$("#pdf-preview-frame").attr("src", "about:blank");
		});
	}

	$(document).ready(function () { 
		bind_behaviours();
		bind_pdf_preview_behaviours();
		
		$(".show-datafiles").click(function(){
			$(".data-files .hidden").removeClass("hidden");
			$(".show-datafiles").hide();
			return false;
		});

		//setup bootstrap scrollspy
		$("body").attr('data-spy', 'scroll');
		$("body").attr('data-target', '#dataset-metadata-sidebar');
		$("body").attr('data-offset', '0');
		$("body").scrollspy('refresh');

	});	
	
	function bind_behaviours() {
		//show variable info by id
		$(".resource-info").unbind('click');
		$(".resource-info").click(function(){
			if($(this).attr("id")!=''){
				toggle_resource('info_'+$(this).attr("id"));
			}
			return false;
		});			
	}
</script>
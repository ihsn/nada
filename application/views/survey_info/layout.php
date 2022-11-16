<style>
.metadata-sidebar-container .nav .active{
	background:#e9ecef;		
}
.study-metadata-page .page-header .nav-tabs .active a {
	background: white;
	font-weight: bold;
	border-top: 2px solid #0071bc;
	border-left:1px solid gainsboro;
	border-right:1px solid gainsboro;
}

.study-info-content {
    font-size: 14px;
}

.study-subtitle{
	font-size:.7em;
	margin-bottom:10px;
}

.badge-outline{
	background:transparent;
	color:#03a9f4;
	border:1px solid #9e9e9e;
}
.study-header-right-bar span{
	display:block;
	margin-bottom:15px;
}
.study-header-right-bar{
	font-size:14px;
	color:gray;
}
.get-microdata-btn{
	font-size:14px;
}

.link-col .badge{
	font-size:14px;
	font-weight:normal;
	background:transparent;
	border:1px solid #9E9E9E;
	color:#03a9f4;
}

.link-col .badge:hover{
	background:#03a9f4;
	color:#ffffff;
}

.study-header-right-bar .stat{
	margin-bottom:10px;
	font-size:small;
}

.study-header-right-bar .stat .stat-label{
	font-weight:bold;
	text-transform:uppercase;
}

.field-metadata__table_description__ref_country .field-value,
.field-metadata__study_desc__study_info__nation .field-value{
	max-height:350px;
	overflow:auto;
}
.field-metadata__table_description__ref_country .field-value  ::-webkit-scrollbar,
.field-metadata__study_desc__study_info__nation .field-value ::-webkit-scrollbar {
  -webkit-appearance: none;
  width: 7px;
}

.field-metadata__table_description__ref_country .field-value  ::-webkit-scrollbar-thumb,
.field-metadata__study_desc__study_info__nation .field-value ::-webkit-scrollbar-thumb {
  border-radius: 4px;
  background-color: rgba(0, 0, 0, .5);
  box-shadow: 0 0 1px rgba(255, 255, 255, .5);
}
</style>
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
		data-repositoryid="<?php echo $survey['owner_repo']['repositoryid'];?>"
		data-id="<?php echo $survey['id'];?>"
		data-idno="<?php echo $survey['idno'];?>"
	></span>

<div class="container-fluid page-header">
<div class="container">
<?php if(intval($published)===0):?>
	<?php if($this->session->userdata('user_id')):?>
		<div class="content-unpublished"><?php echo t('content_is_not_published');?></div>		
	<?php else:?>	
		<?php show_404();?>
	<?php endif;?>
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

if (!$page_tabs['related_materials'] && in_array($tab,$tab_urls['related_materials'])){
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
if($this->config->item("guests_hide_microdata_tab")===true && !$this->ion_auth->logged_in()){
	unset($page_tabs['get_microdata']);
}
?>


<!-- Nav tabs -->
<ul class="nav nav-tabs wb-nav-tab-space flex-wrap" role="tablist">
	<?php foreach($page_tabs as $tab_name=>$tab):?>
		<?php if ($tab['show_tab']==0){continue;};?>
		<?php if($tab_name=='get_microdata'):?>
			<li class="nav-item nav-item-get-microdata tab-<?php echo $tab_name;?> <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" >
				<a href="<?php echo $tab['url'];?>" class="nav-link wb-nav-link wb-text-link-uppercase " role="tab" data-id="related-materials" >
					<span class="get-microdata icon-da-<?php echo $data_access_type;?>"></span> <?php echo $tab['label'];?>
				</a>
			</li>                            
		<?php else:?>
			<li class="nav-item tab-<?php echo $tab_name;?> <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>"  >
				<a href="<?php echo $tab['url'];?>" class="nav-link wb-nav-link wb-text-link-uppercase <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" role="tab"  data-id="related-materials" ><?php echo $tab['label'];?></a>
			</li>
		<?php endif;?>
	<?php endforeach;?>
	
	<!--review-->
	<?php if(isset($page_tabs['review_study']) && $page_tabs['review_study']===TRUE):?>                    
		<li class="nav-item tab-<?php echo $tab_name;?> <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" >
			<a href="<?php echo site_url('catalog/'.$survey_id.'/review');?>" class="nav-link wb-nav-link wb-text-link-uppercase <?php echo ($tab=='review') ? $active_tab_class : '';?>" role="tab"  data-id="review">
				<?php echo t('review_study');?>
			</a>
		</li>
	<?php endif;?>
</ul>
<!-- end nav tabs -->
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
	
	$(document).ready(function () { 
		bind_behaviours();
		
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
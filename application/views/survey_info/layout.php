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
	border-left:1px solid gainsboro;
	padding-left:20px;
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

<?php 
	$sub_title=array();
	if ($survey['nation']!=''){
		$sub_title[]='<span id="dataset-country">'.$survey['nation'].'</span>';
	}
	$dates=array_unique(array($survey['year_start'],$survey['year_end']));
	$dates=implode(" - ", $dates);
	if(!empty($dates)){
		$sub_title[]='<span id="dataset-year">'.$dates.'</span>';
	}
	$sub_title=implode(", ", $sub_title);
?>

<div class="row">
	<?php if ($survey['owner_repo']['thumbnail']!='' || ( isset($survey['thumbnail']) && trim($survey['thumbnail'])!='')):?>
		<?php 
			$thumbnail=$survey['owner_repo']['thumbnail'];
			if(isset($survey['thumbnail']) && trim($survey['thumbnail'])!='' && file_exists('files/thumbnails/'.$survey['thumbnail'])){
				$thumbnail='files/thumbnails/'.$survey['thumbnail'];
			}
		?>
		<div class="col-md-2">
			<div class="collection-thumb-container">
				<a href="<?php echo site_url('catalog/'.$survey['owner_repo']['repositoryid']);?>">
				<img  src="<?php echo base_url().$thumbnail;?>" class="mr-3 img-fluid img-thumbnail" alt="<?php echo $survey['owner_repo']['repositoryid'];?>" title="<?php echo $survey['owner_repo']['title'];?>"/>
				</a>
			</div>		
		</div>
	<?php endif;?>

	<div class="col-md-10">
		
		<div class="rowx">
		
		<h1 class="mt-0 mb-1" id="dataset-title"><?php echo $survey_title;?></h1>
		<h6 class="sub-title" id="dataset-sub-title"><?php echo $sub_title;?></h6>

		</div>

		<div class="row">
		
	<div class="col pr-5">

		<div class="row mt-4 mb-2 pb-2  border-bottom">
			<div class="col-md-2">
				<?php echo t('refno');?>
			</div>
			<div class="col">
				<div class="study-idno">
					<?php echo $survey['idno'];?>
				</div>
			</div>
		</div>
		
		<?php if (isset($survey['authoring_entity']) && !empty($survey['authoring_entity'])):?>
			<div class="row mb-2 pb-2  border-bottom">
				<div class="col-md-2">
					<?php echo t('producers');?>
				</div>
				<div class="col">
					<div class="producers">
						<?php echo $survey['authoring_entity'];?>
					</div>
				</div>
			</div>
		<?php endif;?>

		<?php if (isset($survey['repositories']) && is_array($survey['repositories']) && count($survey['repositories'])>0): ?> 
		<div class="row  border-bottom mb-2 pb-2 mt-2">
			<div class="col-md-2">
			<?php echo t('collections');?>
			</div>
			<div class="col">
				<div class="collections">           
						<?php foreach($survey['repositories'] as $repository):?>
							<div class="collection"><?php echo anchor('catalog/'.$repository['repositoryid'],$repository['title']);?></div>
						<?php endforeach;?>
				</div>
			</div>
		</div>
		<?php endif;?>

		<div class="row  border-bottom mb-2 pb-2 mt-2">
			<div class="col-md-2">
				<?php echo t('metadata');?>
			</div>
			<div class="col">
				<div class="metadata">
					<!--metadata-->
					<span class="mr-2 link-col">
						<?php $report_file=unix_path($survey['storage_path'].'/ddi-documentation-'.$this->config->item("language").'-'.$survey['id'].'.pdf');?>
						<?php if (file_exists($report_file)):?>
							<a href="<?php echo site_url('catalog/'.$survey['id'].'/pdf-documentation');?>" title="<?php echo t('documentation_in_pdf');?>" >
								<span class="badge badge-success"><i class="fa fa-file-pdf-o" aria-hidden="true"> </i> <?php echo t('documentation_in_pdf');?></span>
							</a>
						<?php endif;?>
					
						<?php if($survey['type']=='survey'):?>
							<a href="<?php echo site_url('metadata/export/'.$survey['id'].'/ddi');?>" title="<?php echo t('metadata_in_ddi_xml');?>">
								<span class="badge badge-primary"> <?php echo t('DDI/XML');?></span>
							</a>
						<?php endif;?>

						<a href="<?php echo site_url('metadata/export/'.$survey['id'].'/json');?>" title="<?php echo t('metadata_in_json');?>">
							<span class="badge badge-info"><?php echo t('JSON');?></span>
						</a>
						</span>	
					<!--end-metadata-->
				</div>
			</div>
		</div>

		<?php if($survey['link_study']!='' || $survey['link_indicator']!=''): ?>
		<div class="row  border-bottom  mb-2 pb-2 mt-2">
			<div class="col-md-2">
				
			</div>
			<div class="col">
				<div class="study-links link-col">
					<?php if($survey['link_study']!=''): ?>						
						<a  target="_blank" href="<?php echo html_escape($survey['link_study']);?>" title="<?php echo t('link_study_website_hover');?>">
							<span class="mr-2">
								<i class="fa fa-globe" aria-hidden="true"> </i> <?php echo t('link_study_website');?>
							</span>
						</a>
					<?php endif; ?>

					<?php if($survey['link_indicator']!=''): ?>
						<a target="_blank"  href="<?php echo html_escape($survey['link_indicator']);?>" title="<?php echo t('link_indicators_hover');?>">
							<span>
								<i class="fa fa-database" aria-hidden="true"> </i> <?php echo t('link_indicators_hover');?>
							</span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<?php if (!empty($data_classification)):?>
		<div class="row  mb-2 pb-2 mt-2">
			<div class="col-md-2">
				<?php echo t('data_access');?>
			</div>
			<div class="col">
				<span class="data-classification">
					<?php echo t('data_class_note_'.$data_classification);?>
				</span>
			</div>
		</div>
		<?php endif;?>

		<?php if(isset($data_access_type) && $data_access_type!='data_na'):?>
			<div class="row  mb-2 pb-2 mt-2">
				<div class="col-md-2">
					<?php echo t('license');?>
				</div>
				<div class="col">
					<span class="license">
						<a href="<?php echo site_url('licenses/'.$data_access_type);?>">
						<?php echo t('legend_data_'.$data_access_type);?> <i class="fa fa-info-circle" aria-hidden="true"></i>
						</a>
					<span>
				</div>
			<div>

			<a href="<?php echo site_url("catalog/$sid/get-microdata");?>" class="get-microdata-btn badge badge-primary wb-text-link-uppercase" title="<?php echo t('get_microdata');?>">					
				<span class="fa fa-download"></span>
				<?php echo t('get_microdata');?>
			</a>
		
		<?php endif;?>
		
	
	
	</div>

	<div class="col-md-2">
		<!--right-->
		<div class="study-header-right-bar">
				<div class="stat">
					<div class="stat-label"><?php echo t('created_on');?> </div>
					<div class="stat-value"><?php echo date("M d, Y",$survey['created']);?></div>
				</div>

				<div class="stat">
					<div class="stat-label"><?php echo t('last_modified');?> </div>
					<div class="stat-value"><?php echo date("M d, Y",$survey['changed']);?></div>
				</div>
				
				<?php if ((int)$survey['total_views']>0):?>
					<div class="stat">
						<div class="stat-label"><?php echo t('page_views');?> </div>
						<div class="stat-value"><?php echo $survey['total_views'];?></div>
					</div>
				<?php endif;?>

				<?php if ((int)$survey['total_downloads']>0):?>
					<div class="stat">
						<div class="stat-label"><?php echo t('downloads');?> </div>
						<div class="stat-value"><?php echo $survey['total_downloads'];?></div>
					</div>				
				<?php endif;?>
		</div>		
		<!--end-right-->
		</div>
	


	</div>
	</div>
</div>




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

?>


<!-- Nav tabs -->
<ul class="nav nav-tabs wb-nav-tab-space flex-wrap" role="tablist">
	<?php foreach($page_tabs as $tab_name=>$tab):?>
		<?php if ($tab['show_tab']==0){continue;};?>
		<?php if($tab_name=='get_microdata'):?>
			<li class="nav-item nav-item-get-microdata tab-<?php echo $tab_name;?>" >
				<a href="<?php echo $tab['url'];?>" class="nav-link wb-nav-link wb-text-link-uppercase <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" role="tab" data-id="related-materials" >
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
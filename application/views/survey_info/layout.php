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
		<?php /* ?>
		<?php if (isset($survey['repositories']) && is_array($survey['repositories']) && count($survey['repositories'])>0): ?>
			<?php foreach($survey['repositories'] as $repository):?>
				<div class="collection"><?php echo anchor('catalog/'.$repository['repositoryid'],$repository['title']);?></div>
			<?php endforeach;?>
		<?php endif;?>
		<?php */ ?>
	</div>
	</div>
	<?php endif;?>		
	<div class="col-md-10">
		<h1 class="mt-0 mb-1" id="dataset-title"><?php echo $survey_title;?></h1>
		<h6 class="sub-title" id="dataset-sub-title"><?php echo $sub_title;?>

		<?php if (isset($survey['repositories']) && is_array($survey['repositories']) && count($survey['repositories'])>0): ?>                    
			<?php foreach($survey['repositories'] as $repository):?>
				<div class="collection badge badge-light"><?php echo anchor('catalog/'.$repository['repositoryid'],$repository['title']);?></div>
			<?php endforeach;?>                    
		<?php endif;?>

		</h6>
					

		<div class="producers mb-3">
		<?php if (isset($survey['authoring_entity']) && !empty($survey['authoring_entity'])):?>
			<?php echo $survey['authoring_entity'];?>
		<?php endif;?>
		</div>
	

		<div class="dataset-footer-bar mt-2">					
			
			<span class="mr-3 link-col float-left">
				<small>
					<?php echo t('created_on');?> 
					<strong><?php echo date("F d, Y",$survey['changed']);?></strong>
				</small>
			</span>
			
			<span class="mr-3 link-col float-left">
				<small>
					<?php echo t('last_modified');?> 
					<strong><?php echo date("F d, Y",$survey['changed']);?></strong>
				</small>
			</span>
			
            <?php if ((int)$survey['total_views']>0):?>
            <span class="mr-3 link-col float-left">
                <small>
				<i class="fa fa-eye" aria-hidden="true"></i> 
				<?php echo t('page_views');?> 
				<strong><?php echo $survey['total_views'];?></strong>
			</small>
            </span>
			<?php endif;?>

			<?php if ((int)$survey['total_downloads']>0):?>
            <span class="mr-3 link-col float-left">
                <small>
				<i class="fa fa-eye" aria-hidden="true"></i> 
				<?php echo t('download');?> 
				<strong><?php echo $survey['total_downloads'];?></strong>
			</small>
            </span>
			<?php endif;?>
			
			<?php $report_file=unix_path($survey['storage_path'].'/ddi-documentation-'.$this->config->item("language").'-'.$survey['id'].'.pdf');?>
			<?php if (file_exists($report_file)):?>
				<span class="mr-3 link-col float-left">
					<small><a href="<?php echo site_url('catalog/'.$survey['id'].'/pdf-documentation');?>" title="<?php echo t('pdf');?>" >
					<i class="fa fa-file-pdf-o" aria-hidden="true"> </i> <?php echo t('documentation_in_pdf');?>
					</a> 
					</small>
				</span>            
			<?php endif;?>

			<?php if($survey['link_study']!=''): ?>
				<span class="mr-3 link-col  float-left">
				<small>
				<a  target="_blank" href="<?php echo html_escape($survey['link_study']);?>" title="<?php echo t('link_study_website_hover');?>">
				<i class="fa fa-globe" aria-hidden="true"> </i> <?php echo t('link_study_website');?>
				</a>
				</small>
				</span>
			<?php endif; ?>
		
			<?php if($survey['link_indicator']!=''): ?>
				<span class="mr-3 link-col float-left">
					<small>
						<a target="_blank"  href="<?php echo html_escape($survey['link_indicator']);?>" title="<?php echo t('link_indicators_hover');?>">
							<i class="fa fa-database" aria-hidden="true"> </i> <?php echo t('link_indicators_hover');?>					
						</a>
					</small>
				</span>
			<?php endif; ?>

			<span class="mr-3 link-col  float-left">
				<small><i class="fa fa-download" aria-hidden="true"> </i> <?php echo t('metadata');?></small>
				<?php if($survey['type']=='survey'):?>
					<a href="<?php echo site_url('metadata/export/'.$survey['id'].'/ddi');?>" title="<?php echo t('metadata_in_ddi_xml');?>">
						<span class="badge badge-primary"> <?php echo t('DDI/XML');?></span>
					</a>
				<?php endif;?>

				<a href="<?php echo site_url('metadata/export/'.$survey['id'].'/json');?>" title="<?php echo t('metadata_in_json');?>">
					<span class="badge badge-info"><?php echo t('JSON');?></span>
				</a>
			</span>			

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

$tab_urls['study_description']=array(
		'study-description',
		'overview',
		'sampling',
		'datacollection',
		'accesspolicy',
		'export-metadata'
);

if (!$page_tabs['related_materials'] && in_array($tab,$tab_urls['related_materials'])){
	$active_tab='study-description';
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
				<a href="<?php echo $tab['url'];?>" class="nav-link wb-nav-link wb-text-link-uppercase <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" role="tab" data-id="related-materials" title="<?php echo $tab['hover_text'];?>">
					<span class="get-microdata icon-da-<?php echo $data_access_type;?>"></span> <?php echo $tab['label'];?>
				</a>
			</li>                            
		<?php else:?>
			<li class="nav-item tab-<?php echo $tab_name;?> <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>"  >
				<a href="<?php echo $tab['url'];?>" class="nav-link wb-nav-link wb-text-link-uppercase <?php echo ($tab_name==$active_tab) ? $active_tab_class : '';?>" role="tab"  data-id="related-materials" title="<?php echo $tab['hover_text'];?>"><?php echo $tab['label'];?></a>
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
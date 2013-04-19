<script type="text/javascript"> 
   var CI = {
				'base_url': '<?php echo site_url(); ?>',
				'current_section': '<?php echo site_url().'/'.$this->uri->segment(1).'/'.$this->uri->segment(2); ?>',
				'js_loading': '<?php echo t('js_loading'); ?>'  
			}; 	
</script> 

<script type="text/javascript">
$(function(){

	//data-dictionary row-click
	$(".data-file-row").click(function(){
		window.location=$(this).attr("data-url");
		return false;
	});

	//tree-view 
	$(".filetree").treeview({collapsed: false});
	$(".tab-sidebar li.item a,.tab-sidebar li.sub-item a").click(function(){
		$('.study-tabs .tab-body').html('<img src="images/loading.gif"/> loading...');
		var hash={
					tab:$("#tabs .ui-tabs-active a").attr("data-id"),
					page:$(this).attr("data-id")
				};

		$.bbq.pushState(hash);
		
		$.get($(this).attr("href"),{ ajax: "1"}, function(data) {
			$('.study-tabs .tab-body').html(data);
			window.hash_cache[ $.param.fragment() ]=data;			
		});
		
		$(".tab-sidebar li.item a,.tab-sidebar li.sub-item a").removeClass("selected");
		$(this).addClass("selected");
		
		return false;
	});
	
	window.hash_cache={};
	
	//hashchange event handler
	$(window).bind( 'hashchange', function(e) {		
		fragments=$.deparam.fragment();
		
		if(typeof fragments.tab != 'undefined'){
			//$("#from option[value='"+fragments.from+"']").prop('selected', 'selected');
			$("#tabs .ui-tabs-nav a").each(function(){ 				
				if (fragments.tab==$(this).attr("data-id")){
					$("#tabs .ui-tabs-active").removeClass("ui-tabs-active ui-state-active");
					$(this).closest("li").addClass("ui-tabs-active ui-state-active");
				}
			})
		}
		
		var cache_exists=false;
		var fragment_str = $.param.fragment();
		if ( window.hash_cache[ fragment_str ] ) {
			//found in cache
			$("#tabs .tab-body").html(window.hash_cache[ fragment_str ]);
			cache_exists=true;
		}
		
		if(typeof fragments.page!='undefined')
		{
			$(".tab-sidebar a").each(function(){
				if ( $(this).attr("data-id")==fragments.page ){
					if(cache_exists==false){
						$('.study-tabs .tab-body').load($(this).attr("href")+'?ajax=1');
					}
					$(".tab-sidebar li.item a,.tab-sidebar li.sub-item a").removeClass("selected");
					$(this).addClass("selected");
					return;
				}
			});
		}
		
	});
	
	//trigger hashchnage
	$(window).trigger('hashchange');

});
</script>
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

<div class="page-body-full study-metadata-page" >

<?php if (isset($survey_title)):?>
	<h1><?php echo $survey_title;?></h1>
<?php endif;?>	


<?php 

$tab=$this->uri->segment(3);
$survey_id=$this->uri->segment(2);
$active_tab_class="ui-tabs-active ui-state-active";

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

?>
    
<!-- tabs -->
<div id="tabs" style="" class="study-metadata ui-tabs ui-widget ui-widget-content ui-corner-all study-tabs" >

	<div class="tab-heading"><?php echo $survey_info;?><a name="tab"></a></div>

  <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" role="tablist" style="background:none;margin-top:-35px;border-bottom:1px solid gainsboro;">
    <li class="ui-state-default ui-corner-top  <?php echo ($tab=='' || $tab=='study-description') ? $active_tab_class : '';?>" role="tab" tabindex="0" aria-controls="tabs-1" aria-labelledby="ui-id-1" aria-selected="true"><a href="<?php echo site_url('catalog/'.$survey_id.'/study-description');?>#tab" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-1" data-id="study-desc">Study Description</a></li>
    <?php if($page_tabs['data_dictionary']>0):?>
	<li class="ui-state-default ui-corner-top <?php echo (in_array($tab,$tab_urls['data_dictionary'])) ? $active_tab_class : '';?>" role="tab" tabindex="-1" aria-controls="tabs-2" aria-labelledby="ui-id-2" aria-selected="false"><a href="<?php echo site_url('catalog/'.$survey_id.'/data_dictionary');?>#tab" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-2" data-id="data-dictionary">Data Dictionary</a></li>
    <?php endif;?>
    <?php if($page_tabs['get_microdata']>0):?>
	<li class="ui-state-default ui-corner-top <?php echo ($tab=='get_microdata') ? $active_tab_class : '';?>" role="tab" tabindex="-1" aria-controls="tabs-23" aria-labelledby="ui-id-23" aria-selected="false"><a href="<?php echo site_url('catalog/'.$survey_id.'/get_microdata');?>#tab" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-23" data-id="get-microdata"><span class="get-microdata da-icon-small da-<?php echo $data_access_type;?>"></span>Get Microdata</a></li>
    <?php endif;?>
    <?php if($page_tabs['related_materials']>0):?>
    <li class="ui-state-default ui-corner-top <?php echo ($tab=='related_materials') ? $active_tab_class : '';?>" role="tab" tabindex="-1" aria-controls="tabs-3" aria-labelledby="ui-id-4" aria-selected="false"><a href="<?php echo site_url('catalog/'.$survey_id.'/related_materials');?>#tab" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-3" data-id="related-materials">Related Materials</a></li>
    <?php endif;?>
    <?php if($page_tabs['related_citations']>0):?>
    <li class="ui-state-default ui-corner-top <?php echo ($tab=='related_citations') ? $active_tab_class : '';?>" role="tab" tabindex="-1" aria-controls="tabs-4" aria-labelledby="ui-id-5" aria-selected="false"><a href="<?php echo site_url('catalog/'.$survey_id.'/related_citations');?>#tab" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-35" data-id="related-citations">Citations</a></li>
    <?php endif;?>
  </ul>
  <div id="tabs-1" aria-labelledby="ui-id-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom" role="tabpanel" style="" aria-expanded="true" aria-hidden="false">
  	
	<?php if(isset($sidebar) && $sidebar!=''):?>
    <div style="overflow:hidden;clear:both">
        <div class="tab-sidebar"><?php echo isset($sidebar) ? $sidebar : ''; ?></div>    
        <div class="tab-body"><?php echo $body;?></div>
    </div>
    <?php else:?>
    <div class="tab-body-no-sidebar"><?php echo $body;?></div>
    <?php endif;?>
  </div>
</div>
<!-- end-tabs-->


    
    
</div>

<br style="clear:both;"/>
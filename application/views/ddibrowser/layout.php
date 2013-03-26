<style>
.tab-heading {
text-align: left;
padding: 10px;
padding-bottom: 40px;
z-index: -1;
background-color:#F3F3F3;
background: transparent url(http://localhost/nada4/themes/wb/tab-bg.png) repeat-x scroll bottom;
padding-left: 20px;
border:1px solid gainsboro;
font-size:small;
}

.ui-tabs .ui-tabs-panel{
background:white;border:1px solid gainsboro;overflow:auto;border-top:0px;
}

.ui-tabs .ui-tabs-nav li.ui-state-active {
position: relative;
background:gray;
background: url(http://localhost/nada4/themes/wb/tab-active-arrow.png) bottom center no-repeat;
}

/*active tab*/
.ui-tabs .ui-tabs-nav li.ui-state-active a {
	background: url(http://localhost/nada4/themes/wb/active-tab-bg.png);
	color: white;
	margin-bottom:0px;
}

/*set tab borders*/
.ui-tabs .ui-tabs-nav li a, 
.ui-tabs-collapsible .ui-tabs-nav li.ui-tabs-active a{
	border: 1px solid gainsboro;
	border-bottom: 0px;
	background:white;
	xfont-size:12px;
	padding:5px 10px;
	line-height:140%;
}

.ui-tabs .ui-tabs-nav li {
	border: 0px;
}

.ui-tabs .ui-tabs-nav li.ui-tabs-active{margin-bottom:-4px;padding-bottom:4px;}

.tab-sidebar{float:left;width:160px;font-size:smaller;border-right:1px solid gainsboro;margin-right:10px;padding-right:10px;}

.page-body h1{margin-bottom:15px;}

.ui-tabs li a .get-microdata{display:block;background:url(http://localhost/nada4/themes/wb/data-access-small.gif) no-repeat 0% -2%;width:16px;height:16px;float:left;margin-right:5px;}
.tab-body{font-size:small;margin-left:170px;border-left:1px solid gainsboro;padding-left:20px;}
.tab-sidebar .study-items{text-align:right;}
.tab-sidebar .filetree li a{font-size:11px;}

.tab-sidebar li.item{padding:0px;line-height:150%;background:none;margin-bottom:10px;}
.tab-sidebar li.item a,
.tab-sidebar .filetree li a{
	color:maroon;
}

.tab-sidebar li.item a:hover,
.tab-sidebar .filetree li a:hover
{
	color:black;
}

.tab-sidebar li.sub-item a{color:maroon;font-size:small;}
.tab-sidebar li.sub-item a:hover{color:black;}


.filetree{padding:0px;}
.filetree li{overflow:hidden;}

.dictionary-search{margin-bottom:15px;border:1px solid gainsboro;padding:5px;overflow:auto;}
.dictionary-search .search-keywords{width:105px;border:1px solid gainsboro;margin-right:2px;padding:2px;font-size:11px;margin:0px;float:left;display:block;height:15px;}
.dictionary-search .btn-search{border:0px solid gainsboro;background:gainsboro;float:left;display:block;height:21px;outline:0;margin-left:2px;}
.selected{font-weight:bold;}

.var-info-panel{border:2px solid gray;}

/*citations row number*/
.grid-table .row-num{color:gray;font-size:smaller;}

</style>
<script type="text/javascript"> 
   var CI = {
				'base_url': '<?php echo site_url(); ?>',
				'current_section': '<?php echo site_url().'/'.$this->uri->segment(1).'/'.$this->uri->segment(2); ?>',
				'js_loading': '<?php echo t('js_loading'); ?>'  
			}; 	
</script> 

<script type="text/javascript">
$(function(){
	//tree-view 
	$(".filetree").treeview({collapsed: false});
	//$( "#tabs" ).tabs();
	
	$(".tab-sidebar li.item a,.tab-sidebar li.sub-item a").click(function(){
		$('.study-tabs .tab-body').html('<img src="images/loading.gif"/> loading...');
		var hash={
					tab:$("#tabs .ui-tabs-active a").attr("data-id"),
					page:$(this).attr("data-id")
				};
		console.log(hash);		
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
		console.log(fragments);
		
		if(typeof fragments.tab != 'undefined'){
			//$("#from option[value='"+fragments.from+"']").prop('selected', 'selected');
			$("#tabs .ui-tabs-nav a").each(function(){ 
				console.log( $(this).attr("data-id") );
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

<div class="page-body-full" >

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
<div id="tabs" style="" class="ui-tabs ui-widget ui-widget-content ui-corner-all study-tabs" >

	<div class="tab-heading"><?php echo $survey_info;?><a name="tab"></a></div>

  <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" role="tablist" style="background:none;margin-top:-35px;border-bottom:1px solid gainsboro;">
    <li class="ui-state-default ui-corner-top  <?php echo ($tab=='' || $tab=='study-description') ? $active_tab_class : '';?>" role="tab" tabindex="0" aria-controls="tabs-1" aria-labelledby="ui-id-1" aria-selected="true"><a href="<?php echo site_url('catalog/'.$survey_id.'/study-description');?>" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-1" data-id="study-desc">Study Description</a></li>
    <?php if($page_tabs['data_dictionary']>0):?>
	<li class="ui-state-default ui-corner-top <?php echo (in_array($tab,$tab_urls['data_dictionary'])) ? $active_tab_class : '';?>" role="tab" tabindex="-1" aria-controls="tabs-2" aria-labelledby="ui-id-2" aria-selected="false"><a href="<?php echo site_url('catalog/'.$survey_id.'/data_dictionary');?>" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-2" data-id="data-dictionary">Data Dictionary</a></li>
    <?php endif;?>
    <?php if($page_tabs['get_microdata']>0):?>
	<li class="ui-state-default ui-corner-top <?php echo ($tab=='get_microdata') ? $active_tab_class : '';?>" role="tab" tabindex="-1" aria-controls="tabs-23" aria-labelledby="ui-id-23" aria-selected="false"><a href="<?php echo site_url('catalog/'.$survey_id.'/get_microdata');?>" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-23" data-id="get-microdata"><span class="get-microdata"></span>Get Microdata</a></li>
    <?php endif;?>
    <?php if($page_tabs['related_materials']>0):?>
    <li class="ui-state-default ui-corner-top <?php echo ($tab=='related_materials') ? $active_tab_class : '';?>" role="tab" tabindex="-1" aria-controls="tabs-3" aria-labelledby="ui-id-4" aria-selected="false"><a href="<?php echo site_url('catalog/'.$survey_id.'/related_materials');?>" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-3" data-id="related-materials">Related Materials</a></li>
    <?php endif;?>
    <?php if($page_tabs['related_citations']>0):?>
    <li class="ui-state-default ui-corner-top <?php echo ($tab=='related_citations') ? $active_tab_class : '';?>" role="tab" tabindex="-1" aria-controls="tabs-4" aria-labelledby="ui-id-5" aria-selected="false"><a href="<?php echo site_url('catalog/'.$survey_id.'/related_citations');?>" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-35" data-id="related-citations">Citations</a></li>
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
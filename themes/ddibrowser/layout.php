<?php
$enable_rtl=$this->config->item("enable_rtl");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php echo ($enable_rtl===TRUE) ? 'dir="rtl"' : '';?> >
<head> 
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
	<title><?php echo $survey_title;?></title> 
   	<base href="<?php echo js_base_url(); ?>">
    
    <?php if($enable_rtl===TRUE):?>
    <link rel="stylesheet" href="javascript/tree/rtl/jquery.treeview.rtl.css" />
    <link type="text/css" rel="stylesheet" href="themes/ddibrowser/ddi-layout.rtl.css" />
    <?php else:?>
    <link rel="stylesheet" href="javascript/tree/jquery.treeview.css" />
    <link type="text/css" rel="stylesheet" href="themes/ddibrowser/ddi-layout.css" />    
	<?php endif;?>
    
	<link type="text/css" rel="stylesheet" href="themes/ddibrowser/ddi.css" />
    

    <script type="text/javascript" src="javascript/jquery.js"></script>
    <script src="javascript/jquery/ui/ui.core.js"></script>
    <script src="javascript/jquery/ui/ui.draggable.js"></script>
    <script src="javascript/jquery.layout.min-1.2.0.js"></script>
    <script src="javascript/tree/jquery.treeview.pack.js"></script>
    
    <?php if (isset($_styles) ){ echo $_styles;} ?>
    <?php if (isset($_scripts) ){ echo $_scripts;} ?>

	<script type="text/javascript"> 
       var CI = {
	   				'base_url': '<?php echo site_url(); ?>',
					'current_section': '<?php echo site_url().'/'.$this->uri->segment(1).'/'.$this->uri->segment(2); ?>',
					'js_loading': '<?php echo t('js_loading'); ?>'  
	   			}; 	   
    </script> 

	<script> 
	var outerLayout, middleLayout, innerLayout; 
	
	$(document).ready(function () { 
		<?php if ($enable_rtl===TRUE):?>
		outerLayout = $('body').layout({ 
			center__paneSelector:	".outer-center" 
		,	west__paneSelector:		".outer-east" 
		,	east__paneSelector:		".outer-west" 
		,	west__size:				125 
		,	east__size:				200 
		,	spacing_open:			8 // ALL panes
		,	spacing_closed:			12 // ALL panes
		,	north__spacing_open:	6
		,	north__spacing_closed:	12
		//,	south__spacing_open:	0
		,	north__maxSize:			50
		,	south__maxSize:			200
		,	center__onresize:		"middleLayout.resizeAll" 		
		,	north__resizable: 		false
		,	north__slidable:		false
		,	west__slidable: 		false
		,   west__togglerLength_open:         0
		,   north__togglerLength_open:         0
		}); 
		<?php else:?>
		outerLayout = $('body').layout({ 
			center__paneSelector:	".outer-center" 
		,	west__paneSelector:		".outer-west" 
		,	east__paneSelector:		".outer-east" 
		,	west__size:				200 
		,	east__size:				125 
		,	spacing_open:			8 // ALL panes
		,	spacing_closed:			12 // ALL panes
		,	north__spacing_open:	6
		,	north__spacing_closed:	12
		//,	south__spacing_open:	0
		,	north__maxSize:			50
		,	south__maxSize:			200
		,	center__onresize:		"middleLayout.resizeAll" 		
		,	north__resizable: 		false
		,	north__slidable:		false
		,	west__slidable: 		false
		,   west__togglerLength_open:         0
		,   north__togglerLength_open:         0
		}); 
		<?php endif;?>

		middleLayout = $('div.outer-center').layout({ 
			center__paneSelector:	".middle-center" 
		,	west__paneSelector:		".middle-west" 
		,	east__paneSelector:		".middle-east" 
		,	west__size:				100 
		,	east__size:				100 
		,	spacing_open:			8  // ALL panes
		,	spacing_closed:			12 // ALL panes
		,	center__onresize:		"innerLayout.resizeAll" 
		}); 

		innerLayout = $('div.middle-center').layout({ 
			center__paneSelector:	".inner-center" 
		,	west__paneSelector:		".inner-west" 
		,	east__paneSelector:		".inner-east" 
		,	west__size:				75 
		,	east__size:				75 
		,	south__size:			$(window).height()/2 
		,	spacing_open:			8  // ALL panes
		,	spacing_closed:			8  // ALL panes
		,	west__spacing_closed:	12
		,	east__spacing_closed:	12
		,	closable:				true	// pane can open & close
		,	resizable:				true	// when open, pane can be resized 
		,	slidable:				true	// when closed, pane can 'slide' open over other panes - closes on mouse-out
		,   isHidden:               true

		}); 

		//hide variable list by default
		innerLayout.hide("south");
		
		//tree-view 
		$("#browser").treeview({collapsed: true});
				
		$(".ajax").click(function(){get_section($(this).attr("href"));return false;})
		
	}); 
	
	function get_section(url)
	{
		ajax_error_handler("body-content");		
		innerLayout.hide("south");
		$("#body-content").html('<img src="images/loading.gif" border="0"/> '+ CI.js_loading);
		$.get(url+'?ajax=true', 
			function(data){
				$("#body-content").html(data);
				bind_behaviours();
			});
	}
	
	function get_variable(id)
	{
		ajax_error_handler("variable-content");
		url=CI.current_section+'/variable/'+id;
		innerLayout.show("south");
		innerLayout.open("south");
		$("#variable-content").html('<img src="images/loading.gif" border="0"/> '+ CI.js_loading);
		$("#variable-content").load(url+'?ajax=true');
	}
	
	function ajax_error_handler(id)	
	{
		$.ajaxSetup({
			error:function(XHR,e)	{
				$("#"+id).html('<div class="error">'+XHR.responseText+'</div>');
			}				
		});	
	}
	//show/hide resource
	function toggle_resource(element_id){
		$("#"+element_id).toggle();
	}

	
	$(document).ready(function () { 
		bind_behaviours();
	});	
	
	function bind_behaviours() {
		//show variable info by id
		$("#body-content .row-color2, #body-content .row-color1").click(function(){
			if($(this).attr("id")!=''){
				get_variable($(this).attr("id"));
			}
			return false;
		});	
	}
	
	function vsearch(url) {		
		$("#variable-list").html('<img src="images/loading.gif" border="0"/> '+ CI.js_loading);		
		$.get(url,$("#form_vsearch").serialize(), function(data){
			$("#variable-list").html(data);
			bind_behaviours();	
		});		
	}
	
	</script> 
<!--[if IE]>
	<style>
    	#variable-list{width:98%}
    </style>
<![endif]-->

</head> 

<body> 

<div class="outer-center">

	<div class="middle-center">
		
		<div class="inner-center" style="overflow:auto;">
			<div class="ui-layout-content" id="body-content">
			<div style="text-align:right">
                <a target="blank_" href="<?php echo $section_url.'?print=yes';?>"><img alt="Print" src="images/print.gif" border="0"/></a>
                <a target="blank_" href="<?php echo $section_url.'?pdf=yes';?>"><img alt="PDF" src="images/pdf.gif" border="0"/></a>
			</div>
			<?php echo $content; ?>
            </div>
        </div> 
		
		<div class="ui-layout-south" style="overflow:auto;">
			<!-- inner south - variable information -->
			<div class="header"><?php echo t('variable_information');?></div> 
			<div class="ui-layout-content" id="variable-content"><?php echo (isset($variable_contents) ? $variable_contents : '' ); ?></div>
        </div> 
	</div> 

</div> 

<!-- left bar -->
<div class="outer-west" >
    <div class="ui-layout-content" style="overflow:auto;padding:0px;">
		<?php echo isset($sidebar) ? $sidebar : ''; ?>
    </div>
</div> 

<!--header-->
<div class="ui-layout-north"><?php echo $survey_title;?></div> 

</body> 
</html> 
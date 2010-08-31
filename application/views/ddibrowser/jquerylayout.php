<?php
	$contents= "none";//file_get_contents("http://localhost/nada2.1/index.php/ddibrowser/24/overview");
	$contents=str_replace("javascript","x",$contents );
	$contents=str_replace("script","x",$contents );
	
	$variable_contents="none";//substr($contents,4000,600);
	$survey_title='Zambia HIV/AIDS Service Provision Assessment Survey 2005, Health Facility - [ZMB-CSO-ZSPA-2009-v1.0]';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head> 
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
	<title>DDI Metadata Browser</title> 

    <link rel="stylesheet" href="/nada2.1/javascript/tree/jquery.treeview.css" />
    <script src="/nada2.1/javascript/jquery.js"></script>
    <script src="/nada2.1/javascript/jquery/ui/ui.core.js"></script>
    <script src="/nada2.1/javascript/jquery/ui/ui.draggable.js"></script>
    <script src="/nada2.1/javascript/jquery.layout.min-1.2.0.js"></script>
    <script src="/nada2.1/javascript/tree/jquery.treeview.pack.js"></script>

	<script> 
	var outerLayout, middleLayout, innerLayout; 
	
	$(document).ready(function () { 
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
		}); 

		//tree-view 
		$("#browser").treeview();
	}); 
	</script> 

<style type="text/css"> 

/*pane-header*/
.header { 
	background:#333333 url(../img/80ade5_40x100_textures_04_highlight_hard_100.png) 0 50% repeat-x;
	border-bottom: 1px solid #777;
	font-weight: bold;
	text-align: left;
	padding: 5px;
	position: relative;
	overflow: hidden;
	color:white;
}

.ui-layout-pane { /* all 'panes' */ 
	padding:		0px; 
	background:		#FFF; 
	border-top:		1px solid #BBB;
	border-bottom:	1px solid #BBB;
	overflow:		hidden;
	}
.ui-layout-pane-north ,
.ui-layout-pane-south {
	border: 1px solid #BBB;
} 
.ui-layout-pane-west {
	border: 1px solid #BBB;
} 
.ui-layout-pane-east {
	border-right: 1px solid #BBB;
} 

/*.ui-layout-pane-center {
	border-left:  0;
	border-right: 0;
	} 
*/	
.inner-center {
	border: 1px solid #BBB;
} 

/*
.middle-west ,
.middle-east {
	background-color: #F8F8F8;
}
*/
.ui-layout-resizer { /* all 'resizer-bars' */ 
	background: #EDF5FF; 
}
.ui-layout-resizer:hover { /* all 'resizer-bars' */ 
	background: white; 
}

.ui-layout-toggler { /* all 'toggler-buttons' */ 
	background: #AAA; 
} 
.ui-layout-toggler:hover { /* all 'toggler-buttons' */ 
	background: #FC3; 
} 

.outer-center ,
.middle-center {
	/* center pane that are 'containers' for a nested layout */ 
	padding: 0; 
	border: 0; 
} 
.ui-layout-content{padding:10px;}

/*left side bar*/
.left-bar-section{padding:5px;border-bottom:1px solid #BBB;}
.left-bar-section .menu-item{ list-style:none;}

/* page header bar*/
.ui-layout-north {background-color:#CFD9FE;color:black;font-size:14px;padding:10px;font-weight:bold;border:0px}
</style> 

</head> 

<body> 

<div class="outer-center">

	<div class="middle-center">
		
		<div class="inner-center" style="overflow:auto;">
			<div class="ui-layout-content" id="body-content"><?php echo $contents; ?></div>
        </div> 
		
		<div class="ui-layout-south" style="overflow:auto;">
			<!-- inner south - variable information -->
			<div class="header">Variable information</div> 
			<div class="ui-layout-content" id="variable-content"><?php echo $variable_contents; ?></div>
        </div> 

	</div> 

</div> 

<!-- left bar -->
<div class="outer-west" >
    <div class="ui-layout-content" style="overflow:auto;padding:0px;">
    
    <div class="left-bar-section">
    	<?php echo anchor('ddibrowser/1/overview','Overview'); ?>
    </div>

    <div class="left-bar-section">
    	<div>Technical Information</div>
	
    <ul class="menu-item" style="border:0px;padding:0px;margin:0px;"> 
            	<li><a id="overview" href="?section=sampling&id=84" onClick="showSection('sampling');return false;">Sampling</a></li> 
                <li><a id="overview" href="?section=questionnaire&id=84" onClick="showSection('questionnaires');return false;">Questionnaires</a></li> 
                <li><a id="overview" href="?section=datacollection&id=84" onClick="showSection('datacollection');return false;">Data Collection</a></li> 
                <li><a id="overview" href="?section=dataprocessing&id=84" onClick="showSection('dataprocessing');return false;">Data Processing</a></li> 
                <li><a id="overview" href="?section=dataappraisal&id=84" onClick="showSection('dataappraisal');return false;">Data Appraisal</a></li> 
                            </ul> 	

    </div>
    
    <div class="left-bar-section">
    	Datasets
    </div>    
    
    <ul id="browser" class="left-bar-section filetree treeview-famfamfam" style="padding:5px;">
		<li><span class="folder">Folder 1</span>
			<ul>
				<li><span class="folder">Item 1.1</span>
					<ul>

						<li><span class="file">Item 1.1.1</span></li>
					</ul>
				</li>
				<li><span class="folder">Folder 2</span>
					<ul>
						<li><span class="folder">Subfolder 2.1</span>
							<ul id="folder21">

								<li><span class="file">File 2.1.1</span></li>
								<li><span class="file">File 2.1.2</span></li>
							</ul>
						</li>
						<li><span class="folder">Subfolder 2.2</span>
							<ul>
								<li><span class="file">File 2.2.1</span></li>

								<li><span class="file">File 2.2.2</span></li>
							</ul>
						</li>
					</ul>
				</li>
				<li class="closed"><span class="folder">Folder 3 (closed at start)</span>
					<ul>
						<li><span class="file">File 3.1</span></li>

					</ul>
				</li>
				<li><span class="file">File 4</span></li>
			</ul>
		</li>
	</ul>

    <div class="left-bar-section">
    	Access Policy
    </div>

    <div class="left-bar-section">
    	Variable Search
    </div>


    </div>
</div> 

<!--header-->
<div class="ui-layout-north"><?php echo $survey_title;?></div> 


</body> 
</html> 
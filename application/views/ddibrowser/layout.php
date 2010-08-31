<?php
header('Content-Type: text/html; charset=utf-8');	
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

function getmsg($str)
{
	return $str;
}
$surveyid=1;
$page_content='page contente';

$yui='http://localhost/nada2/javascript/yui';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>put SURVEY-TITLE and ID here</title>

<link rel="stylesheet" type="text/css" href="<?php echo $yui; ?>/fonts/fonts-min.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $yui; ?>/resize/assets/skins/sam/resize.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $yui; ?>/layout/assets/skins/sam/layout.css" />
<link type="text/css" rel="stylesheet" href="<?php echo $yui; ?>/treeview/assets/skins/sam/treeview.css">

<script type="text/javascript" src="<?php echo $yui; ?>/utilities/utilities.js"></script>
<script type="text/javascript" src="<?php echo $yui; ?>/container/container_core-min.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/resize/resize-min.js"></script>
<script type="text/javascript" src="<?php echo $yui; ?>/layout/layout-min.js"></script>
<script type="text/javascript" src="<?php echo $yui; ?>/yahoo-dom-event/yahoo-dom-event.js"></script>
<script type="text/javascript" src="<?php echo $yui; ?>/connection/connection-min.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/treeview/treeview-min.js"></script>
<script type="text/javascript" src="http://localhost/nada2/ddibrowser/js/rsh/json2007.js"></script>
<script type="text/javascript" src="http://localhost/nada2/ddibrowser/js/rsh/rsh.compressed.js"></script>

<style type="text/css">
	body {
		margin:0;
		padding:0;overflow:hidden;
	}
	#top1{background-color:#CFD9FE;border-width:0px;color:#000000;padding:5px;font-weight:normal;font-size:14px;}
	#center1, #center1 td, p, table, tr, th, h1, h2, h3, div, span, a{font-family:arial;font-size:12px;}

	#center1,#bottom1{padding:10px;}
	/* Style the body */
	.yui-skin-sam .yui-layout .yui-layout-unit div.yui-layout-bd {
		border: 1px solid silver;
		border-bottom: none;
		border-top: none;
		*border-bottom-width: 0;
		*border-top-width: 0;
		background-color: white;
		text-align: left;
	}
	/* Add a border to the bottom of the body because there is no footer */
	.yui-skin-sam .yui-layout .yui-layout-unit div.yui-layout-bd-noft {
		border-bottom: 1px solid silver;
	}
	/* Add a border to the top of the body because there is no header */
	.yui-skin-sam .yui-layout .yui-layout-unit div.yui-layout-bd-nohd {
		border-top: 1px solid silver;
	}
	#center1 table td{}
	#yui-history-iframe {
	  position:absolute;
	  top:0; left:0;
	  width:1px; height:1px;
	  visibility:hidden;
	}
	/* Set the background color */
	.yui-skin-sam .yui-layout .yui-layout-unit-bottom h2{
		background-color:#666666;padding:0px;margin:0px;
	}
	/* summary .xslt*/
	 td,th, table, p, body{font-family:arial;font-size:12px;}
	.table1{border-collapse: collapse;padding:0px;margin-bottom:20px;width:100%;}
	.table1 td{border:1px solid black;padding:5px;}
	.th1{background:gainsboro;border:1px solid black;font-size:12px;padding:5px;}
	.th2{background:silver;border:1px solid black;font-size:20px;padding:5px;margin-bottom:10px;}					
	.h5{text-decoration: underline;display:block} 
	.survey-title{font-weight:normal;font-size:18px;border-bottom:1px solid gray;margin-top:15px;}
	
	.menu-item{padding:5px;border-bottom:1px solid silver}
	.menu-item a, .menu-item a:visited{text-decoration:none;color:#CC6600 }
	.menu-item a:hover{color:red;}
	.menu-item a:active{color:blue;}
	.menu-item ul {
			list-style: none;
			margin: 0;
			padding: 0;
			border: none;
			}
	.menu-item li {
			border-bottom: 0px solid #90bade;border-left:0px solid blue;margin:0px;
			padding:0px;
			margin: 0;
			background-image:url(../images/table.png); background-repeat:no-repeat; background-position:left top;
			padding-bottom:6px;
			margin-left:15px;
			}
	.menu-item li a{margin-left:25px;border:0px solid red;display:block;padding:0px;}

	/* summary */
	.row-caption{font-weight:bold;margin-top:10px}
	.row-text{margin-bottom:4px;}
	.datafile-row td{padding:5px;background-color:ghostwhite;border:2px solid white;}
	.datafile-row-th td{padding:5px;background-color:gray;border:2px solid white;font-weight:bold;color:white}
	
	/* overview styles */
	.xsl-title{font-size:18px;color:#CC6600;font-weight:bold;margin-bottom:20px;}
	.xsl-subtitle{font-size:16px;color:#CC6600;font-weight:bold;margin-top:15px;border-bottom:1px solid #CC6600;}
	.xsl-table {border:0px;}
	.xsl-caption{font-weight:bold;color:#CC6600;white-space:nowrap;margin-top:10px;}
	.xsl-block{margin-top:5px;margin-bottom:5px}
	
	/*sampling */
	.sampling-field-caption{font-weight:bold;color:#CC6600;font-size:14px;margin-top:15px;}
	/* fixed size box to replace textarea display */
	.textarea{overflow:auto;height:200px;margin-top:4px;margin-bottom:3px;padding:5px;border:1px solid gainsboro;background-color:white;}
	
	/*data file*/
	.table-variable-list td{padding:5px;}
	.data-file-bg1{background-color:#E6E6E6;padding:5px;}
	.var-th td{background-color:#999999;font-weight:bold;color:white;}
	.row-color1{background-color:#EBEBEB}
	.row-color2{background-color:ghostwhite;}
	.var-td{padding:2px;}
	.yui-skin-sam{overflow:hidden}
	.var-row, .var-row a{}
	.var-row-selected, .var-row-selected a{background-color:#DFEEFF}
	.row-mouseover{background-color:#DFEEFF;}
	.var-link {color:black;text-decoration:none;}
	.data-collection, .data-collection  td, .data-collection table td, .data-collection p{padding:0px;margin:0px;}
	.data-collection td{border-bottom:1px solid grainsboro;}

</style>

<!--[if IE]>
    <style>
    #variable-list{width:98%;}
    .variable, .variable table{width:98%}
    .xmenu-item li a{margin-left:-5px;}
    .data-collection{width:98%;}
    </style>
<![endif]-->
</head>

<body class="yui-skin-sam" >
<iframe id="yui-history-iframe" src="path-to-existing-asset"></iframe>
<input id="yui-history-field" type="hidden">
<div id="logwin"></div>
<div id="top1" style="font-weight:bold;">title-refno<?php //echo ($surveyrow["titl"]). ' ['.$surveyrow["refno"].']'; ?></div>
<div id="left1">
    <div id="productsandservices2" class="yuimenu">
    <div >
    <?php 	
		//$mnu_datafiles=str_replace('<?xml version="1.0" encoding="UTF-8"? >','',getDatafilesList($surveyfilepath));
		//$mnu_datafiles=str_replace('php-survey-id',$surveyid, $mnu_datafiles);
		//include ("menu.php"); 			
		echo 'menu here';
	?>
    </div>
</div>
</div>
<div id="center1">
	<?php		
			echo $page_content;
		?>
</div>
<div id="bottom1">&nbsp;</div>

<script type="text/javascript">	
	var surveyid=1;//<?php echo $surveyid;?>;
	function ajax(url,elem,success_act,failure_act) {
		//hide variable info panel, if visible
		if (layout2.getUnitByPosition("bottom") !=false){
			layout2.getUnitByPosition("bottom").close();
		}
		document.getElementById(elem).innerHTML='<img src="../images/loading.gif" border="0"/>loading...';
		var callback =
		{
			success: function(o) { 
								document.getElementById(elem).innerHTML=o.responseText;eval(success_act);set_var_row_event_handlers();
								},
			failure: function(o) {document.getElementById(elem).innerHTML='<div style="background:beige;padding:10px">Error occured: '+o.responseText+'</div>';},
			timeout: 95000,
			argument: [ ]
		}
		if(url != undefined) {
			YAHOO.util.Connect.asyncRequest('GET', url , callback, null);
		}
	}
	
	var var_list_scroll_up=true;//indicates whether to scroll list into view or not
	
	function searchvariable(){
		varname=document.getElementById("chk_name").checked;
		varlabel=document.getElementById("chk_label").checked;
		varcat=document.getElementById("chk_cat").checked;
		varquestion=document.getElementById("chk_question").checked;
		ss=document.getElementById("var-ss").value;
		
		url='search.php?id=<?php echo $surveyid; ?>&search=true';
		if (varname==true){
			url+='&varname='+ ss;
		}
		if (varlabel==true){
			url+='&varlabel='+ ss;
		}
		if (varquestion==true){
			url+='&varquestion='+ ss;
		}
		if (varcat==true){
			url+='&varcat='+ ss;
		}
		ajax(url,'search-result',null,null);
	}
	//load variable info, show in variable panel
	function showVariable(el){
		hightlight_var_row(el);		
		showVariablePanel();
		document.getElementById('bottom1').innerHTML='<img src="../images/loading.gif" border="0"/>loading...';
		url='getsection.php?id=<?php echo $surveyid; ?>&section=variable&varid='+el.id;
		var callback =
		{
			success: function(o) { 
								document.getElementById('bottom1').innerHTML=o.responseText;
								},
			failure: function(o) {
								document.getElementById('bottom1').innerHTML=o.responseText;;
								},
			timeout: 95000,
			argument: [ ]
		}
		if(url != undefined) {
			YAHOO.util.Connect.asyncRequest('GET', url , callback, null);
		}		
	}	
	
	//high lights the variable row [var-search page/ data files]
	function hightlight_var_row(el){
		//first, remove any hightlighted rows
		var elements = YAHOO.util.Dom.getElementsByClassName('var-row-selected', 'tr'); 
		YAHOO.util.Dom.removeClass(elements, 'var-row-selected');
		YAHOO.util.Dom.addClass(elements, 'var-row');
		//highlight the row clicked
		YAHOO.util.Dom.removeClass(el, 'var-row');
		YAHOO.util.Dom.addClass(el, 'var-row-selected');
		if 	(var_list_scroll_up==true){
			YAHOO.util.Dom.get(el).scrollIntoView();//scrolls the variable list up
			var_list_scroll_up=false;
		}
	}
</script>
<script>

var layout;
var layout2;
(function() {
    var Dom = YAHOO.util.Dom,
        Event = YAHOO.util.Event;

    Event.onDOMReady(function() {
       render_layout();
    });
})();

var variable_panel;
function render_layout(){
 layout = new YAHOO.widget.Layout({
            units: [
                { position: 'top', height: 28, body: 'top1', scroll: null, zIndex: 2 },
                { position: 'left', width: 220, body: 'left1', resize:true, gutter: '5', scroll: true, zIndex: 1 },
                { position: 'center', gutter: '3 0', scroll:true }
            ]
        });
        layout.on('render', function() {
		showDataDictionary();
            var el = layout.getUnitByPosition('center').get('wrap');
			var var_panel_height=YAHOO.util.Dom.getViewportHeight();
			var_panel_height=(var_panel_height/2)-50;
            layout2 = new YAHOO.widget.Layout(el, {
                parent: layout,
                minWidth: 400,
                minHeight: var_panel_height,
                units: [
                    { position: 'center', body: 'center1', gutter: '2px', scroll: true },
					//{ position: 'bottom', header: 'Variable Information', height: 200, resize: true, body: 'bottom1', gutter: '5 2 1 2', collapse: true, maxHeight: 800 }
                ]
            });
            layout2.render();
			variable_panel=layout2.addUnit(		{ position: 'bottom', header: '<?php print getmsg('Variable Information');?>', height: var_panel_height, resize: true, body: 'bottom1', gutter: '5 2 1 2', collapse: true, maxHeight: 800 })
			variable_panel.close();

        });
        layout.render();
}
//show variable info panel
function showVariablePanel(){
	var var_panel_height=YAHOO.util.Dom.getViewportHeight();
	var_panel_height=(var_panel_height/2)-50;
	variable_panel=layout2.addUnit(		{ position: 'bottom', header: '<?php print getmsg('Variable Information');?>', height: var_panel_height, resize: true, body: 'bottom1', gutter: '5 2 1 2', collapse: true, maxHeight: 800 })
}

//history management
window.dhtmlHistory.create();
window.onload = function() {        
	dhtmlHistory.initialize();      
	dhtmlHistory.addListener(historyChange);//Attach a history change listener
};

//load data based on the url/bookmark changes
function historyChange(newLocation, historyData) {
	var historyMsg = (typeof historyData == "object" && historyData != null
	? historyStorage.toJSON(historyData)
	: historyData
	);
	//var msg = "<b>A history change has occured:</b> | newLocation=" + newLocation + " | historyData=" + historyMsg + " |";
	//log(msg);
	//alert("historychange event" + newLocation.explode("&")[0] );
	if (newLocation.explode("&")[0]=='datafile'){
		file=newLocation.explode("&")[1];
		file=file.explode("=")[1];
		showSection('datafile',0,file);
	}	
	else{
		showSection(newLocation,0);
	}
	if (historyData!=null){
	//	alert(historyData);
	}
};
//show sections from history
function showSection(section, addhistory, file){
	if (addhistory!=0){
		location_=section;
		if (section=='datafile'){
			location_+="&file="+file;
		}
		dhtmlHistory.add(location_,"");
	}	
	url="getsection.php?section="+section+"&id="+surveyid;
	if (file){
		url+="&file="+file;
	}
	ajax(url,"center1",null,null);		
}

//show/hide description,abstract, and toc content
function toggle_info(el){
	if (el.checked==true){
		YAHOO.util.Dom.setStyle(el.id+'_div', 'display', 'block'); 
	}
	else{
		YAHOO.util.Dom.setStyle(el.id+'_div', 'display', 'none'); 
	}
}
//search box , on key press event handler
function search_keypress(e){
	var code;
	if (!e) var e = window.event;
	if (e.keyCode) code = e.keyCode;
	else if (e.which) code = e.which;
	var character = String.fromCharCode(code);
	if (code == 13){
	javascript:searchvariable();return false;
	}
}	
function showDataDictionary(){
//instantiate the TreeView control:
var tree = new YAHOO.widget.TreeView("treeDiv1");

//render/show tree
tree.render();
}

function printwindow(params){
	print_window= window.open ("print.php"+params,  "ddi_print","status=1,width=700,height=600,resizable=1,scrollbars=1"); 
	print_window.focus();
	return false;
}

//hightlight survey rows on mouse-over
function onVariableMouseOver(e) { 
	YAHOO.util.Dom.addClass(this.id, 'row-mouseover'); 
}
function onVariableMouseOut(e) { 
	YAHOO.util.Dom.removeClass(this.id, 'row-mouseover'); 
}

//set row hover
function set_var_row_event_handlers(){		
       _set_var_row_event_handlers('row-color1');
	   _set_var_row_event_handlers('row-color2');
	   _set_var_row_event_handlers('var-row');	   
}
function _set_var_row_event_handlers(classname){
	var elements = YAHOO.util.Dom.getElementsByClassName(classname, 'tr'); 
	YAHOO.util.Event.addListener(elements, "mouseover", onVariableMouseOver);
	YAHOO.util.Event.addListener(elements, "mouseout", onVariableMouseOut);	
}


</script>
</body>
</html>
<?php 
//returns a list of data files
function getDatafiles($xml){
		$xslt=DDI_BROWSER_PATH."/xslt/datafiles.xslt";//xslt file
		$parameters=array('lang'=>LANGUAGE);		
		return xslTransform($xml,$xslt,$parameters);
}		

//returns datafiles UL/LI list for left menu inclusion
function getDatafilesList($xml){
	$xslt=DDI_BROWSER_PATH."/xslt/mnu_datafiles.xslt";//xslt file				
	$parameters=array('lang'=>LANGUAGE);
	return xslTransform($xml,$xslt,$parameters);
}

?>
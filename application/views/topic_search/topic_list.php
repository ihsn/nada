<style>

.button {	
	border:1px solid gainsboro;
	text-decoration:none;
	color:gray;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
}
	.topic-container {
	border: 1px solid gainsboro;
	overflow: hidden;
	width: 100%;
	margin-bottom:10px;
	background-color:#EBEBEB;
	padding-top:10px;
	padding-bottom:10px;
	font-size:12px;
}

x.topic-container ul,.xtopic-container li{list-style:none;display:block;}
div.left {
	width: 45%;
	float: left;
	margin-right:5px;
	margin-bottom:5px;
	margin-left:5px;
}

div.right {
	width: 45%;
	float: right;
}

/* tabs start */
#tabmenu {
		color: gray;
		border-bottom: 1px solid gainsboro;
		margin: 12px 0px 0px 0px;
		padding: 0px;
		z-index: 1;
		padding-left: 10px }

	#tabmenu li {
		display: inline;
		overflow: hidden;
		list-style-type: none; }

	#tabmenu a, a.active {
		color: red;
		background: gainsboro;
		font: normal 1em "Trebuchet MS", Arial, sans-serif;
		border: 1px solid gainsboro;
		padding: 2px 5px 0px 5px;
		margin: 0;
		text-decoration: none; }

	#tabmenu a.active {
		background: #EBEBEB;
		color:green;
		border-bottom: 3px solid #F3F3F3; }

	#tabmenu a:hover {
		color: #fff;
		background: #ADC09F; }

	#tabmenu a:visited {
		color:#666666 }

	#tabmenu a.active:hover {
		background: #ABAD85;
		color: orange; }

	#tab-contents {font: 0.9em/1.3em "bitstream vera sans", verdana, sans-serif;
		text-align: justify;
		background: #F3F3F3;
		padding: 20px;
		border: 1px solid gainsboro;
		border-top: none;
		z-index: 2;	}

	#tab-contents a {
		text-decoration: none;
		color: #E8E9BE; }

	#tab-contents a:hover { background-color:#F3F3F3 }
/* tabs end */

.topic-heading{background-color:gainsboro;padding:5px;}
.topic-items{background:white;display:inline;list-style:none;padding:0px;margin:0px;}
.topic-items li{padding:5px;margin-left:10px;}
.topic{}
</style>

<form name="topic_search_form" id="topic_search_form" method="post" >
<ul id="tabmenu">
	<li><a href="#">Project</a></li>
    <li class="selected"><a href="#">studies</a></li>
    <li><a href="#">variables</a></li>
    <li><a class="active" href="#">topics</a></li>
</ul>
<div class="topic-container">	
	<?php echo create_topic_list($topics);?>
	<br style="clear:both"/>
	<div style="float:right;margin-right:10px;">
    	<input class="button" style="padding:5px;border:0px solid silver; background-color:#0099FF;color:white;" type="button" name="filter" value="Search" onclick="topic_search();return false;"/>
        <input class="button" style="border:0px solid silver; background-color:none;" type="button" name="reset" value="Reset"/>
    </div>
</div>
<div id="surveys"><?php echo $survey_list; ?></div>
    </form>



<?php
	function create_topic_list($data)
	{
		/*$result=array();
		foreach($data as $row)
		{
			$row=(object)$row;

			$result[$row->pid][$row->tid]=$row->title. "({$row->surveys_found})";
			
			if ($row->pid==0)
			{
			
			}
		}
		
		echo '<pre>';
		print_r($result);
	*/
		$result='';
		foreach($data as $key=>$row)
		{
			$row=(object)$row;
			if (!isset($row->used) && $row->pid==0){
				$result.='<div class="left">';
				$result.= '<div class="topic-heading button"><input name="id[]" type="checkbox" value="'.$row->tid.'"/>'.$row->title. " ({$row->surveys_found})".'</div>';
				$data[$key]['used']=true;
				$result.=process_sub_item($data,$row->tid);
				$result.='</div>';
			}			
		}
		return $result;
//		print_r($data);	
	}

	//show childrent of tid
	function process_sub_item(&$data,$tid)
	{
		$result='<ul class="topic-items">';
		foreach($data as $key=>$row)
		{
			$row=(object)$row;
			
//						var_dump($row);
			if (isset($row->pid))
			{
				if ($row->pid==$tid && (!isset($row->used)) )
				{
					//var_dump($data[$key]);
					//echo "\t".$row->tid."\r\n";
					$result.='<li class="topic"><input name="id[]" type="checkbox" value="'.$row->tid.'"/>'.$row->title. " ({$row->surveys_found})".'</li>';
					$data[$key]['used']=true;
					//echo 'processing tid=['.$row->tid.'] and pid=['.$row->pid.']--------------';
					//echo $key;
					//var_dump($data[$key]);
					//process_sub_item($data,$tid);
				}	
			}
		}
		$result.= '</ul>';
		return $result;
	}
?>

<script type="text/javascript">
function topic_search(){
 	data=$("#topic_search_form").serialize();
	$.ajax({
        type: "GET",
        url: CI.base_url+"/topic_search/search",
        data: data,
        cache: false,
		timeout:20000,
		success: function(data) {
            $('#surveys').html(data);
			//bindBehaviors(this);
        },
		error: function(XMLHttpRequest, textStatus, errorThrow) {
			$('#surveys').html('<div class="error">Search failed<br>'+XMLHttpRequest.responseText+'</div>');
        }		
    });
    return false;        
}


$(document).ready(function() 
{	
	//click handler to start search via checkbox	
	$("#topic_search_form :checkbox").click(function() {
    	topic_search();
	});

});

</script>
<style type="text/css">
body{font-family:Verdana, Arial, Helvetica, sans-serif}
.survey-info{font-size:.7em;margin-bottom:10px;}
.compare-header{padding:5px;}
.compare-header div {float:left;}
.remove {color:white;}
.error{font-size:16px;color:red;padding:10px;margin:10px;}
</style>

<script type="text/javascript">
$(function() {
	//compare variables
	$('.remove').click(function(event) {
			var id=$(this).attr("id");
			update_compare_variable_list('remove',id);
			$(this).parents("td").remove();
			if(opener.document!=null){
				//$(".compare[value='"+id+"']",opener.document).attr("checked",false);
				$(".compare[value='"+id+"']",opener.document).trigger("click");
			}
			return false;
	});
});

function remove_all(){
	update_compare_variable_list('remove-all',0);
	
	//remove form the parent/search page
	$("#surveys .compare",opener.document).attr("checked",false);
	window.location.reload();
}

function update_compare_variable_list(action,value){
	var sel_items=readCookie("variable-compare");
	
	if(sel_items==null){
		sel_items=Array();
	}
	else{
		sel_items=sel_items.split(",");
	}

	switch(action)
	{
		case 'add':
			if($.inArray(value, sel_items)==-1){
				sel_items.push(value);
			}
			break;
		
		case 'remove':
			var index_matched=$.inArray(value, sel_items);
			if(index_matched>0){
				sel_items.splice(index_matched,1);
			}			
			break;
		
		case 'remove-all':
			eraseCookie("variable-compare");return;
		break;
	}

	//update cookie
	createCookie("variable-compare",sel_items,1);
}
function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}
</script>
</head>
<body style="margin:0px;padding:0px;">

<?php if ($list): ?>
<div class="compare-header">
	<div class="xsl-title" style="margin:0px;"><?php echo t('title_compare_variables');?></div>
    <div style="padding-top:5px;padding-left:20px;">
    	<a href="<?php echo current_url(); ?>" onClick="window.location.reload();return false;"><img src="images/arrow_reorder.png" border="0"/> <?php echo t('refresh');?></a> | 
        <a href="<?php echo current_url(); ?>#clear" onClick="remove_all();return false;" title="Clear selection of variables to be compared"><img src="images/bin_closed.png" border="0"/> <?php echo t('clear');?></a> | 
		<?php echo anchor('catalog/compare/print/pdf','<img src="images/acrobat.png" border="0"/> '. t('download_pdf'), array('target'=>'_blank'));?>
    </div>
    <br style="clear:both" />
</div>    
<?php $tr_class=""; ?>
	<table class="draggable" cellpadding="0" cellspacing="5" >
	    <tr  class="<?php echo $tr_class; ?>" valign="top">
		<?php foreach($list as $item):?>
    		<td>
				<?php $survey_title=$this->compare_variable->get_survey_title($item['surveyid']);?>
                <?php $variable_name=$this->compare_variable->get_variable_name($item['surveyid'],$item['varid']);?>
                <?php if ($survey_title!==FALSE && $variable_name!==FALSE):?>
            	<div style="border:1px solid gray;margin:5px;">
	            	<div style="background-color:gray;color:white;padding:5px;cursor:move" title="<?php echo t('click_drag_move');?>">
						<div style="float:left;font-weight:bold;"><?php echo $variable_name;?></div>
                        <div style="float:right;font-size:11px;">
							<?php echo anchor('catalog/compare/#remove='.$item['surveyid'].'/'.$item['varid'],t('remove'),array('class'=>'remove','title'=>t('remove'),'id'=>$item['surveyid'].'/'.$item['varid']));?>
                            </div>
                        <br style="clear:both"/>
                     </div>
    	        	<div style="padding:5px;width:350px;">                        
                            <div class="survey-info"><?php echo anchor("ddibrowser/".$item['surveyid'],$this->compare_variable->get_survey_title($item['surveyid']),array('target'=>'_blank'));?></div
                            ><?php echo $this->compare_variable->get_variable_html($item['surveyid'], $item['varid']);?>
                    </div>
				</div>
				<?php endif;?>                
            </td>
		<?php endforeach;?>
    </tr>
	</table>
<?php else: ?>
	<div class="error">
	<?php echo t('no_variables_to_compare');?>
    </div>
<?php endif; ?>
</body>
</html>
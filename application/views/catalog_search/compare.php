<?php
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header("Pragma: no-cache");
?>
<html>
<head>
	<title><?php echo t('title_compare_variables');?></title>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
    <base href="<?php echo js_base_url(); ?>"/>
   	<link type="text/css" rel="stylesheet" href="themes/ddibrowser/ddi.css" />
    <script type="text/javascript" src="javascript/jquery-1.5.1.min.js"></script>
    <script type="text/javascript" src="javascript/dragtable.js"></script>
    <style>
		body,html,td{font-family:Arial, Helvetica, sans-serif;font-size:.8em;}
		.survey-info{font-size:.7em;margin-bottom:10px;}
		.compare-header{padding:5px;}
		.compare-header div {float:left;}
		.remove {color:white;}
		.error{font-size:16px;color:red;padding:10px;margin:10px;}
	</style>
	<script type="text/javascript"> 
       var CI = {'base_url': '<?php echo site_url(); ?>'}; 	   
    </script> 

<script type="text/javascript">
$(function() {
	//compare variables
	$('.remove').click(function(event) {
			
			var id=$(this).attr("id");

			//remove from the parent page
			$(".compare:[value='"+id+"']",opener.document).attr("checked",false);
			//$("#surveys",window.parent.document).(".compare:[value="+id+"]").attr("checked",false);
			//parent.$("#surveys .compare").attr("checked",false);			
			
			$.get($(this).attr("href"));
			$(this).parents("td").remove();
			return false;
	});
});	
function remove_all(){
	$.get(CI.base_url+'/catalog/compare_remove_all', function(data){window.location.reload();});
	
	//remove form the parent/search page
	$("#surveys .compare",opener.document).attr("checked",false);
	
	//close the popup
	window.close();return false;	
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
		<?php echo anchor('catalog/compare',t('open_in_new_window'), array('target'=>'_blank'));?> | 
		<?php echo anchor('catalog/compare/print','<img src="images/print.gif" border="0" /> '. t('print'), array('target'=>'_blank'));?> | 
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
							<?php echo anchor('catalog/compare_remove/'.$item['surveyid'].'/'.$item['varid'],t('remove'),array('class'=>'remove','title'=>t('remove'),'id'=>$item['surveyid'].'/'.$item['varid']));?>
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
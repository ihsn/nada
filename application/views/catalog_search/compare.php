<style type="text/css">
.var-compare-container .compare-box .survey-link{margin-bottom:10px;}
.var-compare-container .compare-box .variable-content{}
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
	//$("#surveys .compare",opener.document).attr("checked",false);
	$("#surveys .compare",opener.document).prop( "checked", false );
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


<div class="var-compare-container">

<?php if (!$list): ?>
	<div class="error"><?php echo t('no_variables_to_compare');?></div>
<?php endif;?>

<div class="compare-header clearfix">
	<h1 class="title float-left" ><?php echo t('title_compare_variables');?></h1>
    <div class="action-bar float-right">
		<a href="<?php echo current_url(); ?>" onClick="window.location.reload();return false;" class="refresh btn btn-outline-primary btn-sm">
			<i class="fa fa-refresh" aria-hidden="true"></i><?php echo t('refresh');?>
		</a> 
		<a href="<?php echo current_url(); ?>#clear" onClick="remove_all();return false;" title="Clear selection of variables to be compared" class="clear btn btn-outline-primary btn-sm">
			<i class="fa fa-trash" aria-hidden="true"></i> <?php echo t('clear');?>
		</a> 

		<a href="<?php echo site_url('catalog/compare/print/pdf'); ?>"  class="download btn btn-outline-primary btn-sm">
			<i class="fa fa-file-pdf-o" aria-hidden="true"></i> <?php echo t('download_pdf');?>
		</a> 

		<a href="<?php echo site_url('catalog/compare/export/csv'); ?>"  class="download btn btn-outline-primary btn-sm">
			<i class="fa fa-file-excel-o" aria-hidden="true"></i> <?php echo t('download_csv');?>
		</a> 

		<a href="<?php echo site_url('catalog/compare/export/json'); ?>"  class="download btn btn-outline-primary btn-sm">
		<i class="fa fa-file-code-o" aria-hidden="true"></i> <?php echo t('download_json');?>
		</a> 

    </div>
</div>    
  
	<?php $tr_class=""; ?>
	<table class="draggable" cellpadding="0" cellspacing="5" >
	    <tr  class="<?php echo $tr_class; ?>" valign="top">
		<?php foreach($list as $item):?>
    		<td><?php //var_dump($item['variable']);?>
				<?php $survey_title=$item['dataset']['title'];?>
                <?php $variable_name=$item['variable']['name'];?>
                <?php if ($survey_title!==FALSE && $variable_name!==FALSE):?>
            	<div class="compare-box" >
	            	<div class="compare-box-title" title="<?php echo t('click_drag_move');?>">
						<div class="var-name" ><?php echo $variable_name;?></div>
                        <div class="var-links" >
							<?php echo anchor('catalog/compare/#remove='.$item['sid'].'/'.$item['vid'],t('remove'),array('class'=>'remove btn btn-outline-primary btn-sm','title'=>t('remove'),'id'=>$item['sid'].'/'.$item['vid']));?>
                        </div>
                        <br style="clear:both"/>
                     </div>
    	        	<div class="compare-box-body" >
                            <div class="survey-link">
								<?php echo anchor("catalog/".$item['sid'],$survey_title,array('target'=>'_blank'));?>
                            </div>
                            <div class="variable-content">
							<?php echo $item['html']; //echo $this->compare_variable->get_variable_html($item['surveyid'], $item['vid']);?>
                            </div>
                    </div>
				</div>
				<?php endif;?>                
            </td>
		<?php endforeach;?>
    </tr>
	</table>
</div> <!--end var-compare-container-->

<style>
.survey-row{font-size:12px; line-height:140%;border-bottom:1px solid gainsboro;padding:5px;;}
.survey-row:hover,
.survey-active{
	background:gainsboro;
}

.survey-row .title{font-weight:bold;}
.survey-row .sub{}

.survey-request-downloads{width:100%;border:0px solid gainsboro;}
.survey-request-downloads tr td{vertical-align:top;}
.survey-resources{padding:10px;}
.resources-container{border:0px solid gainsboro;border-left:0px;}
.survey-resources .survey-info{margin-bottom:15px;}
.survey-list{width:250px;background:url(themes/wb/active-tab-bg.png) repeat-y right;}

/*survey resources summary*/
.resources h3{font-weight:bold;padding-top:10px;}
.abstract{display:none;margin-bottom:10px;background-color:none;}
.resources .alternate, .resources .resource{border-bottom:1px solid #C1DAD7;padding:5px;width:98%;}
.resources .alternate{background-color:#FBFBFB;}
.resources .alternate:hover, .resources .resource:hover{background-color:#EAEAEA}
.resources fieldset {border:0px;border-top:4px solid gainsboro;margin:0px;padding:0px;margin-top:20px;margin-bottom:10px;padding-top:5px;color:#333333;}
.resources fieldset legend{font-weight:bold;;padding:5px;text-transform:capitalize;margin-left:10px;}	
.resource-info{cursor:pointer;}
.resource-right-col{float:right;width:15%;}
.resource-left-col{float:left;width:85%;}
.resource-file-size{display:inline-block;width:100px;text-align:left;color:#999999;}
.tbl-resource-info{padding:0px;margin:0px; border-collapse:collapse}
/*.resource-info{padding-left:20px;background:url('images/blue-add.png') no-repeat left top;}*/
.active .resource-info{font-weight:bold;margin-bottom:10px;/*background:url('images/blue-remove.png') no-repeat left top;*/}
.resources .active{border:1px solid gainsboro;margin-bottom:20px;}
.resource .caption{font-weight:bold;}

#accordion{}
.ui-state-active{font-weight:bold;}
</style>

<?php
$request_url=site_url('access_licensed/track/'.$id);
?>

<h2><?php echo t('download_microdata_and_resources');?></h2>

<?php if (count($surveys)==1):?>
<div class="survey-resources">
    <?php $this->load->view('access_licensed/survey_resources_microdata',array('resources_microdata'=>$microdata_resources,'request_id'=>$id));?>
    <br style="margin-top:20px;"/>
    <?php $this->load->view('access_licensed/survey_resources',array('resources'=>$external_resources,'request_id'=>$id));?>
</div>

<?php else:?>


<div id="accordion">
<?php $k=0;foreach ($surveys as $survey):?>
	<?php if (!in_array($survey['id'],(array)$surveys_with_files)){
		continue;
	}
	?>
    <?php 
	if ($sid==$survey['id']) {
		$active_survey=$k;
	}
	$k++;
	?>
  <h3 data-id="survey-<?php echo $survey['id'].'-'.$id;?>" data-url="<?php echo site_url('access_licensed/get_resources/'.$survey['id'].'/'.$id);?>">
  	<?php echo anchor($request_url.'?sid='.$survey['id'],$survey['title']);?> - <?php echo $survey['nation'];?>, <?php echo $survey['year_start'];?>
  </h3>
  <div id="survey-<?php echo $survey['id'].'-'.$id;?>-resources" >
    <?php if ($sid==$survey['id']):?>
    <div class="resources-container">
        <div class="survey-resources">
            <?php $this->load->view('access_licensed/survey_resources_microdata',array('resources_microdata'=>$microdata_resources,'request_id'=>$id));?>
            <br style="margin-top:20px;"/>
            <?php $this->load->view('access_licensed/survey_resources',array('resources'=>$external_resources,'request_id'=>$id));?>
        </div>
    </div>
	<?php else:?>
    ...
    <?php endif;?>
  </div>
<?php endforeach;?>
</div>


<?php endif;?>

<!--survey summary resources--> 
<script type="text/javascript">
	function toggle_resource(element_id){
		$("#"+element_id).parent(".resource").toggleClass("active");
		$("#"+element_id).toggle();
	}
	
	$(document.body).on("click",".resource-info", function(){ 
		if($(this).attr("id")!=''){
			toggle_resource('info_'+$(this).attr("id"));
		}
		return false;
	});			

</script>
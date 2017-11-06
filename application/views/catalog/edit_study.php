<?php
//survey id
$sid=$this->uri->segment(4);
?>

<script type="text/javascript">
function toggle(element){
	$(element).toggleClass("collapse");
}
	
$(document).ready(function () { 

		$(".collapsible .box-caption").unbind('click');
		$(".collapsible .box-caption").click(function(e){
			toggle_box(this);
			return false;
		});

		$(".collapsible .cancel-toggle").click(function(e){
				reset_box(this);
				return false;
		});	

		$(".box .box-header").click(function(e){
			toggle_sidebar(this);
			return false;
		});

		//show/hide remote da url depending on the da form selected
		$("#formid").change(function(e){				
			sh_remote_da_link();
		});	

		//show/hide da
		sh_remote_da_link();

		//tags
		$("#btn-tag").click(function(e){
			add_tag();
			return false;
		});
		
		$("#tag").live('keyup',function(event){		
			if(event.keyCode==13){
				add_tag();
				return false;
			}
		});
		
		//study publish/unpublish
		$(document.body).on("click","#survey .publish", function(){ 
			var studyid=$(this).attr("data-sid");
			if ($(this).attr("data-value")==0){
				$(this).attr("data-value",1);
				$(this).html("<?php echo t('published');?>");
				$(this).addClass("label-success");
				$.post(CI.base_url+'/admin/catalog/publish/'+studyid+'/1?ajax=1',{submit:"submit"});
			}
			else{
				$(this).html("<?php echo t('unpublished');?>");
				$(this).attr("data-value",0);
				$(this).removeClass("label-success");
				$.post(CI.base_url+'/admin/catalog/publish/'+studyid+'/0?ajax=1',{submit:"submit"});
			}		
		});


		//mark study as featured
		$(document.body).on("click","#survey .feature_study", function(){ 
			var studyid=$(this).attr("data-sid");
			var repoid=$(this).attr("data-repositoryid");
			var status=0;

			if ($(this).attr("checked")) {
				status=1;
			}
	
			$.post(CI.base_url+'/admin/catalog/set_featured_study/'+repoid+'/'+studyid+'/'+status+'?ajax=1',{submit:"submit"});
		});




		bind_behaviours();
});	

//show/hide remote data access text box
function sh_remote_da_link()
{
	if ($("#formid").val()==5)
	{
		$(".link-da").show();
	}
	else
	{
		$(".link-da").hide();
	}
}

function clear_all_toggle()
{
	$("#survey .active").removeClass("active");
	$("#survey .box-body").addClass("collapse");
}

function toggle_sidebar(e){
	$(e).parent().toggleClass("iscollapsed");
	$(e).parent().find(".box-body").toggleClass("collapse");
}

function toggle_box(e){	
		//clear_all_toggle();
		$(e).toggleClass("collapse");
		$(e).parent().find(".box-body").toggleClass("collapse");
		$(e).parent().parent("td").toggleClass("active");
}

function reset_box(e){
		var td=$(e).closest('td');
		td.removeClass("active");
		td.find(".collapse").removeClass("collapse");
		td.find(".box-body").addClass("collapse");
		console.log($(e));
}
	
function bind_behaviours() {
	bind_survey_collection_events();
}

function bind_survey_collection_events(){
	//click events for checkboxes
	$("#survey-collection-list .chk").click(function(e){
		update_survey_collection(this);
	});	
}

function update_survey_collection(e) {
	var tid=$(e).val();
	var url=CI.base_url+'/admin/studycollections/detach/<?php echo $sid;?>/'+tid;

	if ($(e).attr("checked")) {
		url=CI.base_url+'/admin/studycollections/attach/<?php echo $sid;?>/'+tid;
	}
	
	$.ajax({
        type: "GET",
        url: url,
        cache: false,
		timeout:30000,
		success: function(data) {
        },
		error: function(XMLHttpRequest, textStatus, errorThrow) {
			alert(XMLHttpRequest.responseText);
        }		
    });
}

	//attach related citations
	$(function() {
		
		var survey_id=<?php echo $survey_id;?>;
		
		//attach survey dialog
		$('.attach_citations').live('click', function() {
				var iframe_url=CI.base_url+'/admin/related_citations/index/'+survey_id;
				$('<div id="dialog-modal" title="Select Related Citations"></div>').dialog({ 
					height: 530,
					width: 700,
					resizable: false,
					draggable: false,
					modal: true,
					buttons: {
						"close": function() {
						$( this ).dialog( "close" );
						}
					},
					close: function() {
						$.get(CI.base_url+'/admin/catalog/related_citations/'+survey_id, function(data) {
							$('#related-citations').html(data);
							related_citations_click();
						});
					}
				}).append('<iframe height="404" width="700" src="'+iframe_url+'" frameborder="0"></iframe>');
		});
		
		//remove related citations
		$('#related-citations .remove').live('click', function() {
			$.get($(this).attr("href")+'/1');
			$(this).parent().parent().remove();
			return false;
		});
		
		//attach related studies
		$(document.body).on("click",".related_studies_attach_studies", function(){
			dialog_select_related_studies();return false;
		});
		
	});
	
	
	
	//related_studies_attach_studies selection dialog
	function dialog_select_related_studies()
	{
		var dialog_id='dialog-related-studies';
		var title="Select Studies";
		var survey_id=<?php echo $survey_id;?>;
				
		var tmp_id='sess-'+survey_id;//for saving dialog selection to cookies
		var url=CI.base_url+'/admin/dialog_select_studies/index/'+tmp_id;
		var get_selection_url=CI.base_url+"/admin/dialog_select_studies/get_list/"+tmp_id;
		var tab_id="#related-studies-tab";
		
		//already attached related studies
		var source_selected=get_selected_related_studies();
		
		//add attached surveys to session, needed when editing a citations with survey attached
		//var url_add=CI.base_url+'/admin/related_surveys/add/'+tmp_id+'/'+'<?php //echo implode(",",$selected_surveys_id_arr);?>/1';
		//$.get(url_add);	//update session
		if ($('#'+dialog_id).length==0){
			$("body").append('<div id="'+dialog_id+'" title="'+title+'"></div>');
		}
		
		var dialog=$( "#"+dialog_id ).dialog({
			height: 500,
			position:"center",
			width:750,
			modal: true,
			autoOpen: true,
			buttons: {
				"Cancel": function() {
					$( this ).dialog( "close" );
				},
				"Apply filter": function() {
					$.getJSON(get_selection_url, function( json ) {
					   var selected=json.selected;
					   
					   //clear session selection
					   $.get(CI.base_url+'/admin/dialog_select_studies/clear_all/'+tmp_id);
					   
					   //attach selected
					   var xhr=$.get(CI.base_url+'/admin/catalog/update_related_study/'+survey_id + '/'+selected + '/0');
					   
					   //refresh the tab contents
					   $("#related-studies-tab").html("loading...");
					   xhr.done(function() { 
					   		$("#related-studies-tab").load(CI.base_url+'/admin/catalog/get_related_studies/'+survey_id);
					   });
					 });
					 
					$( this ).dialog( "close" );
				}
			}//end-buttons
		});//end-dialog

		//reset selected items each time dialog is loaded
		dialog.data("selected","");
		
		//load dialog content
		$('#'+dialog_id).load(url, function() {
			console.log("loaded");			
		});
	
		//dialog pagination link clicks
		$(document.body).on("click","#related-surveys th a,#related-surveys .pagination a", function(){
			$("#dialog-related-studies").load( $(this).attr("href") );
			return false;
		});
		
		//dialog search button click
		 $(document.body).on("click","#dialog-related-studies .btn-search-submit", function(){
			data=$("#dialog-related-studies form").serialize();
			$("#dialog-related-studies").load( url+"?"+data );
			return false;
		});
				
		//dialog show selected only checkbox
		 $(document.body).on("click","#dialog-related-studies #show-only-selected", function(){
		 	if($(this).prop("checked")){
				data='show_selected_only=1';
			}
			else{data="";}	
			$("#dialog-related-studies").load( url+"?"+data );
			return false;
		});
		
		//dialog attach/select study link
		$(document.body).on("click",".table-container a.attach", function(e){ 
			$.get($(this).attr("href"));
			$(this).html("<?php echo t('deselect'); ?>");
			$(this).removeClass("attach").addClass("remove");
			return false;
		});
	
		//dialog delest study link	
		$(document.body).on("click","#related-surveys .table-container a.remove", function(){ 
			$.get($(this).attr("href"));
			$(this).html("<?php echo t('select'); ?>");	
			$(this).removeClass("remove").addClass("attach");
			return false;
		});
	
	}//end-function	
	
	//return array of selected items on the related study tab
	function get_selected_related_studies(){
		var items_selected=[];
		$("#related-studies-tab .table-related-studies .item").each(function(){
			items_selected.push($(this).attr("data-sid_2")); 
		});
		return items_selected;
	}
	
	//relationship type change event
	$(document.body).on("change",".table-related-studies .rel-type", function(){
		var tr=$(this).closest("tr");
		var sid_1=tr.attr("data-sid_1");
		var sid_2=tr.attr("data-sid_2");
		var url=CI.base_url+'/admin/catalog/update_related_study/'+sid_1+'/'+sid_2+'/'+$(this).val();
		var url_remove_study=CI.base_url+'/admin/catalog/remove_related_study/'+sid_1+'/'+sid_2+'/'+$(this).val();
		tr.find(".remove-related-study").attr("href",url_remove_study);
		$.get(url);
		return false;
	});

	//remove related study link
	$(document.body).on("click","#related-studies-tab .table-related-studies .remove-related-study", function(){
		var survey_id=$(this).closest("tr").attr("data-sid_1");
		$.get($(this).attr("href")).done(function() { 
			$("#related-studies-tab").load(CI.base_url+'/admin/catalog/get_related_studies/'+survey_id);
	   	});
		
		return false;
	});

	function set_data_access_display(el)
	{
		if ($("#formid").val() >=1 && $("#formid").val() <=3) {
			$("#formid").closest("form").addClass("microdata").removeClass("no-microdata");
		}
		else{
			$("#formid").closest("form").removeClass("microdata").addClass("no-microdata");
		}		
	
	}

	//data access type change
	$(document.body).on("change","#formid", function(){
		set_data_access_display();
		return false;
	});
	
	
	$(function() {
		//set data access display on page load
		set_data_access_display();
	});
	
</script>

<style>
.filter-box{margin:5px;margin-right:20px;}
.filter-box li{font-size:11px;}
.filter-box a{text-decoration:none;color:black;display:block;padding:3px;padding-left:15px;background:url('images/bullet_green.png') left top no-repeat;}
.filter-box a:hover{background:black;color:white;}
.filter-field{
border: 1px solid gainsboro;
-moz-border-radius: 5px;
-webkit-border-radius: 5px;
color: #333;
margin-bottom:10px;
}
.filter-title {
	font-size: 14px;
	text-transform: uppercase;
	padding: 5px;
	background: gainsboro;
}
span.active-repo{font-size:smaller;color:gray;}
span.link-change{font-size:10px;padding-left:5px;}
.unlink-study .linked{padding-left:20px;}
.width-80{width:80%;}
.collapse {display:none;}
.sh{float:right;display:block;}
.box-caption:hover{cursor:pointer;color:maroon;}


/*survey collections*/
#terms input {float:left;width:15px;margin-right:4px;}
#terms label {float:left;width:80%;font-size:smaller}
#terms {clear:both;}
#terms .term{clear:both;overflow:auto;margin-bottom:5px;}
#survey-collection-list{padding:5px;}

/*editable rows*/
.collapsible .box-caption{background:url(images/edit.gif) no-repeat;padding-left:30px;line-height:150%;}
td.active{background:gainsboro;}

/*fields*/
#survey .field label{font-weight:bold;display:block;}
#survey .field{margin-bottom:10px;}

/*hyperlinks*/
#survey a{font-size:small;}

#survey .actions{margin-left:10px;}

/*box*/
.box{border:1px solid gainsboro;float:right;width:18%;margin-right:5px;line-height:150%;margin-bottom:10px;
-webkit-border-radius: 3px;
border-radius: 3px;clear:right;}

.box-header{
	font-weight:normal;
	padding:5px;
	font-size:14px;
	background: #F1F1F1;
	background-image: -webkit-gradient(linear,left bottom,left top,from(#ECECEC),to(#F9F9F9));
	background-image: -webkit-linear-gradient(bottom,#ECECEC,#F9F9F9);
	background-image: -moz-linear-gradient(bottom,#ECECEC,#F9F9F9);
	background-image: -o-linear-gradient(bottom,#ECECEC,#F9F9F9);
	background-image: linear-gradient(to top,#ECECEC,#F9F9F9);
	border-bottom: 1px solid #DFDFDF;
	text-shadow: white 0 1px 0;
	-webkit-box-shadow: 0 1px 0 white;
	box-shadow: 0 1px 0 white;
	position:relative;
	cursor:pointer;
}

.box-header .sh{
	position:absolute;
	right:3px;
	top:5px;
	background: url('images/blue-remove.png') no-repeat left top;
	display:block;
	width:16px;
	height:16px;
	cursor:pointer;
}
.iscollapsed .sh{background: url('images/blue-add.png') no-repeat left top;}

.box-body{margin:5px;padding-bottom:10px;}
.box-body .input-flex{width:85%;}
.info-box{
  position: fixed;
  top: 20%;
  left:0px;
  width:100%;
/*  margin-top: -2.5em;*/
}
.info-box .error{background:red;margin-left:200px;margin-right:200px;color:white;display:none;}
.admin-notes-container .input-flex{width:85%;}
.reviewer-notes-container .input-flex{width:85%;}
.tags-container .input-flex{width:85%;}
.survey-other-ids .input-flex{width:85%;}
.remove{padding:5px;cursor:pointer;}
.tag{font-size:11px;}
.vscroll{overflow:auto;overflow-x:hidden;height:150px;}
.survey-tabs .count{font-size:smaller;}

/*model dialog*/
.ui-widget-header{background:black;border:black;color:white;}
.ui-dialog .ui-dialog-content{overflow:hidden;padding:0px;background:white;}

/*related studies tab*/
.dialog-container .table-container {
	height: 246px;
	overflow: auto;
	font-size: 12px;
}

.dialog-container .pagination em{float:left;}
.dialog-container .pagination .page-nums{float:right;}

.dialog-container a.attach, 
.dialog-container a.remove {
background: green;
padding: 3px;
color: white;
display: block;
-webkit-border-radius: 3px;
-moz-border-radius: 3px;
border-radius: 3px;
float:left;
width:60px;
text-align:center;
text-transform:capitalize
}
.dialog-container a.remove{background:red;}

.dialog-container a.attach:hover, 
.dialog-container a.remove:hover {background:black;}


.ui-dialog .ui-dialog-titlebar-close {top:22%;}

.ui-widget-header {
background: white;
border: 0px;
color: black;
height: 56px;
}

/*dialog header*/
.ui-dialog .ui-dialog-titlebar {
	border-radius: 0px;
	border: 0px;
	text-align: left;
	margin-bottom: 10px;
	height: 35px;
	height: 1;
	padding-top: 31px;
	background:#F3F3F3
}

/*dialog footer*/
.ui-dialog .ui-dialog-buttonpane {
	font-size: 12px;	
}

.grid-table .header{font-weight:bold;}
.sub-text{font-size:smaller;color:gray;}

.alert-warning{
border:2px solid #FF0000;
color:red;
}

.warnings{margin-top:5px;}

.no-microdata .study-microdata{display:none;}
.no-microdata-assigned{color: red;
border: 1px solid red;
padding: 5px;
margin-bottom: 10px;
background: white;
}

.microdata-applies-to{font-weight:bold;}
</style>

<div class="body-container" style="padding:10px;">

<?php if($warnings):?>
<div class="alert alert-warning">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <h4><?php echo t('study_warnings');?></h4>
  <ul class="warnings">
  <?php foreach($warnings as $warning):?>
  <li><?php echo t($warning);?></li>
  <?php endforeach;?>
  </ul>
</div>
<?php endif;?>


<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>


<?php		
	//current page url
	$page_url=site_url().'/'.$this->uri->uri_string();
?>

<div class="info-box">
	<div class="error" style="overflow:auto;">
    	<div style="float:left;margin-right:20px;" id="page-error">system message box</div>
        <div style="float:right;"><img src="<?php echo base_url();?>/images/close.gif" onclick="javascript:hide_msgbox()"/></div>
    </div>
</div>

<div id="survey" style="width:80%;float:left;">

        <h1><?php echo $titl; ?></h1>
		<table class="grid-table" cellspacing="0">
        <tr>
            <td nowrap="nowrap" style="width:150px;"><?php echo t('ref_no');?></td>
            <td><?php echo $surveyid; ?></td>
        </tr>
        
		<?php if(isset($survey_alias_array) && count($survey_alias_array)>0):?>
        <tr>
            <td><?php echo t('study_other_IDs');?></td>
            <td>
            	<span class="survey-alias">
					<?php foreach($survey_alias_array as $alias):?>
					<span class="alias"><?php echo $alias['alternate_id'];?></span>
                    <?php endforeach;?>
                </span>    
            </td>
        </tr>
        <?php endif;?>
        
        <tr>
            <td><?php echo t('year');?></td>
            <td><?php 
				$years=array($data_coll_start,$data_coll_end);
				$years=array_unique($years);				
				?>			
				<?php echo implode(" - ",$years); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo t('country');?></td>
            <td><div class="survey-countries">            
				<?php foreach($countries as $country):?>                	
                	<?php if((int)$country['cid']<1):?>
                        <span class="country error" id="country-<?php echo $country['id'];?>" title="<?php echo t('Fix country code');?>">
                            <a href="<?php echo site_url('admin/countries/mappings');?>"><?php echo $country['country_name'];?></a>
                        </span>
                    <?php else:?>
                    	<span class="country" id="country-<?php echo $country['id'];?>">
                            <?php echo $country['country_name'];?>
                        </span>
                    <?php endif;?>
            	<?php endforeach;?>
                </div>
            </td>
        </tr>
        <tr>
            <td><?php echo t('producer');?></td>
            <td><?php echo $authenty;//implode(",",(array)json_decode($authenty)); ?></td>
        </tr>
        <tr>
            <td><?php echo t('sponsor');?></td>
            <td><?php echo $sponsor; ?></td>
        </tr>
        <tr>
            <td><?php echo t('folder');?></td>
            <td><?php echo $dirpath;?></td>
        </tr>
        <tr>
            <td><?php echo t('repository');?></td>
            <td>
            	<?php if ($repo):?>
				<?php foreach($repo as $r):?>
                	<span class="label <?php echo ($r['isadmin']==1) ? 'label-info' : ''; ?>" title="<?php //echo $r['title'];?>"><?php echo strtoupper($r['repositoryid']);?></span>
                <?php endforeach;?>
                <?php else:?>
                	N/A
                <?php endif;?>                
            </td>
        </tr>        
        <tr>
            <td><?php echo t('data_access');?></td>
            <td>
				<div class="collapsible">
						<div class="box-caption">	
                        <?php error_reporting(0); ?>									
							<?php echo $this->forms_list[$formid];?>
						</div>
						
						<div class="box-body collapse">
                                <form method="post" id="da-form" action="<?php echo site_url();?>/admin/catalog/update">
                                    <input type="hidden" name="sid" value="<?php echo $id;?>"/>	
                                    
									<div class="field">
                                    	<label><?php echo t('msg_select_data_access_type');?></label>
										<?php echo form_dropdown('formid', $this->forms_list, get_form_value("formid",isset($formid) ? $formid : ''),'id="formid"'); ?>
									</div>
                                    
                                    <div class="field link-da">
                                        <label for="link_da"><?php echo t('remote_data_access_url');?></label>
                                        <input name="link_da" type="text" id="link_da" class="input-flex" value="<?php echo get_form_value('link_da',isset($link_da) ? $link_da : ''); ?>"/>
                                    </div>
                                    
                                    <div class="study-microdata model-<?php echo $model;?>">
                                    <?php if (count($microdata_files)>0):?>                                    
										<div class="microdata-applies-to"><?php echo t('data_selection_apply_to_files');?></div>
                                        <ul>
                                        <?php foreach($microdata_files as $mf):?>
                                        <li><?php echo basename($mf['filename']);?></li>
                                        <?php endforeach;?>
                                        </ul>
                                    <?php else:?>
                                    <div class="no-microdata-assigned"><?php echo sprintf(t('study_no_data_files_assigned'),'');?></div>
									<?php endif;?>
                                    </div>
                                    
                                    <div class="field">
                                    <input type="submit" value="<?php echo t('update');?>" name="submit"/>
                                    <input type="button" value="<?php echo t('cancel');?>" name="cancel" class="cancel-toggle"/>
                                    </div>
                                </form> 										
						</div>				
				</div>
									        
            </td>
        </tr>

        <tr>
            <td><?php echo t('Status');?></td>
            <td>
                <div class="status" title="<?php echo t('click_to_publish_unpublish');?>">
                <?php if (!$published):?>
                    <span class="label publish" data-value="0" data-sid="<?php echo $sid;?>"><?php echo t('unpublished');?></span>
                <?php else:?>
                    <span class="label publish label-success" data-value="1"  data-sid="<?php echo $sid;?>"><?php echo t('published');?></span>
                <?php endif;?>
                </div>                        
            </td>
        </tr>

        <tr>
            <td><?php echo t('metadata_in_pdf');?></td>
            <td>
            	<?php if ($pdf_documentation['status']=='na'):?>
            		<span class="label label-important"  title="<?php echo t('pdf_not_generated');?>"><i class="icon-exclamation-sign icon-white"></i> <?php echo t('pdf_not_generated');?></span>
                <?php else:?>
                	<?php if ($pdf_documentation['status']=='uptodate'):?>
                		<span class="label label-success" title="<?php echo t('pdf_uptodate');?>"><i class="icon-ok icon-white"></i> <?php echo t('pdf_uptodate');?></span>
                    <?php else:?>
                    	<span class="label label-warning" title="<?php echo t('pdf_outdated');?>"><i class="icon-exclamation-sign icon-white"></i> <?php echo t('pdf_outdated');?></span>
                    <?php endif;?>
                <?php endif;?>
                <span class="actions">
                	<?php if ($pdf_documentation['status']=='na' || $pdf_documentation['status']=='outdated' ):?>
                    	<a href="<?php echo site_url("admin/pdf_generator/setup/$sid");?>"><?php echo t('Generate PDF');?></a>
                    <?php endif;?>
                    <span class="sep">  </span>
                    <?php if($pdf_documentation['status']!=='na'):?>
                    <a href="<?php echo site_url("admin/pdf_generator/delete/$sid");?>"><?php echo t('delete');?></a>
                    <?php endif;?>
                </span>
            </td>
		</tr>
        <tr>
            <td><?php echo t('indicator_database');?></td>
            <td>
				
				<div class="collapsible">
                    <div class="box-caption">	
                                <?php if ($link_indicator):?>
                                            <?php echo $link_indicator;?>
                                <?php else:?>
                                            ...
                                <?php endif;?>
                    </div>
					
                    <div class="box-body study-publish-box collapse">
								<form method="post" action="<?php echo site_url();?>/admin/catalog/update">
									<input type="hidden" name="sid" value="<?php echo $id;?>"/>	
									<input class="input-flex width-80" name="link_indicator" type="text" id="link_indicator" value="<?php echo get_form_value('link_indicator',isset($link_indicator) ? $link_indicator : '') ; ?>"/>
									<input type="submit" name="submit" id="submit" value="<?php echo t('update'); ?>" />
									<input type="button" value="<?php echo t('cancel');?>" name="cancel" class="cancel-toggle"/>
								<?php echo form_close(); ?>    
					</div>                
				</div>
			
            </td>
        </tr>
        
         <tr>
            <td><?php echo t('study_website');?></td>
            <td>
			
			<div class="collapsible">
					<div class="box-caption">	
							<?php if ($link_study):?>
										<?php echo $link_study;?>
							<?php else:?>
										...
							<?php endif;?>
					</div>
				
					<div class="box-body study-publish-box collapse">
                        <form method="post" action="<?php echo site_url();?>/admin/catalog/update">
                            <input type="hidden" name="sid" value="<?php echo $id;?>"/>
                            <input class="input-flex width-80" name="link_study" type="text" id="link_study" value="<?php echo get_form_value('link_study',isset($link_study) ? $link_study : '') ; ?>"/>
                            <input type="submit" name="submit" id="submit" value="<?php echo t('update'); ?>" />
                            <input type="button" value="<?php echo t('cancel');?>" name="cancel" class="cancel-toggle"/>
                        </form>
					</div>
				</div>
            </td>
        </tr>
        <!--
        <tr>
        <td></td>
        <td>
		        <div class="featured_survey" title="<?php echo t('click_to_feature');?>">
                    <label for="chk_featured_survey"><input id="chk_featured_survey" type="checkbox" class="feature_study" data-repositoryid="<?php echo $repositoryid;?>" data-sid="<?php echo $sid;?>" <?php echo ($is_featured ===TRUE ) ? 'checked="checked"' : '';?>/> <?php echo t('mark_as_featured');?></span>
                </div>        
        </td>
        </tr>
         -->     
        </table>

	<input name="tmp_id" type="hidden" id="tmp_id" value="<?php echo get_form_value('tmp_id',isset($tmp_id) ? $tmp_id: $this->uri->segment(4)); ?>"/>

	<!-- survey tabs -->
    <div style="margin-top:50px;margin-bottom:100px;">
		<?php $this->load->view("catalog/study_tabs"); ?>        
    </div>

</div>

</div>

<!--Side Bars-->
<div class="box" >
<div class="box-header">
	<span><?php echo t('Survey options');?></span>
    <span class="sh" title="<?php echo t('toggle_box');?>">&nbsp;</span>
</div>
<div class="box-body">
    <ul class="bull-list">
        <li><a target="_blank" href="<?php echo site_url();?>/catalog/<?php echo $sid;?>"><?php echo t('browse_metadata');?></a></li>
        <li><a href="<?php echo site_url();?>/admin/resources/import/<?php echo $sid;?>"><?php echo t('upload_rdf');?></a></li>
        <li><a href="<?php echo site_url();?>/admin/resources/fixlinks/<?php echo $sid;?>"><?php echo t('link_resources');?></a></li>
        <li><a href="<?php echo site_url();?>/admin/pdf_generator/setup/<?php echo $sid;?>"><?php echo t('generate_pdf');?></a></li>
        <li><a href="<?php echo site_url();?>/admin/catalog/transfer/<?php echo $sid;?>"><?php echo t('transfer_study_ownership');?></a></li>
        <li><a href="<?php echo site_url();?>/admin/catalog/replace_ddi/<?php echo $sid;?>"><?php echo t('replace_ddi');?></a></li>
        <li><a href="<?php echo site_url();?>/admin/catalog/ddi/<?php echo $sid;?>"><?php echo t('export_ddi');?></a></li>
        <li><a href="<?php echo site_url();?>/admin/catalog/refresh/<?php echo $sid;?>"><?php echo t('refresh_ddi');?></a></li>
        <li><a href="<?php echo site_url();?>/admin/catalog/export_rdf/<?php echo $sid;?>"><?php echo t('export_rdf');?></a></li>        
        <li><a href="<?php echo site_url();?>/admin/catalog/delete/<?php echo $sid;?>"><?php echo t('delete_study');?></a></li>
    </ul>
</div>
</div>


<div class="box ">
	<div class="box-header"><?php echo t('Tags');?>
       <span class="sh" title="<?php echo t('toggle_box');?>">&nbsp;</span>
    </div>
    <div class="box-body survey-tags">
		<?php echo $tags; ?>
	</div>
</div>


<div class="box iscollapsed">
	<div class="box-header">
    	<?php echo t('study_collections');?>
        <span class="sh" title="<?php echo t('toggle_box');?>">&nbsp;</span>
    </div>
    
    <div class="box-body collapse">
	<div id="survey-collection-list"><?php echo $collections;?></div>
    </div>
</div>

<div class="box iscollapsed">
	<div class="box-header"><?php echo t('study_aliases');?>
      <span class="sh" title="<?php echo t('toggle_box');?>">&nbsp;</span>

    </div>
    <div class="box-body collapse">
		<?php echo $survey_aliases; ?>
	</div>
</div>
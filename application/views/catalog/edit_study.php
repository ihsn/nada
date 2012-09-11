<?php
//survey id
$sid=$this->uri->segment(4);

//vocabulary id for survey collections
$vid=$this->config->item("collections_vocab");
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
		
		$("#tag").keyup(function(event){
			if(event.keyCode==13){
				add_tag();
			}
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

function toggle_box(e){	
		//clear_all_toggle();
		$(e).toggleClass("collapse");
		$(e).parent().find(".box-body").toggleClass("collapse");
		$(e).parent().parent("td").toggleClass("active");
		console.log($(e));
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

/*tags*/
function add_tag() {
	
	var tag=$("#tag").val();

	if (tag==""){
		return false;
	}
	
	url=CI.base_url+'/admin/catalog_tags/add/<?php echo $sid;?>/'+tag+'/';

	$.ajax({
        type: "GET",
        url: url,
        cache: false,
		timeout:30000,
		dataType:'json',
		success: function(data) {
			if (data.success){
				//add tag to the list
				$("#tag-list").append("<li>"+tag+"</li>");
				$("#tag").val("");				
			}
			else{
				show_error(data.error);
			}
        },
		error: function(XMLHttpRequest, textStatus, errorThrow) {
			show_error(XMLHttpRequest.responseText);
        }		
    });
}

function load_tags(){
	url=CI.base_url+'/admin/catalog_tags/get_tags/<?php echo $sid;?>';
	
	$.ajax({
        type: "GET",
        url: url,
        cache: false,
		timeout:30000,
		success: function(data) {
			$("#tag-list").html(data);
        },
		error: function(XMLHttpRequest, textStatus, errorThrow) {
			alert(XMLHttpRequest.responseText);
        }		
    });
}

function show_error(error_msg){
	$("#page-error").html(error_msg);
	$(".info-box .error").show();
}

function hide_msgbox(){
	$(".info-box .error").hide();
	$(".info-box .msg").hide();
}

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
#terms label {float:left;width:90%;fonts-size:11px;}
#terms {clear:both;}
#terms .term{clear:both;overflow:auto;margin-bottom:5px;}
#survey-collection-list{padding:5px;}

/*editable rows*/
.collapsible .box-caption{background:url(images/edit.gif) no-repeat;padding-left:30px;line-height:150%;}
td.active{background:gainsboro;}

/*fields*/
#survey .field label{font-weight:bold;display:block;}
#survey .field{margin-bottom:10px;}

/*box*/
.box{border:1px solid gainsboro;float:right;width:18%;margin-right:5px;line-height:150%;margin-bottom:10px;}
.box-header{font-weight:bold;padding:5px;background:gainsboro;}
.box-body{margin:5px;}
.info-box{
  position: fixed;
  top: 20%;
  left:0px;
  width:100%;
/*  margin-top: -2.5em;*/
}
.info-box .error{background:red;margin-left:200px;margin-right:200px;color:white;display:none;}
</style>
<div class="body-container" style="padding:10px;">

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
            <td nowrap="nowrap"><?php echo t('ref_no');?></td>
            <td><?php echo $surveyid; ?></td>
        </tr>
        <tr>
            <td><?php echo t('year');?></td>
            <td><?php echo $data_coll_start; ?></td>
        </tr>
        <tr>
            <td><?php echo t('country');?></td>
            <td><?php echo $nation; ?></td>
        </tr>
        <tr>
            <td><?php echo t('authenty');?></td>
            <td><?php echo $authenty; ?></td>
        </tr>
        <tr>
            <td><?php echo t('sponsor');?></td>
            <td><?php echo $sponsor; ?></td>
        </tr>
        <tr>
            <td><?php echo t('series');?></td>
            <td><?php echo $sername; ?></td>
        </tr>
        <tr>
            <td><?php echo t('data_access');?></td>
            <td>
				<div class="collapsible">
						<div class="box-caption">										
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
            <td><?php echo t('publish');?></td>
            <td>
				<div class="collapsible">
					<div class="box-caption">
							<?php if ($published):?>
										<?php echo t('published');?>
							<?php else:?>
										<?php echo t('unpublished');?>
							<?php endif;?>							
					</div>
				
					<div class="box-body study-publish-box collapse">
						<form  method="post" action="<?php echo site_url();?>/admin/catalog/update">
                            <input type="hidden" name="sid" value="<?php echo $id;?>"/>	
                            <div class="field">
                            	<label><?php echo t("select_publish_unpublish");?></label>
                            	<?php echo form_dropdown('published', array('1'=>'Publish','0'=>'Unpublish'), get_form_value("pubilshed",isset($published) ? $published : '')); ?>
                            </div>
                            <input type="submit" value="<?php echo t('update');?>"/>
                            <input type="button" value="<?php echo t('cancel');?>" name="cancel" class="cancel-toggle"/>
						</form>
					</div>
				</div>
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
       
 <tr>
            <td>Admin notes</td>
            <td>notes here...</td>
        </tr>
      
        
        <tr>
            <td>Reviewer notes</td>
            <td>notes here...</td>
        </tr>       
        </table>

	<div style="margin-top:50px;margin-bottom:100px;">
		<?php $this->load->view("catalog/study_tabs");?>
        <div style="border:1px solid gainsboro;padding:10px;overflow:auto;">
        <?php 
            switch($this->uri->segment(5)) {
                case 'resources':
                    echo $resources;
                break;
                case 'citations':
                    echo "todo";
                break;
                default:
                    echo $files;
            }
        ?>
    	</div>	
    </div>

</div>

</div>

<div class="box" >
<div class="box-header" >Survey options</div>
<ul class="bull-list">
	<li><a href="<?php echo site_url();?>/admin/catalog/upload">Upload DDI</a></li>
	<li><a href="<?php echo site_url();?>/admin/catalog/batch_import">Import DDI</a></li>
	<li><a href="<?php echo site_url();?>/admin/catalog/ddi/<?php echo $sid;?>">Download DDI</a></li>
	<li><a href="<?php echo site_url();?>/admin/catalog/copy_study">Copy studies from other catalogs</a></li>
	<li><a href="<?php echo site_url();?>/admin/catalog/transfer/<?php echo $sid;?>">Transfer study ownership</a></li>
	<li><a href="<?php echo site_url();?>/admin/catalog/replace_ddi/<?php echo $sid;?>">Replace DDI</a></li>
    <li><a href="<?php echo site_url();?>/admin/catalog/delete/<?php echo $sid;?>">Delete Study</a></li>
	<li><a href="<?php echo site_url();?>/catalog/<?php echo $sid;?>">Browse Metadata</a></li>
</ul>
</div>


<div class="box">
<div class="box-header">External Resources</div>
<ul class="bull-list">
    <li><a href="<?php echo site_url();?>/admin/resources/add/new/<?php echo $sid;?>">Upload RDF</a></li>
    <li><a href="<?php echo site_url();?>/admin/catalog/export_rdf/<?php echo $sid;?>">Export RDF</a></li>
	<li><a href="<?php echo site_url();?>/admin/resources/fixlinks/<?php echo $sid;?>">Link resources</a></li>
</ul>
</div>

<div class="box">
<div class="box-header">Notes</div>
<ul class="bull-list">
	<li>Admin notes</li>
    <li>Reviewer notes</li>
</ul>
</div>


<div class="box">
	<div class="box-header">Survey Collections</div>
	<div id="survey-collection-list"><?php echo $collections;?></div>
</div>

<div class="box">
	<div class="box-header">Tags</div>
    <div class="box-body">
        <div class="field">
            <input id="tag" type="text" value="..." class="input-flex" style="width:85%;"/>
            <input type="button" value="+" name="btn-tag" id="btn-tag" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;"/>
        </div>
        <ul class="bull-list" id="tag-list">
            <li>tag 1</li>
            <li>tag 2</li>
            <li>tag 3</li>
            <li>tag 4</li>
        </ul>
	</div>
</div>

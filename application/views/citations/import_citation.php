<style>
/*model dialog*/
.ui-widget-header{background:black;border:black;color:white;}
.ui-dialog .ui-dialog-content{overflow:auto;padding:0px;background:white;}

/*related studies tab*/
.dialog-container .table-container {
/*	height: 246px;
	overflow: auto;*/
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


.ui-widget-header {
background: white;
border: 0px;
color: black;
}

/*dialog header*/
.ui-dialog .ui-dialog-titlebar {
	border-radius: 0px;
	border: 0px;
	text-align: left;
	height: 1;
	background:#F3F3F3
}

/*dialog footer*/
.ui-dialog .ui-dialog-buttonpane {
	font-size: 12px;	
}
.ui-widget-overlay {opacity:0.8}

.grid-table .header{font-weight:bold;}
.sub-text{font-size:smaller;color:gray;}

</style>

<?php
$citation_formats = array(
                  'bibtex'  => 'BibTex',
                  'endnote_bibix'    => 'EndNote (Refer/BibIX)',
                  'endnote_ris'   => 'EndNote (RIS)',
				  'nada_serialized'   => 'NADA Serialized Array',
                 // 'endnote_xml' => 'EndNote (XML)',
                );

$publish_options=array(
	'1'=>t('option_publish'),
	'0'=>t('option_do_not_publish')
	);

$flag_options=array(
	''=>'--',
	'ds_unclear'=>t('ds_unclear'),
	'incomplete'=>t('incomplete'),
	'tobe_checked'=>t('tobe_checked'),
	'duplicate'=>t('duplicate'),
	'back_to_editor'=>t('back_to_editor'),
	);

?>
<script>
	$(function(){
	
	$('.add_survey').click(function() {
		dialog_select_related_studies();return false;});
	});
	
	//return array of selected items on the related study tab
	function get_selected_related_studies(){
		var items_selected=[];
		$("#related-surveys :checkbox").each(function(){
			items_selected.push($(this).val()); 
		});
		return items_selected;
	}


	//related_studies_attach_studies selection dialog
	function dialog_select_related_studies()
	{
		var dialog_id='dialog-related-studies';
		var title="Select Studies";
		var item_id=$("#tmp_id").val();
				
		var tmp_id='sess-cit-'+item_id;//for saving dialog selection to cookies
		var url=CI.base_url+'/admin/dialog_select_studies/index/'+tmp_id;
		var get_selection_url=CI.base_url+"/admin/dialog_select_studies/get_list/"+tmp_id;
		var tab_id="#related-studies-tab";
		var attach_url=CI.base_url+'/admin/dialog_select_studies/add/'+tmp_id+'/';
		var detach_url=CI.base_url+'/admin/dialog_select_studies/remove/'+tmp_id+'/';
		
		//already attached related studies
		var source_selected=get_selected_related_studies();
		
		if(source_selected.length>0){
			//add attached surveys to session, needed when editing a citations with survey attached
			$.get(CI.base_url+'/admin/dialog_select_studies/add/'+tmp_id+'/'+source_selected+'/1');
		}
		
		if ($('#'+dialog_id).length==0){
			$("body").append('<div id="'+dialog_id+'" title="'+title+'"></div>');
		}
		
		var dialog=$( "#"+dialog_id ).dialog({
			height: $(window).height()-100,
			position:"center",
			width:$(window).width()-100,
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
					   //$.get(CI.base_url+'/admin/dialog_select_studies/clear_all/'+tmp_id);
					   
					   $("#related-surveys").html("loading...");
					   $("#related-surveys").load(CI.base_url+'/admin/citations/selected_surveys/'+tmp_id+'/1');
					   
					 });
					 
					$( this ).dialog( "close" );
				}
			}//end-buttons
		});//end-dialog

		//reset selected items each time dialog is loaded
		dialog.data("selected","");
		
		//load dialog content
		$('#'+dialog_id).html("<?php echo t("js_loading");?>");
		$('#'+dialog_id).load(url, function() {
			//console.log("loaded");			
		});
	
		//dialog pagination link clicks
		$(document.body).off("click","#related-surveys th a,#related-surveys .pagination a");
		$(document.body).on("click","#related-surveys th a,#related-surveys .pagination a", function(){
			$("#dialog-related-studies").load( $(this).attr("href") );
			return false;
		});
		
		//dialog search button click
		 $(document.body).off("click","#dialog-related-studies .btn-search-submit");
		 $(document.body).on("click","#dialog-related-studies .btn-search-submit", function(){
			data=$("#dialog-related-studies form").serialize();
			$("#dialog-related-studies").load( url+"?"+data );
			return false;
		});
				
		//dialog show selected only checkbox
		 $(document.body).off("click","#dialog-related-studies #show-only-selected");
		 $(document.body).on("click","#dialog-related-studies #show-only-selected", function(){
		 	if($(this).prop("checked")){
				data='show_selected_only=1';
			}
			else{data="";}	
			$("#dialog-related-studies").load( url+"?"+data );
			return false;
		});
		
		//dialog attach/select study link
		$(document.body).off("click",".table-container a.attach");
		$(document.body).on("click",".table-container a.attach", function(event){ 
			$.get($(this).attr("href"));
			$(this).html("<?php echo t('deselect'); ?>");
			$(this).removeClass("attach").addClass("remove");
			var sid=$(this).attr("data-value");
			$(this).attr("href",detach_url+sid);			
			return false;
		});
	
		//dialog delest study link	
		$(document.body).off("click","#related-surveys .table-container a.remove");
		$(document.body).on("click","#related-surveys .table-container a.remove", function(event){ 
			$.get($(this).attr("href"));
			$(this).html("<?php echo t('select'); ?>");	
			$(this).removeClass("remove").addClass("attach");
			var sid=$(this).attr("data-value");
			$(this).attr("href",detach_url+sid);
			return false;
		});
	
	}//end-function		
</script>
<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/citations/" class="button"><img src="images/house.png"/><?php echo t('citation_home');?></a> 
</div>

<div class="content-container">

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('import_citation'); ?></h1>

<?php echo form_open_multipart(site_url().'/admin/citations/import/', array('class'=>'form') ); ?>
<input name="survey_id" type="hidden" id="survey_id" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id: ''); ?>"/>
<input name="tmp_id" type="hidden" id="tmp_id" value="<?php echo get_form_value('tmp_id',isset($tmp_id) ? $tmp_id: 'cit-'.date("U")); ?>"/>
<div class="field">
	<label for="citation_format"><?php echo t('citation_import_format');?></label>
	<?php echo form_dropdown('citation_format', $citation_formats);?>
</div>

<div class="field">
	<label for="citation_string"><?php echo t('paste_citation_string');?></label>
	<textarea rows="10" name="citation_string" id="citation_string" class="input-flex"><?php echo get_form_value('citation_string',isset($citation_string) ? $citation_string : ''); ?></textarea>
</div>

<div class="field">
    <label for="publish"><?php echo t('publish_citation');?></label>
    <?php echo form_dropdown('published', $publish_options, get_form_value("published",isset($published) ? $published : ''),'id="published"'); ?>
</div>

<div class="field">
    <label for="flag"><?php echo t('flag_entry_as');?></label>
    <?php echo form_dropdown('flag', $flag_options, get_form_value("flag",isset($flag) ? $flag : ''),'id="flag"'); ?>
</div>

<fieldset class="field-expanded">
	<legend><?php echo t('related_studies');?></legend>
<div class="field">
    <div id="related-surveys" style="height:200px;overflow:scroll;overflow-x: hidden;border:1px solid gainsboro;padding:5px;margin-bottom:5px;">    	
			<?php echo $survey_list; ?>
    </div> 
	<a style="display:block" class="add_survey" href="javascript:void(0);">Add Surveys</a>   
</div>
</fieldset>
<?php
/*
<div class="field">
	<label for="url"><?php echo t('bibtex_url');?></label>
	<input name="url" type="text" id="url" size="50" class="input-flex"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>

<div class="field">
	<label for="upload"><?php echo t('upload');?></label>
	<input name="file" type="file" id="file" size="50" class="input-flex"  />
</div>
*/
?>

<div class="field">
	<input type="submit" name="submit" id="submit" value="<?php echo t('submit'); ?>" />
	<?php echo anchor('admin/citations/', t('cancel'), array('class'=>''));?>
</div>


<?php echo form_close();?>
</div>
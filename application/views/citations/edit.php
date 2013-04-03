<style>
.field-expanded,.always-visible{background-color:#F8F8F8;border:1px solid gainsboro;margin-top:5px;margin-bottom:10px;margin-right:8px;}
.always-visible{padding:10px;}
.field-expanded .field, .always-visible .field {padding:5px;}
.field-expanded legend, .field-collapsed legend, .always-visible legend{background:white;padding-left:5px;padding-right:5px;font-weight:bold; cursor:pointer}
.field-collapsed{background:none; border:0px;border-top:1px solid gainsboro;margin-top:5px;margin-bottom:5px;}
.field-collapsed legend {background-image:url(images/next.gif); background-position:left top; padding-left:20px;background-repeat:no-repeat;}
.field-collapsed .field{display:none;}
.field-expanded .field label, .always-visible label{font-weight:normal;}
.page-inline-links {text-align:right;}
.page-inline-links a{text-decoration:none;}
.page-inline-links a:hover{color:red;}
.inline-fields .input-flex{width:150px;margin-right:5px;}
#citation-preview{padding:5px;}
#citation-preview .citation-title{font-weight:bold;text-decoration:underline;}
#citation-preview .citation-subtitle{ font-style:italic}
#citation-preview {background-color:#F0F0F0;border:1px solid gainsboro;display:none;}
table .input-flex{margin-bottom:5px;}
.always-visible a{cursor:pointer}
.always-visible table{margin-top:6px;}
.header span{width:12px;display:inline-block}
.headerSortUp span {background:url("<?php echo js_base_url();?>images/arrow-asc.png") no-repeat right center;}
.headerSortDown span {background:url("<?php echo js_base_url();?>images/arrow-desc.png") no-repeat right center;}


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

</style>

<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/citations/" class="button"><img src="images/house.png"/><?php echo t('citation_home');?></a> 
</div>

<div class="content-container">

<?php
	$citation_types=array(
		'book'					=>t('Book'),
		'book-section'			=>t('Book Section'),
		'report'				=>t('report'),			//same as book
		'anthology-editor'		=>t('Anthology (Author & Editor)'),
		'anthology-translator'	=>t('Anthology (Author & Translator)'),
		'corporate-author'		=>t('corporate-author'),		//todo
		'journal'				=>t('Journal'),
		'working-paper'			=>t('working-paper'), 		//same as journal
		'conference-paper'		=>t('conference-paper'),
		'magazine'				=>t('Magazine'),
		'newspaper'				=>t('Newspaper'),
		'website'				=>t('Website'),
		'website-doc'			=>t('Website Document'),
		'thesis'				=>t('Thesis or Dissertation'),
	);
	
	$flag_options=array(
		''=>'--',
		'ds_unclear'=>t('ds_unclear'),
		'incomplete'=>t('incomplete'),
		'tobe_checked'=>t('tobe_checked'),
		'duplicate'=>t('duplicate'),
		'back_to_editor'=>t('back_to_editor'),
		);
	
	$publish_options=array(
		'1'=>t('option_publish'),
		'0'=>t('option_do_not_publish')
		);
	
?>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo $form_title; ?></h1>

<?php echo form_open_multipart($this->html_form_url, array('class'=>'form') ); ?>
<input name="survey_id" type="hidden" id="survey_id" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id: ''); ?>"/>
<input name="tmp_id" type="hidden" id="tmp_id" value="<?php echo get_form_value('tmp_id',isset($tmp_id) ? $tmp_id: 'cit-'.date("U")); ?>"/>

<div id="citation-preview" class="field">
    <span class="citation-author"><?php echo isset($authors) ? $authors : ''; ?></span>
    <span class="citation-title"><?php echo isset($title) ? $title : ''; ?></span>
    <span class="citation-subtitle"><?php echo isset($subtitle) ? $subtitle : ''; ?></span>
    <span class="citation-volume"><?php echo isset($volume) ? $volume : ''; ?></span>
    <span class="citation-issue"><?php echo isset($issue) ? $issue : ''; ?></span>
    <span class="citation-pages"><?php echo isset($pages) ? $pages : ''; ?></span>
</div>

<div class="field">
	<label for="ctype"><?php echo t('select_citation_type');?></label>
	<?php echo form_dropdown('ctype', $citation_types, get_form_value("ctype",isset($ctype) ? $ctype : ''),'id="citation_type"'); ?>
    <input type="submit" name="select" id="change_type" value="Change type"/>
</div>

<span id="citation-edit-view">
<?php 
	//load the citation view based on the citation view
	$citation_view=get_form_value('ctype',isset($ctype) ? $ctype: 'book');
	$citation_view=str_replace("-","_",'edit_'.$citation_view);
	//include 'edit_book.php'; 
	$this->load->view("citations/$citation_view");
?>
</span>


<div class="field">
    <label for="doi"><?php echo t('doi');?></label>
    <input name="doi" type="text" id="doi" size="50" class="input-flex"  value="<?php echo get_form_value('doi',isset($doi) ? $doi : ''); ?>"/>
</div>

<div class="field">
    <label for="flag"><?php echo t('flag_entry_as');?></label>
    <?php echo form_dropdown('flag', $flag_options, get_form_value("flag",isset($flag) ? $flag : ''),'id="flag"'); ?>
</div>

<div class="field">
    <label for="publish"><?php echo t('publish_citation');?></label>
    <?php echo form_dropdown('published', $publish_options, get_form_value("published",isset($published) ? $published : ''),'id="published"'); ?>
</div>

<div class="field">
    <label for="owner"><?php echo t('citation_owner');?></label>
    <input name="owner" type="text" id="owner" size="50" class="input-flex"  value="<?php echo get_form_value('owner',isset($owner) ? $owner : $this->ion_auth->current_user()->username); ?>"/>
</div>
        
<fieldset class="field-expanded">
	<legend><?php echo t('abstract');?></legend>
	<div class="field">
        <textarea name="abstract" id="abstract" rows="5" class="input-flex"><?php echo get_form_value('abstract',isset($abstract) ? $abstract : ''); ?></textarea>
	</div>
</fieldset>

<fieldset class="field-expanded">
	<legend><?php echo t('related_studies');?></legend>
    <div class="field">
	    <a style="text-align:right;float:right;display:block;" class="attach_studies" href="javascript:void(0);">Attach Studies</a>   
        <div id="related-surveys" ><?php echo $survey_list; ?></div>         
    </div>
</fieldset>

<fieldset class="field-expanded">
	<legend><?php echo t('keywords');?></legend>
	<div class="field">
        <textarea name="keywords" id="keywords" rows="5" class="input-flex"><?php echo get_form_value('keywords',isset($keywords) ? $keywords : ''); ?></textarea>
	</div>
</fieldset>

<fieldset class="field-expanded">
	<legend><?php echo t('notes');?></legend>
	<div class="field">
        <textarea name="notes" id="notes" rows="5" class="input-flex"><?php echo get_form_value('notes',isset($notes) ? $notes : ''); ?></textarea>
	</div>
</fieldset>

<div class="field">
	<input type="submit" name="submit" id="submit" value="<?php echo t('submit'); ?>" />
	<?php echo anchor('admin/citations/', t('cancel'), array('class'=>'button'));?>
</div>
<?php echo form_close();?>
</div>
<script type="text/javascript">
	
	$(".input-flex").keyup(function() {
		$("#citation-preview .citation-title").html($("#title").val()+'.');
		var authors_arr=$("#authors").val().split("\n");
		var authors='';
		for(i=0;i<authors_arr.length;i++)
		{
			if (authors_arr[i]!=''){
				if (i==0){
					authors=authors_arr[i];
				}
				else{
					authors+=', '+authors_arr[i];
				}
			}
		}
		if (authors!=''){ authors+='.';}
        $("#citation-preview .citation-author").html(authors);
		
		var subtitle=$("#subtitle").val();
		if (issue!=''){subtitle=subtitle+'. ';}
		$("#citation-preview .citation-subtitle").html(subtitle);

				
		var issue=$("#issue").val();
		if (issue!=''){issue='('+issue+'), ';}
		$("#citation-preview .citation-issue").html(issue);
		
		var volume=$("#volume").val();
		if (volume!=''){volume=volume+',';}
		$("#citation-preview .citation-volume").html(volume);
				
		$("#citation-preview .citation-pages").html($("#pages").val()+'.');
		$("#citation-preview").show();
    });
	
	$('.field-expanded > legend').click(function(e) {
			e.preventDefault();
			$(this).parent('fieldset').toggleClass("field-collapsed");
			return false;
	});
	
	$(document).ready(function() {
  		$('.field-expanded > legend').parent('fieldset').toggleClass('field-collapsed');
		
		//change citation type
		$("#citation_type").change(function(){
			$("#change_type").click();
		});		
	});	
		
	//add a new author/translator/editor row
	function add_author_row(id,name)
	{
		html='<tr>';
		html+='<td><input name="'+name+'_fname[]" size="50" class="input-flex" type="text"></td>';
		html+='<td><input name="'+name+'_lname[]" size="50" class="input-flex" type="text"></td>';
		html+='<td><input name="'+name+'_initial[]" size="10" class="input-flex" type="text" maxlength="1"></td>';
		html+='<td class="remove-link"><a href="#" onclick="remove_author_row(this);return false;">remove</a></td>';
		html+='</tr>';
		
		$("#"+id).append(html);
	}
	function remove_author_row(el)
	{
		$(el).parent().parent().remove();
	}
	
	
	
	$(function(){
	
	$('.attach_studies').click(function() {
		dialog_select_related_studies();return false;});
	});
/*

	//attach related surveys
	$(function() {
		
		//temp session id
		var tmp_id=$("#tmp_id").val();
		
		//add attached surveys to session, needed when editing a citations with survey attached
		var url_add=CI.base_url+'/admin/dialog_select_studies/add/'+tmp_id+'/'+'<?php echo implode(",",$selected_surveys_id_arr);?>/1';
		$.get(url_add);
		
		//attach survey dialog
		$('.attach_studies').click(function() {
				var iframe_url=CI.base_url+'/admin/dialog_select_studies/index/'+tmp_id;
				$('<div id="dialog-modal" title="Select Related Surveys"></div>').dialog({ 
					height: 500,
					width: 700,
					resizable: false,
					draggable: false,
					modal: true,
					close: function() {
						$.get(CI.base_url+'/admin/citations/dialog_select_studies/'+tmp_id, function(data) {
							$('#related-surveys').html(data);
							related_surveys_click();
						});
					}
				}).append('<iframe height="495" width="654" src="'+iframe_url+'" frameborder="0"></iframe>');
				return false;
		});
		
		related_surveys_click();
	});

	//attach click event handler for survey select/unselect
	function related_surveys_click()
	{
			$('.chk').unbind('click').click(function(e) {
			
			var tmp_id=$("#tmp_id").val();
			
			if($(this).is(':checked')) {
            	url=CI.base_url+'/admin/dialog_select_studies/add/'+tmp_id+'/'+$(this).val()+'/1';
         	}
			else{
				url=CI.base_url+'/admin/dialog_select_studies/remove/'+tmp_id+'/'+$(this).val()+'/1';
			}
			
			$.get(url);
		});
	}
*/


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

<?php 
/**
*
* Create multi-textbox field for authors, editors, translators
*	
*	@name	name for the field (author, editor, translator)
*/
function form_author_field($name,$title)
{
	//names
	$fname=$name.'_fname';
	$lname=$name.'_lname';
	$initial=$name.'_initial';
	
	//read postback values
	$fnames=get_form_value($fname,isset($$fname) ? $$fname: array('') );
	$lnames=get_form_value($lname,isset($$lname) ? $$lname: array('') );
	$initials=get_form_value($initial,isset($$initial) ? $$initial: array('') );
	
	$table_id='citation-fieldset-'.$name;
	
	$output=	'<fieldset class="always-visible">';
	$output.=	'<legend>'.$title.'</legend>';

	$output.=	'<table border="0" class="inline-fields" id="'.$table_id.'">';
	$output.=	'<tr>
					<th><label>'.t('first_name').'</label></th>
					<th><label>'.t('last_name').'</label></th>
					<th><label>'.t('middle_initial').'</label></th>
				</tr>';
	
	//create input fields
	for($i=0;$i<count($fnames);$i++)
	{
		$id="";
		$class=' class="dynamic"';
		$remove_link='<a href="#" onclick="remove_author_row(this);return false;">remove</a>';
		
		if ($i==0)
		{
			$id=sprintf('id="citation-%s-%s"',$name,$i);
			$class=' class="static"';
			$remove_link='&nbsp;';
		}

    	$output.='<tr '.$id.$class.'>
					<td><input name="'.$fname.'[]" type="text" size="50" class="input-flex"  value="'.$fnames[$i].'"/></td>
					<td><input name="'.$lname.'[]" type="text" size="50" class="input-flex"  value="'.$lnames[$i].'"/></td>
					<td><input name="'.$initial.'[]" type="text" size="50" class="input-flex"  value="'.$initials[$i].'" maxlength="1"/></td> 
					<td class="remove-link">'.$remove_link.'</td>
        		</tr>';
	}
	
	$output.=	'</table>';
	$output.=	sprintf('<a href="#" onclick="add_author_row(\'%s\',\'%s\');return false;">Click here to add more...</a>',$table_id,$name);
	$output.=	'</fieldset>';

	return $output;
}
?>
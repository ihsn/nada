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

<?php echo form_open_multipart(current_url(), array('class'=>'form') ); ?>
<input name="survey_id" type="hidden" id="survey_id" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id: ''); ?>"/>

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
        
<fieldset class="field-expanded">
	<legend><?php echo t('abstract');?></legend>
	<div class="field">
        <textarea name="abstract" id="abstract" rows="5" class="input-flex"><?php echo get_form_value('abstract',isset($abstract) ? $abstract : ''); ?></textarea>
	</div>
</fieldset>

<fieldset class="field-expanded">
	<legend><?php echo t('related_studies');?></legend>
<div class="field">
    <div id="related-surveys" style="height:200px;overflow:scroll;overflow-x: hidden;border:1px solid gainsboro;padding:5px;margin-bottom:5px;">    	
			<?php echo $survey_list; ?>
    </div>    
    <a style="" href="#clear" title="Clear all the selected studies" onclick="clear_studies();return false"><?php echo t('clear_selection');?></a>
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
	function clear_studies()
	{
		$('.chk').each(function(){ 
            this.checked = false; 
         });
	}
	
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
<style>
.field-expanded,.always-visible{background-color:#F8F8F8;border:1px solid gainsboro;margin-top:5px;margin-bottom:10px;margin-right:8px;}
.always-visible{padding:10px;}
.field-expanded .field, .always-visible .field {padding:5px;}
.field-collapsed{background:none; border:0px;border-top:1px solid gainsboro;margin-top:5px;margin-bottom:5px;}
.field-collapsed .field{display:none;}
.field-expanded .field label, .always-visible label{font-weight:normal;}
.page-inline-links {text-align:right;}
.page-inline-links a{text-decoration:none;}
.page-inline-links a:hover{color:red;}
.form {font-size: 10pt !important; }
.inline-fields .input-flex{width:150px;margin-right:5px;}
#citation-preview{padding:5px;}
#citation-preview .citation-title{font-weight:bold;text-decoration:underline;}
table .input-flex{margin-bottom:5px;}
.always-visible a{cursor:pointer}
.always-visible table{margin-top:6px;}
.header span{width:12px;display:inline-block}
.headerSortUp span {background:url("<?php echo js_base_url();?>images/arrow-asc.png") no-repeat right center;}
.headerSortDown span {background:url("<?php echo js_base_url();?>images/arrow-desc.png") no-repeat right center;}

.ui-dialog .ui-dialog-content {background:white;}
.ui-widget-header{background:black;border:none;}


.citation-edit .form-control{
	height:auto!important;
	max-width:98%;
}

.dd-content-container .citation-edit fieldset legend {
    font-weight: bold;
    cursor: pointer;
    padding: 5px;
    display: inline!important;
    width: auto;
    font-size: 14px;
}

.form-inline .form-group label{
	display:block;
	width:100%;
}
</style>


<div class="content-container citation-edit">

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

<form method="post" class="form">

<div id="citation-preview" class="field">
<!--
    <span class="citation-subtitle"><?php echo isset($citation['subtitle']) ? $citation['subtitle'] : ''; ?></span>
    <span class="citation-volume"><?php echo isset($citation['volume']) ? $citation['volume'] : ''; ?></span>
    <span class="citation-issue"><?php echo isset($citation['issue']) ? $citation['issue'] : ''; ?></span>
    <span class="citation-pages"><?php echo isset($citation['pages']) ? $citation['pages'] : ''; ?></span>
-->
</div>

<div class="field">
	<label for="ctype"><?php echo t('select_citation_type');?></label>
	<?php echo form_dropdown('ctype', $citation_types, get_form_value("ctype",isset($citation['ctype']) ? $citation['ctype'] : ''),'id="citation_type"'); ?>
    <input type="submit" name="select" id="change_type" value="Change type"/>
</div>

<span id="citation-edit-view">
<?php 
	//load the citation view based on the citation view
	$citation_view=get_form_value('ctype',isset($citation['ctype']) ? $citation['ctype'] : 'book');
	$citation_view=str_replace("-","_",'edit_'.$citation_view);
	//include 'edit_book.php'; 
	$this->load->view("citations/$citation_view");
?>
</span>


<div class="field">
    <label for="doi"><?php echo t('doi');?></label>
    <input name="doi" type="text" id="doi" size="50" class="input-flex"  value="<?php echo get_form_value('doi',isset($citation['doi']) ? $citation['doi'] : ''); ?>"/>
</div>

<?php /*
<div class="field">
    <label for="flag"><?php echo t('flag_entry_as');?></label>
    <?php echo form_dropdown('flag', $flag_options, get_form_value("flag",isset($citation['flag']) ? $citation['flag'] : ''),'id="flag"'); ?>
</div>
*/ ?>
<?php /*
<div class="field">
    <label for="owner"><?php echo t('citation_owner');?></label>
    <input name="owner" type="text" id="owner" size="50" class="input-flex"  value="<?php echo get_form_value('owner',isset($citation['owner']) ? $citation['owner'] : $this->ion_auth->current_user()->username); ?>"/>
</div>
*/?>

<fieldset class="field-expanded">
	<legend><?php echo t('abstract');?></legend>
	<div class="field">
        <textarea name="abstract" id="abstract" rows="5" class="input-flex"><?php echo get_form_value('abstract',isset($citation['abstract']) ? $citation['abstract'] : ''); ?></textarea>
	</div>
</fieldset>

<fieldset class="field-expanded">
	<legend><?php echo t('keywords');?></legend>
	<div class="field">
        <textarea name="keywords" id="keywords" rows="5" class="input-flex"><?php echo get_form_value('keywords',isset($citation['keywords']) ? $citation['keywords'] : ''); ?></textarea>
	</div>
</fieldset>

<?php /*
<fieldset class="field-expanded">
	<legend><?php echo t('notes');?></legend>
	<div class="field">
        <textarea name="notes" id="notes" rows="5" class="input-flex"><?php echo get_form_value('notes',isset($citation['notes']) ? $citation['notes'] : ''); ?></textarea>
	</div>
</fieldset>
*/?>
<div class="field">
	<div onclick="$('.form').submit();" class="btn btn-primary">
        <span>Submit</span>
    </div>
    <a class="btn_cancel" href="<?php echo site_url('datadeposit/citations/'.$this->uri->segment(3));?>" >Cancel</a></div>
</div>
</form>

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
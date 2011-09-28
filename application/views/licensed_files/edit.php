<?php
/**
* Menu Add/Edit form
*/
?>
<div class="content-container">
<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/menu/add" class="button"><img src="images/icon_plus.gif"/>Add new</a> 
    <a href="<?php echo site_url(); ?>/admin/menu/add/external" class="button"><img src="images/icon_plus.gif"/>Add external page</a> 
    <span class="button">Home</span>
</div>

<h1 class="page-title">Add files</h1>
<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=isset($this->error) ? $this->error : '';?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php echo form_open(current_url(), array('class'=>'form') ); ?>
    <div class="field">
        <label for="filepath">Provide path/url to the licensed files. The file paths will not be shown to the user.</label>
        <?php for($i=0;$i<10;$i++):?>
        <input class="input-flex" name="filepath[]" type="text" id="filepath<?php echo $i; ?>"  value="<?php echo isset($filepath[$i]) ? $filepath[$i]: ''; ?>"/>
        <?php endfor;?>
    </div>


<?php
 //edit user
 if (isset($id) )
 {
	echo form_submit('submit','Update'); 
 }
 else
 {
	echo form_submit('submit','Add'); 
 }
 	echo anchor('admin/menu','Cancel',array('class'=>'button') );	
?>

<? echo form_close(); ?>    
</div>
<script type="text/javascript">

$(document).ready(function() {

    $("#title").change(function() {
		if( $("#url").val()==''){
			$path=$("#title").val().trim().replace(/\s/g,"-").toLowerCase();
			$("#url").val($path);
			$("#url-label").html(CI.base_url+'/'+$path);
		}
    });
	$("#url").keyup(function() {		
	       $("#url-label").html(CI.base_url+'/'+$("#url").val());
    });

});
</script>
<script type="text/javascript">
<?php 
/*
//TODO:
1. read about the compressor - http://wiki.moxiecode.com/index.php/TinyMCE:Compressor/PHP
2. move this script to a seperate file
3. setup css file references
4. setup image_list_url below
*/
?>

tinyMCE.init({
	// General options
	mode : "textareas",
	theme : "advanced",
	plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

	// Theme options
	theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
	theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
	theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
	theme_advanced_buttons4 : "moveforward,movebackward,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,

	//site styles
	content_css : "<?php echo base_url(); ?>themes/default/styles.css",

	// Drop lists for link/image/media/template dialogs
	template_external_list_url : "js/template_list.js",
	external_link_list_url : "js/link_list.js",
	external_image_list_url : "js/image_list.js",
	media_external_list_url : "js/media_list.js"

});
</script>
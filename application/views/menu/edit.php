<div class="container-fluid page-menu-edit">
<?php
	//menu breadcrumbs
	include 'menu_breadcrumb.php';
?>

<h1 class="page-title"><?php echo isset($id) ? t('menu_edit') : t('menu_add'); ?></h1>
<?php if (validation_errors() ) : ?>
    <div class="alert alert-danger">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php echo form_open($this->html_form_url, array('class'=>'form') ); ?>
    <div class="form-group">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="form-control" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
        <input type="hidden" name="pid" value="<?php echo get_form_value('pid',isset($pid) ? $pid : ''); ?>"/>
    </div>

    <div class="form-group" >
     	<label for="username"><?php echo t('url');?><span class="required">*</span></label>
        <input class="form-control"  name="url" type="text" id="url"  value="<?php echo get_form_value('url',isset($url) ? $url : '') ; ?>"/>
        <label for="url" class="desc" id="url-label"><?php echo site_url(); ?>/<?php echo get_form_value('url',isset($url) ? $url : '') ; ?></label>
    </div>

    <div class="form-group">
        <label for="body"><?php echo t('body');?></label>
        <textarea id="body" class="form-control"  name="body" rows="20"><?php echo get_form_value('body',isset($body) ? $body : ''); ?></textarea>
    </div>

	<div class="form-group form-inline form-inline-with-spacing">
		
		<div class="form-group field">
			<label for="target"><?php echo t('open_in');?><span class="required">*</span></label>
			<?php echo form_dropdown('target', array(0=>t('same_window'),1=>t('new_window')), get_form_value("target",isset($target) ? $target : '')); ?>
		</div>
		
		<div class="form-group">
			<label for="weight"><?php echo t('weight');?><span class="required">*</span></label>
			<input class="form-control" name="weight" type="text" id="weight" size="3"  value="<?php echo get_form_value('weight',isset($weight) ? $weight : ''); ?>"/>		
		</div>

		<div class="form-group field">
			<label for="published"><?php echo t('publish');?><span class="required">*</span></label>
			<?php echo form_dropdown('published', array(1=>t('yes'),0=>t('no')), get_form_value("published",isset($published) ? $published : '')); ?>
		</div>

	</div>

<div class="form-group"><?php echo form_submit('submit',t('update'),array('class'=>'btn btn-primary','id'=>'btnupdate')); ?>
<?php echo anchor('admin/menu',t('cancel'),array('class'=>'btn btn-default') );?></div>

<? echo form_close(); ?>
</div>
<script type="text/javascript">

$(document).ready(function() {

    $("#title").change(function() {
		if( $("#url").val()==''){
			$path=$("#title").val().trim().replace(/\s/g,"-").toLowerCase();
			$("#url").val($path);
			$("#url-label").text(CI.base_url+'/'+$path);
		}
    });
	$("#url").keyup(function() {
	       $("#url-label").text(CI.base_url+'/'+$("#url").val());
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
<?php if ($this->config->item("use_html_editor")!=="no"):?>
tinyMCE.init({
	mode : "textareas",
	theme : "advanced",
	plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

	// Theme options
	theme_advanced_buttons1 : "save,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
	theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
	theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
	theme_advanced_buttons4 : "moveforward,movebackward,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	document_base_url : "<?php echo base_url();?>",

	//site styles
	//content_css: "<?php echo base_url(); ?>themes/default/styles.css",

	// Drop lists for link/image/media/template dialogs
	//template_external_list_url : "js/template_list.js",
	//external_link_list_url : "js/link_list.js",
	external_image_list_url : "index.php/tinymce/image_list",
	//media_external_list_url : "js/media_list.js",

	setup : function(ed) {
      ed.onSaveContent.add(function(ed, o) {
	  	$('#btnupdate').click();
	  });
   	}

});

<?php endif;?>
</script>

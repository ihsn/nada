<!--<pre>
<?php //var_dump($rows);?>
</pre>-->
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
.row{margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid gainsboro;}
.survey-row .links{text-align:left;margin-right:10px;font-size:smaller;margin-top:10px;}
h3{font-size:16px;margin-top:0px;margin-bottom:5px;}
.filter{font-size:smaller;}
.result-count{color:gray;font-size:smaller}

/*box*/
.box{
	border:1px solid gainsboro;
	margin-right:5px;
	line-height:150%;
	margin-bottom:10px;
	-webkit-border-radius:3px;
	border-radius:3px;
}

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
.pad5{padding:5px;}
.pad10{padding:10px;}
.box-body .input-flex{width:85%;}
.vscroll{overflow:auto;overflow-x:hidden;height:300px;}
.mini{width:70%;}
.btn-tiny{font-size:11px;}
.box .field{margin-bottom:10px;}
.sort_by{display:inline;}
.sort_by li {list-style:none;display:inline;margin-right:5px; border-left:1px solid gainsboro;padding-left:5px;}
.sort_by a{color:gray;font-size:11px;}
.sort_by li a.selected{color:black;font-weight:bold;}
.tags{float:right;}
.tags .label{font-weight:normal;}
</style>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>


<div class="body-container" style="padding:10px;">
<?php $this->load->view('catalog/catalog_page_links');?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title">
	<?php echo t('catalog_maintenance');?>
    <?php if ( isset($this->active_repo->id)):?>
    	<span class="active-repo">[<?php echo $this->active_repo->title;?>]</span><span class="link-change"><?php echo anchor('admin/repositories/select',t('change_repo'));?></span>
    <?php endif;?>
</h1>

<?php if (!$rows): ?>
	<?php echo t('no_records_found');return;?>
<?php endif;?>


<!--
<div class="">  
  <select name="field" id="field" class="" style="margin-right:5px;">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="titl"	<?php echo ($this->input->get('field')=='titl') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="nation"	<?php echo ($this->input->get('field')=='nation') ? 'selected="selected"' : '' ; ?> ><?php echo t('country');?></option>
    <option value="surveyid"><?php echo t('survey_id');?></option>
    <option value="authenty"><?php echo t('producer');?></option>
    <option value="sponsor"><?php echo t('sponsor');?></option>
    <option value="repositoryid"><?php echo t('repository');?></option>
  </select>
  
  <input  type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
   <input class="" type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a  class="btn-link" href="<?php echo site_url();?>/admin/catalog"><?php echo t('reset');?></a>
  <?php endif; ?>

</div>
-->


<div class="row-fluid">
	<form class="left-pad span10" style="margin-bottom:10px;" method="GET" id="catalog-search">
    <div id="surveys">
	<?php $this->load->view('catalog/search');?>
    </div>    
	</form>

    <div id="side-bar" class="span2">
	<?php $this->load->view('catalog/index_sidebar'); //right side bar?>
    </div>
    
</div> 


</div>


<script type='text/javascript'>
//translations	
var i18n={
		'no_item_selected':"<?php echo t('js_no_item_selected');?>",
		'confirm_delete':"<?php echo t('js_confirm_delete');?>",
		'js_loading':"<?php echo t('js_loading');?>"
		};
		
$(".box .box-header").click(function(e){
	toggle_sidebar(this);
	return false;
});

function toggle_sidebar(e){
	$(e).parent().toggleClass("iscollapsed");
	$(e).parent().find(".box-body").toggleClass("collapse");
}

function search()
{
	data=$("#form_filter").serialize();
	$("#surveys").html('<img src="images/loading.gif"/><?php echo t('js_updating_please_wait');?>');
	$.ajax({
		timeout:1000*120,
		dataType: "html",
		data:data,
		type:'GET', 
		url: CI.base_url+'/admin/catalog/search/',
		success: function(data) {
			$("#surveys").html(data);
		},
		error: function(XHR,err) {
			$("#surveys").html("Error occured " + XHR.status + " - " + err);
		}
	});
}

jQuery(document).ready(function(){
	//checkbox select/deselect
	$("#form_filter input[type=checkbox]").live("click",function(e){
		search();
	});
	
	$("#form_filter input[type=textbox]").live('keyup',function(event){		
		if(event.keyCode==13){
			search();
		}
	});

	$("#form_filter select").live("change",function(e){
		search();
	});
	
});
</script>
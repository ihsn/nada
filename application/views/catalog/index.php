<?php  $this->template->add_css($this->load->view('catalog/catalog_style'),'embed'); ?>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>


<div class="body-container" style="padding:10px;">
<?php //$this->load->view('catalog/catalog_page_links');?>

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
		'js_loading':"<?php echo t('js_loading');?>",
		'published':"<?php echo t('published');?>",
		'unpublished':"<?php echo t('unpublished');?>"
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
	$("#surveys").html('<img src="images/loading.gif"/><?php echo t('js_loading');?>');
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
	//search using filter
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

	$("#form_filter .apply-filter").live("click",function(e){
		search();
	});

	//set max height for div and add vertical scroll bars 
	var max_height = 100;
	$('.scrollable').each(function(index) {
		$(this).text();
		var actual_height = $(this).height();
		if (actual_height > max_height){
			$(this).addClass('vscroll');
		};
	});
	
	//toggle study publish/unpublish status
	function toggle_study_status(elem){
		if (elem.attr("data-value")==0){
			elem.attr("data-value",1);
			elem.html(i18n.published);
			elem.addClass("label-success");
		}
		else{
			elem.html(i18n.unpublished);
			elem.attr("data-value",0);
			elem.removeClass("label-success");
		}
	}
	
	//publish/unpublish
	$(document.body).on("click",".survey-row .publish", function(){ 
		var studyid=$(this).attr("data-sid");
		var status=$(this).attr("data-value");
		var elem=$(this);			
		
		if(status==0){
			status=1;
		}
		else{
			status=0;
		}
		
		$.post(CI.base_url+'/admin/catalog/publish/'+studyid+'/'+status+'?ajax=1',{submit:"submit"},
			  function(data){
				toggle_study_status( elem );
			  }, "json")
			  .fail(function() { alert(i18n.update_failed);});			  			
	});
	
});

</script>
<?php  //$this->template->add_css($this->load->view('catalog/catalog_style'),'embed'); ?>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>


<div class="container-fluid">
<?php //$this->load->view('catalog/catalog_page_links');?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<h1 class="page-title">
	<?php echo t('catalog_maintenance');?>
    <?php if ( isset($this->active_repo->id)):?>
    	<span class=""> \ <?php echo $this->active_repo->title;?></span><span class="link-change"><?php echo anchor('admin/repositories/select',t('change_repo'));?></span>
    <?php endif;?>
</h1>


<div class="row">
	<div id="side-bar" class="col-md-3">
		<?php $this->load->view('catalog/index_sidebar'); //right side bar?>
    </div>
	<div class="col-md-9">
		<?php if (!$rows): ?>
			<div>
				<?php echo t('no_records_found');?>
				<a href="<?php echo site_url('admin/catalog');?>" class="btn btn-primary btn-sm">Reset search</a>
			</div>
		<?php else:?>
		<form  method="GET" id="catalog-search">
			<div id="surveys">
				<?php $this->load->view('catalog/search');?>
			</div>    
		</form>
		<?php endif;?>

	
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
	$("#form_filter").submit();
	return;
	$("#surveys").html('<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i><?php echo t('js_loading');?>');
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
	$("#form_filter input[type=checkbox]").on("click",null,function(e){
		search();
	});
	
	$("#form_filter input[type=textbox]").on('keyup',null,function(event){		
		if(event.keyCode==13){
			search();
		}
	});

	$("#form_filter select").on("change",null,function(e){
		search();
	});

	$("#form_filter .apply-filter").on("click",null,function(e){
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

	//publish/draft status
	$('.publish-toggle').change(function() {
		var studyid=$(this).attr("data-sid");
		
		if ($(this).prop('checked')){
			status=1
		}
		else{
			status=0
		}

		$.post(CI.base_url+'/admin/catalog/publish/'+studyid+'/'+status+'?ajax=1',{submit:"submit"},
			function(data){
			//toggle_study_status( elem );
			}, "json")
			.fail(function() { 
				$(this).prop('checked', !status).change();//undo status change
				alert(i18n.update_failed);
			});		
    })

	
	
});

</script>

<?php
//echo $feed->get_item_quantity();
//$rows=$feed->get_items(0,100);?>

<style>
.completed,.COMPLETED,.harvested{background-color:#00CC00;color:#00CC00;}
.pending,.new{background-color:#FFCC33;color:#FFCC33}
.error,.FAILED{background-color:#FF0000;color:#FF0000}
.processing-box{border:0px solid gainsboro;padding:10px;margin-top:10px;margin-bottom:10px;font-size:16px;background-color:#00CC33;color:white;}
strong{font-weight:bold;}
.fixed-title{width:100px;overflow:hidden;height:25px;}
.batch-header{background-color:#666666;color:white;padding:5px;}
#batch-import-box{padding:0px;margin-bottom:50px;display:none;}
#batch-import-processing{background-color:#00CC33;color:white;padding:10px;font-size:16px;}
#batch-import-log{height:100px;overflow:scroll;border:1px solid #00CC33; overflow-x:hidden;}
.log{border-bottom:1px solid gainsboro;padding:5px;}

</style>
<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>

<div class="page-links">
<a href="<?php echo site_url(); ?>/admin/repositories/" class="button"><img src="images/icon_plus.gif"/><?php echo t('repositories');?></a> 
<a id="run-harvester" href="<?php echo site_url(); ?>/admin/menu" class="button"><img src="images/icon_play.gif"/><?php echo t('harvest');?></a> 
</div>
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('ddi_harvester');?></h1>

<div id="batch-import-box" >
    <div id="batch-import-processing" style="padding:5px;">Processing survey...</div>
    <div id="batch-import-log" ></div>
    <div id="batch-footer"><a style="float:right;" href="#" onclick="batch_process.abort();return false;"><?php echo t('cancel_process');?></a></div>
</div>


<form class="left-pad" style="margin-bottom:10px;" method="GET" id="user-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo $this->input->get('keywords'); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="repositoryid"	<?php echo ($this->input->get('field')=='repositoryid') ? 'selected="selected"' : '' ; ?> ><?php echo t('repository');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo current_url();?>"><?php echo t('reset');?></a>
  <?php endif; ?>
</form>
<?php endif; ?>
<?php if ($rows): ?>
<?php		
		$sort_by=$this->input->get("sort_by");
		$sort_order=$this->input->get("sort_order");			
?>
<?php 
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;
	
	//sort
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	
	//current page url
	$page_url=site_url().$this->uri->uri_string();
?>

<?php
	if ($this->pagination->cur_page>0) {
		$to_page=$this->pagination->per_page*$this->pagination->cur_page;

		if ($to_page> $this->pagination->total_rows) 
		{
			$to_page=$this->pagination->total_rows;
		}

		$pager=sprintf(t('showing %d-%d of %d')
						,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
						,$to_page
						,$this->pagination->total_rows);
	}
	else
	{
		$pager=sprintf(t('showing %d-%d of %d')
				,$current_page
				,$this->pagination->total_rows
				,$this->pagination->total_rows);
	}
?>

<form autocomplete="off">

	<!-- batch operations -->
    <table width="100%">
        <tr>
            <td>
                <select id="batch_actions">
                    <option value="-1"><?php echo t('batch_actions');?></option>
                    <option value="delete"><?php echo t('delete');?></option>
                    <option value="harvest"><?php echo t('status_harvest');?></option>
                    <option value="ignore"><?php echo t('status_ignore');?></option>
                    <option value="new"><?php echo t('status_reset');?></option>                    
                </select>
                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>                
            </td>
            <td align="right">
                <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
            </td>
        </tr>
    </table>
    
    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'repositoryid',t('repositoryid'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'country',t('country'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url); ?></th>            
			<th><?php echo create_sort_link($sort_by,$sort_order,'changed',t('last_harvested'),$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'status',t('status'),$page_url); ?></th>
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'retries',t('retries'),$page_url); ?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->id; ?>" id="<?php echo $row->id; ?>" class="chk"/></td>
            <td><?php echo $row->repositoryid?></td>
            <td><?php echo $row->country?></td>
            <td class="row-title"><a href="<?php echo $row->survey_url; ?>"><?php echo $row->title?></a></td>
			<td nowrap="nowrap"><?php echo date("m/d/y H:i:s",$row->changed); ?></td>
			<td><div title="<?php echo $row->status; ?>" class="<?php echo $row->status; ?>"><?php echo $row->status; ?></div></td>
            <td><?php echo $row->retries; ?></td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>
</form>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>

<script type="text/javascript" >
jQuery(document).ready(function(){
	$(".ceebox2").ceebox();
});


//checkbox select/deselect
jQuery(document).ready(function(){
	$("#run-harvester").click(
			function (e) 
			{
				batch_process.process();
				return false;
			}
	);
	$("#chk_toggle").click(
			function (e) 
			{
				$('.chk').each(function(){ 
                    this.checked = (e.target).checked; 
                }); 
			}
	);
	$("#batch_actions_apply").click(
		function (e){
			if( $("#batch_actions").val()=="delete"){
				batch_delete();
			}
			else if( $("#batch_actions").val()=="harvest"){
				update_status($("#batch_actions").val())
			}
			else if( $("#batch_actions").val()=="ignore"){
				update_status($("#batch_actions").val())
			}
			else if( $("#batch_actions").val()=="new"){
				update_status($("#batch_actions").val())
			}
		}
	);			
});


function batch_delete(){
	if ($('.chk:checked').length==0){
		alert("You have not selected any items");
		return false;
	}
	if (!confirm("Are you sure you want to delete the selected item(s)?"))
	{
		return false;
	}
	selected='';
	$('.chk:checked').each(function(){ 
		if (selected!=''){selected+=',';}
        selected+= this.value; 
     });
	
	$.ajax({
		timeout:1000*120,
		cache:false,
        dataType: "json",
		data:{ submit: "submit",id:selected},
		type:'POST', 
		url: CI.base_url+'/admin/harvester/delete/?ajax=true',
		success: function(data) {
			if (data.success){
				location.reload();
			}
			else{
				alert(data.error);
			}
		},
		error: function(XHR, textStatus, thrownError) {
			alert("Error occured " + XHR.status);
		}
	});	
}

function update_status(status_code){
	if ($('.chk:checked').length==0){
		alert("You have not selected any items");
		return false;
	}

	selected='';
	$('.chk:checked').each(function(){ 
		if (selected!=''){selected+=',';}
        selected+= this.value; 
     });
	
	$.ajax({
		timeout:1000*120,
		cache:false,
        dataType: "json",
		data:{ submit: "submit",id:selected,status:status_code},
		type:'POST', 
		url: CI.base_url+'/admin/harvester/set_status/?ajax=true',
		success: function(data) {
			if (data.success){
				location.reload();
			}
			else{
				alert(data.error);
			}
		},
		error: function(XHR, textStatus, thrownError) {
			alert("Error occured " + XHR.status);
		}
	});	
}



var batch_process = {
	
	id:null,
	queue:[],
	queue_idx:0,
	xhr:null,
	isprocessing:false,
	
	process : function() {
		
		if (this.isprocessing==true){
			return false;
		}
		
		this.queue_idx=0;
		this.queue=[];
		obj=this;
		var i=0;

		$('.chk').each(function(){ 
		   if (this.checked==true) {
				obj.queue[i++]={id:this.id,name:$(this).parent().parent().find(".row-title:first").text()};
		   }
	    }); 

		html=$("#batch-import-box").html();
		$("#batch-import-log").html("");
		this.process_queue();
	},
	
	//process items in queue
	process_queue: function(){
		if (this.queue_idx<this.queue.length) {			
			
			html='<img src="images/loading.gif" align="absbottom"> Processing '+ (this.queue_idx+1) +' of '+this.queue.length+'... <b class="fixed-title">['+this.queue[this.queue_idx].name+']</b>';
			//html+=' <a style="float:right;" href="#" onclick="batch_process.abort();return false;"><?php echo t('cancel_process');?></a>';
			$("#batch-import-box").show();
			$("#batch-import-processing").html(html);
			
			this.isprocessing=true;
			this.import_single(this.queue[this.queue_idx++].id);		
		}
		else{
			$("#batch-import-processing").html('<?php echo t('process_completed');?>');
			this.isprocessing=false;
		}		
	},
	
	import_single: function(id) {

	obj=this;
	this.xhr=$.ajax({
			type: "GET",
			url: CI.base_url+"/admin/harvester/process_single/"+id,
			dataType: "json",
			success: function(data){
					if (data.success){
						obj.queue[obj.queue_idx-1].status=data.success;
						$("#batch-import-log").append('<div class="log" style="color:green;">#' + (obj.queue_idx) + ': '  + obj.queue[obj.queue_idx-1].name + ' - ' + data.success+ '</div>');
					 }
					 else{
						obj.queue[obj.queue_idx-1].status=data.error;
						$("#batch-import-log").append('<div class="log" style="color:red">#' + (obj.queue_idx) + ': '  +  obj.queue[obj.queue_idx-1].name + ' - ' + data.error+ '</div>');
					 }
					 obj.process_queue();
			},
			error: function(XHR, textStatus, errorThrown){
				if (textStatus=='parseerror')
				{
					obj.queue[obj.queue_idx-1].status="Parse error";
				}
				else if (textStatus=='error')
				{
					obj.queue[obj.queue_idx-1].status='Error ';
					$("#batch-import-log").append('<div class="log" style="color:red">#' + (obj.queue_idx) + ': '  +  obj.queue[obj.queue_idx-1].name + ' - ' + "error..."+ '</div>');
				}

				//console.log(XHR);
				//console.log(textStatus);
				//console.log(errorThrown);
				alert(textStatus);
				obj.process_queue();
			}
	});

	},
	
	abort: function(){
		$("#batch-import-processing").html('<?php echo t('import_cancelled');?>');
		this.xhr.abort();
		this.isprocessing=false;
	}
	
};
</script>
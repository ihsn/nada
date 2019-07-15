<style type="text/css">
.active-repo{background:gainsboro;padding:5px;}
.state-hidden{
	display:none;
}
</style>

<?php
//get repositories list by user access
$user_repositories=$this->acl->get_user_repositories();
$repositories_list=array();
foreach($user_repositories as $repo)
{
	$repositories_list[$repo["repositoryid"]]=$repo['title'];
}

//active repository
$active_repository='';

//get active repo
if (isset($active_repo) && $active_repo!=NULL)
{
	$active_repository=$active_repo->repositoryid;
}
?>

<?php 
//batch uploader using PLUPLOAD
$batch_upload_options=array(
	'allowed_extensions'	=>'xml,rdf',
	'destination_url'		=>'admin/catalog/batch_import',
	'upload_url'			=>'admin/catalog/process_batch_uploads'
);
$batch_uploader=$this->load->view('catalog/batch_file_upload',$batch_upload_options,TRUE);
?>

<div class="container-fluid">

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('batch_import_title');?> <span class="label label-default"><?php echo $repositories_list[$active_repository];?></span></h1>
<div>

<?php if ( count($files)==0 || $files===false) :?>
    <div class="alert alert-block">
		<?php echo sprintf(t('import_ddi_no_files_found'),$this->config->item('ddi_import_folder'));?>
    </div>
    
    <?php echo $batch_uploader;?>
    <?php return; ?>
<?php endif; ?>

<div>
	<?php echo sprintf(t('import_ddi_files_found'),count($files));?> <input class="btn btn-primary" type="button" name="import" value="<?php echo t('btn_import');?>" onclick="batch_import.process();"/>
    
    <div>
    
    <div class="form-group">
        <label for="overwrite" class="desc" >
        	<input type="checkbox" name="overwrite" id="overwrite" checked="checked"  value="yes"/> <?php echo t('ddi_overwrite_exist');?> 
        </label>
    </div>  
    
    </div>
</div>

<div class="note" id="batch-import-box" style="display:none;" >
    <div id="batch-import-processing" style="padding:5px;border-bottom:1px solid gainsboro;margin-bottom:10px;">Processing survey...</div>
    <div id="batch-import-log" ></div>
</div>


<?php echo form_open_multipart('admin/catalog/batch_import', array('class'=>'form')	 );?>
<input type="hidden" name="repositoryid" id="repositoryid" value="<?php echo $active_repository;?>"/>

<div class="batch-links">
<a class="batch-uploader" href="#" onclick="javascript:return false;"><?php echo t('batch_upload_files');?></a> | 
<a href="<?php echo site_url('admin/catalog/clear_import_folder');?>"><?php echo t('clear_import_folder');?></a>
</div>

<div class="uploader-body state-hidden"><?php echo $batch_uploader;?></div>

<table class="table table-striped" width="100%" cellspacing="0" cellpadding="0"> 
<tr align="left" class="header">
	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
    <th><?php echo t('name');?></th>
    <th><?php echo t('size');?></th>
    <th><?php echo t('date');?></th>
</tr>
<?php foreach($files as $file):?>
	<tr>
    	<td><input type="checkbox" class="chk" id="<?php echo base64_encode($file['server_path']);?>" value="<?php echo $file['name'];?>"/></td>
    	<td><?php echo $file['name'];?></td>
        <td><?php echo format_bytes($file['size']);?></td>
        <td><?php echo date($this->config->item('date_format'),$file['date']);?></td>
    </tr>
<?php endforeach;?>
</table>
<?php echo form_close();?>
</div>

</div>
<script language="javascript">
//translations	
var i18n=
{
'cancel_import_process':"<?php echo t('cancel_import_process');?>",
'import_completed':"<?php echo t('import_completed');?>",
'import_cancelled':"<?php echo t('import_cancelled');?>"
};

$(".log").css({ border: '1px solid gray'});
var batch_import = {
	
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
				obj.queue[i++]={id:this.id,name:this.value};
		   }
	    }); 

		html=$("#batch-import-box").html();
		$("#batch-import-log").html("");
		this.process_queue();
	},
	
	//process items in queue
	process_queue: function(){
		if (this.queue_idx<this.queue.length) {			
			
			html='<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Importing '+ (this.queue_idx+1) +' of '+this.queue.length+'... <b>['+this.queue[this.queue_idx].name+']</b>';
			html+=' <a href="#" onclick="batch_import.abort();return false;">' +i18n.cancel_import_process+'</a>';
			$("#batch-import-box").show();
			$("#batch-import-processing").html(html);
			
			this.isprocessing=true;
			this.import_single(this.queue[this.queue_idx++].id);		
		}
		else{
			$("#batch-import-processing").html(i18n.import_completed);
			this.isprocessing=false;
		}
		
	},
	
	import_single: function(id) {
		obj=this;
		//set error hanlder
		$.ajaxSetup({
				error:function(x,e){					
					alert("Error code: " + x.status);
					obj.abort();					
				}
			});		
		
		var overwrite=0;
		var repositoryid=null;
		if ($("#overwrite").is(":checked")){overwrite=1}
		repositoryid=$("#repositoryid").val();
		//post	
		this.xhr=$.post(CI.base_url+"/admin/catalog/do_batch_import",{id:id,overwrite:overwrite,repositoryid:repositoryid},func_data, "json");
		
		//handle json returned values
		function func_data(data){
			 if (data.success){
				obj.queue[obj.queue_idx-1].status=data.success;
				$("#batch-import-log").append('<div class="log" style="color:green;">#' + (obj.queue_idx) + ': '  + obj.queue[obj.queue_idx-1].name + ' - ' + data.success+ '</div>');
			 }
			 else{
			 	obj.queue[obj.queue_idx-1].status=data.error;
				$("#batch-import-log").append('<div class="log" style="color:red">#' + (obj.queue_idx) + ': '  +  obj.queue[obj.queue_idx-1].name + ' - ' + data.error+ '</div>');
			 }
			 obj.process_queue();
		}//end-func
	},
	
	abort: function(){
		$("#batch-import-processing").html(i18n.import_cancelled);
		this.xhr.abort();
		this.isprocessing=false;
	}	
};


//checkbox select/deselect
jQuery(document).ready(function(){
	$("#chk_toggle").click(
			function (e) 
			{
				$('.chk').each(function(){ 
                    this.checked = (e.target).checked; 
                }); 
			}
	);
	
	$(".batch-uploader").click(function(e){
		$(".uploader-body").toggleClass("state-hidden");
	});
	
});

</script>

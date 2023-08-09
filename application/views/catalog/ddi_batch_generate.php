<div class="content-container">

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('Batch generate DDI');?></h1>

<div style="margin-bottom:20px;" class="mt-3 pb-4"><input type="button" class="btn btn-primary" name="refresh" value="<?php echo t('Generate DDIs');?>" onclick="batch_import.process();"/></div>
	

<div>

<?php if ( count($surveys)==0 || $surveys===false) :?>
    <div class="error">
		<?php echo t('no_surveys_found');?>
    </div>    
    <?php return; ?>
<?php endif; ?>

<div class="note">
	<?php echo sprintf(t('total_studies_found') . ': %s',count($surveys));?>    
</div>

<div class="note" id="batch-import-box" style="display:none;" >
    <div id="batch-import-processing" style="padding:5px;border-bottom:1px solid gainsboro;margin-bottom:10px;">Processing survey...</div>
    <div id="batch-import-log" ></div>
</div>

<table class="grid-table table table-striped" width="100%" cellspacing="0" cellpadding="0"> 
<tr align="left" class="header">
	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
    <th><?php echo t('id');?></th>
    <th><?php echo t('nation');?></th>
    <th><?php echo t('title');?></th>
</tr>
<?php foreach($surveys as $survey):?>
	<tr>
    	<td><input type="checkbox" class="chk" id="<?php echo $survey['id'];?>" value="<?php echo $survey['id'];?>"/></td>
    	<td><?php echo $survey['id'];?></td>
        <td><?php echo $survey['nation'];?></td>
        <td><a target="_blank" href="<?php echo site_url('admin/catalog/edit/'.$survey['id'])?>"><?php echo $survey['title'];?></a></td>
    </tr>
<?php endforeach;?>
</table>
<input class="btn btn-primary" type="button" name="refresh" value="<?php echo t('Generate DDIs');?>" onclick="batch_import.process();"/>

</div>

</div>
<script language="javascript">
//translations	
var i18n=
{
'cancel_import_process':"<?php echo t('Cancel');?>",
'import_completed':"<?php echo t('Completed');?>",
'import_cancelled':"<?php echo t('Cancelled');?>"
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
			
			html='<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Updating '+ (this.queue_idx+1) +' of '+this.queue.length+'... <b>['+this.queue[this.queue_idx].name+']</b>';
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
					console.log(x,e);
					obj.queue[obj.queue_idx-1].status=x.status;
					$("#batch-import-log").append('<div class="log" style="color:red">#' + (obj.queue_idx) + ': '  +  obj.queue[obj.queue_idx-1].name + ' - ' + x.responseText+ '</div>');

					if (confirm("Error: " + x.status + " - Ignore errors and continue?")==false){	
						obj.abort();
					}
					else
					{
						obj.process_queue();
					}	
				}
			});		
		
		//post	
		this.xhr=$.get(CI.base_url+"/api/datasets/generate_ddi/"+id+'/?id_format=id',{id:id},func_data, "json");
		
		//handle json returned values
		function func_data(data){
				console.log(data);
			 if (data.status=='success'){
				obj.queue[obj.queue_idx-1].status=data.status;
				$("#batch-import-log").append('<div class="log" style="color:green;">#' + (obj.queue_idx) + ': '  + obj.queue[obj.queue_idx-1].name + ' - ' + data.status+ '</div>');
			 }
			 else{
			 	obj.queue[obj.queue_idx-1].status=data.status;
				$("#batch-import-log").append('<div class="log" style="color:red">#' + (obj.queue_idx) + ': '  +  obj.queue[obj.queue_idx-1].name + ' - ' + data.status+ '</div>');
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
	
});

</script>
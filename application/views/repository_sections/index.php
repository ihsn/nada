<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>

<?php $this->load->view('repository_sections/navigation_links'); ?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<h1 class="page-title"><?php echo t('repository_sections_management');?></h1>

<?php endif; ?>
<?php if ($rows): ?>
<?php		
		$sort_by=$this->input->get("sort_by");
		$sort_order=$this->input->get("sort_order");			
?>
<?php 

	
	//sort
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	
	//current page url
	$page_url=site_url().'/'.$this->uri->uri_string();
?>


<form autocomplete="off">

	<!-- batch operations -->
    <table width="100%">
        <tr>
            <td>
                <select id="batch_actions">
                    <option value="-1"><?php echo t('batch_actions');?></option>
                    <option value="delete"><?php echo t('delete');?></option>
                </select>
                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>                
            </td>
        </tr>
    </table>
    
    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'weight',t('weight'),$page_url); ?></th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td style="width:20px;"><input type="checkbox" value="<?php echo $row->id; ?>" class="chk"/></td>
            <td><a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a></td>
            <td><?php echo $row->weight; ?>&nbsp;</td>
			<td>
            	<a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> | 
                <a href="<?php echo current_url();?>/delete/<?php echo $row->id;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
</form>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>

<script type="text/javascript" >

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
	$(".chk").click(
			function (e) 
			{
			   if (this.checked==false){
				$("#chk_toggle").attr('checked', false);
			   }			   
			}
	);			
	$("#batch_actions_apply").click(
		function (e){
			if( $("#batch_actions").val()=="delete"){
				batch_delete();
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
		data:{ submit: "submit"},
		type:'POST', 
		url: CI.base_url+'/admin/repository_sections/delete/'+selected+'/?ajax=true',
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
</script>
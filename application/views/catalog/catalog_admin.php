<div class="body-container" style="padding:10px;">
<?php include 'catalog_page_links.php';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('catalog_maintenance');?></h1>

<!-- search form-->
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="catalog-search">	
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo $this->input->get('keywords'); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="titl"	<?php echo ($this->input->get('field')=='titl') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="surveyid"><?php echo t('survey_id');?></option>
    <option value="authenty"><?php echo t('producer');?></option>
    <option value="sponsor"><?php echo t('sponsor');?></option>
    <option value="repositoryid"><?php echo t('repository');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo site_url();?>/admin/catalog"><?php echo t('reset');?></a>
  <?php endif; ?>
</form>

<?php if ($rows): ?>
<?php		
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

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

<table width="100%">
    <tr>
        <td>
			<select id="batch_actions">
                    <option value="-1"><?php echo t('batch_actions');?></option>
                    <option value="delete"><?php echo t('delete');?></option>
                </select>
                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>              
        </td>
        <td align="right">
            <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
        </td>
    </tr>
</table>

<div id="surveys">
    <div class="row-header">
        <div class="row-title">
          <div style="float:left;"><input type="checkbox" id="chk_toggle"/></div>
          <div class="row-text">
			<?php if ($this->config->item("regional_search")=='yes'):?>
                <span style="float:left;display:block;width:150px;overflow:hidden;"><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('keywords','field')); ?></span>
            <?php endif;?>                
			<?php echo create_sort_link($sort_by,$sort_order,'titl',t('title'),$page_url,array('keywords','field')); ?></div>
          <div class="row-date"><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field')); ?></div>
        </div>
    </div>
	    
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
	    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
      <div class="row <?php echo $tr_class; ?>" id="<?php print $row['id']; ?>" >
        <div class="row-title" >
        	<div style="float:left;padding:0px;margin-top:-2px;"><input type="checkbox" class="chk" value="<?php print $row['id']; ?>"/></div>
          <div class="row-text">
          	<?php if ($this->config->item("regional_search")=='yes'):?>
			  	<span style="float:left;display:block;width:150px;overflow:hidden;"><?php echo $row['nation'];?></span>
            <?php endif;?>
          	<?php echo $row['titl']; ?>
          </div>
          <div class="row-date"><?php echo date($this->config->item('date_format'), $row['changed']); ?></div>
        </div>
      </div>
    <?php endforeach;?>
    
<div class="pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
</div>

</div>
<?php else: ?>
<?php echo t('no_records_found');?>
<?php endif; ?>
</div>

<script type='text/javascript'>
//translations	
var i18n={
		'no_item_selected':"<?php echo t('js_no_item_selected');?>",
		'confirm_delete':"<?php echo t('js_confirm_delete');?>",
		'js_loading':"<?php echo t('js_loading');?>"
		};
 
jQuery(document).ready(function(){
	$(".ceebox2").ceebox();
});

function IFrameDialog(href){
	$.fn.ceebox.overlay();
	$.fn.ceebox.popup(href,{onload:true});
	return false;		  	
}

function UploadDialog(href){
	$.fn.ceebox.overlay();
	$.fn.ceebox.popup(href,{onload:true, htmlWidth:600,htmlHeight:450, unload:function(){location.reload();}});
	return false;		  	
}

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
		alert(i18n.no_item_selected);
		return false;
	}
	if (!confirm(i18n.confirm_delete))
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
		url: CI.base_url+'/admin/catalog/delete/'+selected+'/?ajax=true',
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

function share_ddi(e,surveyid)
{
	share=0;
	if ($("#"+e.id).is(':checked')==true) {share=1;}
	url=CI.base_url+'/admin/catalog/shareddi/'+surveyid+'/'+share;
	$.get(url);
}
</script>
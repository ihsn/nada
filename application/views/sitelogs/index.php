<style>
.published{background:url(images/tick.png) no-repeat center;cursor:pointer; }
.unpublished{background:url(images/cross.png) no-repeat center; cursor:pointer;}
</style>
<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<h1 class="page-title"><?php echo t('site_logs');?></h1>
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="user-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="logtype"	<?php echo ($this->input->get('field')=='logtype') ? 'selected="selected"' : '' ; ?> ><?php echo t('logtype');?></option>
    <option value="section"	<?php echo ($this->input->get('field')=='section') ? 'selected="selected"' : '' ; ?> ><?php echo t('section');?></option>
    <option value="keywords"	<?php echo ($this->input->get('field')=='keywords') ? 'selected="selected"' : '' ; ?> ><?php echo t('keywords');?></option>
    <option value="username"	<?php echo ($this->input->get('field')=='username') ? 'selected="selected"' : '' ; ?> ><?php echo t('username');?></option>
    <option value="ip"	<?php echo ($this->input->get('field')=='ip') ? 'selected="selected"' : '' ; ?> ><?php echo t('ip_addresss');?></option>
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
            <td align="right">
                <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
            </td>
        </tr>
    </table>
    
    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo create_sort_link($sort_by,$sort_order,'logtype',t('logtype'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'section',t('section'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'url',t('url'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'keyword',t('keywords'),$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'username',t('user'),$page_url); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'logtime',t('logtime'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'ip',t('ip_address'),$page_url); ?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><a href="<?php echo current_url();?>/?keywords=<?php echo $row->logtype;?>&field=logtype"><?php echo $row->logtype; ?></a></td>
            <td><a href="<?php echo current_url();?>/?keywords=<?php echo $row->section;?>&field=section"><?php echo $row->section; ?></a></td>
            <td><span title="<?php echo form_prep($row->url);?>"><?php echo form_prep(substr($row->url,1,50));?></span>&nbsp;</td>
            <td><a href="<?php echo current_url();?>/?keywords=<?php echo form_prep($row->keyword);?>&field=keyword"><?php echo htmlentities($row->keyword); ?></a></td>
			<td><a href="<?php echo current_url();?>/?keywords=<?php echo $row->username;?>&field=username"><?php echo $row->username; ?></a></td>
			<td nowrap="nowrap"><?php echo date("m-d-Y H:i",$row->logtime); ?></td>
            <td><a href="<?php echo current_url();?>/?keywords=<?php echo $row->ip;?>&field=ip"><?php echo $row->ip; ?></a></td>
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

	bind_events();
});

function bind_events()
{
	//remove events
    $(".unpublished, .published").unbind('click');
    
	$(".unpublished").click(
			function (e) {				
                $(this).removeClass('unpublished').addClass('published');bind_events();
                url=CI.base_url+'/admin/menu/publish/'+$(this).attr("id")+'/'+1;
                $.get(url);
			}
	);    

	$(".published").click(
			function (e) {				
				$(this).removeClass('published').addClass('unpublished');bind_events();
                url=CI.base_url+'/admin/menu/publish/'+$(this).attr("id")+'/'+0;
                $.get(url);
			}
	);    
}

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
		url: CI.base_url+'/admin/menu/delete/'+selected+'/?ajax=true',
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
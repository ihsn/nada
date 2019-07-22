<style>
.icon-legend{margin-top:20px;}
.icon-legend span{padding-right:5px;color:gray;}
.published{background:url(images/tick.png) no-repeat center;cursor:pointer; }
.unpublished{background:url(images/cross.png) no-repeat center; cursor:pointer;}
</style>

<div class="body-container" >
<div class="instruction-box"><?php echo t('instructions_citations'); ?></div>
<?php if (!isset($hide_form)):?>
<div class="page-links">
<div onclick="javascript:document.location.href='<?php echo site_url('datadeposit/add_citations'), '/', $this->uri->segment(3); ?>'" class="btn btn-primary">
  <span><?php echo t('add_new_citation');?></span>
</div>
</div>
<br />
<br />
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div style="clear:both" class="success">'.$message.'</div>' : '';?>

<form class="left-pad" style="margin-bottom:10px;" method="GET" id="citation-search">
 
<?php endif; ?>
<?php if ($rows): ?>
<?php 
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;
	
	//sort
	$sort_by='title';//$this->sort_by;
	$sort_order='asc';//$this->sort_order;
	
	//current page url
	$page_url=site_url().'/'.$this->uri->uri_string();
?>

<?php
	$total_rows=count($rows);
	if ($this->pagination->cur_page>0) {
		$to_page=$this->pagination->per_page*$this->pagination->cur_page;

		if ($to_page> $total_rows) 
		{
			$to_page=$total_rows;
		}

		$pager=sprintf(t('showing %d-%d of %d')
						,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
						,$to_page
						,$total_rows);
	}
	else
	{
		$pager=sprintf(t('showing %d-%d of %d')
				,$current_page
				,$total_rows
				,$total_rows);
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
                </select>
                <input type="button" class="btn btn-default btn-sm" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>                
            </td>
            <td align="right">
              <!--  <div style="font-size:10pt" class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div> -->
            </td>
        </tr>
    </table>
    
    <table class="grid-table table table-striped" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'ctype',t('citation_type'),$page_url,array('keywords','field','ps')); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'pub_year',t('date'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'created',t('created'),$page_url,array('keywords','field','ps')); ?></th>            
            <th><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row; //var_dump($row);exit;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->id; ?>" class="chk"/></td>
            <td><?php echo t($row->ctype); ?></td>
            <td><a href="<?php echo site_url('datadeposit/edit_citations/'.$row->id.'/'.$this->uri->segment(3))?>"><?php echo $row->title;?></a></td>
            <td nowrap="nowrap"><?php echo $row->pub_year; ?>&nbsp;</td>
            <td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->created); ?></td>
			<td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->changed); ?></td>            
			<td nowrap="nowrap"><a href="<?php echo site_url('datadeposit/edit_citations/'.$row->id.'/'.$this->uri->segment(3));?>"><?php echo t('edit');?></a> | 
            <a href="<?php echo site_url('datadeposit/delete_citation/'.$row->id.'/'.$this->uri->segment(3))?>/?destination=<?php echo $this->uri->uri_string();?>"><?php echo t('delete');?></a></td>
        </tr>
    <?php endforeach;?>
    </table>



<?php else: ?>
	<?php echo '<span style="font-size:10pt">', t('no_records_found'), '</span>'; ?>
<?php endif; ?>
</form>
</div>
<script type='text/javascript' >
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
	
	//bind_events();
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
		dataType: "json",
		data:{ submit: "submit"},
		type:'POST', 
		url: CI.base_url+'/datadeposit/delete_citation/'+selected+'/?ajax=true',
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
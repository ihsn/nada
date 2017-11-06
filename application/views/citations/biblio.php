<style>
.grid-table .citation-title{font-weight:normal;text-decoration:underline;}
.grid-table .citation-subtitle{ font-style:italic}
.sort-links{padding-left:20px;}
.sort-links a{border-left:1px solid gainsboro;padding:0px 5px 0px 5px;display:inline-block;text-decoration:none;}
</style>
<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>
<div class="page-links">
	<a href="<?php echo current_url(); ?>/add" class="button"><img src="images/icon_plus.gif"/>Add new</a> 
</div>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title">Citation Manager</h1>
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="user-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo $this->input->get('keywords'); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> >All fields</option>
    <option value="title"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> >Title</option>
    <option value="abstract"	<?php echo ($this->input->get('field')=='abstract') ? 'selected="selected"' : '' ; ?> >Abstract</option>        
    <option value="authors"	<?php echo ($this->input->get('field')=='authors') ? 'selected="selected"' : '' ; ?> >Author</option>
    <option value="dcdate"	<?php echo ($this->input->get('field')=='dcdate') ? 'selected="selected"' : '' ; ?> >Date</option>
  </select>
  <input type="submit" value="Search" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo current_url();?>">Reset</a>
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
		//pager displays records e.g. showing n - n of N
		$pager= 'showing '.(($this->pagination->cur_page-1)*$this->pagination->per_page+(1));
		$pager.= ' - ';
		$to_page=$this->pagination->per_page*$this->pagination->cur_page;
		if ($to_page> $this->pagination->get_total_rows()) 
		{
			$to_page=$this->pagination->get_total_rows();
		}
		$pager.= $to_page;
		$pager.= ' of '.$this->pagination->get_total_rows();
	}
	else
	{
		$pager='showing 1 - '.$this->pagination->get_total_rows(). ' of '.$this->pagination->get_total_rows();
	}
?>

<form autocomplete="off">
	<!-- batch operations -->
    <table width="100%">
        <tr>
            <td>
                <select id="batch_actions">
                    <option value="-1">Batch actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="Apply"/>
                
            	<span class="sort-links">
            	Sort by: 
				<?php echo create_sort_link($sort_by,$sort_order,'title','Title',$page_url); ?>
				<?php echo create_sort_link($sort_by,$sort_order,'authors','Author(s)',$page_url); ?>
                <?php echo create_sort_link($sort_by,$sort_order,'dcdate','Date',$page_url); ?>
                <?php echo create_sort_link($sort_by,$sort_order,'changed','Modified',$page_url); ?>
                </span>
            </td>
            <td align="right">
                <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
            </td>
        </tr>
    </table>
    
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th nowrap="nowrap">Date</th>
            <th>Title</th>			
            <th nowrap="nowrap">Modified</th>
			<th>Actions</th>
        </tr>
        
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row; //var_dump($row);exit;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->id; ?>" class="chk"/></td>
            <td nowrap="nowrap"><?php echo nl2br($row->dcdate); ?>&nbsp;</td>            
            <td>
            <?php 
				$authors=explode("\n",$row->authors);
				$authors=implode(", ",$authors);
				if ($authors!='')
				{
					$authors.='. ';
				}
			?>
            <a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>">                        
                <span class="citation-author"><?php echo isset($authors) ? $authors : ''; ?></span>
                <span class="citation-title"><?php echo isset($row->title) ? $row->title  : ''; ?></span><?php echo isset($row->title) ? '. '  : ''; ?>                
                <span class="citation-subtitle"><?php echo isset($row->subtitle) ? $row->subtitle.'. ' : ''; ?></span>            
                <span class="citation-volume"><?php echo isset($row->volume) ? $row->volume : ''; ?></span>
                <span class="citation-issue"><?php echo strlen($row->issue)>0 ? '('.$row->issue.')' : ''; ?></span>
                <span class="citation-pages"><?php echo strlen($row->pages)>0 ? $row->pages.'.' : ''; ?></span>
			</a>            
            </td>
			<td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->changed); ?></td>
			<td nowrap="nowrap"><a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>">Edit</a> | 
            <a href="<?php echo current_url();?>/delete/<?php echo $row->id;?>/?destination=<?php echo $this->uri->uri_string();?>">Delete</a></td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>

<?php else: ?>
No records found
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
		url: CI.base_url+'/admin/resources/delete/'+selected+'/?ajax=true',
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
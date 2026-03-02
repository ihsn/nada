<style>
.published{background:url(images/tick.png) no-repeat center;cursor:pointer; }
.unpublished{background:url(images/cross.png) no-repeat center; cursor:pointer;}

.sitelogs-index-page .logs-table {
    table-layout: fixed;
    width: 100%;
}
.sitelogs-index-page .logs-table th,
.sitelogs-index-page .logs-table td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sitelogs-index-page .logs-table .col-logtype  { width: 8%; }
.sitelogs-index-page .logs-table .col-section  { width: 10%; }
.sitelogs-index-page .logs-table .col-url      { width: 28%; }
.sitelogs-index-page .logs-table .col-keyword  { width: 18%; }
.sitelogs-index-page .logs-table .col-user     { width: 12%; }
.sitelogs-index-page .logs-table .col-logtime  { width: 13%; }
.sitelogs-index-page .logs-table .col-ip       { width: 11%; }
</style>
<div class="container-fluid sitelogs-index-page" style="padding:10px;">
<?php if (!isset($hide_form)):?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>


<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <h1 class="page-title" style="margin: 0;"><?php echo t('site_logs');?></h1>
    <a href="<?php echo site_url('admin/logs/cleanup'); ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-trash-o"></i> Cleanup &amp; Archiving
    </a>
</div>

<div id="sitelogs-row-count-warning" style="display:none" class="alert alert-warning">
    <strong>&#9888; Large number of log entries detected.</strong>
    The site logs table contains a high number of rows which may affect performance.
    <a href="<?php echo site_url('admin/logs/cleanup'); ?>" class="alert-link">Run Cleanup &amp; Archiving</a> to archive old entries.
    <span id="sitelogs-row-count-detail" class="text-muted ms-2"></span>
</div>

<script>
(function() {
    fetch('<?php echo site_url("api/db_logs/row_counts"); ?>')
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.status === 'success' && data.data.sitelogs_exceeds_threshold) {
                var el = document.getElementById('sitelogs-row-count-warning');
                var detail = document.getElementById('sitelogs-row-count-detail');
                detail.textContent = '(~' + data.data.sitelogs.toLocaleString() + ' rows, threshold: ' + data.data.warning_threshold.toLocaleString() + ')';
                el.style.display = '';
            }
        })
        .catch(function(){});
})();
</script>
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

		if ($to_page> $this->pagination->get_total_rows()) 
		{
			$to_page=$this->pagination->get_total_rows();
		}

		$pager=sprintf(t('showing %d-%d of %d')
						,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
						,$to_page
						,$this->pagination->get_total_rows());
	}
	else
	{
		$pager=sprintf(t('showing %d-%d of %d')
				,$current_page
				,$this->pagination->get_total_rows()
				,$this->pagination->get_total_rows());
	}
?>

<?php echo form_open();?>

	<!-- batch operations -->
    <table width="100%">
        <tr>            
            <td align="right">
                <ul class="nada-pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></ul>
            </td>
        </tr>
    </table>
    
    <!-- grid -->
    <table class="table table-striped logs-table" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th class="col-logtype"><?php echo create_sort_link($sort_by,$sort_order,'logtype',t('logtype'),$page_url); ?></th>
            <th class="col-section"><?php echo create_sort_link($sort_by,$sort_order,'section',t('section'),$page_url); ?></th>
            <th class="col-url"><?php echo create_sort_link($sort_by,$sort_order,'url',t('url'),$page_url); ?></th>
            <th class="col-keyword"><?php echo create_sort_link($sort_by,$sort_order,'keyword',t('keywords'),$page_url); ?></th>
			<th class="col-user"><?php echo create_sort_link($sort_by,$sort_order,'username',t('user'),$page_url); ?></th>
			<th class="col-logtime"><?php echo create_sort_link($sort_by,$sort_order,'logtime',t('logtime'),$page_url); ?></th>
            <th class="col-ip"><?php echo create_sort_link($sort_by,$sort_order,'ip',t('ip_address'),$page_url); ?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td class="col-logtype"><a href="<?php echo current_url();?>/?keywords=<?php echo $row->logtype;?>&field=logtype"><?php echo $row->logtype; ?></a></td>
            <td class="col-section"><a href="<?php echo current_url();?>/?keywords=<?php echo $row->section;?>&field=section"><?php echo $row->section; ?></a></td>
            <td class="col-url" title="<?php echo form_prep($row->url);?>"><?php echo form_prep($row->url);?></td>
            <td class="col-keyword"><a href="<?php echo current_url();?>/?keywords=<?php echo form_prep($row->keyword);?>&field=keyword"><?php echo htmlentities($row->keyword); ?></a></td>
			<td class="col-user"><a href="<?php echo current_url();?>/?keywords=<?php echo $row->username;?>&field=username"><?php echo $row->username; ?></a></td>
			<td class="col-logtime"><?php echo date("m-d-Y H:i",$row->logtime); ?></td>
            <td class="col-ip"><a href="<?php echo current_url();?>/?keywords=<?php echo $row->ip;?>&field=ip"><?php echo $row->ip; ?></a></td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="nada-pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>
<?php echo form_close();?>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>
<?php $survey_id=$this->uri->segment(4); ?>
<style>
.table-resources .glyphicon-ok-sign{
  color:green;
}
.table-resources .glyphicon-remove-sign{
  color:red;
}

.resource-notfound{background:url(images/close.gif) no-repeat; padding-left:25px;}
</style>
<div class="body-container" >
    <div class="page-links pull-right">
        <a href="<?php echo site_url(); ?>/admin/resources/add/new/<?php echo $survey_id;?>" ><?php echo t('link_add_new_resource'); ?></a>  |
        <a href="<?php echo site_url(); ?>/admin/resources/import/<?php echo $survey_id;?>" ><?php echo t('link_import_rdf'); ?></a>  |
        <a href="<?php echo site_url(); ?>/admin/resources/fixlinks/<?php echo $survey_id;?>" ><?php echo t('link_fix_broken'); ?></a>  |
        <a href="<?php echo site_url(); ?>/admin/catalog/export_rdf/<?php echo $survey_id;?>" ><?php echo t('rdf_export'); ?></a>
    </div>

<form class="left-pad" style="margin-bottom:10px;" method="GET" id="search-form">
<?php if ($rows): ?>
<?php
	//sort
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");

	//current page url
	$page_url=site_url().'/'.$this->uri->uri_string();
?>


	<!-- batch operations -->
    <table width="100%">
        <tr>
            <td>
                <select id="batch_actions">
                    <option value="-1"><?php echo t('batch_actions'); ?></option>
                    <option value="delete"><?php echo t('delete'); ?></option>
                </select>
                <input style="margin-bottom:5px;" class="btn btn-default btn-sm" type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply'); ?>"/>
            </td>
            <td align="right">
                &nbsp;
            </td>
        </tr>
    </table>

    <table class="table table-striped resources table-resources" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1" id="chk_toggle"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field','ps')); ?></th>
            <th><?php echo t('link'); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'dctype',t('resource_type'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></th>
			<th><?php echo t('actions'); ?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <?php
				$resource_exists=FALSE;
				if( trim($row->filename)=='')
				{
					$resource_exists=FALSE;
				}
				else if(file_exists(unix_path($survey_folder.'/'.$row->filename)) )
				{
					$resource_exists=TRUE;
				}
				else if (is_url($row->filename))
				{
					$resource_exists=TRUE;
				}
		?>
        <?php $resource_class=($resource_exists===TRUE) ? 'resource-found' : 'resource-notfound';?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->resource_id; ?>" class="chk"/></td>
            <td><a href="<?php echo site_url();?>/admin/resources/edit/<?php echo $row->resource_id;?>/<?php echo $row->survey_id;?>"><?php echo $row->title; ?></a></td>
            <td>
                <?php if ($resource_exists):?>
                  <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <?php else:?>
                  <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
                <?php endif;?>

            </td>
            <td><?php echo $row->dctype; ?>&nbsp;</td>
      			<td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->changed); ?></td>
      			<td nowrap="nowrap">
              <a href="<?php echo site_url();?>/admin/resources/edit/<?php echo $row->resource_id;?>/<?php echo $row->survey_id;?>"><?php echo t('edit'); ?></a> |
              <a href="<?php echo site_url();?>/admin/resources/delete/<?php echo $row->resource_id;?>/?destination=<?php echo $this->uri->uri_string();?>"><?php echo t('delete'); ?></a>
              <?php if($row->filename!=''):?>
              | <a href="<?php echo site_url();?>/ddibrowser/<?php echo $survey_id; ?>/download/<?php echo $row->resource_id;?>"><?php echo t('download'); ?></a>
              <?php endif;?>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="table-resources">
    	<div style="float:left;">

        	<div style="display:inline;">
            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> <?php echo t('legend_file_exist'); ?>
          </div>
            <div style="display:inline;margin-left:10px;">
              <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span> <?php echo t('legend_file_no_exist'); ?></div>
        </div>
    </div>

<?php else: ?>
<div>
<?php echo t('no_records_found'); ?>
</div>
<?php endif; ?>
</form>
</div>
<script type='text/javascript' >
//checkbox select/deselect
jQuery(document).ready(function(){
	$("#chk_toggle").click(
			function (e)
			{
				$('.resources .chk').each(function(){
                    this.checked = (e.target).checked;
                });
			}
	);
	$(".resources .chk").click(
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
	if ($('.resources .chk:checked').length==0){
		alert("You have not selected any items");
		return false;
	}
	if (!confirm("Are you sure you want to delete the selected item(s)?"))
	{
		return false;
	}
	selected='';
	$('.resources .chk:checked').each(function(){
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
//page change
$('#ps').change(function() {
  $('#search-form').submit();
});
</script>

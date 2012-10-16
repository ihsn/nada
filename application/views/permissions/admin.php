<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<h1 style="float:left" class="page-name"><?php echo t('permissions_list');?></h1>
<div class="page-links">
	<a href="<?php echo site_url('admin/permissions/add'); ?>" class="button"><img src="images/icon_plus.gif">Create new section</a> 
</div>
<?php endif; ?>

<script type="text/javascript">
// The Permissions javascript code
/*
$(function() {
		// turn on all enabled permissions on load
		var ar=[<?php echo implode(',', array_keys($enabled)); ?>];
		$.each(ar, function(index, value) {
			$('.chk[value="'+value+'"]').attr('checked', 'checked');
		});
	// turn on/off permission
	$('.chk').click(function() {
		if (!$(this).attr('checked')) {
			$.get("<?php echo site_url('admin/permissions/remove_permission'), '?group=', $this->uri->segment(3); ?>"+"&id="+$(this).val());	
		} else {
			$.get("<?php echo site_url('admin/permissions/add_permission'), '?group=', $this->uri->segment(3); ?>"+"&id="+$(this).val(), function(data) {
				if (data) {
					$(this).attr('checked', 'checked');
				}
			});
		}
	});
});
*/
</script>

<form autocomplete="off">
	<?php $tr_class=""; ?>
    	<?php $current = ''; ?>
		<?php foreach($permissions as $row): if ($row->label == null) continue; $current = ($current == $row->section) ? '' : $row->section; ?>
    	<?php if ($current == $row->section): ?>
        
    <!-- grid -->
    <table style="margin-bottom:60px" class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr style="background:#74B376" class="header">
            <th style="font-size:10pt;width:30%"><?php echo t($row->section); ?></th>
        	<th style="font-size:10pt;width:50%;text-align:left"><?php echo t('urls'); ?></th>
        	<th style="width:15%;font-size:10pt;text-align:center">&nbsp;</th>          
        	<th style="font-size:10pt;text-align:left"><a href="<?php echo site_url("admin/permissions/add/"), '/', $row->section; ?>">Add</a> | <a href="<?php echo site_url('admin/permissions/delete'), '/', $row->section ?>">Del</a></th>                          
        </tr>
       <?php endif; ?>
	<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td style="width:30%"><p style="margin-bottom:5px"><?php echo $row->label; ?></p>
             <span style="font-style:italic;color:#666;margin-left:10px"><?php echo $row->description; ?></span>
            </td>
            <td style="width:50%">
            <?php foreach ($this->Permissions_model->get_urls_by_permission_id($row->id) as $urls): ?>
            <p><?php echo $urls->url; ?></p>
            <?php endforeach; ?>
           	<td style="width:15%;text-align:center"><a href="<?php echo site_url('admin/permissions/edit'), '/', $row->id ?>">Edit</a> | <a href="<?php echo site_url('admin/permissions/del'), '/', $row->id ?>">Del</a></td>
            <td >&nbsp;</td>
	    </tr>
        <?php $current = $row->section; ?>
   <?php endforeach; ?>
    </table>
<br />

</form>
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


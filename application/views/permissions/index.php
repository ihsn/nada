<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>


<h1 class="page-name"><?php echo t('permissions');?></h1>

<?php endif; ?>

<script type="text/javascript">
// The Permissions javascript code
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
</script>

<form autocomplete="off">

    <!-- grid -->
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th style="font-size:10pt;width:90%"><?php echo t('permission'); ?></th>
        	<th style="font-size:10pt;text-align:center"><?php echo $group ?></th>
        </tr>
	<?php $tr_class=""; ?>
    	<?php $current = ''; ?>
		<?php foreach($permissions as $row): if ($row->label == null) continue; $current = ($current == $row->section) ? '' : $row->section; ?>
    	<?php if ($current == $row->section): ?>
        	<tr>
    			<td><label style="padding:5px 0;font-size:12pt;font-weight:bold"><?php echo t($row->section); ?></label></td>
        		<td>&nbsp;</td>
    		</tr>
       <?php endif; ?>
	<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td style="width:90%"><p style="margin-bottom:5px"><?php echo $row->label; ?></p>
             <span style="font-style:italic;color:#666;margin-left:10px"><?php echo $row->description; ?></span>
            </td>
           	<td style="text-align:center"><input style="padding:100px" name="<?php echo $row->section; ?>" type="checkbox" value="<?php echo $row->id; ?>" class="chk"/></td>
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


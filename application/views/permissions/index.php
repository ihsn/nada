<style type="text/css">
.clicked { background: #9F9; }
</style>
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

	$("input[type='checkbox']").click(function() {
		this_=$(this);
		setTimeout(function() { 
			this_.parent().addClass('clicked');
			setTimeout(function() { 
				this_.parent().removeClass('clicked');
			}, 1000, this_);
		}, 600, this_);

			
	});

jQuery(document).ready(function(){
	$("#chk_toggle").click(
			function (e) 
			{
				$('.chk2').each(function(){ 
                    this.checked = (e.target).checked; 
                }); 
			}
	);
	$(".chk2").click(
			function (e) 
			{
			   if (this.checked==false){
				$("#chk_toggle").attr('checked', false);
			   }			   
			}
	);			
});

		// turn on all enabled permissions on load
		var ar=[<?php echo implode(',', array_keys($enabled)); ?>];
		$.each(ar, function(index, value) {
			$('.chk[value="'+value+'"]').attr('checked', 'checked');
		});
		var ro=[<?php foreach($repos_enabled as $key => $value) echo $value->repo_id, ','; ?>];
		$.each(ro, function(index, value) {
			$('.chk2[value="'+value+'"]').attr('checked', 'checked');
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
	// turn on/off repo access
	$('.chk2').click(function() {
		if (!$(this).attr('checked')) {
			$.get("<?php echo site_url('admin/permissions/remove_repo'), '?group=', $this->uri->segment(3); ?>"+"&id="+$(this).val());	
		} else {
			$.get("<?php echo site_url('admin/permissions/add_repo'), '?group=', $this->uri->segment(3); ?>"+"&id="+$(this).val());	
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
<?php if (strcasecmp($repo_access, 'limited') === 0): ?>
<h1 style="margin-top:10px" class="page-name"><?php echo t('repositories');?></h1>
 <table style="margin-bottom:20px" class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('title') ?></th>
            <!--<th><?php echo t('url') ?></th>-->
            <th><?php echo t('organization') ?></th>
			<th style="width:45%"><?php echo t('country') ?></th>
            <th >&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($repos as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
            <td><a href="<?php echo site_url();?>/admin/repositories/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a></td>
            <!--<td><?php echo $row->url; ?></td>-->
            <td><?php echo $row->organization; ?></td>
			<td style="width:45%"><?php echo $row->country; ?></td>
			<td>
            <input type="checkbox" value="<?php echo $row->id; ?>" class="chk2"/>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<?php endif; ?>
</form>
</div>




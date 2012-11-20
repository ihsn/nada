<style type="text/css">
	div#admin_notes {
		width: 100%;
		height: 200px;
	}

	div#admin_notes form {
	}
	
	ul#admin_notes {
		height: 200px;
		width: 100%;
		float: left;
		list-style-type: square !important;
	}
	
	ul#admin_notes li {
		padding: 5px 0;
		float: left;
		height: auto;
		margin-left: 15px;
		list-style-type: square !important;	
	}
	ul#admin_notes li small {
		color: #666;
		font-size:7pt;
	}
</style>
<form method="post" action="">
<div class="field">
            <input id="admin_note" type="text" name="admin_note" class="input-flex" >
            <input type="button" value="+" name="admin_note_submit" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;">
        </div>
</form>
<div style="overflow:auto;margin-left:20px">
<script type="text/javascript">
$(function() {
	$("ul#admin_notes li a").live('click', function() {
		id=$(this).parent().attr('id');
		$.get("<?php echo site_url('admin/catalog_notes/delete'); ?>/"+id);
		$(this).parent().remove();
	});
	$("input[name='admin_note_submit']").click(function(e) {
		e.preventDefault();
		data = {
			note: $("input[name='admin_note']").val(),
			type: 'admin'
		};
		$.post("<?php echo site_url('admin/catalog_notes/add') . '/' . $this->uri->segment(4); ?>", data, function(data) {
			$("ul#admin_notes").html(data);
		});
		$("input[name='admin_note']").val('');
	
		return false;
	});
});
</script>
<ul id="admin_notes">
	<?php $x=1; foreach($notes as $note) {
		$note['date'] = current(explode(' ', $note['date']));
			$note['date'] = current(explode(' ', $note['date']));
			$note['note'] = str_split($note['note'], 70);
			$note['note'] = implode(PHP_EOL, $note['note']);
		$user         = $this->ion_auth->get_user($note['uid']);
		echo "		<li id='{$note['id']}'>{$note['note']}", "<br /><small style='margin-left:7px'>{$note['date']}&nbsp;by {$user->username}</small>&nbsp;&nbsp;<a href='javascript:void(0);' style='text-decoration:none'>-</a></li>", PHP_EOL;
		$x++;
	} ?>
</ul>
</div>

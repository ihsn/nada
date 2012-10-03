<style type="text/css">
	div#admin_tags {
		width: 100%;
		height: 200px;
	}

	div#admin_tags form {
	}
	
	ul#admin_tags {
		height: 200px;
		width: 100%;
		float: left;
		list-style-type: square !important;
	}
	
	ul#admin_tags li {
		padding: 5px 0;
		float: left;
		clear:both;
		height: auto;
		margin-left: 15px;
		list-style-type: square !important;	
	}
	ul#admin_tags li small {
		color: #666;
		font-size:7pt;
	}
</style>
<<<<<<< HEAD
<form method="post" action="">
<div class="field">
            <input id="tag" type="text" name="admin_tag" class="input-flex" style="width:88%;">
=======
<form method="post" action="" class="tags-container">
<div class="field">
            <input id="tag" type="text" name="admin_tag" class="input-flex" >
>>>>>>> origin
            <input type="button" value="+" name="admin_tag_submit" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;">
        </div>
</form>
<div style="overflow:auto;margin-left:20px">
<script type="text/javascript">
$(function() {
	$("ul#admin_tags li a").live('click', function() {
		id=$(this).parent().attr('id');
		$.get("<?php echo site_url('admin/catalog_tags/delete'); ?>/"+id);
		$(this).parent().remove();
	});
	$("input[name='admin_tag_submit']").click(function(e) {
		data = {
			tag: $("input[name='admin_tag']").val(),
			type: 'admin'
		};
		$.post("<?php echo site_url('admin/catalog_tags/add') . '/' . $this->uri->segment(4); ?>", data, function(data) {
			$("ul#admin_tags").html(data);
		});
		$("input[name='admin_tag']").val('');
	
		return false;
	});
});
</script>
<ul id="admin_tags">
	<?php foreach($tags as $tag) {
		echo "	<li id='{$tag['id']}'>{$tag['tag']}&nbsp;&nbsp;<a href='javascript:void(0);' style='text-decoration:none'>-</a></li>", PHP_EOL;
	} ?>
</ul>
</div>

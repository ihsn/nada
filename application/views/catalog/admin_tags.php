<?php
$tag_array=array();
foreach($tag_list as $t)
{
	$tag_array[]='&quot;'.$t['tag'].'&quot;';
}
$tag_str=implode(',',$tag_array);
?>
<div class="form-inline survey-tags">
<div class="field form-group">
    <input id="tag" type="text" name="admin_tag" class="form-control"
				data-provide="typeahead" data-items="4" data-source="[<?php echo $tag_str;?>]"
				placeholder="Type a tag ...">
    <input id="admin_tag_submit" type="button" value="+" name="admin_tag_submit" class="btn btn-default" />
</div>
</div>


<div class="vscroll">
<?php $this->load->view("catalog/survey_tags_list");?>
</div>

<script type="text/javascript">
$(function() {
	//remove tags
	remove_tag_handler();

	//add tags
	$("#admin_tag_submit").click(function(e) {
		add_tag();
		return false;
	});
});

function remove_tag_handler(){
	$("#admin_tags .remove").unbind("click").on('click', function() {
		id=$(this).attr('itemid');
		$.get("<?php echo site_url('admin/catalog_tags/delete'); ?>/"+id);
		$(this).remove();
	});
}

function add_tag() {
	data = {
		tag: $("#tag").val(),
		type: 'admin'
	};
	url=CI.base_url+'/admin/catalog_tags/add/<?php echo $this->uri->segment(4); ?>';
	$.ajax({
        type: "POST",
        url: url,
        cache: false,
		timeout:30000,
		data:data,
		success: function(data) {
			$("#admin_tags").html(data);
			$("#tag").val("");
			remove_tag_handler();
        },
		error: function(XMLHttpRequest, textStatus, errorThrow) {
			alert(XMLHttpRequest.responseText);
        }
    });
}
</script>

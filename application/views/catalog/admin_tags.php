<?php
$tag_array=array();
foreach($tag_list as $t)
{
	$tag_array[]='&quot;'.$t['tag'].'&quot;';
}
$tag_str=implode(',',$tag_array);
?>
<div class="field">
    <input id="tag" type="text" name="admin_tag" class="input-flex" style="width:70%" data-provide="typeahead" data-items="4" data-source="[<?php echo $tag_str;?>]">
    <input id="admin_tag_submit" type="button" value="+" name="admin_tag_submit" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;">
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
		$(this).parent().remove();
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
			$("ul#admin_tags").html(data);
			$("#tag").val("");
			remove_tag_handler();
        },
		error: function(XMLHttpRequest, textStatus, errorThrow) {
			alert(XMLHttpRequest.responseText);
        }		
    });
}
</script>
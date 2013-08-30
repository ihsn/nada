<?php
//var_dump($collections);

?>



<p>This study is part of collection(s) <b>“Living Standards Measurement Study”</b> and “Enterprise Surveys”. You can request data either for a single study or for all studies in the collection:</p>

<div>
<div><input type="radio" name="type" value="study" class="by_study rdo"/> Request access to this study only</div>
<?php foreach($collections as $collection):?>
	<div>
    	<input type="radio" name="type" value="bulk_access" class="bulk_access rdo" data-cid="<?php echo $collection['cid'];?>"/>
		Request access to <b><?php echo $collection['studies_found'];?> licensed studies</b> in the collection <b>"<?php echo $collection['title'];?>"</b>
    </div>
<?php endforeach;?>
</div>
<form method="get">
<input type="hidden" name="request" value="new"/>
<input type="hidden" name="type" value="" id="access_type"/>
<input type="hidden" name="da_coll" value="" id="da_coll"/>
<input type="submit" class="btn btn-small"/>
</form>

<script type="text/javascript">
$(document).ready(function() 
{	
	$(".by_study").click(function() {
		$("#access_type").val("study");
		$("#da_coll").val("");
	});
	
	$(".bulk_access").click(function() {
		$("#access_type").val("bulk");
		$("#da_coll").val($(this).attr("data-cid") );
	});
	
});	
</script>
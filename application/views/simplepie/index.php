<style>
	*,html{margin:0px;padding:0px}
	.feed{border-bottom:1px solid gainsboro;margin-bottom:5px;}
	.feed-body{margin-top:5px;}
</style>
<?php foreach($feed->get_items(0,10) as $item):?>
	<div class="feed">
	<h3><a href="<?php echo $item->get_permalink()?>"><?php echo $item->get_title()?></a></h3>
	<p class="feed-date"><small><?php echo $item->get_date('j F Y, g:i a')?></small></p>
	<p class="feed-body"><?php echo $item->get_description()?></p> 
    </div>
<?php endforeach;?>

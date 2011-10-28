<div class="recent-surveys">
<ul>
<?php foreach($recent_surveys as $survey):?>
	<li><?php echo anchor('catalog/'.$survey['id'],$survey['nation'].' - '.$survey['titl']);?></li>
<?php endforeach;?>
</ul>
</div>
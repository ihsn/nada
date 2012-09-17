<style>
.collections .row{margin-bottom:30px;}
.collections h3{margin-bottom:0px;}
</style>

<div class="collections">
<?php foreach($rows as $row):?>
<div class="row">
	<h3><?php echo $row['title'];?></h3>
</div>
<?php endforeach;?>
</div>
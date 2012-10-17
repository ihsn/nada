<style>
.collections .row{margin-bottom:30px;}
.collections h3{margin-bottom:0px;}
</style>

<div class="user_groups">
<?php foreach($rows as $row):?>
<div class="row">
	<h3><?php echo $row['name'];?></h3>
    <div><?php echo $row['description'];?></div>
</div>
<?php endforeach;?>
</div>
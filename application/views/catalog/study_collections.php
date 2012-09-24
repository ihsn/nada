<div id="terms">
<?php foreach($collections as $coll):?>
	<?php 
		$checked='';
		if (in_array($coll['id'],$selected))
		{
			$checked='checked="checked"';
		}
	?>
	<div class="term">	
	<input class="chk" type="checkbox" id="t-<?php echo $coll['id'];?>" value="<?php echo $coll['id'];?>" <?php echo $checked;?>/>
	<label for="t-<?php echo $coll['id'];?>"><?php echo $coll['title'];?></label>
	</div>
<?php endforeach;?>
</div>
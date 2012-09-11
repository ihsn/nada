<?php 
//list of survey related collections
//var_dump($selected);
//var_dump($terms);
?>
<div id="terms">
<?php foreach($terms as $term):?>
	<?php 
		$checked='';
		if (in_array($term['tid'],$selected))
		{
			$checked='checked="checked"';
		}
	?>
	<div class="term">	
	<input class="chk" type="checkbox" id="t-<?php echo $term['tid'];?>" value="<?php echo $term['tid'];?>" <?php echo $checked;?>/>
	<label for="t-<?php echo $term['tid'];?>"><?php echo $term['title'];?></label>
	</div>
<?php endforeach;?>
</div>
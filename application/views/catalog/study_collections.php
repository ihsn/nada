<div class="study-attached-collections">
<?php foreach($collections as $coll):?>
	<?php
		$checked='';
		if (in_array($coll['repositoryid'],$selected)){
			$checked='checked="checked"';
		}
	?>
	<div class="col-md-6">
      <div class="checkbox">
        <label>
          <input class="chk" type="checkbox" id="t-<?php echo $coll['repositoryid'];?>" value="<?php echo $coll['repositoryid'];?>" <?php echo $checked;?>/>
				<span class="label-repo-text" for="t-<?php echo $coll['repositoryid'];?>"><?php echo $coll['title'];?> (<?php echo strtoupper($coll['repositoryid']);?>)</span>
        </label>
    </div>
</div>
<?php endforeach;?>
</div>

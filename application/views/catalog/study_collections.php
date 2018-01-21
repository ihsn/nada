<div class="study-attached-collections">
<?php foreach($collections as $coll):?>
	<?php
		$checked='';
		if (in_array($coll['repositoryid'],$selected)){
			$checked='checked="checked"';
		}
	?>
	<div class="col-md-12d">
      <div class="checkbox">
        <label>
          <input class="chk" type="checkbox" id="t-<?php echo $coll['repositoryid'];?>" value="<?php echo $coll['repositoryid'];?>" <?php echo $checked;?>/>
					<span class="label-repo-text" for="t-<?php echo $coll['repositoryid'];?>"><?php echo $coll['title'];?></span>
					<!--<span class="label label-default label-repo"><?php echo $coll['repositoryid'];?></span>-->
        </label>
    </div>
</div>
<?php endforeach;?>
</div>

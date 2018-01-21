<?php
/**
*
* Tags assigned to Survey
**/
?>


<div id="admin_tags" >
<?php foreach($tags as $tag):?>
  <span title="Remove" class="remove label label-info survey-tag" itemid="<?php echo $tag['id'];?>">
       <?php echo form_prep($tag['tag']);?>  
       <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
  </span>
<?php endforeach;?>
</div>



<?php return;?>
<div id="admin_tags" >
<?php foreach($tags as $tag):?>
<div class="btn-group" style="margin-right:5px;">
  <div title="Remove" class="remove" itemid="<?php echo $tag['id'];?>">
  <button type="button" class="btn btn-default">
    <!--<span class="glyphicon glyphicon-minus" aria-hidden="true" ></span>-->
  <!--<span class="tag label label-primary" id="tag-<?php echo $tag['id'];?>" style="padding:5px;margin-right:10px;font-weight:normal;">-->
    <span>
       <?php echo form_prep($tag['tag']);?>
     </span>
  <!--</span>-->
  </button>
  <button type="button" class="btn btn-default">
    <span class="glyphicon glyphicon-trash" aria-hidden="true" ></span>
  </button>
</div>
</div>
<?php endforeach;?>

</div>

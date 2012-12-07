<?php
/**
*
* Tags assigned to Survey
**/
?>
<ul id="admin_tags" style="list-style:none;margin-left:0px;margin-top:10px">
<?php foreach($tags as $tag):?>
    <li class="tag" id="tag-<?php echo $tag['id'];?>"><span title="Remove" class="remove" itemid="<?php echo $tag['id'];?>"> <i class="icon-remove-sign"></i> <?php echo form_prep($tag['tag']);?></span> </li>
<?php endforeach;?>  
</ul>
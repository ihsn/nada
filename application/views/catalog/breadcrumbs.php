<?php if (!$breadcrumbs){return;}?>
<ul class="breadcrumb">
<?php foreach($breadcrumbs as $key=>$value):?>  
   <?php end($breadcrumbs);?>
   <?php if ($key === key($breadcrumbs)):?>
   	<li class="active"><?php echo $value;?></li>
   <?php else:?>
   <li><a href="<?php echo site_url($key);?>"><?php echo $value;?></a> <span class="divider">/</span></li>
   <?php endif;?>
<?php endforeach;?>  
</ul>
<?php if (isset($menus)): ?>
<?php $current_page=current_url();?>
<ul>
<?php foreach($menus as $item):?>
	<?php 
		if ($item['target']==1)
		{
			$target='target="_blank"';
		}
		else
		{
			$target="";
		}
	?>	

	<?php 
        //if internal link, add site url
        if (substr($item['url'], 0, 7) != 'http://')
        {
            $item['url']=site_url().'/'.$item['url'];
        } 
    ?>

        <?php $this->template->write('title', $item['title'],true);?>
        
		<li <?php echo ($item['url']==$current_page) ? 'class="selected"' : ''; ?>>
        	<a <?php echo $target; ?> href="<?php echo $item['url'];?>"><?php echo $item['title'];?></a>
        </li>
<?php endforeach; ?>
</ul>
<?php endif;?>
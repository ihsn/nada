<ul class="dropdown-menu rounded-0x">
    <?php foreach($collections as $collection):?>        
        <li><a href="<?php echo site_url('admin/repositories/active/'.$collection['id']);?>">
            <?php echo $collection['title'];?>            
        </a></li>
    <?php endforeach;?>
</ul>
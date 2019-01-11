<div class="sidebar-data-files-list">
<ul class="list-group">
    <li class="list-group-item list-group-title">Data files</li>
    <?php foreach($file_list as $file_):?>
    <li class="list-group-item">
        <a href="<?php echo site_url("catalog/$sid/data-dictionary/{$file_['file_id']}");?>?file_name=<?php echo html_escape($file_['file_name']);?>"><?php echo wordwrap($file_['file_name'],15,"<BR>");?></a>
    </li>
    <?php endforeach;?>
</ul>
</div>
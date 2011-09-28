<?php
$active_repo=strtolower($this->session->userdata('active_repository'));
?>
<div class="repositories-sidebar">
<ul>
<li><a class="<?php echo ($active_repo=='central') ? 'active' : ''; ?>" href="<?php echo site_url();?>/catalog/central">Central Microdata Catalog</a></li>
<?php if ($rows):?>	
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row; ?>
        <?php if (!$row->ispublished){continue;} //skip unpublished?>
			<li><a class="<?php echo ($active_repo==strtolower($row->repositoryid)) ? 'active' : ''; ?>" href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>"><?php echo $row->title; ?></a></li>
    <?php endforeach;?>    
<?php endif; ?>
</ul>
</div>
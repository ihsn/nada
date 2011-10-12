<?php
$active_repo=strtolower($this->session->userdata('active_repository'));
$groups=array(
	'regional'=>t('regional_catalogs'),
	'specialized'=>t('specialized_catalogs')
);
?>
<div class="repositories-sidebar">
<h3 class="first"><a class="<?php echo ($active_repo=='central') ? 'active' : ''; ?>" href="<?php echo site_url();?>/catalog/central">Central Microdata Catalog</a></h3>
<?php if ($rows):?>	
	<?php foreach($groups as $group_key=>$group_value):?>
    	<h3><?php echo $group_value;?></h3>
        <ul>
		<?php foreach($rows as $row): ?>
            <?php $row=(object)$row; ?>
            <?php //if (!$row->ispublished){continue;} //skip unpublished?>
            <?php //if ($row->type==2){continue;} //skip system?>
            <?php if ($row->section==$group_key):?>            
                <li><a class="<?php echo ($active_repo==strtolower($row->repositoryid)) ? 'active' : ''; ?>" href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>"><?php echo $row->title; ?></a></li>
            <?php endif;?>    
        <?php endforeach;?>
        </ul>
    <?php endforeach;?>
<?php endif; ?>
</div>
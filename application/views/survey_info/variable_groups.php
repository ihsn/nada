<style>
.table-data-files td{
    cursor:pointer;
    border-bottom:1px solid gainsboro;
}
</style>

<h3><?php echo t('variable_groups');?></h3>
<?php echo $variable_groups_html;return;?>
<?php 
$groups=array();
$vars=array();

foreach($variable_groups as $idx=> $vgroup){
    $variable_groups[$idx]['variable_groups']=explode(" ",$vgroup['variable_groups']);
    $variable_groups[$idx]['variables']=explode(" ",$vgroup['variables']);

    
    $groups[$vgroup['vgid']]=$vgroup;
    $groups[$vgroup['vgid']]['variable_groups']=explode(" ",$vgroup['variable_groups']);
    $groups[$vgroup['vgid']]['variables']=explode(" ",$vgroup['variables']);
}
?>

<?php 
function print_vgroup($group,$groups){
?>
    
    <?php foreach($group['variable_groups'] as $vgid):?>
        <?php if(empty($vgid)){continue;}?>
        <ul>
        <li class="x"><?php echo $vgid;?></li>
        <?php if(array_key_exists($vgid,$groups)):?>
            <?php print_vgroup($groups[$vgid],$groups);?>
            <?php foreach($groups[$vgid]['variables'] as $variable):?>
                <ul><li><?php echo $variable;?></li></ul>
            <?php endforeach;?>
        <?php endif;?>
        
        </ul>    
    <?php endforeach;?>
    
<?php
}
?>

<ul>
<?php foreach($groups as $vgid=>$group):?>
    <li>
        <?php echo $vgid;?>: <?php echo $group['label'];?>
        <?php print_vgroup($group,$groups);?>
        <?php /*
        <?php if(count($group['variable_groups'])>0):?>
            <?php foreach($group['variable_groups'] as $subgroup):?>
                <ul>
                    <li><?php echo $subgroup;?></li>
                </ul>
            <?php endforeach;?>            
        <?php endif;?>
        */ ?>
    </li>
<?php endforeach;?>
</ul>
<hr>

<pre>
            <?php print_r($groups);?>
        </pre>
<hr>        
<?php
/**
 * 
 * Array to comma seperated list
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 *  - badge class - a single or multiple classes 
 */

 $hide_column_headings=false;
 $badge_class="badge badge-pill badge-light";

 if(isset($options['hide_column_headings'])){
     $hide_column_headings=$options['hide_column_headings'];
 }

 if(isset($options['badge_class'])){
    $badge_class=$options['badge_class'];
}

 

?>


<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="table-responsive field field-<?php echo $name;?>">
    <?php if($hide_column_headings!==true):?>
        <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <?php endif;?>
    <div class="field-value">                
        <?php if (isset($data[0]) && is_array($data[0])):?>
            <?php            
                if(!isset($columns)){
                $columns=array_keys($data[0]);
                }            
            ?>
            <?php foreach($data as $row):?>
                    <span class="<?php echo $badge_class;?>"><?php echo $row[$columns[0]];?></span>
            <?php endforeach;?>
        <?php else:?>            
            <?php foreach($data as $row):?>
                    <span class="<?php echo $badge_class;?>"><?php echo $row;?></span>
            <?php endforeach;?>
        <?php endif;?>

    </div>
</div>
<?php endif;?>

<?php
/**
 * 
 * tags
 *
 *  options
 * 
 */
$hide_column_headings=false;

 if(isset($options['hide_column_headings'])){
     $hide_column_headings=$options['hide_column_headings'];
 }
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="field field-<?php echo $name;?>">
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
        <?php 
            $output=array();
            foreach($data as $row):?>
                <span class="mr-2 badge badge-primary"><?php echo implode(": ",$row);?></span>
            <?php endforeach;?>        
        <?php else:?>            
            <span class="mr-2 badge badge-light"><?php echo implode(", ",$output);?></span>
        <?php endif;?>

    </div>
</div>
<?php endif;?>

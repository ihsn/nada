<?php
/**
 * 
 * Array to comma seperated list
 * 
 */
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="table-responsive field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">                
        <?php if (isset($data[0]) && is_array($data[0])):?>
        <?php            
            if(!isset($columns)){
             $columns=array_keys($data[0]);
            }            
        ?>
        <?php 
            $output=array();
            foreach($data as $row){
                $output[]=$row[$columns[0]];
            }

            echo implode(", ",$output); 
            
        ?>
        <?php else:?>            
            <?php echo implode(", ",$data);?>
        <?php endif;?>

    </div>
</div>
<?php endif;?>

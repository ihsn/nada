<?php
/**
 * 
 * Array to comma seperated list
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 */

 $hide_column_headings=false;

 if(isset($options['hide_column_headings'])){
     $hide_column_headings=$options['hide_column_headings'];
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

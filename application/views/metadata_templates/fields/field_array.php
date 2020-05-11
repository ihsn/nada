<?php
/**
 * 
 * Table field
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
<div class="table-responsive field field-<?php echo str_replace(".","__",$name);?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">                
        <?php if (isset($data[0]) && is_array($data[0])):?>
        <?php            
            if(!isset($columns)){
             $columns=array_keys($data[0]);
            }

            //remove empty columns
            $non_empty_columns=array();            
            foreach($columns as $column){
                $column_data=array_filter(array_column($data, $column));
                if(!empty($column_data)){
                    $non_empty_columns[]=$column;
                }
            }
            $columns=$non_empty_columns;
        ?>
        
        <table class="table table-bordered table-striped table-condensed xsl-table table-grid">
            <?php if($hide_column_headings!==true):?>
            <tr>
                <?php foreach($columns as $column_name):?>
                    <th><?php echo t($name.'.'.$column_name);?></th>
                <?php endforeach;?>
            </tr>
            <?php endif;?>
            
            <?php foreach($data as $row):?>
                <tr>
                    <?php foreach($row as $key=>$value):?>
                        <?php if(!in_array($key,$columns)){continue;}?>
                        <td>
                            <?php if(is_array($value)):?>
                            <?php echo render_field($field_type='array_comma',$field_name=$name.'.'.$key,$value);?>
                            <?php else:?>
                                <?php echo $value;?>
                            <?php endif;?>    
                        </td>
                    <?php endforeach;?>                    
                </tr>
            <?php endforeach;?>
        </table>
        <?php else:?>
        <table class="table xsl-table table-grid">            
               <?php foreach($data as $row):?>
               <tr>
                <td><?php echo $row;?></td>
               </tr>
               <?php endforeach;?>
        </table>
        <?php endif;?>

    </div>
</div>
<?php endif;?>

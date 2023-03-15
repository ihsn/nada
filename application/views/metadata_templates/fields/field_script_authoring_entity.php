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
                foreach($data as $row){                 
                    foreach(array_keys($row) as $row_col){
                       if (!in_array($row_col,$columns)){
                           $columns[]=$row_col;
                       }
                    }                 
                }
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
                        <?php foreach($columns as $column_name):?>  
                        <td>
                            <?php if(empty($row[$column_name])){continue;}?>
                            <?php if ($column_name=='author_id' ):?> 
                                <?php foreach($row['author_id'] as $author_row):?>
                                    <div>
                                    <?php
                                        $author_id_row=array();
                                        $author_id_row[]=isset($author_row['type']) ? $author_row['type'] : 'x';
                                        $author_id_row[]=isset($author_row['id']) ? $author_row['id'] : null;
                                        echo implode(": ",array_filter($author_id_row));
                                    ?>
                                    </div>
                                <?php endforeach;?>
                                <?php continue;?>
                            <?php endif;?>

                            <?php if(is_array($row[$column_name])):?>
                                <?php echo render_field($field_type='array_comma',$field_name=$name.'.'.$column_name,$row[$column_name], array('hide_column_headings'=>true));?>
                            <?php else:?>
                                <?php echo $row[$column_name];?>
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

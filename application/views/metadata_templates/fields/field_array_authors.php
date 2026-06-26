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
 $hide_field_title=false;
 $columns=NULL;

 if (isset($options[$name])){
    $options=$options[$name];
}

 if(isset($options['hide_column_headings'])){
     $hide_column_headings=$options['hide_column_headings'];
 }
 
 if(isset($options['columns'])){
    $columns=$options['columns'];
}

 if(isset($options['hide_field_title'])){
    $hide_field_title=$options['hide_field_title'];
 }

 

?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="table-responsive field field-<?php echo str_replace(".","__",$name);?>">
    <?php if ($hide_field_title!=true):?>
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <?php endif;?>
    <div class="field-value">                
        <?php if (isset($data[0]) && is_array($data[0])):?>
        <?php            
            if(!isset($columns)){
             $columns=array('full_name','last_name','first_name','initial','affiliation','author_id');
            }

            // Remove columns with no values (full_name included so harvest rows with only full_name keep a column).
            $non_empty_columns=array();            
            foreach($columns as $column){
                $column_data=array_filter(array_column($data, $column));
                if(!empty($column_data)){
                    $non_empty_columns[]=$column;
                    continue;
                }
                // Keep last_name column when names live only in full_name (legacy substitution cell).
                // Note: if full_name is already shown as its own column, we should NOT keep last_name just for substitution,
                // otherwise the name appears twice.
                if ($column === 'last_name') {
                    foreach ($data as $row) {
                        if (!empty($row['full_name'])) {
                            $non_empty_columns[] = $column;
                            break;
                        }
                    }
                }
            }
            // If full_name is displayed, drop last_name when it has no real values.
            if (in_array('full_name', $non_empty_columns, true) && in_array('last_name', $non_empty_columns, true)) {
                $last_name_values = array_filter(array_column($data, 'last_name'));
                if (empty($last_name_values)) {
                    $non_empty_columns = array_values(array_diff($non_empty_columns, array('last_name')));
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
                            <?php
                            if ($column_name !== 'author_id') {
                                if ($column_name === 'full_name') {
                                    if (empty($row['full_name'])) {
                                        continue;
                                    }
                                } elseif ($column_name === 'last_name') {
                                    if (empty($row['last_name']) && empty($row['full_name'])) {
                                        continue;
                                    }
                                } elseif (empty($row[$column_name])) {
                                    continue;
                                }
                            }
                            ?>
                            <?php if($column_name=='author_id' && is_array($row['author_id'])):?>
                                <?php foreach($row['author_id'] as $author_id):?>
                                    <div>
                                    <?php if (isset($author_id['type'])):?>
                                        <?php echo $author_id['type'];?>: 
                                    <?php endif;?>
                                    <?php if (isset($author_id['id'])):?>
                                        <?php echo $author_id['id'];?>
                                    <?php endif;?>
                                    </div>                                      
                                <?php endforeach;?>
                            <?php else:?>
                                <?php if(is_url($row[$column_name])):?>
                                     <a target="_blank" href="<?php echo html_escape($row[$column_name]);?>"><i class="fas fa-external-link-alt"></i> <?php echo t('Link');?></a>
                                <?php else:?>
                                    <?php if($column_name=='last_name' && !in_array('full_name', $columns, true) && isset($row['full_name'])):?>
                                        <?php echo $row['full_name'];?>
                                    <?php else:?>
                                        <?php echo $row[$column_name];?>
                                    <?php endif;?>
                                <?php endif;?>
                            <?php endif;?>    
                        </td>
                    <?php endforeach;?>                    
                    <?php  /*
                    <?php foreach($row as $key=>$value):?>
                        <?php if(!in_array($key,$columns)){continue;}?>
                        <td>
                            <?php if(is_array($value)):?>
                            <?php echo render_field($field_type='array_br',$field_name=$name.'.'.$key,$value, array('hide_column_headings'=>true));?>
                            <?php else:?>
                                <?php echo $value;?>
                            <?php endif;?>    
                        </td>
                    <?php endforeach;?>                    
                    */ ?>
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

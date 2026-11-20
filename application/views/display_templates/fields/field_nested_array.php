<?php if (!$data) {return false;}
/**
 * 
 * nested repeatd field
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 */

 $this->load->helper('display_template');
 $columns=display_template_filter_props($template['props']);
 if (count($columns) < 1) {
     return false;
 }
 $name=$template['title'];
 $hide_field_title=false;
 $hide_column_headings=false;
?>

 <div class="field-title"><?php echo display_template_resolve_title(isset($template['key']) ? $template['key'] : '', $template['title']);?></div>

<?php foreach($data as $row_idx=>$row):?>
    <?php foreach($columns as $column):?>
        <?php
            $column_type=$column['type'];            

            if (in_array($column_type,array("text","string","boolean","integer"))){
                $column_type='text';
            }

            if ($column_type=='section'){
                $column_type='nested_section';
            }

            echo $this->load->view('display_templates/fields/field_'.$column_type,
                array(
                    'data'=>$column_type=='nested_section' ? $row : array_data_get($row, $column['key']), //$this->displaytemplate->get_nested_section_data($template['key'],$column['key'],$row),
                    'template'=>$column
                )
            ,true);
        ?>
        

        
        
    <?php endforeach;?>
<?php endforeach;?>

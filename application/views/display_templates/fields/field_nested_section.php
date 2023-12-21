<?php 
/**
 * 
 * nested section
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 */

 $columns=$template['props'];
 $name=$template['title'];
 $hide_field_title=false;
 $hide_column_headings=false;
 $show_empty=false;
?>
<?php $html_output=[];?>
<?php foreach($columns as $column):?>
    <?php
        $column_type=$column['type'];

        if (in_array($column_type,array("text","string","boolean","integer"))){
            $column_type='text';
        }

        if ($column_type=='section'){
            $column_type='nested_section';
        }        

        /*echo '<pre style="border:1px solid blue;">';
        var_dump($column_type);
        var_dump($column['key']);
        var_dump(array_data_get($data, $column['key']));
        var_dump($data);
        echo '</pre>'; 
        */

        $html_output[]= $this->load->view('display_templates/fields/field_'.$column_type,
            array(
                'data'=>$column_type=='nested_section' ? $data : array_data_get($data, $column['key']), //$this->displaytemplate->get_nested_section_data($template['key'],$column['key'],$data),
                'template'=>$column
            )
        ,true);
    ?>    
<?php endforeach;?>

<?php $html_output=implode("",$html_output);?>
<?php if ( ($html_output!='') || $show_empty==true ):?>
    <div class="mb-2 field field-<?php echo str_replace(".'","-",$template['key']);?>">
        <?php if ($hide_field_title!=true):?>
            <h5 class="field-title"><?php echo t($template['title']);?></h5>
        <?php endif;?>
        <div><?php echo $html_output;?></div>
    </div>
<?php endif;?>
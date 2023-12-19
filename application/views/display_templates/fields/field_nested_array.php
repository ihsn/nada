<?php if (!$data) {return false;}
/**
 * 
 * nested repeatd field
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 */

 $columns=$template['props'];
 $name=$template['title'];
 $hide_field_title=false;
 $hide_column_headings=false;
?>

<h4 class="field-caption" style="color:red;"><?php echo t($template['title']);?></h4>

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
        
        <?php /* <pre style="border:1px solid pink;">
            <?php echo $column['key'];?> - <?php echo $column['type'];?>
            <?php var_dump( array_data_get($row, $column['key'])) ;?>
            <hr/>
            <?php //var_dump($row);?>
        </pre>
        */?>
        
        
    <?php endforeach;?>
<?php endforeach;?>
<?php return;?>

<pre><?php var_dump($data);?></pre>
<pre><?php var_dump($template);?></pre>




<?php return;?>
<?php if ($hide_field_title!=true):?>
    <h4 class="field-caption"><?php echo t($template['title']);?></h4>
    <?php echo $template['key'];?>
<?php endif;?>
<div class="table-responsive field field-<?php echo str_replace(".","_",$template['key']);?>">
<table class="table table-bordered table-striped table-condensed xsl-table table-grid">
    <tr>
        <?php foreach($columns as $column):?>            
        <th><?php echo $column['title'];?></th>
        <?php endforeach;?>
    </tr>
    <?php foreach($data as $row):?>
    <tr>
        <?php foreach($columns as $column):?>        
        <td>
            <?php if (in_array($column['type'],array('array','nested_array','simple_array'))):?>
                <?php 
                    $column['hide_column_headings']=true;
                    $column['hide_field_title']=true;
                ?>
                <?php  echo $this->load->view('display_templates/fields/field_'.$column['type'],array('data'=>isset($row[$column['key']]) ? $row[$column['key']] : [] ,'template'=>$column),true);?>
            <?php else:?>
                <?php echo isset($row[$column['key']]) ? $row[$column['key']] : '';?>
            <?php endif;?>
        </td>
        <?php endforeach;?>
    </tr>
    <?php endforeach;?>    
</table>

<pre><?php var_dump($data);?></pre>

</div>
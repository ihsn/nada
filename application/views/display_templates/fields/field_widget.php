<?php
    $widget_options=$template['widget_options'];
    $value=array_data_get($data, $widget_options['key']);

    echo $this->load->view('display_templates/fields/field_'.$widget_options['widget_field'],
        array(
            'data'=>$value,
            'template'=>$template
        )
    ,true);
?>
    
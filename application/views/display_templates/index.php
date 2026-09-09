<?php
$dti_attrs = '';
if (!empty($display_template_info) && is_array($display_template_info)) {
    $dti = $display_template_info;
    $attr_map = array(
        'data-display-template-uid' => isset($dti['uid']) ? $dti['uid'] : '',
        'data-display-template-name' => isset($dti['name']) ? $dti['name'] : '',
        'data-display-template-resolution' => isset($dti['resolution']) ? $dti['resolution'] : '',
        'data-display-template-type' => isset($dti['template_type']) ? $dti['template_type'] : '',
        'data-display-template-status' => isset($dti['status']) ? $dti['status'] : '',
        'data-display-template-data-type' => isset($dti['data_type']) ? $dti['data_type'] : '',
    );
    $skipped = (isset($dti['skipped_site_default']) && is_array($dti['skipped_site_default']))
        ? $dti['skipped_site_default']
        : null;
    if ($skipped && !empty($skipped['uid'])) {
        $attr_map['data-display-template-skipped-uid'] = $skipped['uid'];
    }
    $attr_parts = array();
    foreach ($attr_map as $attr => $value) {
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }
        $attr_parts[] = $attr . '="' . html_escape($value) . '"';
    }
    if ($attr_parts) {
        $dti_attrs = ' ' . implode(' ', $attr_parts);
    }
}
?>
<div class="container-fluid"<?php echo $dti_attrs; ?>>
    <div class="row">
        <div class="col-md-3">
            <div class="navbar-collapse sticky-top metadata-sidebar-container">
            <div class="nav flex-column">
            <?php foreach($sidebar as $key=>$item):?>
                <li class="nav-item">                    
                    <a class="nav-link" href="#<?php echo str_replace(".",".",$key);?>"><?php echo tt(strtolower($item),$item);?></a>
                </li>
            <?php endforeach;?>
            </div>
            </div>
        </div>
        <div class="col-md-9">
            <?php echo $html;?>
        </div>
    </div>
</div>

<style>

.study-metadata h2{
    border-bottom:1px solid #e8e8e8;
    padding-bottom:5px;
    margin-bottom:25px;
    padding-top:20px;
}
.study-metadata .field-title{
    text-transform: uppercase;
    margin-top:15px;
    font-weight:bold;
}

.study-metadata h4.field-title{
    margin-top:0px;
}

.field-section-container .field-section-container h2{
    font-size:22px;
}

.badge-tags{
    font-size:14px!important;
}
.study-metadata .field-array-nested table{
    width:auto;
    margin:0;
    min-width:8rem;
}
</style>

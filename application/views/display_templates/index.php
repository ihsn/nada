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
<div class="container-fluid display-template-page"<?php echo $dti_attrs; ?>>
    <div class="row">
        <div class="col-md-3">
            <div class="sticky-top metadata-sidebar-container">
            <nav class="nav flex-column" id="dataset-metadata-sidebar">
            <?php foreach($sidebar as $key=>$item):?>
                <a class="nav-link" href="#<?php echo html_escape($key);?>"><?php echo display_template_resolve_section_title($key, $item);?></a>
            <?php endforeach;?>
            </nav>
            </div>
        </div>
        <div class="col-md-9">
            <?php echo $html;?>
        </div>
    </div>
</div>


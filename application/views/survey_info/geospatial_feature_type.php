<?php
$file_meta = array();
if (!empty($file['metadata'])){
    $decoded = json_decode($file['metadata'], true);
    if (is_array($decoded)){
        $file_meta = $decoded;
    }
}
$type_name = isset($file['file_name']) ? (string)$file['file_name'] : '';
$type_desc = isset($file['description']) ? trim((string)$file['description']) : '';
if (strcasecmp($type_desc, $type_name)===0){
    $type_desc = '';
}

$type_props = array();
if (isset($file_meta['code']) && is_scalar($file_meta['code']) && trim((string)$file_meta['code'])!=='' && (string)$file_meta['code']!==$type_name){
    $type_props[t('attribute_code')] = (string)$file_meta['code'];
}
if (!empty($file_meta['aliases']) && is_array($file_meta['aliases'])){
    $aliases = array();
    foreach($file_meta['aliases'] as $alias){
        if (is_array($alias)){
            $alias = implode(' ', array_filter($alias, 'is_scalar'));
        }
        if (is_scalar($alias) && trim((string)$alias)!==''){
            $aliases[] = trim((string)$alias);
        }
    }
    if ($aliases){
        $type_props[t('aliases')] = implode(', ', $aliases);
    }
}
if (isset($file_meta['isAbstract']) && $file_meta['isAbstract']!=='' && $file_meta['isAbstract']!==null){
    $is_abstract = filter_var($file_meta['isAbstract'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($is_abstract !== null){
        $type_props[t('is_abstract')] = $is_abstract ? t('yes') : t('no');
    }
}

$attr_count = isset($file_variables_count) ? (int)$file_variables_count : (int)(isset($file['var_count']) ? $file['var_count'] : 0);
$has_pages = trim(strip_tags((string)$variable_pagination)) !== '';
?>

<div class="container-fluid geo-ft-header mb-4" id="datafile-container">
    <div class="geo-muted small text-uppercase"><?php echo t('feature_type');?></div>
    <h3 class="text-break"><?php echo html_escape($type_name);?></h3>
    <?php if ($type_desc !== ''): ?>
        <p class="geo-ft-desc"><?php echo nl2br(html_escape($type_desc));?></p>
    <?php endif; ?>
    <?php if (!empty($type_props)): ?>
    <dl class="row geo-dl">
        <?php foreach($type_props as $prop_label=>$prop_value): ?>
        <dt class="col-sm-3 col-lg-2"><?php echo html_escape($prop_label); ?></dt>
        <dd class="col-sm-9 col-lg-10 text-break"><?php echo html_escape($prop_value); ?></dd>
        <?php endforeach; ?>
    </dl>
    <?php endif; ?>
</div>

<div class="container-fluid variables-container" id="variables-container">
    <h4><?php echo t('attributes');?> <span class="geo-count">(<?php echo $attr_count;?>)</span></h4>
    <div class="container-fluid table-variable-list data-dictionary geo-attr-list">
        <?php foreach ($variables as $variable): ?>
            <?php
            $meta = isset($variable['metadata']) && is_array($variable['metadata']) ? $variable['metadata'] : array();
            $var_name = (string)$variable['name'];
            $var_label = trim((string)$variable['labl']);
            if (strcasecmp($var_label, $var_name)===0){
                $var_label = '';
            }
            $value_type = !empty($meta['valueType']) && is_scalar($meta['valueType']) ? (string)$meta['valueType'] : '';
            $value_count = isset($meta['listedValue']) && is_array($meta['listedValue']) ? count($meta['listedValue']) : 0;
            $var_url = site_url("catalog/$sid/variable/$file_id/{$variable['vid']}").'?name='.urlencode($var_name);
            ?>
            <div class="row var-row">
                <div class="icon-toggle"><i class="collapased_ fa fa-angle-down" aria-hidden="true"></i><i class="expanded_ fa fa-angle-up" aria-hidden="true"></i></div>
                <div class="col-md-3">
                    <div class="var-td p-1">
                        <a class="var-id text-break" id="<?php echo md5($variable['vid']);?>" href="<?php echo $var_url;?>"><?php echo html_escape($var_name);?></a>
                    </div>
                </div>
                <div class="col">
                    <div class="p-1 text-break geo-muted"><?php echo html_escape($var_label);?></div>
                </div>
                <div class="col-auto pr-5">
                    <div class="p-1 geo-attr-meta">
                        <?php if ($value_count): ?>
                            <span class="geo-count"><?php echo sprintf(t($value_count===1 ? 'n_value' : 'n_values'), $value_count);?></span>
                        <?php endif; ?>
                        <?php if ($value_type !== ''): ?>
                            <span class="geo-type"><?php echo html_escape($value_type);?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row var-info-panel" id="pnl-<?php echo md5($variable['vid']);?>">
                <div class="panel-td p-4"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($has_pages): ?>
    <div class="row mt-2">
        <div class="col-md-3 geo-muted">
            <?php echo t('total');?>: <?php echo $attr_count;?>
        </div>
        <div class="col-md-9">
            <div class="pagination float-right">
                <?php echo $variable_pagination;?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

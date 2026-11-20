<?php
$feature = isset($variable['metadata']) && is_array($variable['metadata']) ? $variable['metadata'] : array();
$member_name = isset($feature['memberName']) ? (string)$feature['memberName'] : (isset($variable['name']) ? (string)$variable['name'] : '');
$definition = isset($feature['definition']) && is_scalar($feature['definition']) ? trim((string)$feature['definition']) : (isset($variable['labl']) ? trim((string)$variable['labl']) : '');
if (strcasecmp($definition, $member_name)===0){
    $definition = '';
}

$props=array();
if (isset($feature['code']) && is_scalar($feature['code']) && trim((string)$feature['code'])!=='' && (string)$feature['code']!==$member_name){
    $props[t('attribute_code')]=array((string)$feature['code'], 'geo-type');
}
if (!empty($feature['valueType']) && is_scalar($feature['valueType'])){
    $props[t('value_type')]=array((string)$feature['valueType'], 'geo-type');
}
if (!empty($feature['valueMeasurementUnit']) && is_scalar($feature['valueMeasurementUnit'])){
    $props[t('measurement_unit')]=array((string)$feature['valueMeasurementUnit'], '');
}
if (isset($feature['cardinality']) && is_array($feature['cardinality'])){
    $lower=isset($feature['cardinality']['lower']) ? (string)$feature['cardinality']['lower'] : '';
    $upper=isset($feature['cardinality']['upper']) ? (string)$feature['cardinality']['upper'] : '';
    if ($lower!=='' || $upper!==''){
        $props[t('cardinality')]=array($lower.'..'.($upper!=='' ? $upper : '*'), 'geo-type');
    }
}
?>

<div class="geo-attr">
    <?php if (empty($in_panel)): ?>
    <h3><?php echo html_escape($member_name); ?></h3>
    <?php endif; ?>
    <?php if (empty($in_panel) && !empty($file['file_name'])): ?>
    <div class="geo-attr-in geo-muted">
        <?php echo t('feature_type'); ?>:
        <a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/'.$file['file_id']);?>?file_name=<?php echo rawurlencode($file['file_name']);?>"><?php echo html_escape($file['file_name']);?></a>
    </div>
    <?php endif; ?>

    <?php if ($definition !== ''): ?>
        <p><?php echo nl2br(html_escape($definition)); ?></p>
    <?php endif; ?>

    <?php if (!empty($props)): ?>
    <dl class="row geo-dl">
        <?php foreach ($props as $prop_label=>$prop): ?>
        <dt class="col-sm-3 col-lg-2"><?php echo html_escape($prop_label); ?></dt>
        <dd class="col-sm-9 col-lg-10 text-break"><?php if ($prop[1]!==''): ?><span class="<?php echo $prop[1]; ?>"><?php echo html_escape($prop[0]); ?></span><?php else: ?><?php echo html_escape($prop[0]); ?><?php endif; ?></dd>
        <?php endforeach; ?>
    </dl>
    <?php endif; ?>

    <div class="row">
    <?php $this->load->view('survey_info/geospatial_listed_values', array('listed_values'=>isset($feature['listedValue']) ? $feature['listedValue'] : null)); ?>
    </div>
</div>

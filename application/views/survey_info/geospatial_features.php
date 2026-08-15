<style>
    .var-breadcrumb{
        list-style:none;
        clear:both;
        margin-bottom:25px;
        color:gray;
    }

    .var-breadcrumb li{display:inline;}
</style>

<?php
$feature = isset($variable['metadata']) && is_array($variable['metadata']) ? $variable['metadata'] : array();
$member_name = isset($feature['memberName']) ? $feature['memberName'] : (isset($variable['name']) ? $variable['name'] : '');
$definition = isset($feature['definition']) ? $feature['definition'] : (isset($variable['labl']) ? $variable['labl'] : '');
?>

<h5><?php echo html_escape($member_name); ?></h5>
<?php if (!empty($file['file_name'])): ?>
<h5 class="var-file"><?php echo t('feature_type'); ?>: <a href="<?php echo site_url('catalog/'.$file['sid'].'/data-dictionary/'.$file['file_id']);?>"><?php echo html_escape($file['file_name']);?></a></h5>
<?php endif; ?>

<div class="row p-3">
    <?php if ($definition !== ''): ?>
    <div class="col-md-12">
        <p><?php echo html_escape($definition); ?></p>
    </div>
    <?php endif; ?>

    <?php if (isset($feature['cardinality']) && is_array($feature['cardinality'])): ?>
    <div class="col-md-12">
        <div class="row">
            <div class="col-auto font-weight-bold">Cardinality</div>
            <div class="col-md-9">
                Lower: <?php echo isset($feature['cardinality']['lower']) ? html_escape($feature['cardinality']['lower']) : ''; ?>
                Upper: <?php echo isset($feature['cardinality']['upper']) ? html_escape($feature['cardinality']['upper']) : ''; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($feature['valueMeasurementUnit'])): ?>
    <div class="col-md-12">
        <div class="row">
            <div class="col-auto font-weight-bold">Measurement unit</div>
            <div class="col"><?php echo html_escape($feature['valueMeasurementUnit']); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($feature['valueType'])): ?>
    <div class="col-md-12">
        <div class="row">
            <div class="col-auto font-weight-bold">Value type</div>
            <div class="col"><?php echo html_escape($feature['valueType']); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($feature['listedValue']) && is_array($feature['listedValue'])): ?>
    <div class="col-md-12">
        <div class="border-bottom mt-3 font-weight-bold">Listed values</div>
        <?php foreach ($feature['listedValue'] as $row): ?>
            <?php if (!is_array($row)) { continue; } ?>
            <div class="row border-bottom">
                <div class="col-md-2 text-break"><?php echo isset($row['label']) ? html_escape($row['label']) : ''; ?></div>
                <div class="col"><?php echo isset($row['definition']) ? html_escape($row['definition']) : ''; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
/**
 * Data structure field - collapsible list for timeseries data_structure array.
 * Each item uses name/label as the section header; details (description, data_type,
 * column_type, time_period_format, code_list, code_list_reference) shown inside.
 */
?>
<?php if (isset($data) && is_array($data) && count($data) > 0): ?>
<div class="field field-<?php echo str_replace('.', '__', $name); ?> field-data-structure">
    <div class="xsl-caption field-caption"><?php echo t($name); ?></div>
    <div class="field-value">
        <div id="accordion-data-structure" class="accordion-data-structure">
            <?php $k = 0;
            foreach ($data as $row): $k++;
                $header_label = isset($row['label']) && (string)$row['label'] !== '' ? $row['label'] : (isset($row['name']) ? $row['name'] : ('Item ' . $k));
                $item_id = 'data-structure-' . $k;
            ?>
                <div class="card border-bottom">
                    <div class="card-header py-2 px-3" id="heading-<?php echo $item_id; ?>">
                        <h6 class="mb-0">
                            <button class="btn btn-link btn-block text-left p-0 accordion-title collapsed" type="button" data-toggle="collapse" data-target="#<?php echo $item_id; ?>" aria-expanded="false" aria-controls="<?php echo $item_id; ?>">
                                <i class="fa fa-chevron-right mr-2 accordion-icon" aria-hidden="true"></i>
                                <?php echo html_escape($header_label); ?>
                            </button>
                        </h6>
                    </div>
                    <div id="<?php echo $item_id; ?>" class="collapse" aria-labelledby="heading-<?php echo $item_id; ?>" data-parent="#accordion-data-structure">
                        <div class="card-body small">
                            <dl class="row mb-0">
                                <?php if (!empty($row['description'])): ?>
                                    <dt class="col-sm-3"><?php echo t($name . '.description'); ?></dt>
                                    <dd class="col-sm-9"><?php echo nl2br(html_escape($row['description'])); ?></dd>
                                <?php endif; ?>
                                <?php if (isset($row['data_type']) && $row['data_type'] !== '' && $row['data_type'] !== null): ?>
                                    <dt class="col-sm-3"><?php echo t($name . '.data_type'); ?></dt>
                                    <dd class="col-sm-9"><?php echo html_escape($row['data_type']); ?></dd>
                                <?php endif; ?>
                                <?php if (isset($row['column_type']) && $row['column_type'] !== '' && $row['column_type'] !== null): ?>
                                    <dt class="col-sm-3"><?php echo t($name . '.column_type'); ?></dt>
                                    <dd class="col-sm-9"><?php echo html_escape($row['column_type']); ?></dd>
                                <?php endif; ?>
                                <?php if (isset($row['time_period_format']) && $row['time_period_format'] !== '' && $row['time_period_format'] !== null): ?>
                                    <dt class="col-sm-3"><?php echo t($name . '.time_period_format'); ?></dt>
                                    <dd class="col-sm-9"><?php echo html_escape($row['time_period_format']); ?></dd>
                                <?php endif; ?>
                                <?php if (!empty($row['code_list_reference'])): ?>
                                    <dt class="col-sm-3"><?php echo t($name . '.code_list_reference'); ?></dt>
                                    <dd class="col-sm-9"><?php echo html_escape($row['code_list_reference']); ?></dd>
                                <?php endif; ?>
                            </dl>
                            <?php if (!empty($row['code_list']) && is_array($row['code_list'])): ?>
                                <div class="mt-2">
                                    <div class="xsl-caption field-caption"><?php echo t($name . '.code_list'); ?></div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th><?php echo t($name . '.code_list.code'); ?></th>
                                                    <th><?php echo t($name . '.code_list.label'); ?></th>
                                                    <th><?php echo t($name . '.code_list.description'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($row['code_list'] as $code_item): ?>
                                                    <tr>
                                                        <td><?php echo html_escape(is_array($code_item) ? (isset($code_item['code']) ? $code_item['code'] : '') : $code_item); ?></td>
                                                        <td><?php echo html_escape(is_array($code_item) && isset($code_item['label']) ? $code_item['label'] : ''); ?></td>
                                                        <td><?php echo html_escape(is_array($code_item) && isset($code_item['description']) ? $code_item['description'] : ''); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<style>
.accordion-data-structure .accordion-title[aria-expanded="true"] .accordion-icon:before { content: "\f078"; }
.accordion-data-structure .accordion-title .accordion-icon:before { content: "\f054"; }
.accordion-data-structure .card-header { background: #f8f9fa; }
</style>
<script>
$(document).ready(function() {
    if (typeof $().collapse !== 'undefined') {
        $('#accordion-data-structure .collapse').collapse();
    }
});
</script>
<?php endif; ?>

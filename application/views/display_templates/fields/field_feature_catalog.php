<?php
/**
 * ISO 19110 feature catalogue (name, feature types, attributes).
 */
$field_key = isset($template['key']) ? (string) $template['key'] : (isset($name) ? (string) $name : 'feature_catalogue');
?>
<?php if (isset($data) && is_array($data) && !empty($data['featureType']) && is_array($data['featureType'])): ?>
<style>
.feature-row { position: relative; color: #0071bc; }
.feature-row:hover { background: #f0f0f0; }
.icon-toggle { position: absolute; right: 20px; top: 8px; font-size: 20px; }
.collapsed .up_arrow { display: none; }
.show_feature .down_arrow { display: none; }
.show_feature { background: #ced4da; }
.feature-catalog-container .collapsed,
.feature-catalog-container .show_feature { cursor: pointer; }
.text-truncate { margin-right: 30px; }
</style>
<script>
$(document).ready(function () {
	$(document.body).on("click", ".feature-row", function () {
		$(this).toggleClass("show_feature");
	});
});
</script>
<?php $row_counter = 0; ?>
<div class="feature-catalog-container table-responsive field field-<?php echo str_replace('.', '__', html_escape($field_key)); ?>">
	<?php if (isset($data['name'])): ?>
	<div class="field-title"><?php echo html_escape($data['name']); ?></div>
	<?php endif; ?>
	<div class="field-value">
		<?php foreach ($data['featureType'] as $features): ?>
			<?php if (!is_array($features)) { continue; } ?>
			<h3 class="border-bottom mt-5">
				<i class="fa fa-file-o" aria-hidden="true"></i>
				<?php echo isset($features['typeName']) ? html_escape($features['typeName']) : ''; ?>
			</h3>
			<p><?php echo isset($features['definition']) ? html_escape($features['definition']) : ''; ?></p>
			<?php if (empty($features['carrierOfCharacteristics']) || !is_array($features['carrierOfCharacteristics'])) { continue; } ?>
			<div class="row feature-row-header border-bottom p-2">
				<div class="col-md-3 font-weight-bold"><?php echo t('name'); ?></div>
				<div class="col font-weight-bold"><?php echo t('description'); ?></div>
			</div>
			<?php foreach ($features['carrierOfCharacteristics'] as $feature): $row_counter++; ?>
				<?php if (!is_array($feature)) { continue; } ?>
				<div class="row feature-row border-bottom p-2 collapsed" data-toggle="collapse" href="#feature-<?php echo (int) $row_counter; ?>" role="button" aria-expanded="false" aria-controls="feature-<?php echo (int) $row_counter; ?>">
					<div class="icon-toggle">
						<i class="up_arrow fa fa-angle-up" aria-hidden="true"></i>
						<i class="down_arrow fa fa-angle-down" aria-hidden="true"></i>
					</div>
					<div class="col-md-3"><?php echo isset($feature['memberName']) ? html_escape($feature['memberName']) : ''; ?></div>
					<div class="col text-truncate"><?php echo isset($feature['definition']) ? html_escape($feature['definition']) : ''; ?></div>
				</div>
				<div class="row collapse bg-light p-3" id="feature-<?php echo (int) $row_counter; ?>">
					<div class="col-md-12">
						<div>
							<h5><?php echo isset($feature['memberName']) ? html_escape($feature['memberName']) : ''; ?></h5>
							<p><?php echo isset($feature['definition']) ? html_escape($feature['definition']) : ''; ?></p>
						</div>
					</div>
					<?php if (isset($feature['cardinality'])): ?>
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-3 font-weight-bold">Cardinality</div>
							<div class="col-md-9">
								Lower: <?php echo isset($feature['cardinality']['lower']) ? html_escape($feature['cardinality']['lower']) : ''; ?>
								Upper: <?php echo isset($feature['cardinality']['upper']) ? html_escape($feature['cardinality']['upper']) : ''; ?>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<?php if (isset($feature['valueMeasurementUnit'])): ?>
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-3 font-weight-bold">Measurement unit</div>
							<div class="col"><?php echo html_escape($feature['valueMeasurementUnit']); ?></div>
						</div>
					</div>
					<?php endif; ?>
					<?php if (isset($feature['valueType'])): ?>
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-3 font-weight-bold">Value type</div>
							<div class="col"><?php echo html_escape($feature['valueType']); ?></div>
						</div>
					</div>
					<?php endif; ?>
					<?php if (isset($feature['listedValue']) && is_array($feature['listedValue'])): ?>
					<div class="col-md-12">
						<div class="border-bottom mt-3 font-weight-bold">Listed values</div>
						<?php foreach ($feature['listedValue'] as $listed): ?>
							<div class="row border-bottom">
								<div class="col-md-2"><?php echo isset($listed['label']) ? html_escape($listed['label']) : ''; ?></div>
								<div class="col"><?php echo isset($listed['definition']) ? html_escape($listed['definition']) : ''; ?></div>
							</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

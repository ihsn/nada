<?php
/**
 * ISO 19110 feature catalogue (name, feature types, attributes).
 */
$field_key = isset($template['key']) ? (string) $template['key'] : (isset($name) ? (string) $name : 'feature_catalogue');
?>
<?php if (isset($data) && is_array($data) && !empty($data['featureType']) && is_array($data['featureType'])): ?>
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
							<div class="col-md-3 font-weight-bold"><?php echo t('cardinality'); ?></div>
							<div class="col-md-9">
								<?php echo t('cardinality_lower'); ?>: <?php echo isset($feature['cardinality']['lower']) ? html_escape($feature['cardinality']['lower']) : ''; ?>
								<?php echo t('cardinality_upper'); ?>: <?php echo isset($feature['cardinality']['upper']) ? html_escape($feature['cardinality']['upper']) : ''; ?>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<?php if (isset($feature['valueMeasurementUnit'])): ?>
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-3 font-weight-bold"><?php echo t('measurement_unit'); ?></div>
							<div class="col"><?php echo html_escape($feature['valueMeasurementUnit']); ?></div>
						</div>
					</div>
					<?php endif; ?>
					<?php if (isset($feature['valueType'])): ?>
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-3 font-weight-bold"><?php echo t('value_type'); ?></div>
							<div class="col"><?php echo html_escape($feature['valueType']); ?></div>
						</div>
					</div>
					<?php endif; ?>
					<?php $this->load->view('survey_info/geospatial_listed_values', array('listed_values'=>isset($feature['listedValue']) ? $feature['listedValue'] : null)); ?>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

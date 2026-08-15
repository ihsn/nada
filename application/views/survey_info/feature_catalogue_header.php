<?php
/**
 * ISO 19110 catalogue identity (name, version, producer) for the feature-catalogue tab.
 */
$catalog = isset($feature_catalogue) && is_array($feature_catalogue) ? $feature_catalogue : array();
unset($catalog['featureType']);

$producer_label = '';
if (isset($catalog['producer']) && is_array($catalog['producer'])) {
	$producer_parts = array();
	if (!empty($catalog['producer']['individualName'])) {
		$producer_parts[] = $catalog['producer']['individualName'];
	}
	if (!empty($catalog['producer']['organisationName'])) {
		$producer_parts[] = $catalog['producer']['organisationName'];
	}
	$producer_label = implode(', ', $producer_parts);
} elseif (!empty($catalog['producer']) && is_string($catalog['producer'])) {
	$producer_label = $catalog['producer'];
}

$version_label = '';
if (!empty($catalog['versionNumber'])) {
	$version_label = (string) $catalog['versionNumber'];
}
$version_date = '';
if (isset($catalog['versionDate']) && is_array($catalog['versionDate']) && !empty($catalog['versionDate']['date'])) {
	$version_date = (string) $catalog['versionDate']['date'];
} elseif (!empty($catalog['versionDate']) && is_string($catalog['versionDate'])) {
	$version_date = $catalog['versionDate'];
}
if ($version_date !== '') {
	$version_label = $version_label !== '' ? $version_label . ' (' . $version_date . ')' : $version_date;
}

$scope = array();
if (isset($catalog['scope']) && is_array($catalog['scope'])) {
	$scope = array_filter($catalog['scope']);
} elseif (!empty($catalog['scope']) && is_string($catalog['scope'])) {
	$scope = array($catalog['scope']);
}

$fields_of_application = array();
if (isset($catalog['fieldOfApplication']) && is_array($catalog['fieldOfApplication'])) {
	$fields_of_application = array_filter($catalog['fieldOfApplication']);
} elseif (!empty($catalog['fieldOfApplication']) && is_string($catalog['fieldOfApplication'])) {
	$fields_of_application = array($catalog['fieldOfApplication']);
}

$has_header = !empty($catalog['name']) || $version_label !== '' || $producer_label !== ''
	|| !empty($scope) || !empty($fields_of_application) || !empty($catalog['functionalLanguage']);
?>
<?php if ($has_header): ?>
<div class="feature-catalogue-header mb-4">
	<?php if (!empty($catalog['name'])): ?>
	<h3><?php echo html_escape($catalog['name']); ?></h3>
	<?php endif; ?>
	<dl class="row mb-0">
		<?php if ($version_label !== ''): ?>
		<dt class="col-sm-3"><?php echo t('feature_catalogue_version'); ?></dt>
		<dd class="col-sm-9"><?php echo html_escape($version_label); ?></dd>
		<?php endif; ?>
		<?php if (!empty($scope)): ?>
		<dt class="col-sm-3"><?php echo t('feature_catalogue_scope'); ?></dt>
		<dd class="col-sm-9"><?php echo html_escape(implode(', ', $scope)); ?></dd>
		<?php endif; ?>
		<?php if (!empty($fields_of_application)): ?>
		<dt class="col-sm-3"><?php echo t('field_of_application'); ?></dt>
		<dd class="col-sm-9"><?php echo html_escape(implode(', ', $fields_of_application)); ?></dd>
		<?php endif; ?>
		<?php if ($producer_label !== ''): ?>
		<dt class="col-sm-3"><?php echo t('producer'); ?></dt>
		<dd class="col-sm-9"><?php echo html_escape($producer_label); ?></dd>
		<?php endif; ?>
		<?php if (!empty($catalog['functionalLanguage'])): ?>
		<dt class="col-sm-3"><?php echo t('functional_language'); ?></dt>
		<dd class="col-sm-9"><?php echo html_escape($catalog['functionalLanguage']); ?></dd>
		<?php endif; ?>
	</dl>
</div>
<?php endif; ?>

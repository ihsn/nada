<?php
/**
 * ISO 19139 responsible-party / contact list.
 *
 * display_options.hide_field_title — hide the field caption
 */
$hide_field_title = false;
if (isset($template['hide_field_title'])) {
	$hide_field_title = (bool) $template['hide_field_title'];
} elseif (isset($template['display_options']['hide_field_title'])) {
	$hide_field_title = (bool) $template['display_options']['hide_field_title'];
} elseif (isset($options['hide_field_title'])) {
	$hide_field_title = (bool) $options['hide_field_title'];
}

$field_key = isset($template['key']) ? (string) $template['key'] : (isset($name) ? (string) $name : 'contact');
$field_title = isset($template['title']) ? (string) $template['title'] : $field_key;
?>
<?php if (isset($data) && is_array($data) && count($data) > 0): ?>
<div class="table-responsive field field-<?php echo str_replace('.', '__', html_escape($field_key)); ?>">
	<?php if ($hide_field_title != true): ?>
	<div class="field-title"><?php echo display_template_resolve_title($field_key, $field_title); ?></div>
	<?php endif; ?>
	<div class="field-value">
		<?php if (isset($data[0]) && is_array($data[0])): ?>
		<?php foreach ($data as $row): ?>
			<?php if (!is_array($row)) { continue; } ?>
			<?php if (array_data_get($row, 'individualName')): ?>
				<div>
					<?php echo html_escape(array_data_get($row, 'individualName')); ?>
					<?php if (array_data_get($row, 'role')): ?>
					<span class="contact-role"> (<?php echo html_escape(array_data_get($row, 'role')); ?>)</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if (array_data_get($row, 'organisationName')): ?>
				<div><?php echo html_escape(array_data_get($row, 'organisationName')); ?></div>
			<?php endif; ?>

			<?php if (array_data_get($row, 'positionName')): ?>
				<div><?php echo html_escape(array_data_get($row, 'positionName')); ?></div>
			<?php endif; ?>

			<div>
			<?php if (array_data_get($row, 'contactInfo.phone.voice')): ?>
				<span class="pr-2">Phone: <?php echo html_escape(array_data_get($row, 'contactInfo.phone.voice')); ?></span>
			<?php endif; ?>
			<?php if (array_data_get($row, 'contactInfo.phone.facsimile')): ?>
				<span class="pr-2">Fax: <?php echo html_escape(array_data_get($row, 'contactInfo.phone.facsimile')); ?></span>
			<?php endif; ?>
			</div>

			<?php if (array_data_get($row, 'contactInfo.address.deliveryPoint')): ?>
				<div><?php echo html_escape(array_data_get($row, 'contactInfo.address.deliveryPoint')); ?></div>
			<?php endif; ?>

			<div>
			<?php if (array_data_get($row, 'contactInfo.address.city')): ?>
				<span class="mr-2"><?php echo html_escape(array_data_get($row, 'contactInfo.address.city')); ?></span>
			<?php endif; ?>
			<?php if (array_data_get($row, 'contactInfo.address.postalCode')): ?>
				<span><?php echo html_escape(array_data_get($row, 'contactInfo.address.postalCode')); ?></span>
			<?php endif; ?>
			</div>

			<?php if (array_data_get($row, 'contactInfo.address.country')): ?>
				<div><?php echo html_escape(array_data_get($row, 'contactInfo.address.country')); ?></div>
			<?php endif; ?>

			<?php if (array_data_get($row, 'contactInfo.address.elctronicMailAddress')): ?>
				<div><?php echo html_escape(array_data_get($row, 'contactInfo.address.elctronicMailAddress')); ?></div>
			<?php endif; ?>
			<?php if (array_data_get($row, 'contactInfo.address.electronicMailAddress')): ?>
				<div><?php echo html_escape(array_data_get($row, 'contactInfo.address.electronicMailAddress')); ?></div>
			<?php endif; ?>

			<?php if (array_data_get($row, 'contactInfo.onlineResource.linkage')): ?>
				<div><?php echo html_escape(array_data_get($row, 'contactInfo.onlineResource.linkage')); ?></div>
			<?php endif; ?>
			<?php if (array_data_get($row, 'contactInfo.onlineResource.name')): ?>
				<div><?php echo html_escape(array_data_get($row, 'contactInfo.onlineResource.name')); ?></div>
			<?php endif; ?>

			<br class="border-bottom mb-2"/>
		<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

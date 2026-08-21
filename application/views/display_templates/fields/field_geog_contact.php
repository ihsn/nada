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
	<div class="field-title"><?php echo tt('metadata.' . $field_key, $field_title); ?></div>
	<?php endif; ?>
	<div class="field-value">
		<?php if (isset($data[0]) && is_array($data[0])): ?>
		<?php foreach ($data as $row): ?>
			<?php if (get_field_value('individualName', $row)): ?>
				<div>
					<?php echo html_escape(get_field_value('individualName', $row)); ?>
					<?php if (get_field_value('role', $row)): ?>
					<span class="contact-role"> (<?php echo html_escape(get_field_value('role', $row)); ?>)</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if (get_field_value('organisationName', $row)): ?>
				<div><?php echo html_escape(get_field_value('organisationName', $row)); ?></div>
			<?php endif; ?>

			<?php if (get_field_value('positionName', $row)): ?>
				<div><?php echo html_escape(get_field_value('positionName', $row)); ?></div>
			<?php endif; ?>

			<div>
			<?php if (get_field_value('contactInfo.phone.voice', $row)): ?>
				<span class="pr-2">Phone: <?php echo html_escape(get_field_value('contactInfo.phone.voice', $row)); ?></span>
			<?php endif; ?>
			<?php if (get_field_value('contactInfo.phone.facsimile', $row)): ?>
				<span class="pr-2">Fax: <?php echo html_escape(get_field_value('contactInfo.phone.facsimile', $row)); ?></span>
			<?php endif; ?>
			</div>

			<?php if (get_field_value('contactInfo.address.deliveryPoint', $row)): ?>
				<div><?php echo html_escape(get_field_value('contactInfo.address.deliveryPoint', $row)); ?></div>
			<?php endif; ?>

			<div>
			<?php if (get_field_value('contactInfo.address.city', $row)): ?>
				<span class="mr-2"><?php echo html_escape(get_field_value('contactInfo.address.city', $row)); ?></span>
			<?php endif; ?>
			<?php if (get_field_value('contactInfo.address.postalCode', $row)): ?>
				<span><?php echo html_escape(get_field_value('contactInfo.address.postalCode', $row)); ?></span>
			<?php endif; ?>
			</div>

			<?php if (get_field_value('contactInfo.address.country', $row)): ?>
				<div><?php echo html_escape(get_field_value('contactInfo.address.country', $row)); ?></div>
			<?php endif; ?>

			<?php if (get_field_value('contactInfo.address.elctronicMailAddress', $row)): ?>
				<div><?php echo html_escape(get_field_value('contactInfo.address.elctronicMailAddress', $row)); ?></div>
			<?php endif; ?>
			<?php if (get_field_value('contactInfo.address.electronicMailAddress', $row)): ?>
				<div><?php echo html_escape(get_field_value('contactInfo.address.electronicMailAddress', $row)); ?></div>
			<?php endif; ?>

			<?php if (get_field_value('contactInfo.onlineResource.linkage', $row)): ?>
				<div><?php echo html_escape(get_field_value('contactInfo.onlineResource.linkage', $row)); ?></div>
			<?php endif; ?>
			<?php if (get_field_value('contactInfo.onlineResource.name', $row)): ?>
				<div><?php echo html_escape(get_field_value('contactInfo.onlineResource.name', $row)); ?></div>
			<?php endif; ?>

			<br class="border-bottom mb-2"/>
		<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

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
			<?php
			if (!is_array($row)) { continue; }
			$c_name = trim((string) array_data_get($row, 'individualName'));
			$c_role = trim((string) array_data_get($row, 'role'));
			$c_org = trim((string) array_data_get($row, 'organisationName'));
			$c_position = trim((string) array_data_get($row, 'positionName'));
			$c_phone = trim((string) array_data_get($row, 'contactInfo.phone.voice'));
			$c_fax = trim((string) array_data_get($row, 'contactInfo.phone.facsimile'));
			$c_address = array();
			foreach (array('deliveryPoint', 'city', 'administrativeArea', 'postalCode', 'country') as $part) {
				$v = array_data_get($row, 'contactInfo.address.'.$part);
				if (is_scalar($v) && trim((string) $v) !== '') {
					$c_address[] = trim((string) $v);
				}
			}
			$c_email = trim((string) array_data_get($row, 'contactInfo.address.electronicMailAddress'));
			if ($c_email === '') {
				// legacy misspelled key
				$c_email = trim((string) array_data_get($row, 'contactInfo.address.elctronicMailAddress'));
			}
			$c_url = trim((string) array_data_get($row, 'contactInfo.onlineResource.linkage'));
			$c_url_name = trim((string) array_data_get($row, 'contactInfo.onlineResource.name'));
			$c_url_ok = $c_url !== '' && preg_match('#^https?://#i', $c_url);
			?>
			<div class="geog-contact">
				<?php if ($c_name !== ''): ?>
					<div>
						<?php echo html_escape($c_name); ?>
						<?php if ($c_role !== ''): ?><span class="contact-role text-muted"> (<?php echo html_escape($c_role); ?>)</span><?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ($c_org !== ''): ?>
					<div><?php echo html_escape($c_org); ?><?php if ($c_name === '' && $c_role !== ''): ?><span class="contact-role text-muted"> (<?php echo html_escape($c_role); ?>)</span><?php endif; ?></div>
				<?php endif; ?>
				<?php if ($c_position !== ''): ?>
					<div><?php echo html_escape($c_position); ?></div>
				<?php endif; ?>
				<?php if (!empty($c_address)): ?>
					<div class="text-muted"><?php echo html_escape(implode(', ', $c_address)); ?></div>
				<?php endif; ?>
				<?php if ($c_phone !== '' || $c_fax !== ''): ?>
					<div class="text-muted">
						<?php if ($c_phone !== ''): ?><span class="pr-2"><?php echo t('contact_phone'); ?>: <?php echo html_escape($c_phone); ?></span><?php endif; ?>
						<?php if ($c_fax !== ''): ?><span class="pr-2"><?php echo t('contact_fax'); ?>: <?php echo html_escape($c_fax); ?></span><?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ($c_email !== ''): ?>
					<div><a href="mailto:<?php echo html_escape($c_email); ?>"><?php echo html_escape($c_email); ?></a></div>
				<?php endif; ?>
				<?php if ($c_url !== ''): ?>
					<div class="text-break">
						<?php if ($c_url_ok): ?>
							<a href="<?php echo html_escape($c_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($c_url_name !== '' ? $c_url_name : $c_url); ?></a>
						<?php else: ?>
							<?php echo html_escape($c_url); ?>
						<?php endif; ?>
					</div>
				<?php elseif ($c_url_name !== ''): ?>
					<div><?php echo html_escape($c_url_name); ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

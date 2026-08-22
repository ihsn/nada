<?php
/**
 * ISO 19139 lineage process steps (description, date, processor, sources).
 */
$field_key = isset($template['key']) ? (string) $template['key'] : (isset($name) ? (string) $name : 'lineage');
$field_title = isset($template['title']) ? (string) $template['title'] : $field_key;
?>
<?php if (isset($data) && is_array($data) && count($data) > 0): ?>
<div class="field field-<?php echo str_replace('.', '__', html_escape($field_key)); ?>">
	<div class="field-title"><?php echo display_template_resolve_title($field_key, $field_title); ?></div>
	<div class="field-value">
	<?php foreach ($data as $row): ?>
		<?php if (!is_array($row)) { continue; } ?>
		<div class="mb-3">
		<?php if (isset($row['description'])): ?>
			<div class="mb-2"><?php echo nl2br(html_escape($row['description'])); ?></div>
		<?php endif; ?>

		<?php if (isset($row['dateTime'])): ?>
			<div class="mb-3"><span class="font-weight-bold"><?php echo t('Date'); ?>:</span> <?php echo html_escape($row['dateTime']); ?></div>
		<?php endif; ?>

		<?php if (isset($row['processor'])): ?>
		<div>
			<?php echo $this->load->view('display_templates/fields/field_geog_contact', array(
				'data' => $row['processor'],
				'template' => array(
					'key' => $field_key . '.processor',
					'title' => t('lineage.processStep.processor'),
					'hide_field_title' => false,
				),
			), true); ?>
		</div>
		<?php endif; ?>

		<?php if (isset($row['source']) && is_array($row['source'])): ?>
			<div class="field-title"><?php echo t('Sources'); ?></div>
			<table class="table table-striped table-sm">
				<tr>
					<th><?php echo t('Description'); ?></th>
					<th><?php echo t('Citation source'); ?></th>
					<th><?php echo t('Organization'); ?></th>
				</tr>
			<?php foreach ($row['source'] as $source): ?>
				<tr>
					<td><?php echo isset($source['description']) ? html_escape($source['description']) : ''; ?></td>
					<td><?php echo html_escape((string) get_field_value('sourceCitation.title', $source)); ?></td>
					<td>
					<?php echo $this->load->view('display_templates/fields/field_geog_contact', array(
						'data' => get_field_value('sourceCitation.citedResponsibleParty', $source),
						'template' => array(
							'key' => $field_key . '.source',
							'title' => '',
							'hide_field_title' => true,
						),
					), true); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</table>
		<?php endif; ?>
		</div>
	<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

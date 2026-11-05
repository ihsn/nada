<?php
$viewer_url = site_url('viewer/pdf') . '?' . http_build_query(array(
    'source' => 'resource',
    'sid' => (int) $sid,
    'resource_id' => (int) $selected['resource_id'],
    'embed' => 1,
    'theme' => $theme,
));

$document_label = function ($resource) {
    $title = trim((string) $resource['title']);
    return $title !== '' ? $title : basename($resource['filename']);
};
?>

<style>
    .view-document {
        padding-top: 20px;
    }

    .view-document-picker {
        margin-bottom: 0.5rem;
    }

    .view-document-picker select {
        max-width: 100%;
        width: auto;
    }

    .view-document-frame {
        display: block;
        width: 100%;
        height: 85vh;
        min-height: 500px;
        border: 1px solid #dee2e6;
    }
</style>

<div class="view-document">
    <?php if (count($resources) > 1): ?>
        <form method="get" action="<?php echo html_escape(site_url('catalog/' . (int) $sid . '/view-document')); ?>" class="view-document-picker">
            <?php if ($theme === 'dark'): ?>
                <input type="hidden" name="theme" value="dark">
            <?php endif; ?>
            <label class="sr-only" for="view-document-select"><?php echo t('view_document_select'); ?></label>
            <select id="view-document-select" name="resource_id" class="custom-select custom-select-sm">
                <?php foreach ($resources as $resource): ?>
                    <option value="<?php echo (int) $resource['resource_id']; ?>" <?php echo $resource['resource_id'] == $selected['resource_id'] ? 'selected' : ''; ?>>
                        <?php echo html_escape($document_label($resource)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <script type="text/javascript">
            document.getElementById('view-document-select').addEventListener('change', function() {
                this.form.submit();
            });
        </script>
    <?php endif; ?>

    <iframe class="view-document-frame"
            src="<?php echo html_escape($viewer_url); ?>"
            title="<?php echo html_escape($document_label($selected)); ?>"></iframe>
</div>

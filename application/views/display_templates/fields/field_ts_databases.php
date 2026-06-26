<?php
/**
 * Custom display renderer: series_description.databases[]
 *
 * Variables:
 *   $data     — array of database entries, each with:
 *               id, name, uri, is_primary, note, catalog_url (pre-resolved by Study::metadata())
 *   $template — field definition from display template JSON
 */
if (!isset($data) || !is_array($data) || count($data) === 0) {
    return;
}

$title = count($data) === 1 ? t('dataset') : t('datasets');
?>
<div class="field field-series_description_databases pb-3">
    <div class="field-title"><?php echo $title; ?></div>
    <div class="field-value">
        <?php foreach ($data as $db): ?>
            <?php
            $idno        = isset($db['id'])          ? trim((string)$db['id'])   : '';
            $name        = isset($db['name'])         ? trim((string)$db['name']) : '';
            $uri         = isset($db['uri'])          ? trim((string)$db['uri'])  : '';
            $catalog_url = isset($db['catalog_url'])  ? $db['catalog_url']        : null;
            $label       = $name !== '' ? $name : ($idno !== '' ? $idno : '');
            ?>
            <?php if ($label !== ''): ?>
            <div>
                <?php if ($catalog_url): ?>
                    <a href="<?php echo htmlspecialchars($catalog_url); ?>"><?php echo htmlspecialchars($label); ?></a>
                <?php elseif ($uri !== ''): ?>
                    <a href="<?php echo htmlspecialchars($uri); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($label); ?> <i class="fas fa-external-link-alt fa-xs"></i></a>
                <?php else: ?>
                    <?php echo htmlspecialchars($label); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

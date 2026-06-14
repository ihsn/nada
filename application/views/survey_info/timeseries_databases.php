<?php
/**
 * Timeseries series detail page — "Databases" tab.
 * Shows the list of databases from series_description.databases[].
 * Each entry is resolved against the catalog; linked internally if found, external URI otherwise.
 *
 * Variables passed in:
 *   $databases  array of [ 'meta' => [...], 'resolved' => row|null ]
 */
?>
<div class="timeseries-databases py-3">

    <?php if (empty($databases)): ?>
        <p class="text-muted"><?php echo t('no_databases_found'); ?></p>
    <?php else: ?>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?php echo t('name'); ?></th>
                <th><?php echo t('identifier'); ?></th>
                <th><?php echo t('note'); ?></th>
                <th><?php echo t('primary'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($databases as $entry): ?>
            <?php
            $meta     = $entry['meta'];
            $resolved = $entry['resolved'];
            $idno     = isset($meta['id'])   ? $meta['id']   : '';
            $name     = isset($meta['name']) ? $meta['name'] : $idno;
            $uri      = isset($meta['uri'])  ? $meta['uri']  : '';
            $note     = isset($meta['note']) ? $meta['note'] : '';
            $is_primary = !empty($meta['is_primary']);

            if ($resolved) {
                $link = site_url('catalog/' . $resolved['id'] . '/study-description');
                $label = '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($name ?: $resolved['title']) . '</a>';
            } elseif ($uri !== '') {
                $label = '<a href="' . htmlspecialchars($uri) . '" target="_blank" rel="noopener">' . htmlspecialchars($name) . '</a>';
            } else {
                $label = htmlspecialchars($name);
            }
            ?>
            <tr>
                <td><?php echo $label; ?></td>
                <td><?php echo htmlspecialchars($idno); ?></td>
                <td><?php echo htmlspecialchars($note); ?></td>
                <td>
                    <?php if ($is_primary): ?>
                        <span class="badge bg-primary"><?php echo t('primary'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>

</div>

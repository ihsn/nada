<?php
/**
 * Timeseries-database detail page — "Related Series" tab.
 * Shows paginated list of timeseries series linked to this database.
 *
 * Variables passed in:
 *   $series    array of survey rows (id, idno, title, published)
 *   $total     int  total count
 *   $page      int  current page (1-based)
 *   $limit     int  rows per page
 *   $db_title  string  database title
 */

$total_pages = $limit > 0 ? ceil($total / $limit) : 1;
$base_url    = current_url();
?>
<div class="timeseries-db-series py-3">

    <p class="text-muted mb-3">
        <?php echo sprintf(t('related_series_count'), (int)$total); ?>
    </p>

    <?php if (empty($series)): ?>
        <p class="text-muted"><?php echo t('no_related_series_found'); ?></p>
    <?php else: ?>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?php echo t('identifier'); ?></th>
                <th><?php echo t('title'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($series as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['idno']); ?></td>
                <td>
                    <a href="<?php echo site_url('catalog/' . (int)$row['id'] . '/study-description'); ?>">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo $base_url . '?page=' . ($page - 1); ?>">&laquo;</a>
            </li>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo $base_url . '?page=' . $p; ?>"><?php echo $p; ?></a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo $base_url . '?page=' . ($page + 1); ?>">&raquo;</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>

</div>

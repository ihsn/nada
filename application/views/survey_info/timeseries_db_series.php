<?php
/**
 * Timeseries-database detail page — "Related Series" tab.
 *
 * Variables:
 *   $series    array  paginated rows (id, idno, title, nation, year_start, year_end,
 *                     authoring_entity, abstract, thumbnail, changed, published)
 *   $total     int    total count
 *   $page      int    current page (1-based)
 *   $limit     int    rows per page
 *   $view      string 'list' or 'card'
 *   $db_title  string database title
 */

$total_pages = $limit > 0 ? ceil($total / $limit) : 1;
$base_url    = current_url();

// Build a URL preserving both ?page= and ?view= params
function ts_url($base, $page, $view) {
    $params = array();
    if ($page > 1)       $params[] = 'page=' . $page;
    if ($view === 'list') $params[] = 'view=list';
    return $base . ($params ? '?' . implode('&', $params) : '');
}
?>
<div class="timeseries-db-series py-3">

    <div class="d-flex align-items-center justify-content-between mb-1">
        <a href="<?php echo site_url('catalog?dtype[]=timeseries&database=' . urlencode($db_idno)); ?>"
           class="btn btn-sm btn-outline-primary">
            <?php echo t('browse_all_indicators_in_catalog'); ?>
        </a>
        <div class="btn-group btn-group-sm" role="group" aria-label="<?php echo t('view_toggle'); ?>">
            <a href="<?php echo ts_url($base_url, $page, 'list'); ?>"
               class="btn btn-outline-secondary <?php echo $view === 'list' ? 'active' : ''; ?>"
               title="<?php echo t('list_view'); ?>">
                <i class="fa fa-list"></i>
            </a>
            <a href="<?php echo ts_url($base_url, $page, 'card'); ?>"
               class="btn btn-outline-secondary <?php echo $view === 'card' ? 'active' : ''; ?>"
               title="<?php echo t('card_view'); ?>">
                <i class="fa fa-th-large"></i>
            </a>
        </div>
    </div>
    <p class="text-muted mb-3">
        <?php echo sprintf(t('related_series_count'), (int)$total); ?>
    </p>

    <?php if (empty($series)): ?>
        <p class="text-muted"><?php echo t('no_related_series_found'); ?></p>
    <?php elseif ($view === 'list'): ?>

    <!-- LIST VIEW -->
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?php echo t('identifier'); ?></th>
                <th><?php echo t('title'); ?></th>
                <th><?php echo t('years'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($series as $row): ?>
            <tr>
                <td class="text-nowrap"><?php echo htmlspecialchars($row['idno']); ?></td>
                <td>
                    <a href="<?php echo site_url('catalog/' . (int)$row['id'] . '/study-description'); ?>">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </a>
                    <?php if (!empty($row['nation'])): ?>
                    <div class="text-muted small"><?php echo htmlspecialchars($row['nation']); ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <?php
                    $yr = array_unique(array_filter([(int)$row['year_start'], (int)$row['year_end']]));
                    echo htmlspecialchars(implode(' - ', $yr));
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php else: ?>

    <!-- CARD VIEW -->
    <?php foreach ($series as $row): ?>
    <div class="survey-row border-bottom pb-3 mb-2">
        <div class="row">
            <div class="<?php echo !empty($row['thumbnail']) ? 'col-10 col-lg-11' : 'col-12'; ?>">
                <h5 class="wb-card-title title">
                    <a href="<?php echo site_url('catalog/' . (int)$row['id'] . '/study-description'); ?>"
                       title="<?php echo htmlspecialchars($row['title']); ?>" class="d-flex">
                        <i class="fa fa-chart-line fa-nada-icon wb-title-icon"></i>
                        <span><?php echo htmlspecialchars($row['title']); ?></span>
                    </a>
                </h5>

                <?php if (!empty($row['nation']) || !empty($row['year_start'])): ?>
                <div class="study-country">
                    <?php if (!empty($row['nation'])): ?>
                        <?php echo htmlspecialchars($row['nation']); ?><?php echo !empty($row['year_start']) ? ',' : ''; ?>
                    <?php endif; ?>
                    <?php
                    $yr = array_unique(array_filter([(int)$row['year_start'], (int)$row['year_end']]));
                    if (!empty($yr)) echo ' ' . htmlspecialchars(implode(' - ', $yr));
                    ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($row['abstract'])): ?>
                <?php
                $abstract_full  = htmlspecialchars($row['abstract']);
                $abstract_short = htmlspecialchars(mb_substr($row['abstract'], 0, 250));
                $needs_trunc    = mb_strlen($row['abstract']) > 250;
                ?>
                <div class="study-abstract mb-2">
                    <?php if ($needs_trunc): ?>
                        <span class="abstract-short"><?php echo $abstract_short; ?>&hellip; <a class="abstract-toggle" href="#"><?php echo t('read_more'); ?></a></span>
                        <span class="abstract-full" style="display:none"><?php echo $abstract_full; ?> <a class="abstract-toggle" href="#"><?php echo t('read_less'); ?></a></span>
                    <?php else: ?>
                        <?php echo $abstract_full; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($row['authoring_entity'])): ?>
                <div class="sub-title">
                    <span class="study-by" style="font-size:14px;"><?php echo htmlspecialchars($row['authoring_entity']); ?></span>
                </div>
                <?php endif; ?>

                <div class="survey-stats">
                    <span class="study-idno">
                        <span class="wb-label"><?php echo t('ID'); ?>:</span>
                        <span class="text-dark wb-value"><?php echo htmlspecialchars($row['idno']); ?></span>
                    </span>
                    <?php if (!empty($row['changed'])): ?>
                    <span>
                        <span class="wb-label"><?php echo t('last_modified'); ?>:</span>
                        <span class="wb-value"><?php echo date('M d, Y', (int)$row['changed']); ?></span>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($row['thumbnail'])): ?>
            <div class="col-2 col-lg-1 wb-col-media">
                <a href="<?php echo site_url('catalog/' . (int)$row['id'] . '/study-description'); ?>">
                    <img src="<?php echo base_url(); ?>files/thumbnails/<?php echo htmlspecialchars(basename($row['thumbnail'])); ?>"
                         class="study-thumbnail" alt=""/>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <nav class="d-flex justify-content-center">
        <ul class="pagination">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo ts_url($base_url, $page - 1, $view); ?>">&laquo;</a>
            </li>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo ts_url($base_url, $p, $view); ?>"><?php echo $p; ?></a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo ts_url($base_url, $page + 1, $view); ?>">&raquo;</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<?php if ($view === 'card'): ?>
<script>
document.querySelectorAll('.abstract-toggle').forEach(function (link) {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        var card = link.closest('.study-abstract');
        card.querySelector('.abstract-short').style.display =
            card.querySelector('.abstract-short').style.display === 'none' ? '' : 'none';
        card.querySelector('.abstract-full').style.display =
            card.querySelector('.abstract-full').style.display === 'none' ? '' : 'none';
    });
});
</script>
<?php endif; ?>

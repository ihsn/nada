<?php
/**
 * Timeseries-database detail page — "Related Indicators" tab.
 *
 * Variables:
 *   $series    array  paginated rows (id, idno, title, nation, year_start, year_end,
 *                     authoring_entity, abstract, thumbnail, changed, published, is_primary)
 *   $total     int    total count
 *   $page      int    current page (1-based)
 *   $limit     int    rows per page
 *   $view      string 'list' or 'card'
 *   $db_idno   string database IDNO
 *   $db_title  string database title
 */

$total_pages = $limit > 0 ? ceil($total / $limit) : 1;
$base_url    = current_url();
$showing_from = $total > 0 ? ($page - 1) * $limit + 1 : 0;
$showing_to   = min($page * $limit, $total);

function ts_url($base, $page, $view) {
    $params = array();
    if ($page > 1)        $params[] = 'page=' . $page;
    if ($view === 'list') $params[] = 'view=list';
    return $base . ($params ? '?' . implode('&', $params) : '');
}
?>
<style>
.ts-idno-chip {
    font-family: monospace;
    font-size: 12px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 1px 6px;
    color: #555;
}
.ts-no-results {
    text-align: center;
    padding: 40px 0;
    color: #999;
}
.ts-no-results i {
    font-size: 48px;
    margin-bottom: 12px;
    display: block;
}
</style>

<div class="timeseries-db-series py-3">

    <!-- TOP BAR: browse button -->
    <div class="mb-3">
        <a href="<?php echo site_url('catalog?database=' . urlencode($db_idno)); ?>"
           class="btn btn-sm btn-outline-primary">
            <i class="fa fa-search mr-1"></i><?php echo t('browse_all_indicators_in_catalog'); ?>
        </a>
    </div>

    <!-- SECOND ROW: count + view toggle -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <p class="text-muted mb-0">
            <?php if ($total > $limit): ?>
                <?php echo sprintf(t('showing_x_to_y_of_z_indicators'), $showing_from, $showing_to, $total); ?>
            <?php else: ?>
                <?php echo sprintf(t('related_series_count'), (int)$total); ?>
            <?php endif; ?>
        </p>
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

    <?php if (empty($series)): ?>

    <!-- NO RESULTS -->
    <div class="ts-no-results">
        <i class="fa fa-chart-line"></i>
        <p><?php echo t('no_related_series_found'); ?></p>
    </div>

    <?php elseif ($view === 'list'): ?>

    <!-- LIST VIEW -->
    <table class="table table-striped table-hover ts-list-table">
        <thead>
            <tr>
                <th><?php echo t('identifier'); ?></th>
                <th><?php echo t('title'); ?></th>
                <th><?php echo t('years'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($series as $row): ?>
            <?php $study_url = site_url('catalog/' . (int)$row['id'] . '/study-description'); ?>
            <tr>
                <td class="text-nowrap">
                    <span class="ts-idno-chip"><?php echo htmlspecialchars($row['idno']); ?></span>
                    </td>
                <td>
                    <a href="<?php echo $study_url; ?>">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </a>
                    <?php
                    $meta = array();
                    if (!empty($row['nation'])) $meta[] = htmlspecialchars($row['nation']);
                    $yr = array_unique(array_filter([(int)$row['year_start'], (int)$row['year_end']]));
                    if (!empty($yr)) $meta[] = htmlspecialchars(implode(' - ', $yr));
                    ?>
                    <?php if ($meta): ?>
                    <div class="text-muted small"><?php echo implode(' · ', $meta); ?></div>
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
    <?php $study_url = site_url('catalog/' . (int)$row['id'] . '/study-description'); ?>
    <div class="survey-row border-bottom pb-3 mb-2 px-2">
        <div class="row">
            <div class="<?php echo !empty($row['thumbnail']) ? 'col-10 col-lg-11' : 'col-12'; ?>">

                <h5 class="wb-card-title title">
                    <a href="<?php echo $study_url; ?>"
                       title="<?php echo htmlspecialchars($row['title']); ?>" class="d-flex">
                        <i class="fa fa-chart-line fa-nada-icon wb-title-icon"></i>
                        <span><?php echo htmlspecialchars($row['title']); ?></span>
                    </a>
                </h5>

                <?php if (!empty($row['nation']) || !empty($row['year_start'])): ?>
                <div class="study-country">
                    <?php
                    $meta = array();
                    if (!empty($row['nation'])) $meta[] = htmlspecialchars($row['nation']);
                    $yr = array_unique(array_filter([(int)$row['year_start'], (int)$row['year_end']]));
                    if (!empty($yr)) $meta[] = htmlspecialchars(implode(' - ', $yr));
                    echo implode(', ', $meta);
                    ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($row['ts_frequency'])): ?>
                <div class="study-frequency mt-1 mb-1">
                    <span class="wb-label mr-1" style="font-size:12px;">Frequency:</span>
                    <span class="wb-chip mr-1 mb-1" style="font-size:11px; display:inline-block;"><?php echo htmlspecialchars((string)$row['ts_frequency']); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($row['ts_dimensions'])): ?>
                <?php
                $dims = array_filter(array_map('trim', explode(',', (string)$row['ts_dimensions'])));
                ?>
                <?php if (!empty($dims)): ?>
                <div class="study-dimensions mt-1 mb-1">
                    <span class="wb-label mr-1" style="font-size:12px;">Dimensions:</span>
                    <?php foreach ($dims as $dim): ?>
                        <span class="wb-chip mr-1 mb-1" style="font-size:11px; display:inline-block;"><?php echo htmlspecialchars($dim); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
                        <span class="ts-idno-chip"><?php echo htmlspecialchars($row['idno']); ?></span>
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
                <a href="<?php echo $study_url; ?>">
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
    <nav class="d-flex justify-content-center mt-3">
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

<script>
// Abstract read more/less
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

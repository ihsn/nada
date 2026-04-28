<?php
/**
 * Dataset type tabs: primary row + optional "More" dropdown when many types exist.
 *
 * @var array $tabs ['types' => [...], 'active_tab' => string, 'search_counts_by_type' => []]
 */

$type_icons = array(
	'survey'       => '<i class="fas fa-database" aria-hidden="true"></i>',
	'geospatial'   => '<i class="fas fa-globe-americas" aria-hidden="true"></i>',
	'timeseries'   => '<i class="fas fa-chart-line" aria-hidden="true"></i>',
	'document'     => '<i class="fas fa-file-alt" aria-hidden="true"></i>',
	'table'        => '<i class="fa fa-table" aria-hidden="true"></i>',
	'visualization'=> '<i class="fas fa-chart-pie" aria-hidden="true"></i>',
	'script'       => '<i class="fas fa-file-code" aria-hidden="true"></i>',
	'image'        => '<i class="fas fa-image" aria-hidden="true"></i>',
	'video'        => '<i class="fas fa-video" aria-hidden="true"></i>',
);

$types_ordered = (isset($tabs['types']) && is_array($tabs['types']))
	? array_values($tabs['types'])
	: array();

/** Max types shown as top-level tabs before folding the rest into "More" */
$max_primary_types = 7;

$total_types       = count($types_ordered);
$use_more_dropdown = $total_types > $max_primary_types;
$primary_tabs      = $use_more_dropdown ? array_slice($types_ordered, 0, $max_primary_types) : $types_ordered;
$overflow_tabs     = $use_more_dropdown ? array_slice($types_ordered, $max_primary_types) : array();

$active_tab = isset($tabs['active_tab']) ? $tabs['active_tab'] : '';

$active_in_overflow = false;
if ($use_more_dropdown && $active_tab !== '') {
	foreach ($overflow_tabs as $ot) {
		if (isset($ot['code']) && $ot['code'] === $active_tab) {
			$active_in_overflow = true;
			break;
		}
	}
}

$tab_counts = isset($tabs['search_counts_by_type']) ? $tabs['search_counts_by_type'] : null;
?>
<div class="search-nav-tabs-container">
<ul class="nav nav-tabs mb-5 search-nav-tabs search-nav-tabs-flex">
    <li class="nav-item">
        <a class="dataset-type-tab dataset-type-tab-all nav-link <?php echo $active_tab === '' ? 'active' : ''; ?>" data-value="" href="#"><?php echo t('All'); ?>
        <span class="type-count-all">&nbsp;</span>
        </a>
    </li>

    <?php foreach ($primary_tabs as $tab) : ?>
        <?php
			$code       = $tab['code'];
			$tab_target = site_url('catalog/?tab_type=' . $code);
			if (isset($active_repo) && isset($active_repo['repositoryid'])) {
				$tab_target = site_url('catalog/' . $active_repo['repositoryid'] . '?tab_type=' . $code);
			}
        ?>
        <li class="nav-item">
            <a class="dataset-type-tab dataset-type-tab-<?php echo $code; ?> nav-link <?php echo $code === $active_tab ? 'active' : ''; ?>" data-value="<?php echo $code; ?>" href="<?php echo $tab_target; ?>">
                <?php echo isset($type_icons[$code]) ? $type_icons[$code] : ''; ?>
                <?php echo t('tab_' . $code); ?>
                <?php if ($tab_counts !== null) : ?>
                    <?php
					$count = 0;
					if (array_key_exists($code, $tab_counts)) {
						$count = $tab_counts[$code];
					}
                    ?>
                    <span class="type-count"> <?php echo number_format((int) $count); ?> </span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>

    <?php if ($use_more_dropdown && count($overflow_tabs) > 0) : ?>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle <?php echo $active_in_overflow ? 'active' : ''; ?>"
           href="#"
           id="catalogTypesMoreDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           <?php echo $active_in_overflow ? 'aria-expanded="true"' : 'aria-expanded="false"'; ?>
           aria-label="<?php echo htmlspecialchars(t('tab_more_types'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($active_in_overflow) : ?>
                <?php echo isset($type_icons[$active_tab]) ? $type_icons[$active_tab] : ''; ?>
                <?php echo t('tab_' . $active_tab); ?>
                <?php if ($tab_counts !== null) : ?>
                    <?php $oc = array_key_exists($active_tab, $tab_counts) ? (int) $tab_counts[$active_tab] : 0; ?>
                    <span class="type-count"><?php echo number_format($oc); ?></span>
                <?php endif; ?>
            <?php else : ?>
                <?php echo t('tab_more_types'); ?>
            <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-right catalog-types-more-menu" aria-labelledby="catalogTypesMoreDropdown">
            <?php foreach ($overflow_tabs as $tab) : ?>
                <?php
				$code       = $tab['code'];
				$tab_target = site_url('catalog/?tab_type=' . $code);
				if (isset($active_repo) && isset($active_repo['repositoryid'])) {
					$tab_target = site_url('catalog/' . $active_repo['repositoryid'] . '?tab_type=' . $code);
				}
                ?>
                <a class="dropdown-item dataset-type-tab dataset-type-tab-<?php echo $code; ?> <?php echo $code === $active_tab ? 'active' : ''; ?>"
                   data-value="<?php echo $code; ?>"
                   href="<?php echo $tab_target; ?>">
                    <?php echo isset($type_icons[$code]) ? $type_icons[$code] : ''; ?>
                    <?php echo t('tab_' . $code); ?>
                    <?php if ($tab_counts !== null) : ?>
                        <?php
						$count = 0;
						if (array_key_exists($code, $tab_counts)) {
							$count = $tab_counts[$code];
						}
                        ?>
                        <span class="type-count"><?php echo number_format((int) $count); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </li>
    <?php endif; ?>
</ul>
</div>

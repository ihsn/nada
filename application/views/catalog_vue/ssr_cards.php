<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$ssr_rows = isset($ssr_rows) && is_array($ssr_rows) ? $ssr_rows : array();
$ssr_search_type = isset($ssr_search_type) ? (string) $ssr_search_type : 'study';
$ssr_found = isset($ssr_found) ? (int) $ssr_found : 0;
$ssr_page = isset($ssr_page) ? max(1, (int) $ssr_page) : 1;
$ssr_ps = isset($ssr_ps) ? max(1, (int) $ssr_ps) : 15;
$ssr_repo = isset($ssr_repo) ? $ssr_repo : null;
$ssr_total_pages = $ssr_ps > 0 ? (int) ceil($ssr_found / $ssr_ps) : 0;
$is_variable_ssr = ($ssr_search_type === 'variable');
?>
<div id="catalog-ssr" class="catalog-ssr<?php echo $is_variable_ssr ? ' catalog-ssr--variable' : ''; ?>">
	<?php if ($ssr_rows !== array()): ?>
		<ul class="catalog-ssr-list">
			<?php foreach ($ssr_rows as $row): ?>
				<?php if ($is_variable_ssr): ?>
					<?php
					$sid = isset($row['sid']) ? $row['sid'] : '';
					$vid = isset($row['vid']) ? $row['vid'] : '';
					$variable_url = !empty($row['variable_url'])
						? $row['variable_url']
						: site_url('catalog/' . $sid . '/variable/' . $vid);
					$study_url = !empty($row['study_url'])
						? $row['study_url']
						: site_url('catalog/' . $sid);
					$study_meta = catalog_browse_ssr_variable_study_meta($row);
					?>
					<li class="catalog-ssr-item catalog-ssr-item--variable">
						<h2 class="catalog-ssr-title">
							<a href="<?php echo html_escape($variable_url); ?>">
								<?php echo html_escape(catalog_browse_ssr_variable_title($row)); ?>
							</a>
						</h2>
						<?php if (!empty($row['qstn'])): ?>
							<p class="catalog-ssr-qstn"><?php echo html_escape($row['qstn']); ?></p>
						<?php endif; ?>
						<?php if (!empty($row['title'])): ?>
							<p class="catalog-ssr-study">
								<a href="<?php echo html_escape($study_url); ?>">
									<?php echo html_escape($row['title']); ?>
								</a>
							</p>
						<?php endif; ?>
						<?php if ($study_meta !== ''): ?>
							<p class="catalog-ssr-meta"><?php echo html_escape($study_meta); ?></p>
						<?php endif; ?>
					</li>
				<?php else: ?>
					<?php
					$study_url = isset($row['url']) ? $row['url'] : site_url('catalog/' . ($row['id'] ?? ''));
					$year_range = catalog_browse_ssr_year_range($row);
					?>
					<li class="catalog-ssr-item">
						<h2 class="catalog-ssr-title">
							<a href="<?php echo html_escape($study_url); ?>">
								<?php echo html_escape($row['title'] ?? ''); ?>
							</a>
						</h2>
						<?php if (!empty($row['subtitle'])): ?>
							<p class="catalog-ssr-subtitle"><?php echo html_escape($row['subtitle']); ?></p>
						<?php endif; ?>
						<?php if (!empty($row['nation']) || $year_range !== ''): ?>
							<p class="catalog-ssr-meta">
								<?php if (!empty($row['nation'])): ?>
									<span><?php echo html_escape($row['nation']); ?></span>
								<?php endif; ?>
								<?php if (!empty($row['nation']) && $year_range !== ''): ?>
									<span aria-hidden="true"> · </span>
								<?php endif; ?>
								<?php if ($year_range !== ''): ?>
									<span><?php echo html_escape($year_range); ?></span>
								<?php endif; ?>
							</p>
						<?php endif; ?>
						<?php if (!empty($row['idno'])): ?>
							<p class="catalog-ssr-idno">
								<span><?php echo html_escape(t('id')); ?>:</span>
								<?php echo html_escape($row['idno']); ?>
							</p>
						<?php endif; ?>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>

		<?php if ($ssr_total_pages > 1): ?>
			<nav class="catalog-ssr-pagination" aria-label="<?php echo html_escape(t('showing_pages', 'Pagination')); ?>">
				<ul class="catalog-ssr-pagination__list">
					<?php if ($ssr_page > 1): ?>
						<li>
							<a href="<?php echo html_escape(catalog_browse_page_url(1, $ssr_repo)); ?>">&laquo;</a>
						</li>
						<li>
							<a href="<?php echo html_escape(catalog_browse_page_url($ssr_page - 1, $ssr_repo)); ?>">
								<?php echo html_escape(t('prev')); ?>
							</a>
						</li>
					<?php endif; ?>

					<?php
					$adjacents = 5;
					$pages = range(1, $ssr_total_pages);
					if ($adjacents >= 1) {
						$offset = max(0, min(count($pages) - $adjacents, $ssr_page - (int) ceil($adjacents / 2)));
						$pages = array_slice($pages, $offset, $adjacents);
					}
					foreach ($pages as $page_num):
						$active = ((int) $page_num === $ssr_page) ? ' catalog-ssr-pagination__link--active' : '';
					?>
						<li>
							<a
								class="catalog-ssr-pagination__link<?php echo $active; ?>"
								href="<?php echo html_escape(catalog_browse_page_url((int) $page_num, $ssr_repo)); ?>"
							><?php echo (int) $page_num; ?></a>
						</li>
					<?php endforeach; ?>

					<?php if ($ssr_page < $ssr_total_pages): ?>
						<li>
							<a href="<?php echo html_escape(catalog_browse_page_url($ssr_page + 1, $ssr_repo)); ?>">
								<?php echo html_escape(t('next')); ?>
							</a>
						</li>
						<li>
							<a
								href="<?php echo html_escape(catalog_browse_page_url($ssr_total_pages, $ssr_repo)); ?>"
								title="<?php echo html_escape(t('Last')); ?>"
							>&raquo;</a>
						</li>
					<?php endif; ?>
				</ul>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</div>

<style>
/* Visible for crawlers and no-JS; hidden once the inline script adds html.js. */
html.js #catalog-ssr {
	display: none;
}
.catalog-ssr {
	margin: 0 0 1rem;
	padding: 0;
}
.catalog-ssr-list {
	list-style: none;
	margin: 0;
	padding: 0;
}
.catalog-ssr-item {
	margin: 0 0 1rem;
	padding: 0 0 1rem;
	border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}
.catalog-ssr-title {
	font-size: 1.05rem;
	font-weight: 600;
	line-height: 1.35;
	margin: 0 0 0.25rem;
}
.catalog-ssr-title a {
	color: inherit;
	text-decoration: none;
}
.catalog-ssr-title a:hover {
	text-decoration: underline;
}
.catalog-ssr-subtitle,
.catalog-ssr-qstn,
.catalog-ssr-study,
.catalog-ssr-meta,
.catalog-ssr-idno {
	margin: 0.15rem 0 0;
	font-size: 0.875rem;
	color: rgba(0, 0, 0, 0.7);
}
.catalog-ssr-study a {
	color: inherit;
	text-decoration: none;
}
.catalog-ssr-study a:hover {
	text-decoration: underline;
}
.catalog-ssr-pagination {
	margin-top: 1rem;
}
.catalog-ssr-pagination__list {
	display: flex;
	flex-wrap: wrap;
	gap: 0.35rem 0.5rem;
	list-style: none;
	margin: 0;
	padding: 0;
}
.catalog-ssr-pagination__list a {
	display: inline-block;
	padding: 0.2rem 0.55rem;
	border: 1px solid rgba(0, 0, 0, 0.15);
	border-radius: 4px;
	text-decoration: none;
	color: inherit;
	font-size: 0.875rem;
}
.catalog-ssr-pagination__link--active {
	font-weight: 600;
	background: rgba(0, 0, 0, 0.05);
}
</style>

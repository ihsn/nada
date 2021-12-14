<?php if (isset($surveys['rows']) && count($surveys['rows']) < 1) : ?>
    <?php $this->load->view("search/search_nav_bar"); ?>

    <div id="surveys">
        <span class="result-types-summary">
            <span class="type-summary" data-types='<?php echo htmlentities(json_encode($surveys['search_counts_by_type']), ENT_QUOTES, 'UTF-8'); ?>'>
                <?php //echo json_encode($surveys['search_counts_by_type']);
                ?>
            </span>
        </span>

        <div class="nada-search-no-result"><?php echo t('search_no_results'); ?></div>
        <div><span class="clear-search"><a href="<?php echo site_url('catalog'); ?>"><?php echo t('reset_search'); ?></a></span></div>
    </div>
    <?php return; ?>
<?php endif; ?>

<?php
//current page url
$page_url = site_url() . $this->uri->uri_string();

//total pages
$pages = ceil($surveys['found'] / $surveys['limit']);
?>

<?php $this->load->view("search/search_nav_bar"); ?>

<hr />

<?php
//citations
if ($surveys['citations'] === FALSE) {
    $citations = array();
}

//sorting
$sort_by = $search_options->sort_by;
$sort_order = $search_options->sort_order;

//set default sort
/*if(!$sort_by)
	{
		if ($this->config->item("regional_search")=='yes')
		{
			$sort_by='nation';
		}
		else
		{
			$sort_by='title';
		}
	}*/

//current page url with query strings
$page_url = site_url() . '/catalog/';

//page querystring for variable sub-search
$variable_querystring = get_querystring(array('sk', 'vk', 'vf'));

//page querystring for variable sub-search
$search_querystring = '?' . get_querystring(array('sk', 'vk', 'vf', 'view', 'topic', 'country'));
?>

<input type="hidden" name="sort_by" id="sort_by" value="<?php echo $sort_by; ?>" />
<input type="hidden" name="sort_order" id="sort_order" value="<?php echo $sort_order; ?>" />
<?php if($search_options->ps>15):?>
<input type="hidden" name="ps" id="ps" value="<?php echo $search_options->ps; ?>" />
<?php endif;?>
<input type="hidden" name="repo" id="repo" value="<?php echo html_escape($active_repo_id); ?>" />


<span class="result-types-summary">
    <span class="type-summary" data-types='<?php echo htmlentities(json_encode($surveys['search_counts_by_type']), ENT_QUOTES, 'UTF-8'); ?>'>
    </span>
</span>

<?php
//default
$thumbnail_col_class = 'col-3';
$body_col_class = 'col-9';

//thumbnail column sizes
if (in_array($tab_type, array('document', 'timeseries', 'script'))) {
    $thumbnail_col_class = 'col-2';
    $body_col_class = 'col-10';
}
?>


<?php if ($search_options->image_view == "thumbnail" && $tab_type == 'image') : ?>
    <style>
        .image-gallery-view ul {
            display: flex;
            flex-wrap: wrap;
            margin: 0px;
            padding: 0px;
        }

        .image-gallery-view li {
            /*height: 40vh;*/
            flex-grow: 1;
            list-style: none;
            margin: 5px;
        }

        .image-gallery-view li:last-child {
            flex-grow: 10;
        }


        .image-gallery-view img {
            height: 150px;
            max-height: 200px;
            min-width: 100%;
            max-width: 300px;
            object-fit: cover;
            vertical-align: bottom;
        }

        .image-gallery-view img:hover {
            border: 1px solid gray;
        }
    </style>
    <script>
        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>

    <div class="image-gallery-view">
        <ul>
            <?php foreach ($surveys['rows'] as $row) : ?>

                <li data-placement="top" data-toggle="tooltip" data-html="true" title="<?php echo $row['title']; ?>">
                    <a href="<?php echo site_url('catalog/' . $row['id']); ?>">
                        <?php if (!empty($row['thumbnail'])) : ?>
                            <img alt="" src="<?php echo base_url(); ?>files/thumbnails/<?php echo basename($row['thumbnail']); ?>" class="img-fluid shadow-sm" />
                        <?php else : ?>
                            <img src="<?php echo base_url(); ?>files/icon-blank.png" class="img-fluid shadow-sm" />
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li></li>
        </ul>
    </div>
<?php else : ?>

    <?php foreach ($surveys['rows'] as $row) : ?>
        <?php if (!isset($row['form_model'])) : ?>
            <?php $row['form_model'] = 'data_na'; ?>
        <?php endif; ?>
        <?php if(isset($row['thumbnail']) && is_array($row['thumbnail'])):?>
            <?php $row['thumbnail']=implode("",$row['thumbnail']);?>
        <?php endif;?>

        <div class="survey-row" data-url="<?php echo site_url('catalog/' . $row['id']); ?>" title="<?php echo t('View study'); ?>">
            <div class="row">

                <div class="<?php echo $body_col_class; ?>">
                    <h5 class="wb-card-title title">
                        <a href="<?php echo site_url('catalog/'.$row['id']); ?>"  title="<?php echo $row['title']; ?>" class="d-flex" >   
                            <i class="fa fa-image fa-nada-icon wb-title-icon"></i>             
                            <span><?php echo $row['title'];?></span>
                        </a>
                    </h5>

                    <div class="study-country">
                        <?php if (isset($row['nation']) && $row['nation'] != '') : ?>
                            <?php echo $row['nation'] . ','; ?>
                        <?php endif; ?>
                        <?php
                        $survey_year = array();
                        $survey_year[$row['year_start']] = $row['year_start'];
                        $survey_year[$row['year_end']] = $row['year_end'];
                        $survey_year = implode('-', $survey_year);
                        ?>
                        <?php echo $survey_year != 0 ? $survey_year : ''; ?>
                    </div>
                    <div class="sub-title">
                        <?php if (isset($row['authoring_entity'])) : ?>
                            <div>
                                <span class="study-by"><?php echo $row['authoring_entity']; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($row['repo_title']) && $row['repo_title'] != '') : ?>
                            <div class="owner-collection"><?php echo t('catalog_owned_by') ?>: <a href="<?php echo site_url('catalog/' . $row['repositoryid']); ?>"><?php echo $row['repo_title']; ?></a></div>
                        <?php endif; ?>
                    </div>
                    <div class="survey-stats">
                        <span><?php echo t('created_on'); ?>: <?php echo date('M d, Y', $row['created']); ?></span>
                        <span><?php echo t('last_modified'); ?>: <?php echo date('M d, Y', $row['changed']); ?></span>
                        <?php if ((int)$row['total_views'] > 0) : ?>
                            <span><?php echo t('views'); ?>: <?php echo (int)$row['total_views']; ?></span>
                        <?php endif; ?>
                        <?php /* ?>
                    <span><?php echo t('downloads');?>: <?php echo (int)$row['total_downloads'];?></span>
                    <?php */ ?>
                        <?php if (array_key_exists($row['id'], $surveys['citations'])) : ?>
                            <span>
                                <a title="<?php echo t('related_citations'); ?>" href="<?php echo site_url('catalog/' . $row['id'] . '/related_citations'); ?>"><?php echo t('citations'); ?>: <?php echo $surveys['citations'][$row['id']]; ?></a>
                            </span>
                        <?php endif; ?>

                        <!--<i class="icon-da icon-da-<?php echo $row['form_model']; ?>" title="<?php echo t("legend_data_" . $row['form_model']); ?>"></i>-->
                    </div>

                    <?php if (isset($row['var_found'])) : ?>
                        <div class="variables-found" style="clear:both;">
                            <a class="vsearch" href="<?php echo site_url(); ?>/catalog/vsearch/<?php echo $row['id']; ?>/?<?php echo $variable_querystring; ?>">
                                <span class="heading-text"><?php echo sprintf(t('variables_keywords_found'), $row['var_found'], $row['varcount']); ?></span>
                                <span class="toggle-arrow">
                                    <i class="toggle-arrow-right fa fa-caret-right" aria-hidden="true"></i>
                                    <i class="toggle-arrow-down fa fa-caret-down" aria-hidden="true"></i>
                                </span>
                            </a>
                            <span class="vsearch-result"></span>
                            <div class="variable-footer">
                                <input class="btn btn btn-outline-primary btn-sm wb-btn-outline btn-style-1 btn-compare-var" type="button" name="compare-variable" value="Compare variables" />
                                <span class="var-compare-summary"><small><?php echo t('To compare, select two or more variables'); ?></small></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="<?php echo $thumbnail_col_class; ?>">
                    <a href="<?php echo site_url('catalog/' . $row['id']); ?>">
                        <?php if (!empty($row['thumbnail'])) : ?>
                            <img src="<?php echo base_url(); ?>files/thumbnails/<?php echo basename($row['thumbnail']); ?>?v=<?php echo $row['changed'];?>" class="img-fluid img-thumbnail rounded shadow-sm study-thumbnail" />
                        <?php else : ?>
                            <img src="<?php echo base_url(); ?>files/icon-blank.png" class="img-fluid img-thumbnail rounded shadow-sm w-100 study-thumbnail" />
                        <?php endif; ?>
                    </a>
                </div>
            </div> <!-- /.row -->
        </div><!-- /.survey-row -->
        <?php endforeach; ?>

    <?php endif; ?>

    <div class="nada-pagination border-top-none">
        <div class="row mt-3 mb-3 d-flex align-items-lg-center">

            <div class="col-12 col-md-3 col-lg-4 text-center text-md-left mb-2 mb-md-0">
                <?php echo sprintf(
                    t('showing_studies'),
                    (($surveys['limit'] * $current_page) - $surveys['limit'] + 1),
                    ($surveys['limit'] * ($current_page - 1)) + count($surveys['rows']),
                    $surveys['found']
                );
                ?>
            </div>

            <div class="col-12 col-md-9 col-lg-8 d-flex justify-content-center justify-content-lg-end text-center">
                <nav aria-label="Page navigation">
                    <?php
                    $pager_bar = (pager($surveys['found'], $surveys['limit'], $current_page, 5));
                    echo $pager_bar;
                    ?>
                </nav>
            </div>
        </div>

    </div>


    <!-- set per page items size-->
    <div id="items-per-page" class="items-per-page light switch-page-size">
        <small>
            <?php echo t('select_number_of_records_per_page'); ?>:
            <span class="nada-btn change-page-size" data-value="15">15</span>
            <span class="nada-btn change-page-size" data-value="30">30</span>
            <span class="nada-btn change-page-size" data-value="50">50</span>
            <span class="nada-btn change-page-size" data-value="100">100</span>
        </small>
    </div>

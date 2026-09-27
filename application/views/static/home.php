<?php
$this->template->add_variable("body_class","container-fluid-full");
$this->title='Home';

$survey_count=$this->stats_model->get_survey_count();
$popular_surveys=$this->stats_model->get_popular_surveys(5);
$latest_surveys=$this->stats_model->get_latest_surveys(6);
$variable_count=$this->stats_model->get_variable_count();

//studies featured on the central catalog
$featured_studies=$this->repository_model->get_featured_study(null);
$featured_studies=is_array($featured_studies) ? array_slice($featured_studies,0,4) : array();

//dataset counts by type, largest first
$counts=$this->stats_model->get_counts_by_type();
$counts=is_array($counts) ? array_filter($counts) : array();
arsort($counts);

$type_icons=array(
    'survey'=>'fa-database',
    'microdata'=>'fa-database',
    'geospatial'=>'fa-globe-americas',
    'timeseries'=>'fa-chart-line',
    'timeseriesdb'=>'fa-layer-group',
    'document'=>'fa-file-alt',
    'table'=>'fa-table',
    'script'=>'fa-file-code',
    'image'=>'fa-image',
    'video'=>'fa-video',
);

//NADA-AI search: invite descriptive searches, and searches in other languages when its model matches across them
$semantic_search=nada_ai_search_enabled();
$multilingual_search=nada_ai_multilingual_search();

//example searches (config/home_search.php): under the search box, and typed into it when the model is multilingual
$home_phrases=home_search_phrases();
$search_examples=$home_phrases['examples'];
$placeholder_phrases=$home_phrases['typed'];

//published collections that hold datasets, in the order set by the admin
$collections=array();
$collection_counts=array();
foreach((array)$this->repository_model->get_repositories_with_survey_counts() as $row){
    $collection_counts[$row['repositoryid']]=$row['surveys_found'];
}
foreach($this->repository_model->get_repositories($published=TRUE, $system=FALSE) as $repositoryid=>$repo){
    if ($repositoryid=='central' || empty($collection_counts[$repositoryid])){
        continue;
    }
    $repo['surveys_found']=$collection_counts[$repositoryid];
    $collections[]=$repo;
}
$collections_total=count($collections);
$collections=array_slice($collections,0,4);

$search_url=catalog_search_url();

//links to the site's help and access pages; edit the list to suit the site, and set $show_resources to show them
$show_resources=FALSE;
$resources=array(
    array('icon'=>'fa-folder-open', 'url'=>$search_url, 'title'=>t('home_resource_catalog'), 'text'=>t('home_resource_catalog_text')),
    array('icon'=>'fa-quote-right', 'url'=>site_url('citations'), 'title'=>t('home_resource_citations'), 'text'=>t('home_resource_citations_text')),
    array('icon'=>'fa-code', 'url'=>base_url().'api-documentation/catalog/', 'title'=>t('home_resource_api'), 'text'=>t('home_resource_api_text')),
    array('icon'=>'fa-file-contract', 'url'=>site_url('terms-of-use'), 'title'=>t('home_resource_terms'), 'text'=>t('home_resource_terms_text')),
    array('icon'=>'fa-info-circle', 'url'=>site_url('about'), 'title'=>t('home_resource_about'), 'text'=>t('home_resource_about_text')),
);

?>

<section class="home-hero">
    <div class="container">
        <h1 class="home-hero-title"><?php echo html_escape($this->config->item("website_title")); ?></h1>
        <p class="home-hero-intro"><?php echo sprintf(t('home_search_intro'), '<strong>'.number_format($survey_count).'</strong>'); ?></p>

        <form class="home-search" method="get" action="<?php echo $search_url; ?>" role="search">
            <input type="hidden" name="sort_by" value="rank">
            <input type="hidden" name="sort_order" value="desc">
            <div class="home-search-box">
                <i class="fas fa-search home-search-icon" aria-hidden="true"></i>
                <input class="home-search-input" type="search" name="sk" placeholder="<?php echo html_escape(t($semantic_search ? 'home_semantic_placeholder' : 'home_search_placeholder')); ?>" aria-label="<?php echo html_escape(t('search')); ?>"<?php if (count($placeholder_phrases)>0):?> data-phrases="<?php echo html_escape(json_encode($placeholder_phrases)); ?>"<?php endif;?>>
                <button class="btn btn-primary home-search-button" type="submit"><?php echo t('search'); ?></button>
            </div>
        </form>

        <?php if ($semantic_search):?>
        <p class="home-semantic-hint">
            <span class="home-ai-badge"><i class="fas fa-magic" aria-hidden="true"></i><?php echo t('home_ai_assisted'); ?></span>
            <?php echo t($multilingual_search ? 'home_semantic_hint_multilingual' : 'home_semantic_hint'); ?>
        </p>
        <?php endif;?>

        <?php if (count($search_examples)>0):?>
        <div class="home-search-examples">
            <span class="home-search-examples-label"><?php echo t('home_try'); ?></span>
            <?php foreach($search_examples as $example):?>
                <a class="home-example-chip" lang="<?php echo $example['lang']; ?>" dir="<?php echo $example['dir']; ?>" href="<?php echo $search_url.'?'.http_build_query(array('sk'=>$example['text'],'sort_by'=>'rank','sort_order'=>'desc')); ?>"><?php echo html_escape($example['text']); ?></a>
            <?php endforeach;?>
        </div>
        <?php endif;?>

        <a class="home-browse-link" href="<?php echo $search_url; ?>"><?php echo t('home_browse_catalog'); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</section>

<?php $alt_section=TRUE; //sections below the hero alternate white and grey ?>

<?php if (count($featured_studies)>0):?>
<?php $alt_section=!$alt_section; ?>
<section class="home-section<?php echo $alt_section ? ' home-section-alt' : ''; ?>">
    <div class="container">
        <div class="home-section-header">
            <h2 class="home-section-title"><?php echo t('home_featured'); ?></h2>
        </div>
        <div class="row">
            <?php foreach($featured_studies as $study):?>
                <?php $thumbnail=study_thumbnail_url($study); ?>
                <div class="col-12 col-md-6 mb-4">
                    <a class="home-featured-study" href="<?php echo site_url('catalog/'.$study['id']); ?>">
                        <span class="home-featured-study-media">
                            <?php if ($thumbnail):?>
                                <img src="<?php echo $thumbnail; ?>" alt="" loading="lazy">
                            <?php else:?>
                                <i class="fas <?php echo isset($type_icons[$study['type']]) ? $type_icons[$study['type']] : 'fa-folder'; ?>" aria-hidden="true"></i>
                            <?php endif;?>
                        </span>
                        <span class="home-featured-study-body">
                            <span class="home-featured-study-labels">
                                <span class="home-featured-badge"><?php echo t('home_featured_badge'); ?></span>
                                <span class="home-featured-study-type"><?php echo t('tab_'.$study['type']); ?></span>
                            </span>
                            <span class="home-featured-study-title"><?php echo html_escape($study['title']); ?></span>
                            <?php
                                $years=implode(' - ', array_unique(array_filter(array($study['year_start'],$study['year_end']))));
                                $coverage=implode(', ', array_filter(array($study['nation'],$years)));
                            ?>
                            <?php if ($coverage!==''):?>
                                <span class="home-featured-study-meta"><?php echo html_escape($coverage); ?></span>
                            <?php endif;?>
                        </span>
                    </a>
                </div>
            <?php endforeach;?>
        </div>
    </div>
</section>
<?php endif;?>

<?php $alt_section=!$alt_section; ?>
<section class="home-section<?php echo $alt_section ? ' home-section-alt' : ''; ?>">
    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-8">
                <?php $this->load->view("static/recent_studies_list",array('rows'=>$latest_surveys,'type_icons'=>$type_icons)); ?>
            </div>

            <div class="col-12 col-lg-4">
                <?php if (count($counts)>0):?>
                <section class="home-panel home-glance">
                    <h2 class="home-section-title"><?php echo t('home_catalog_at_a_glance'); ?></h2>
                    <?php if ($variable_count>0):?>
                        <p class="home-glance-summary"><?php echo sprintf(t('home_glance_summary'), '<strong>'.number_format($survey_count).'</strong>', '<strong>'.number_format($variable_count).'</strong>'); ?></p>
                    <?php endif;?>
                    <div class="home-glance-grid">
                        <?php foreach($counts as $data_type=>$count):?>
                            <a class="home-glance-item" href="<?php echo $search_url.'?tab_type='.rawurlencode($data_type); ?>">
                                <i class="fas <?php echo isset($type_icons[$data_type]) ? $type_icons[$data_type] : 'fa-folder'; ?>" aria-hidden="true"></i>
                                <span class="home-glance-count"><?php echo number_format($count); ?></span>
                                <span class="home-glance-label"><?php echo t('tab_'.$data_type); ?></span>
                            </a>
                        <?php endforeach;?>
                    </div>
                    <a class="btn btn-primary btn-block home-glance-button" href="<?php echo $search_url; ?>"><?php echo t('home_explore_catalog'); ?></a>
                </section>
                <?php endif;?>

                <?php if (is_array($popular_surveys) && count($popular_surveys)>0):?>
                <section class="home-panel home-popular">
                    <h2 class="home-section-title"><?php echo t('home_most_popular'); ?></h2>
                    <ol class="home-popular-list">
                    <?php foreach($popular_surveys as $survey): ?>
                        <li>
                            <a href="<?php echo site_url('catalog/'.$survey['id']); ?>" title="<?php echo html_escape($survey['title']); ?>"><?php echo html_escape($survey['title']); ?></a>
                            <?php if ($survey['nation']!=''):?>
                                <span class="home-popular-nation"><?php echo html_escape($survey['nation']); ?></span>
                            <?php endif;?>
                        </li>
                    <?php endforeach;?>
                    </ol>
                </section>
                <?php endif;?>
            </div>
        </div>
    </div>
</section>

<?php if (count($collections)>0):?>
<?php $alt_section=!$alt_section; ?>
<section class="home-section<?php echo $alt_section ? ' home-section-alt' : ''; ?>">
    <div class="container">
        <div class="home-section-header">
            <h2 class="home-section-title"><?php echo t('collections'); ?></h2>
            <?php if ($collections_total>count($collections)):?>
                <a class="home-section-link" href="<?php echo site_url('collections'); ?>"><?php echo sprintf(t('home_view_all_collections'), $collections_total); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            <?php endif;?>
        </div>
        <div class="row">
            <?php foreach($collections as $repo){ render_collection_card($repo); } ?>
        </div>
    </div>
</section>
<?php endif;?>

<?php if ($show_resources):?>
<?php $alt_section=!$alt_section; ?>
<section class="home-section home-resources<?php echo $alt_section ? ' home-section-alt' : ''; ?>">
    <div class="container">
        <div class="home-section-header">
            <h2 class="home-section-title"><?php echo t('home_resources'); ?></h2>
        </div>
        <div class="home-resource-grid">
            <?php foreach($resources as $resource):?>
                <a class="home-resource" href="<?php echo $resource['url']; ?>">
                    <span class="home-resource-icon"><i class="fas <?php echo $resource['icon']; ?>" aria-hidden="true"></i></span>
                    <span class="home-resource-title"><?php echo $resource['title']; ?></span>
                    <span class="home-resource-text"><?php echo $resource['text']; ?></span>
                </a>
            <?php endforeach;?>
        </div>
    </div>
</section>
<?php endif;?>

<?php if (count($placeholder_phrases)>0):?>
<script>
//type example searches in several languages into the placeholder, until the visitor starts typing
(function () {
    var input = document.querySelector('.home-search-input[data-phrases]');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!input || reduceMotion) {
        return;
    }

    var phrases = JSON.parse(input.getAttribute('data-phrases'));

    var staticPlaceholder = input.getAttribute('placeholder');
    var phrase = 0, length = 0, erasing = false, timer = null;

    function tick() {
        var chars = Array.from(phrases[phrase].text);
        input.dir = phrases[phrase].dir;
        input.lang = phrases[phrase].lang;
        if (!erasing) {
            length++;
            input.placeholder = chars.slice(0, length).join('');
            if (length >= chars.length) {
                erasing = true;
                return schedule(2000);
            }
            return schedule(70);
        }
        length--;
        input.placeholder = chars.slice(0, length).join('');
        if (length <= 0) {
            erasing = false;
            phrase = (phrase + 1) % phrases.length;
            return schedule(400);
        }
        schedule(30);
    }

    function schedule(ms) {
        timer = window.setTimeout(tick, ms);
    }

    function stop() {
        window.clearTimeout(timer);
        timer = null;
        input.placeholder = staticPlaceholder;
        input.removeAttribute('dir');
        input.removeAttribute('lang');
    }

    function start() {
        if (timer === null && input.value === '' && document.activeElement !== input) {
            length = 0;
            erasing = false;
            schedule(600);
        }
    }

    input.addEventListener('focus', stop);
    input.addEventListener('blur', start);
    start();
})();
</script>
<?php endif;?>

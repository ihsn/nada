<?php
    $sub_title=array();
    if ($survey['type']=='document' && isset($survey['metadata']['document_description']['authors'])){
        $sub_title[]= '<span id="dataset-country">'.html_escape($survey['authoring_entity']).'</span>';
    }else{
        if ($survey['nation']!=''){
            $sub_title[]='<span id="dataset-country">'.html_escape($survey['nation']).'</span>';
        }
    }
    $dates=array_unique(array($survey['year_start'],$survey['year_end']));
    $dates=implode(" - ", $dates);
    if(!empty($dates)){
        $sub_title[]='<span id="dataset-year">'.html_escape($dates).'</span>';
    }
    $sub_title=implode(", ", $sub_title);

    //thumbnail: study image, else the owner collection's image, else the site default
    $thumbnail='files/icon-blank.png';
    if (!empty($survey['owner_repo']['thumbnail']) && is_file(FCPATH.$survey['owner_repo']['thumbnail'])){
        $thumbnail=$survey['owner_repo']['thumbnail'];
    }
    if (isset($survey['thumbnail']) && trim($survey['thumbnail'])!='' && is_file(FCPATH.'files/thumbnails/'.$survey['thumbnail'])){
        $thumbnail='files/thumbnails/'.$survey['thumbnail'];
    }

    //DOI: link only real DOIs (10.xxxx/...); other web links as-is; anything else as text
    $doi_text=''; $doi_href='';
    if (isset($survey['doi']) && trim((string)$survey['doi'])!==''){
        $doi_raw=trim((string)$survey['doi']);
        $doi_bare=preg_replace('~^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)~i', '', $doi_raw);
        if (preg_match('~^10\.\d{4,9}/\S+$~', $doi_bare)){
            $doi_href='https://doi.org/'.$doi_bare;
            $doi_text=$doi_href;
        }
        elseif (preg_match('~^https?://~i', $doi_raw) && !preg_match('~^https?://(?:dx\.)?doi\.org/~i', $doi_raw)){
            $doi_href=$doi_raw;
            $doi_text=$doi_raw;
        }
        else{
            $doi_text=$doi_raw;
        }
    }

    $copy_btn=function($value){
        return '<button type="button" class="btn btn-link study-copy-btn" data-copy="'.html_escape($value).'"'
            .' title="'.html_escape(t('copy')).'" aria-label="'.html_escape(t('copy')).'">'
            .'<i class="far fa-copy" aria-hidden="true"></i></button>';
    };
?>
<div class="row study-info">
    <div class="col-md-2 d-none d-md-block">
        <div class="collection-thumb-container">
            <a href="<?php echo site_url('catalog/'.$survey['owner_repo']['repositoryid']);?>">
            <img src="<?php echo base_url().html_escape($thumbnail);?>?v=<?php echo (int)$survey['changed']; ?>" class="mr-3 img-fluid img-thumbnail" alt="<?php echo html_escape($survey['owner_repo']['repositoryid']);?>" title="<?php echo html_escape($survey['owner_repo']['title']);?>"/>
            </a>
        </div>
    </div>
    <div class="col">
        <div class="study-title-block">
            <h1 class="mt-0 mb-1" id="dataset-title">
                <span><?php echo html_escape($survey_title);?></span>
                <?php if (isset($survey['subtitle']) && trim((string)$survey['subtitle'])!==''):?>
                    <div class="study-subtitle"><?php echo html_escape($survey['subtitle']);?></div>
                <?php endif;?>
            </h1>
            <div class="clearfix study-title-meta">
                <h6 class="sub-title float-left" id="dataset-sub-title"><?php echo $sub_title;?></h6>
                <?php if(isset($data_access_type) && $data_access_type!='data_na' && $survey['type']=='survey'):?>
                <a
                    href="<?php echo site_url("catalog/$sid/get-microdata");?>"
                    class="get-microdata-btn badge badge-primary wb-text-link-uppercase float-left ml-3"
                    title="<?php echo t('get_microdata');?>">
                    <span class="fa fa-download"></span>
                    <?php echo t('get_microdata');?>
                </a>
                <?php endif;?>
                <?php if(isset($reproducibility_package) && $survey['type']=='script' && isset($reproducibility_package['_links']['type'])):?>
                    <?php
                        $link_type_class='fa fa-download';
                        if ($reproducibility_package['_links']['type']=='link'){
                            $link_type_class='fas fa-external-link-alt';
                        }
                    ?>
                <a
                    href="<?php echo html_escape($reproducibility_package['_links']['download']);?>"
                    class="get-microdata-btn badge badge-primary wb-text-link-uppercase float-left ml-3"
                    target="_blank"
                    title="<?php echo t('get_reproducibility_package');?>">
                    <span class="<?php echo $link_type_class;?>"></span>
                    <?php echo t('get_reproducibility_package');?>
                </a>
                <?php endif;?>
            </div>
        </div>

        <?php
            //created / updated / views / downloads as one muted line under the title
            $lang_locales=array('english'=>'en','french'=>'fr','spanish'=>'es','portuguese'=>'pt','arabic'=>'ar','russian'=>'ru','chinese'=>'zh','mongolian'=>'mn');
            $lang_name=strtolower((string)$this->config->item('language'));
            $date_locale=isset($lang_locales[$lang_name]) ? $lang_locales[$lang_name] : 'en';
            //date in the page language (e.g. "6 juil. 2026"), with a machine-readable <time> tag
            $fmt_date=function($ts) use ($date_locale){
                $ts=(int)$ts;
                $text=date("M d, Y",$ts);
                if ($date_locale!=='en' && class_exists('IntlDateFormatter')){
                    $f=new IntlDateFormatter($date_locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, date_default_timezone_get());
                    $out=$f->format($ts);
                    if ($out!==false){
                        $text=$out;
                    }
                }
                return '<time datetime="'.date('Y-m-d',$ts).'">'.html_escape($text).'</time>';
            };
            //strings are English-only for now: fall back when a language has no translation
            $stat_t=function($key, $default){
                $v=t($key);
                return ($v===$key || $v==='') ? $default : $v;
            };
            $fmt_count=function($n, $one_key, $many_key) use ($stat_t){
                $n=(int)$n;
                $tpl=$n===1 ? $stat_t($one_key,'%s view') : $stat_t($many_key,'%s views');
                if ($one_key==='n_download'){
                    $tpl=$n===1 ? $stat_t($one_key,'%s download') : $stat_t($many_key,'%s downloads');
                }
                if (strpos($tpl,'%s')===false){
                    $tpl='%s '.$tpl;
                }
                //"163 views": number emphasised, word muted
                return '<span class="stat-count">'.sprintf($tpl, '<span class="stat-value">'.number_format($n).'</span>').'</span>';
            };

            $stats=array(
                '<span class="stat stat-created" title="'.html_escape($stat_t('stat_created_hint','Date this entry was added to the catalog')).'"><i class="far fa-calendar-plus" aria-hidden="true"></i><span class="stat-label">'.$stat_t('stat_created','Created').'</span> <span class="stat-value">'.$fmt_date($survey['created']).'</span></span>',
            );
            $stats[]='<span class="stat stat-updated" title="'.html_escape($stat_t('stat_updated_hint','Date the metadata was last changed')).'"><i class="fas fa-history" aria-hidden="true"></i><span class="stat-label">'.$stat_t('stat_updated','Updated').'</span> <span class="stat-value">'.$fmt_date($survey['changed']).'</span></span>';
            if ((int)$survey['total_views']>0){
                $stats[]='<span class="stat stat-views" title="'.html_escape($stat_t('stat_views_hint','Number of times this page was viewed')).'"><i class="fas fa-chart-line" aria-hidden="true"></i>'.$fmt_count($survey['total_views'],'n_view','n_views').'</span>';
            }
            if ((int)$survey['total_downloads']>0){
                $stats[]='<span class="stat stat-downloads" title="'.html_escape($stat_t('stat_downloads_hint','Number of file downloads from this page')).'"><i class="fas fa-download" aria-hidden="true"></i>'.$fmt_count($survey['total_downloads'],'n_download','n_downloads').'</span>';
            }
        ?>
        <div class="study-stats"><?php echo implode('<span class="study-stats-sep" aria-hidden="true">&middot;</span>', $stats);?></div>

        <div class="study-info-content">

            <div class="study-info-row">
                <div class="study-info-label"><?php echo t('refno');?></div>
                <div class="study-info-value study-idno">
                    <?php echo html_escape($survey['idno']);?><?php echo $copy_btn($survey['idno']);?>
                </div>
            </div>

            <?php if ($doi_text!==''):?>
            <div class="study-info-row">
                <div class="study-info-label"><?php echo t('DOI');?></div>
                <div class="study-info-value study-doi">
                    <?php if ($doi_href!==''):?>
                        <a target="_blank" rel="noopener" href="<?php echo html_escape($doi_href);?>"><?php echo html_escape($doi_text);?></a>
                    <?php else:?>
                        <?php echo html_escape($doi_text);?>
                    <?php endif;?>
                    <?php echo $copy_btn($doi_text);?>
                </div>
            </div>
            <?php endif;?>

            <?php if ($survey['type']!='document' && isset($survey['authoring_entity']) && !empty($survey['authoring_entity'])):?>
            <div class="study-info-row">
                <div class="study-info-label"><?php echo t('producers');?></div>
                <div class="study-info-value producers"><?php echo html_escape($survey['authoring_entity']);?></div>
            </div>
            <?php endif;?>

            <?php if ($survey['type'] === 'timeseries' && !empty($survey['resolved_databases'])): ?>
            <div class="study-info-row">
                <div class="study-info-label"><?php echo count($survey['resolved_databases']) === 1 ? t('dataset') : t('datasets'); ?></div>
                <div class="study-info-value ts-databases">
                    <?php foreach ($survey['resolved_databases'] as $db): ?>
                        <?php
                        $idno        = isset($db['id'])          ? $db['id']         : '';
                        $name        = isset($db['name'])        ? trim($db['name']) : '';
                        $uri         = isset($db['uri'])         ? trim($db['uri'])  : '';
                        $catalog_url = isset($db['catalog_url']) ? $db['catalog_url'] : null;
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
            <?php endif; ?>

            <?php if (isset($survey['repositories']) && is_array($survey['repositories']) && count($survey['repositories'])>0): ?>
            <div class="study-info-row">
                <div class="study-info-label"><?php echo t('collections');?></div>
                <div class="study-info-value collections">
                    <?php foreach($survey['repositories'] as $repository):?>
                        <a class="study-collection" href="<?php echo site_url('collections/'.rawurlencode($repository['repositoryid']));?>"><?php echo html_escape($repository['title']);?></a>
                    <?php endforeach;?>
                </div>
            </div>
            <?php endif;?>

            <?php if (!empty($page_tabs['view_document']['show_tab'])):?>
            <div class="study-info-row">
                <div class="study-info-label"><?php echo t('PDF');?></div>
                <div class="study-info-value study-pdf">
                    <a class="study-dl-btn" href="<?php echo html_escape($page_tabs['view_document']['url']);?>">
                        <i class="far fa-file-pdf" aria-hidden="true"></i> <?php echo t('tab_view_document');?>
                    </a>
                </div>
            </div>
            <?php endif;?>

            <div class="study-info-row">
                <div class="study-info-label"><?php echo t('metadata');?></div>
                <div class="study-info-value metadata">
                    <!--metadata-->
                    <?php $report_file=unix_path($survey['storage_path'].'/ddi-documentation-'.$this->config->item("language").'-'.$survey['id'].'.pdf');?>
                    <?php if (file_exists($report_file)):?>
                        <a class="download study-dl-btn"
                           href="<?php echo site_url('catalog/'.$survey['id'].'/pdf-documentation');?>"
                           title="<?php echo t('documentation_in_pdf');?>"
                           data-filename="ddi-documentation-<?php echo $this->config->item("language");?>-<?php echo $survey['id'];?>.pdf"
                           data-dctype="report"
                           data-isurl="0"
                           data-extension="pdf"
                           data-sid="<?php echo $survey['id'];?>">
                            <i class="far fa-file-pdf" aria-hidden="true"></i> <?php echo t('documentation_in_pdf');?>
                        </a>
                    <?php endif;?>
                    <?php if($survey['type']=='survey'):?>
                        <a class="download study-dl-btn"
                           href="<?php echo site_url('metadata/export/'.$survey['id'].'/ddi');?>"
                           title="<?php echo t('metadata_in_ddi_xml');?>"
                           data-filename="metadata-<?php echo $survey['id'];?>.xml"
                           data-dctype="metadata"
                           data-isurl="0"
                           data-extension="xml"
                           data-sid="<?php echo $survey['id'];?>">
                            <i class="far fa-file-code" aria-hidden="true"></i> <?php echo t('DDI/XML');?>
                        </a>
                    <?php endif;?>
                    <a class="download study-dl-btn"
                       href="<?php echo site_url('metadata/export/'.$survey['id'].'/json');?>"
                       title="<?php echo t('metadata_in_json');?>"
                       data-filename="metadata-<?php echo $survey['id'];?>.json"
                       data-dctype="metadata"
                       data-isurl="0"
                       data-extension="json"
                       data-sid="<?php echo $survey['id'];?>">
                        <i class="far fa-file-code" aria-hidden="true"></i> <?php echo t('JSON');?>
                    </a>
                    <!--end-metadata-->
                </div>
            </div>

            <?php if($survey['link_study']!='' || $survey['link_indicator']!=''): ?>
            <div class="study-info-row">
                <div class="study-info-label"><?php echo t('study_links');?></div>
                <div class="study-info-value study-links">
                    <?php if($survey['link_study']!=''): ?>
                        <a class="mr-3" target="_blank" rel="noopener" href="<?php echo html_escape($survey['link_study']);?>" title="<?php echo t('link_study_website_hover');?>">
                            <i class="fa fa-globe-americas" aria-hidden="true"></i> <?php echo t('link_study_website');?>
                        </a>
                    <?php endif; ?>
                    <?php if($survey['link_indicator']!=''): ?>
                        <a target="_blank" rel="noopener" href="<?php echo html_escape($survey['link_indicator']);?>" title="<?php echo t('link_indicators_hover');?>">
                            <i class="fa fa-database" aria-hidden="true"></i> <?php echo t('link_indicators_hover');?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    //copy reference id / DOI to the clipboard
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.study-copy-btn') : null;
        if (!btn || !navigator.clipboard) {
            return;
        }
        navigator.clipboard.writeText(btn.getAttribute('data-copy')).then(function () {
            var icon = btn.querySelector('i');
            btn.setAttribute('title', <?php echo json_encode(t('copied'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);?>);
            if (icon) { icon.className = 'fas fa-check'; }
            setTimeout(function () {
                btn.setAttribute('title', <?php echo json_encode(t('copy'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);?>);
                if (icon) { icon.className = 'far fa-copy'; }
            }, 1500);
        });
    });
</script>

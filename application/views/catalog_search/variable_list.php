<?php $this->load->view("catalog_search/active_filter_tokens");?>

<?php if (isset($rows)): ?>
    <?php if ($rows): ?>

        <?php
        //sort
        $sort_by=$search_options->sort_by;
        $sort_order=$search_options->sort_order;

        //set default sort
        if(!$sort_by)
        {
            $sort_by='name';
        }

        //current page url with query strings
        $page_url=site_url().'/catalog/';

        //page querystring for variable sub-search
        $variable_querystring=get_sess_querystring( array('sk', 'vk', 'vf','view'),'search');

        //variables selected for compare
        $compare_items=explode(",",$this->input->cookie('variable-compare', TRUE));
        ?>

        <input type="hidden"  id="sort_order" value="<?php echo $sort_order;?>"/>
        <input type="hidden" id="sort_by" value="<?php echo $sort_by;?>"/>
        <div id="sort-results-by" class="sort-results-by nada-catalog-sort-links">
            <div class="row">

                <div class="col-12 col-md-3 col-lg-2 text-center text-lg-left">
                    <small><?php echo t('sort_results_by');?>:</small>
                </div>

                <div class="col-12 col-md-9 col-lg-10 text-center text-lg-left">
                    <small>
                        <span>
                            <?php echo create_sort_link($sort_by,$sort_order,'name',t('name'),$page_url,array('sk','vk','vf','view') ); ?>
                        </span>|
                        <span>
                            <?php echo create_sort_link($sort_by,$sort_order,'labl',t('label'),$page_url,array('sk','vk','vf', 'view') ); ?>
                        </span>|
                        <span>
                            <?php echo create_sort_link($sort_by,$sort_order,'title',t('field_survey_title'),$page_url,array('sk','vk','vf','view') ); ?>
                        </span>
                        <?php if ($this->config->item("regional_search")=='yes') { ?>
                            |<span>
                                <?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('sk','vk','vf','view') ); ?>
                        </span>
                        <?php } ?>
                    </small>
                    <small class="float-right">
                            <span>
                                <a class="" href="#" onclick="change_view('s');return false;"><?php echo t('switch_to_study_view');?></a>
                            </span>|
                        <span>
                                <a class="btn-compare-var" target="_blank" title="<?php echo t('compare_hover');?>" target="_blank" href="<?php echo site_url(); ?>/catalog/compare"><?php echo t('compare');?></a>
                            </span>
                    </small>

                </div>

            </div> <!-- /row  -->
        </div>
        <?php
        //current page url
        $page_url=site_url().$this->uri->uri_string();

        //total pages
        $pages=ceil($found/$limit);
        ?>
        <div class="nada-pagination">
            <div class="row mt-3 d-flex align-items-lg-center">

                <div class="col-12 col-md-3 col-lg-4 text-center text-md-left mb-2 mb-md-0">
                        <?php echo sprintf(t('showing_variables'),
                            (($limit*$current_page)-$limit+1),
                            ($limit*($current_page-1))+ count($rows),
                            $found);?>
                </div>

                <div class="col-12 col-md-9 col-lg-8 d-flex justify-content-center justify-content-lg-end text-center">
                    <nav aria-label="Page navigation">
                        <?php
                        $pager_bar=(pager($found,$limit,$current_page,5));
                        echo $pager_bar;
                        ?>
                    </nav>
                </div>
            </div>

        </div>

        <div class="variable-list-container">
            <table class="table table-striped table-hover grid-table variable-list">
                <thead>
                <th><?php echo anchor('catalog/compare',t('compare'), array('class'=>'btn-compare-var','title'=>t('compare_selected_variables'),'target'=>'_blank'));?></th>
                <th><?php echo t('name');?></th>
                <th><?php echo t('label');?></th>
                </thead>
                <tbody>
                <?php foreach($rows as $row):?>
                    <?php
                    $compare='';
                    //compare items selected
                    if (in_array($row['sid'].'/'.$row['vid'], $compare_items) )
                    {
                        $compare=' checked="checked" ';
                    }
                    ?>
                    <tr  class="vrow" valign="top" data-url="<?php echo site_url('catalog/'.$row['sid'].'/variable/'.$row['vid']); ?>" data-url-target="_blank" data-title="<?php echo $row['labl'];?>" title="<?php echo t('variable_info');?>">
                        <td title="<?php echo t('mark_for_variable_comparison');?>">
                            <input type="checkbox" class="nada-form-check-input compare" value="<?php echo $row['sid'].'/'
                                .$row['vid']
                            ?>" <?php echo $compare; ?>/>
                        </td>
						<td>
							<?php echo anchor('catalog/'.$row['sid'].'/variable/'.$row['vid'],$row['name'],array('target'=>'blank_','class'=>'dlg','title'=>t('variable_info')));?>							
						</td>
                        <td>
                            <div class="labl" ><?php echo ($row['labl']!=='') ? $row['labl'] : $row['name']; ?></div>
                            <div class="var-subtitle"><?php echo $row['nation']. ' - '.$row['title']; ?></div>
                        </td>
                    </tr>
                <?php endforeach;?>

                </tbody>
            </table>
        </div>

        <div class="nada-pagination">
            <div class="row mt-3 d-flex align-items-lg-center">

                <div class="col-12 col-md-3 col-lg-4 text-center text-md-left mb-2 mb-md-0">
					<?php echo sprintf(t('showing_variables'),
						(($limit*$current_page)-$limit+1),
						($limit*($current_page-1))+ count($rows),
						$found);
					?>
                </div>

                <div class="col-12 col-md-9 col-lg-8 d-flex justify-content-center justify-content-lg-end text-center">
                    <nav aria-label="Page navigation">
                        <?php
                        $pager_bar=(pager($found,$limit,$current_page,5));
                        echo $pager_bar;
                        ?>
                    </nav>
                </div>
            </div>

        </div>

        <div id="items-per-page" class="items-per-page light switch-page-size">
            <small>
                <?php echo t('select_number_of_records_per_page');?>:
                <span class="nada-btn">15</span>
                <span class="nada-btn">30</span>
                <span class="nada-btn">50</span>
                <span class="nada-btn">100</span>
            </small>
        </div>
        <script type="text/javascript">
            var sort_info = {'sort_by': '<?php echo $sort_by;?>', 'sort_order': '<?php echo $sort_order;?>'};
        </script>

    <?php else: ?>
        <?php echo t('no_records_found');?>
    <?php endif; ?>
<?php endif; ?>

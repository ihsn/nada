<?php if ($resources): ?>
    <div style="padding-top: 20px;">
        <h3><?php echo t('study_resources'); ?></h3>
        
        <div class="resources">
            <?php $class = "resource"; ?>
            
            <?php foreach ($resources as $key => $resourcetype): ?>
                <?php if (count($resourcetype) > 0): ?>
                    <fieldset>
                        <legend>
                            <?php 
                            switch ($key) {
                                case 'technical':
                                    echo t('technical_documents');
                                    break;
                                case 'reports':
                                    echo t('reports');
                                    break;
                                case 'questionnaires':
                                    echo t('questionnaires');
                                    break;
                                case 'other':
                                default:
                                    echo t('other_materials');
                                    break;
                            }
                            ?>
                        </legend>
                        
                        <?php foreach ($resourcetype as $row): ?>
                            <?php 
                            // Clean up fields
                            $row['country'] = strip_brackets($row['country']);
                            $row['language'] = strip_brackets($row['language']);
                            
                            $url = NULL;
                            $file_size = '';
                            $is_url = false;
                            
                            // Check file/URL
                            if (substr($row['filename'], 0, 4) == 'www.' 
                                || substr($row['filename'], 0, 7) == 'http://' 
                                || substr($row['filename'], 0, 8) == 'https://' 
                                || substr($row['filename'], 0, 6) == 'ftp://') {
                                $url = prep_url($row['filename']);
                                $is_url = true;
                            } elseif (trim($row['filename']) !== '' 
                                && check_resource_file($survey_folder . '/' . $row['filename']) !== FALSE) {
                                $url = site_url() . '/catalog/' . $sid . '/download/' . $row['resource_id'];
                                $file_size = format_bytes(filesize($survey_folder . '/' . $row['filename']), 2);
                            }
                            
                            // Get file extension
                            $ext = get_file_extension($row['filename']);
                            ?>
                            
                            <?php if ($class == "resource") { 
                                $class = "resource alternate"; 
                            } else { 
                                $class = "resource"; 
                            } ?>
                            
                            <div class="<?php echo $class; ?>">
                                <div class="row">
                                    <div class="col-md-8 col-lg-9">
                                        <div class="resource-info" 
                                             title="<?php echo t('click_to_view_information'); ?>" 
                                             alt="<?php echo t('view_more_information'); ?>" 
                                             id="<?php echo $row['resource_id']; ?>">
                                            <?php echo $row['title']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 col-lg-3">
                                        <?php if ($url != ''): ?>
                                            <?php
                                            $download_str = array();
                                            $download_str[] = strtoupper($ext);
                                            $download_str[] = $file_size;

                                            $download_str = array_filter($download_str);
                                            $button_icon_class = "fa fa-arrow-circle-down";

                                            if ($file_size != '') {
                                                $download_str = t('download') . " [" . implode(", ", $download_str) . "]";
                                            } else {
                                                $download_str = t('External link');
                                                $button_icon_class = "fas fa-external-link-square-alt";
                                            }
                                            ?>
                                            
                                            <a target="_blank" 
                                               href="<?php echo $url; ?>" 
                                               title="<?php echo html_escape(basename($row['filename'])); ?>"
                                               data-filename="<?php echo html_escape(basename($row['filename'])); ?>"
                                               data-dctype="<?php echo html_escape($row['dctype']); ?>"
                                               data-isurl="<?php echo (int)$is_url; ?>"
                                               data-extension="<?php echo html_escape($ext); ?>"
                                               data-sid="<?php echo $row['survey_id']; ?>"
                                               class="download btn btn-outline-primary btn-sm btn-block">
                                                <i class="<?php echo $button_icon_class; ?>" aria-hidden="true"></i> 
                                                <?php echo $download_str; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($row['description'] != '' || $row['title'] != '' || $row['toc'] != ''): ?>
                                    <div id="info_<?php echo $row['resource_id']; ?>" class="abstract">
                                        <?php $fields_arr = array(
                                            'author' => t('authors'),
                                            'subtitle' => t('subtitle'),
                                            'dcdate' => t('date'),
                                            'country' => t('country'),
                                            'language' => t('language'),
                                            'contributor' => t('contributors'),
                                            'publisher' => t('publishers'),
                                            'rights' => t('rights'),
                                            'description' => t('description'),
                                            'abstract' => t('abstract'),
                                            'toc' => t('table_of_contents'),
                                            'subjects' => t('subjects')
                                        ); ?>
                                        
                                        <table class="grid-table tbl-resource-info">
                                            <?php foreach ($row as $key => $value): ?>
                                                <?php if ($value != ""): ?>
                                                    <?php if (array_key_exists($key, $fields_arr)): ?>
                                                        <tr valign="top">
                                                            <td class="caption"><?php echo $fields_arr[$key]; ?></td>
                                                            <td><?php echo nl2br($value); ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            
                                            <tr>
                                                <td class="caption"><?php echo t('download'); ?></td>
                                                <td>
                                                    <?php if ($url === ""): ?>
                                                        N/A
                                                    <?php else: ?>
                                                        <a class="download" 
                                                           title="<?php echo html_escape(basename($row['filename'])); ?>" 
                                                           href="<?php echo $url; ?>"
                                                           data-filename="<?php echo html_escape(basename($row['filename'])); ?>"
                                                           data-dctype="<?php echo html_escape($row['dctype']); ?>"
                                                           data-isurl="<?php echo (int)$is_url; ?>"
                                                           data-extension="<?php echo html_escape($ext); ?>"
                                                           data-sid="<?php echo $row['survey_id']; ?>">
                                                            <?php echo $url; ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
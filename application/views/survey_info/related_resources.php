<?php

$legend_labels = array(
    'technical' => t('technical_documents'),
    'reports' => t('reports'),
    'questionnaires' => t('questionnaires'),
    'other' => t('other_materials')
);

$fields_arr = array(
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
);

?>

<style>
    .study-metadata .resource-info {
        padding-left: 0px;
    }

    .zip-preview .item {
        padding: 5px;
        margin-left: 10px;
    }
    
    .zip-preview .item .item {
        padding-left: 10px;
        margin-left: 10px;
    }
    
    .zip-preview .folder {
        font-weight: normal;
        font-size: 14px;
    }

    .zip-preview .file {
        font-size: 12px;
    }

    .mouse-pointer {
        cursor: pointer;
    }

    .resource-action-buttons .btn + .btn {
        margin-top: 0.35rem;
    }

    #pdf-preview-modal .modal-body {
        padding: 0;
        min-height: 75vh;
    }

    #pdf-preview-frame {
        width: 100%;
        height: 75vh;
        border: 0;
        display: block;
        background: #f5f5f5;
    }
</style>

<?php if (!$resources): ?>
    <div>No documentation is available</div>
    <?php return; ?>
<?php endif; ?>

<div style="padding-top: 20px;">
    <h5><?php echo t('study_resources'); ?></h5>
    
    <div class="resources">
        <?php $class = "resource"; ?>
        <?php foreach ($resources as $key => $resourcetype): ?>
            <?php if (count($resourcetype) > 0): ?>
                <fieldset>
                    <legend>
                        <?php echo isset($group_labels[$key]) ? $group_labels[$key] : (isset($legend_labels[$key]) ? $legend_labels[$key] : t($key)); ?>
                    </legend>
                    
                    <?php foreach ($resourcetype as $row): ?>
                        <?php
                        // Clean up fields
                        $row['country'] = strip_brackets($row['country']);
                        $row['language'] = strip_brackets($row['language']);

                        $url = NULL;
                        $file_size = '';
                        $link_text = '';
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

                        $is_local_pdf = (
                            ! $is_url
                            && $url
                            && (
                                strtolower($ext) === 'pdf'
                                || (isset($row['dcformat']) && strtolower(trim((string) $row['dcformat'])) === 'application/pdf')
                            )
                        );

                        $pdf_preview_url = '';
                        if ($is_local_pdf) {
                            $pdf_preview_url = site_url(
                                'catalog/' . (int) $sid . '/pdf-stream/' . (int) $row['resource_id']
                            );
                        }
                        ?>
                        
                        <?php if ($class == "resource") { 
                            $class = "resource alternate"; 
                        } else { 
                            $class = "resource"; 
                        } ?>
                        
                        <div class="colx <?php echo $class; ?>">
                            <div class="resource-left-colx row">
                                <div class="col-md-8 col-lg-9">
                                    <span class="resource-info" 
                                          title="<?php echo t('click_to_view_information'); ?>" 
                                          alt="<?php echo t('view_more_information'); ?>" 
                                          id="<?php echo $row['resource_id']; ?>">
                                        <i class="far fa-plus-square icon-expand" aria-hidden="true"></i>
                                        <i class="far fa-minus-square icon-collapsed" aria-hidden="true"></i>
                                        <?php echo $row['title']; ?>
                                    </span>
                                </div>

                                <div class="col-md-4 col-lg-3">
                                    <?php if ($url != '' || $file_size != ''): ?>
                                        <div class="resource-action-buttons">
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

                                        <?php if ($pdf_preview_url !== ''): ?>
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm btn-block pdf-preview-btn"
                                                    data-preview-url="<?php echo html_escape($pdf_preview_url); ?>"
                                                    data-title="<?php echo html_escape($row['title']); ?>">
                                                <i class="far fa-file-pdf" aria-hidden="true"></i>
                                                <?php echo t('preview'); ?>
                                            </button>
                                        <?php endif; ?>
                                        </div>

                                        <?php
                                        $link_text = '<img src="' . get_file_icon($ext) . '" alt="' . $ext . '" title="' . basename($row['filename']) . '"/> ';
                                        if ($file_size != '') {
                                            $link_text .= ' &nbsp; ' . $file_size;
                                        }

                                        if ($url != '') {
                                            $link_text = '<a target="_blank" href="' . $url . '" title="' . basename($row['filename']) . '" class="download">' . $link_text . '</a>';
                                        } else {
                                            $link_text = "";
                                        }
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($row['description'] != '' || $row['title'] != '' || $row['toc'] != ''): ?>
                                <div id="info_<?php echo $row['resource_id']; ?>" class="abstract">
                                    <table class="table table-striped grid-table tbl-resource-info">
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
                                                <?php if ($link_text === ""): ?>
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

                                        <?php if (!$is_url && $ext == 'zip'): ?>
                                            <?php $zip_content = $this->Survey_resource_model->get_zip_archive_info($survey_folder . '/' . $row['filename']); ?>
                                            <?php if ($zip_content): ?>
                                                <tr>
                                                    <td class="caption"><?php echo t('Zip preview'); ?></td>
                                                    <td>
                                                        <div style="max-height: 500px; overflow: auto;" class="zip-preview">
                                                            <?php echo $this->load->view('survey_info/zip_preview', array('data' => $zip_content, 'resource_id' => $row['resource_id']), true); ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endif; ?>
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

<div class="modal fade" id="pdf-preview-modal" tabindex="-1" role="dialog" aria-labelledby="pdf-preview-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdf-preview-modal-title"><?php echo t('pdf_preview'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <iframe id="pdf-preview-frame" title="<?php echo html_escape(t('pdf_preview')); ?>"></iframe>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function toggle_resource(element_id) {
        $("#" + element_id).parent(".resource").toggleClass("active");
        $("#" + element_id).toggle();
    }
    
    function bind_behaviours() {
        $(".resource-info").unbind("click");
        $(".resource-info").click(function() {
            if ($(this).attr("id") != "") {
                toggle_resource("info_" + $(this).attr("id"));
            }
            return false;
        });
    }

    function bind_pdf_preview_behaviours() {
        if (!$("#pdf-preview-modal").length) {
            return;
        }

        $(document).off("click.nadaPdfPreview", ".pdf-preview-btn").on("click.nadaPdfPreview", ".pdf-preview-btn", function(e) {
            e.preventDefault();

            var previewUrl = $(this).attr("data-preview-url");
            var title = $(this).attr("data-title") || "<?php echo html_escape(t('pdf_preview')); ?>";
            var $modal = $("#pdf-preview-modal");
            var $frame = $("#pdf-preview-frame");

            $("#pdf-preview-modal-title").text(title);
            $frame.attr("src", "about:blank");

            $modal.off("shown.bs.modal.nadaPdfPreview").one("shown.bs.modal.nadaPdfPreview", function() {
                $frame.attr("src", previewUrl);
            }).modal("show");
        });

        $("#pdf-preview-modal").off("hidden.bs.modal.nadaPdfPreview").on("hidden.bs.modal.nadaPdfPreview", function() {
            $("#pdf-preview-frame").attr("src", "about:blank");
        });
    }

    $(document).ready(function() {
        bind_behaviours();
        bind_pdf_preview_behaviours();

        $(".show-datafiles").click(function() {
            $(".data-files .hidden").removeClass("hidden");
            $(".show-datafiles").hide();
            return false;
        });
    });
</script>
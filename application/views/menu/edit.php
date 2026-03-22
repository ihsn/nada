<?php
		//menu breadcrumbs
		include 'menu_breadcrumb.php';
		?>
<div class="container-fluid page-menu-edit">
	<div class="row">
	<div class="col-md-12">
			<h3 class="page-title mt-3"><?php echo isset($id) ? t('menu_edit') : t('menu_add'); ?></h3>
			
		<?php if (validation_errors()): ?>
			<div class="alert alert-danger">
				<?php echo validation_errors(); ?>
			</div>
			<?php endif; ?>

	<?php $error = $this->session->flashdata('error');?>
	<?php echo ($error != "") ? '<div class="alert alert-danger">' . $error . '</div>' : ''; ?>

	<?php $message = $this->session->flashdata('message');?>
	<?php echo ($message != "") ? '<div class="alert alert-success">' . $message . '</div>' : ''; ?>

	<?php echo form_open($this->html_form_url, array('class' => 'form')); ?>
			
    <div class="form-group mt-3">
        <label for="title"><?php echo t('title'); ?><span class="required">*</span></label>
				<input class="form-control" name="title" type="text" id="title" value="<?php echo get_form_value('title', isset($title) ? $title : ''); ?>"/>
        <input type="hidden" name="pid" value="<?php echo get_form_value('pid', isset($pid) ? $pid : ''); ?>"/>
    </div>

			<div class="form-group">
				<label for="url"><?php echo t('url'); ?><span class="required">*</span></label>
				<input class="form-control" name="url" type="text" id="url" value="<?php echo get_form_value('url', isset($url) ? $url : ''); ?>"/>
				<a href="<?php echo site_url(get_form_value('url', isset($url) ? $url : '')); ?>" target="_blank" class="desc" id="url-label"><?php echo site_url(); ?>/<?php echo get_form_value('url', isset($url) ? $url : ''); ?></a>
    </div>

    <div class="form-group">
        <label for="body"><?php echo t('body'); ?></label>
        
        <?php if ($this->config->item("use_html_editor") !== "no"): ?>
        <!-- Editor Mode Toggle -->
        <div class="editor-mode-toggle mb-2">
            <button type="button" id="toggle-editor-mode" class="btn btn-sm btn-outline-secondary">
                <span id="mode-label"><?php echo t('switch_to_source'); ?></span>
            </button>
        </div>
        
        <!-- Quill WYSIWYG Editor Container -->
					<div id="quill-editor-container" class="editor-container">
						<div id="quill-editor" class="editor-content"></div>
        </div>
        
        <!-- CodeMirror Source Editor Container -->
					<div id="codemirror-editor-container" class="editor-container" style="display: none;">
            <textarea id="codemirror-editor"><?php echo htmlspecialchars(get_form_value('body', isset($body) ? $body : ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <?php endif; ?>
        
        <!-- Hidden field to store actual content -->
				<textarea id="body" name="body" class="<?php echo ($this->config->item("use_html_editor") !== "no") ? 'editor-hidden' : ''; ?>"><?php echo get_form_value('body', isset($body) ? $body : ''); ?></textarea>
    </div>

	<div class="form-group form-inline form-inline-with-spacing">
		<div class="form-group field">
			<label for="target"><?php echo t('open_in'); ?><span class="required">*</span></label>
					<?php echo form_dropdown('target', array(0 => t('same_window'), 1 => t('new_window')), get_form_value("target", isset($target) ? $target : ''), array('class' => 'form-control')); ?>
		</div>

		<div class="form-group ml-3">
					<label for="weight"><?php echo t('weight'); ?><span class="required">*</span></label>
					<input class="form-control" name="weight" type="text" id="weight" size="3" value="<?php echo get_form_value('weight', isset($weight) ? $weight : ''); ?>"/>
		</div>

		<div class="form-group field ml-3">
			<label for="published"><?php echo t('publish'); ?><span class="required">*</span></label>
					<?php echo form_dropdown('published', array(1 => t('yes'), 0 => t('no')), get_form_value("published", isset($published) ? $published : ''), array('class' => 'form-control')); ?>
		</div>
	</div>
	
			<div class="form-group">
				<?php echo form_submit('submit', t('update'), array('class' => 'btn btn-primary btn-sm', 'id' => 'btnupdate')); ?>
				<?php echo anchor('admin/menu', t('cancel'), array('class' => 'btn btn-secondary btn-sm ml-2')); ?>
			</div>

	<?php echo form_close(); ?>
	</div>
	</div>
</div>

<!-- Image Selector Modal -->
<?php if ($this->config->item("use_html_editor") !== "no"): ?>
<div class="modal fade" id="imageSelectorModal" tabindex="-1" role="dialog" aria-labelledby="imageSelectorModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="imageSelectorModalLabel"><?php echo t('select_or_upload_image'); ?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="<?php echo t('close'); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<!-- Tabs -->
				<ul class="nav nav-tabs mb-3" id="imageSelectorTabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="select-tab" data-toggle="tab" href="#select-pane" role="tab" aria-controls="select-pane" aria-selected="true"><?php echo t('select_existing'); ?></a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="upload-tab" data-toggle="tab" href="#upload-pane" role="tab" aria-controls="upload-pane" aria-selected="false"><?php echo t('upload_new'); ?></a>
					</li>
				</ul>
				
				<!-- Tab Content -->
				<div class="tab-content" id="imageSelectorTabContent">
					<!-- Select Tab -->
					<div class="tab-pane fade show active" id="select-pane" role="tabpanel" aria-labelledby="select-tab">
						<div class="mb-3">
							<div class="input-group">
								<input type="text" class="form-control" id="imageSearchInput" placeholder="<?php echo t('search_images'); ?>">
								<div class="input-group-append">
									<button class="btn btn-outline-secondary" type="button" id="imageSearchBtn">
										<i class="fas fa-search"></i> <?php echo t('search'); ?>
									</button>
									<button class="btn btn-outline-secondary" type="button" id="imageRefreshBtn">
										<i class="fas fa-sync-alt"></i> <?php echo t('refresh'); ?>
									</button>
								</div>
							</div>
						</div>
						
						<div id="imageLoading" class="text-center py-4" style="display: none;">
							<div class="spinner-border text-primary" role="status">
								<span class="sr-only"><?php echo t('loading'); ?>...</span>
							</div>
							<p class="mt-2"><?php echo t('loading_images'); ?></p>
						</div>
						
						<div id="imageError" class="alert alert-danger" style="display: none;"></div>
						
						<div id="imageGrid" class="row"></div>
						
						<div id="imagePagination" class="mt-3"></div>
					</div>
					
					<!-- Upload Tab -->
					<div class="tab-pane fade" id="upload-pane" role="tabpanel" aria-labelledby="upload-tab">
						<div class="mb-3">
							<label for="imageFileInput" class="form-label"><?php echo t('select_image_file'); ?></label>
							<input type="file" class="form-control-file" id="imageFileInput" accept="image/*">
							<small class="form-text text-muted"><?php echo t('max_file_size'); ?></small>
						</div>
						
						<div id="uploadError" class="alert alert-danger" style="display: none;"></div>
						
						<div id="uploadProgress" class="progress mb-3" style="display: none;">
							<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
						</div>
						
						<button type="button" class="btn btn-primary" id="uploadImageBtn" disabled>
							<i class="fas fa-upload"></i> <?php echo t('upload_image'); ?>
						</button>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo t('cancel'); ?></button>
				<button type="button" class="btn btn-primary" id="insertImageBtn" disabled><?php echo t('insert_selected_image'); ?></button>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

	<script type="text/javascript">
		$(document).ready(function() {
			$("#title").change(function() {
			if ($("#url").val() == '') {
				$path = $("#title").val().trim().replace(/\s/g, "-").toLowerCase();
					$("#url").val($path);
				updateUrlLink($path);
				}
			});
		
			$("#url").keyup(function() {
			updateUrlLink($("#url").val());
			});

		function updateUrlLink(urlPath) {
			var fullUrl = CI.base_url + '/' + urlPath;
			$("#url-label").attr('href', fullUrl).text(fullUrl);
		}
		});
		</script>

	<?php if ($this->config->item("use_html_editor") !== "no"): ?>
	<script type="text/javascript">
	$(document).ready(function() {
		// Initialize editors
		var quillEditor = null;
		var codeMirrorEditor = null;
		var currentMode = 'wysiwyg'; // 'wysiwyg' or 'source'
		
		// Store raw HTML to preserve complex structures
		var rawHtmlContent = '';
		
		// Get initial content
		var initialContent = $('#body').val() || '';
		rawHtmlContent = initialContent;
		
		// Initialize Quill
		quillEditor = new Quill('#quill-editor', {
			theme: 'snow',
			modules: {
				toolbar: [
					[{ 'header': [1, 2, 3, false] }],
					['bold', 'italic', 'underline', 'strike'],
					[{ 'list': 'ordered'}, { 'list': 'bullet' }],
					[{ 'align': [] }],
					['link', 'image'],
					['clean']
				]
			},
			placeholder: '<?php echo t('enter_content_here'); ?>'
		});
		
		// Function to format HTML with line breaks for better readability
		function formatHtmlWithLineBreaks(html) {
			if (!html) return '';
			
			// Only add line breaks after closing tags of major block elements
			// Use a more conservative approach - only add breaks where they make sense
			html = html.replace(/(<\/p>)/gi, '$1\n');
			html = html.replace(/(<\/div>)/gi, '$1\n');
			html = html.replace(/(<\/h[1-6]>)/gi, '$1\n');
			html = html.replace(/(<\/ul>)/gi, '$1\n');
			html = html.replace(/(<\/ol>)/gi, '$1\n');
			html = html.replace(/(<\/li>)/gi, '$1\n');
			html = html.replace(/(<\/blockquote>)/gi, '$1\n');
			html = html.replace(/(<\/table>)/gi, '$1\n');
			html = html.replace(/(<\/tr>)/gi, '$1\n');
			// Clean up multiple consecutive line breaks (more than 2)
			html = html.replace(/\n{3,}/g, '\n\n');
			// Remove line breaks at start and end
			html = html.trim();
			
			return html;
		}
		
		// Set initial content in Quill using dangerouslyPasteHTML to preserve HTML structure
		if (initialContent) {
			quillEditor.clipboard.dangerouslyPasteHTML(0, initialContent);
		}
		
		// Initialize CodeMirror
		codeMirrorEditor = CodeMirror.fromTextArea(document.getElementById("codemirror-editor"), {
			lineNumbers: true,
			lineWrapping: true,
			mode: "text/html",
			viewportMargin: Infinity,
			indentUnit: 2,
			indentWithTabs: false
		});
		
		// Set initial content in CodeMirror with formatting
		var formattedInitialContent = formatHtmlWithLineBreaks(initialContent);
		codeMirrorEditor.setValue(formattedInitialContent);
		
		// Set CodeMirror height
		codeMirrorEditor.setSize(null, 400);
		
		// Sync content to hidden field before form submit
		function syncContentToHiddenField() {
			if (currentMode === 'wysiwyg') {
				// Get HTML from Quill - but use rawHtmlContent if available to preserve structure
				var html = rawHtmlContent || quillEditor.root.innerHTML;
				$('#body').val(html);
			} else {
				// Get content from CodeMirror
				var html = codeMirrorEditor.getValue();
				rawHtmlContent = html; // Store raw HTML
				$('#body').val(html);
			}
		}
		
		// Toggle between WYSIWYG and Source mode
		$('#toggle-editor-mode').click(function() {
			var html = '';
			
			// Get content from current mode BEFORE switching
			if (currentMode === 'wysiwyg') {
				// When switching FROM WYSIWYG, use rawHtmlContent if available (preserves complex HTML)
				// Otherwise fall back to Quill's innerHTML
				html = rawHtmlContent || quillEditor.root.innerHTML;
				rawHtmlContent = html; // Update stored raw HTML
				// Sync to hidden field
				$('#body').val(html);
				
				// Format HTML with line breaks for better readability in source mode
				var formattedHtml = formatHtmlWithLineBreaks(html);
				
				// Switching to Source mode - set content in CodeMirror
				codeMirrorEditor.setValue(formattedHtml);
				
				// Hide Quill, show CodeMirror
				$('#quill-editor-container').hide();
				$('#codemirror-editor-container').show();
				
				// Update button
				$('#mode-label').html('<?php echo t('switch_to_visual'); ?>');
				currentMode = 'source';
				
				// Refresh CodeMirror
				setTimeout(function() {
					codeMirrorEditor.refresh();
				}, 100);
			} else {
				// Get latest content from CodeMirror (this is the source of truth for complex HTML)
				html = codeMirrorEditor.getValue();
				rawHtmlContent = html; // Store raw HTML from source mode
				// Sync to hidden field
				$('#body').val(html);
				
				// Switching to WYSIWYG mode - use dangerouslyPasteHTML to preserve HTML structure
				quillEditor.setContents([]); // Clear first
				quillEditor.clipboard.dangerouslyPasteHTML(0, html);
				
				// Hide CodeMirror, show Quill
				$('#codemirror-editor-container').hide();
				$('#quill-editor-container').show();
				
				// Update button
				$('#mode-label').html('<?php echo t('switch_to_source'); ?>');
				currentMode = 'wysiwyg';
			}
		});
		
		// Sync content on form submit
		$('form').on('submit', function(e) {
			syncContentToHiddenField();
		});
		
		// Sync content when Quill content changes
		quillEditor.on('text-change', function() {
			if (currentMode === 'wysiwyg') {
				// Update rawHtmlContent when user edits in WYSIWYG mode
				// Note: This will be sanitized HTML, but it's what user sees/edits
				rawHtmlContent = quillEditor.root.innerHTML;
				syncContentToHiddenField();
			}
		});
		
		// Sync content when CodeMirror content changes
		codeMirrorEditor.on('change', function() {
			if (currentMode === 'source') {
				syncContentToHiddenField();
			}
		});
		
		// Image Selector Functionality
		var imageSelector = {
			apiBase: '<?php echo base_url("index.php/api/filestore"); ?>',
			baseUrl: '<?php echo base_url(); ?>',
			filesPublicUrl: '<?php echo base_url("files/public"); ?>',
			selectedImage: null,
			currentPage: 1,
			itemsPerPage: 24,
			totalImages: 0,
			searchQuery: '',
			
			init: function() {
				// Override Quill image handler
				var toolbar = quillEditor.getModule('toolbar');
				toolbar.addHandler('image', function() {
					imageSelector.openModal();
				});
				
				// Modal event handlers
				$('#imageSelectorModal').on('show.bs.modal', function() {
					imageSelector.resetModal();
					imageSelector.loadImages();
				});
				
				// Tab switching
				$('#imageSelectorTabs a').on('shown.bs.tab', function(e) {
					if ($(e.target).attr('href') === '#select-pane') {
						imageSelector.loadImages();
					}
				});
				
				// Search functionality
				$('#imageSearchBtn').on('click', function() {
					imageSelector.currentPage = 1;
					imageSelector.searchQuery = $('#imageSearchInput').val();
					imageSelector.loadImages();
				});
				
				$('#imageSearchInput').on('keypress', function(e) {
					if (e.which === 13) {
						$('#imageSearchBtn').click();
					}
				});
				
				// Refresh button
				$('#imageRefreshBtn').on('click', function() {
					$('#imageSearchInput').val('');
					imageSelector.searchQuery = '';
					imageSelector.currentPage = 1;
					imageSelector.loadImages();
				});
				
				// File input change
				$('#imageFileInput').on('change', function() {
					var file = this.files[0];
					if (file) {
						imageSelector.validateFile(file);
						$('#uploadImageBtn').prop('disabled', false);
					} else {
						$('#uploadImageBtn').prop('disabled', true);
					}
				});
				
				// Upload button
				$('#uploadImageBtn').on('click', function() {
					imageSelector.uploadImage();
				});
				
				// Insert button
				$('#insertImageBtn').on('click', function() {
					imageSelector.insertImage();
				});
			},
			
			openModal: function() {
				$('#imageSelectorModal').modal('show');
			},
			
			resetModal: function() {
				this.selectedImage = null;
				this.currentPage = 1;
				this.searchQuery = '';
				$('#imageSearchInput').val('');
				$('#imageFileInput').val('');
				$('#uploadImageBtn').prop('disabled', true);
				$('#insertImageBtn').prop('disabled', true);
				$('#imageError').hide();
				$('#uploadError').hide();
				$('#uploadProgress').hide();
				$('#imageSelectorTabs a[href="#select-pane"]').tab('show');
			},
			
			loadImages: function() {
				$('#imageLoading').show();
				$('#imageError').hide();
				$('#imageGrid').empty();
				$('#imagePagination').empty();
				
				var offset = (this.currentPage - 1) * this.itemsPerPage;
				var params = {
					filter_images: 'true',
					limit: this.itemsPerPage,
					offset: offset
				};
				
				if (this.searchQuery) {
					params.search = this.searchQuery;
				}
				
				var self = this;
				$.ajax({
					url: this.apiBase,
					method: 'GET',
					data: params,
					success: function(response) {
						$('#imageLoading').hide();
						if (response.status === 'success') {
							self.totalImages = response.total;
							self.displayImages(response.files);
							self.displayPagination();
						} else {
							$('#imageError').text('<?php echo t('error_loading_images_prefix'); ?>' + (response.message || 'Unknown error')).show();
						}
					},
					error: function(xhr, status, error) {
						$('#imageLoading').hide();
						var errorMsg = '<?php echo t('error_loading_images'); ?>';
						if (xhr.responseJSON && xhr.responseJSON.message) {
							errorMsg += ': ' + xhr.responseJSON.message;
						} else {
							errorMsg += ': ' + error;
						}
						$('#imageError').text(errorMsg).show();
					}
				});
			},
			
			displayImages: function(images) {
				var grid = $('#imageGrid');
				grid.empty();
				
				if (images.length === 0) {
					grid.html('<div class="col-12 text-center py-4"><p class="text-muted"><?php echo t('no_images_found'); ?></p></div>');
					return;
				}
				
				var self = this;
				images.forEach(function(image) {
					var imageUrl = self.getImageUrl(image);
					var col = $('<div class="col-6 col-md-4 col-lg-3 mb-3"></div>');
					var card = $('<div class="card image-thumbnail-card" data-file-name="' + image.file_name + '"></div>');
					
					var img = $('<img class="card-img-top" src="' + imageUrl + '" alt="' + image.file_name + '" style="height: 150px; object-fit: cover; cursor: pointer;">');
					img.on('error', function() {
						$(this).attr('src', self.baseUrl + 'files/icon-blank.png');
					});
					
					var cardBody = $('<div class="card-body p-2"></div>');
					var cardTitle = $('<h6 class="card-title text-truncate mb-0" style="font-size: 0.75rem;">' + image.file_name + '</h6>');
					
					cardBody.append(cardTitle);
					card.append(img);
					card.append(cardBody);
					col.append(card);
					grid.append(col);
					
					// Click handler for selection
					card.on('click', function() {
						$('.image-thumbnail-card').removeClass('border-primary');
						$(this).addClass('border-primary');
						self.selectedImage = image;
						$('#insertImageBtn').prop('disabled', false);
					});
				});
			},
			
			displayPagination: function() {
				var totalPages = Math.ceil(this.totalImages / this.itemsPerPage);
				if (totalPages <= 1) return;
				
				var pagination = $('#imagePagination');
				pagination.empty();
				
				var ul = $('<ul class="pagination justify-content-center"></ul>');
				
				// Previous button
				var prevLi = $('<li class="page-item' + (this.currentPage === 1 ? ' disabled' : '') + '"></li>');
				var prevLink = $('<a class="page-link" href="#"><?php echo t('page_prev'); ?></a>');
				if (this.currentPage > 1) {
					prevLink.on('click', function(e) {
						e.preventDefault();
						imageSelector.currentPage--;
						imageSelector.loadImages();
					});
				}
				prevLi.append(prevLink);
				ul.append(prevLi);
				
				// Page numbers
				for (var i = 1; i <= totalPages; i++) {
					if (i === 1 || i === totalPages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
						var li = $('<li class="page-item' + (i === this.currentPage ? ' active' : '') + '"></li>');
						var link = $('<a class="page-link" href="#">' + i + '</a>');
						if (i !== this.currentPage) {
							link.on('click', function(e) {
								e.preventDefault();
								imageSelector.currentPage = parseInt($(this).text());
								imageSelector.loadImages();
							});
						}
						li.append(link);
						ul.append(li);
					} else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
						var ellipsis = $('<li class="page-item disabled"><span class="page-link">...</span></li>');
						ul.append(ellipsis);
					}
				}
				
				// Next button
				var nextLi = $('<li class="page-item' + (this.currentPage === totalPages ? ' disabled' : '') + '"></li>');
				var nextLink = $('<a class="page-link" href="#"><?php echo t('page_next'); ?></a>');
				if (this.currentPage < totalPages) {
					nextLink.on('click', function(e) {
						e.preventDefault();
						imageSelector.currentPage++;
						imageSelector.loadImages();
					});
				}
				nextLi.append(nextLink);
				ul.append(nextLi);
				
				pagination.append(ul);
			},
			
			getImageUrl: function(file) {
				var filePath = file.file_path || '';
				var fileName = encodeURIComponent(file.file_name);
				var cleanPath = filePath.replace(/^\/+|\/+$/g, '');
				var path = cleanPath ? cleanPath + '/' : '';
				return this.filesPublicUrl + '/' + path + fileName;
			},
			
			validateFile: function(file) {
				var maxSize = 100 * 1024 * 1024; // 100MB
				var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'];
				
				$('#uploadError').hide();
				
				if (!allowedTypes.includes(file.type)) {
					$('#uploadError').text('<?php echo t('invalid_file_type'); ?>').show();
					$('#uploadImageBtn').prop('disabled', true);
					return false;
				}
				
				if (file.size > maxSize) {
					$('#uploadError').text('<?php echo t('file_size_exceeds_max'); ?>').show();
					$('#uploadImageBtn').prop('disabled', true);
					return false;
				}
				
				return true;
			},
			
			uploadImage: function() {
				var fileInput = $('#imageFileInput')[0];
				if (!fileInput.files || !fileInput.files[0]) {
					$('#uploadError').text('<?php echo t('please_select_file'); ?>').show();
					return;
				}
				
				var file = fileInput.files[0];
				if (!this.validateFile(file)) {
					return;
				}
				
				var formData = new FormData();
				formData.append('file', file);
				
				$('#uploadError').hide();
				$('#uploadProgress').show();
				$('#uploadImageBtn').prop('disabled', true);
				
				var self = this;
				var xhr = new XMLHttpRequest();
				
				xhr.upload.addEventListener('progress', function(e) {
					if (e.lengthComputable) {
						var percentComplete = (e.loaded / e.total) * 100;
						$('#uploadProgress .progress-bar').css('width', percentComplete + '%');
					}
				});
				
				xhr.addEventListener('load', function() {
					$('#uploadProgress').hide();
					$('#uploadImageBtn').prop('disabled', false);
					
					if (xhr.status === 200) {
						var response = JSON.parse(xhr.responseText);
						if (response.status === 'success') {
							// Switch to select tab and load images
							$('#imageSelectorTabs a[href="#select-pane"]').tab('show');
							self.currentPage = 1;
							self.loadImages();
							
							// Select the newly uploaded image
							setTimeout(function() {
								var newImage = response.result;
								if (newImage) {
									$('.image-thumbnail-card[data-file-name="' + newImage.file_name + '"]').click();
								}
							}, 500);
							
							$('#imageFileInput').val('');
						} else {
							$('#uploadError').text('<?php echo t('upload_failed_prefix'); ?>' + (response.message || 'Unknown error')).show();
						}
					} else {
						var errorMsg = '<?php echo t('upload_failed'); ?>';
						try {
							var response = JSON.parse(xhr.responseText);
							if (response.message) {
								errorMsg += ': ' + response.message;
							}
						} catch (e) {
							errorMsg += ': HTTP ' + xhr.status;
						}
						$('#uploadError').text(errorMsg).show();
					}
				});
				
				xhr.addEventListener('error', function() {
					$('#uploadProgress').hide();
					$('#uploadImageBtn').prop('disabled', false);
					$('#uploadError').text('<?php echo t('network_error_upload'); ?>').show();
				});
				
				xhr.open('POST', this.apiBase);
				xhr.send(formData);
			},
			
			insertImage: function() {
				if (!this.selectedImage) {
					return;
				}
				
				var imageUrl = this.getImageUrl(this.selectedImage);
				var range = quillEditor.getSelection(true);
				
				if (range) {
					quillEditor.insertEmbed(range.index, 'image', imageUrl);
					quillEditor.setSelection(range.index + 1);
				} else {
					// If no selection, insert at the end
					var length = quillEditor.getLength();
					quillEditor.insertEmbed(length - 1, 'image', imageUrl);
					quillEditor.setSelection(length);
				}
				
				// Sync content
				if (currentMode === 'wysiwyg') {
					rawHtmlContent = quillEditor.root.innerHTML;
					syncContentToHiddenField();
				}
				
				$('#imageSelectorModal').modal('hide');
			}
		};
		
		// Initialize image selector
		imageSelector.init();
	});
	</script>
	
	<style>
	.editor-mode-toggle {
		margin-bottom: 10px;
	}
	
	.editor-container {
		border: 1px solid #ddd;
		border-radius: 4px;
	}
	
	.editor-content {
		height: 400px;
	}
	
	.editor-hidden {
		display: none;
	}
	
	.CodeMirror {
		border-radius: 4px;
		height: 400px;
	}
	.required{
		color: red;
		font-weight: bold;
	}
	
	/* Image Selector Styles */
	.image-thumbnail-card {
		cursor: pointer;
		transition: all 0.2s;
		border: 2px solid transparent;
	}
	
	.image-thumbnail-card:hover {
		transform: scale(1.02);
		box-shadow: 0 2px 8px rgba(0,0,0,0.15);
	}
	
	.image-thumbnail-card.border-primary {
		border-color: #007bff !important;
		box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
	}
	
	#imageGrid {
		max-height: 500px;
		overflow-y: auto;
	}
	
	#imageSelectorModal .modal-body {
		min-height: 400px;
	}
	</style>
	<?php endif; ?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="page-title"><?php echo $page_title; ?></h1>
        </div>
    </div>
    
    <?php if ($this->session->flashdata('message')): ?>
        <div class="alert alert-success">
            <?php echo $this->session->flashdata('message'); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger">
            <?php echo $this->session->flashdata('error'); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($progress) && $progress): ?>
        <div class="alert alert-info">
            <strong>Last Export Progress:</strong>
            <ul class="mb-0">
                <li>Exported: <?php echo number_format($progress['processed']); ?> rows in <?php echo $progress['chunks']; ?> chunks</li>
                <li>Remaining: <?php echo number_format($progress['remaining']); ?> rows</li>
                <?php if (isset($progress['iteration'])): ?>
                    <li>Iteration: <?php echo $progress['iteration']; ?></li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Export Status</h5>
        </div>
        <div class="card-body">
            <?php if ($status['table_exists']): ?>
                <p><strong>Table Status:</strong> <span class="text-warning">sitelogs_legacy EXISTS</span></p>
                <p><strong>Rows Remaining:</strong> <strong class="text-primary"><?php echo number_format($status['row_count']); ?></strong></p>
            <?php else: ?>
                <p><strong>Table Status:</strong> <span class="text-success">sitelogs_legacy DOES NOT EXIST</span></p>
                <p class="text-muted">Export is complete or table was never created.</p>
            <?php endif; ?>
            
            <hr>
            
            <p><strong>CSV Files:</strong> <?php echo $status['csv_file_count']; ?> file(s)</p>
            <p><strong>Total Size:</strong> <?php echo $status['csv_total_size_formatted']; ?></p>
            <?php if ($status['current_file_number'] > 0): ?>
                <p><strong>Current File:</strong> #<?php echo $status['current_file_number']; ?> (<?php echo number_format($status['current_file_row_count']); ?> rows)</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($status['table_exists'] && $status['row_count'] > 0): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Export Options</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will export and delete data from the sitelogs_legacy table. Make sure you have a database backup!
                </div>
                
                <div class="mb-3">
                    <h6>Batch Export (Limited)</h6>
                    <p class="text-muted">Processes up to <?php echo number_format(100 * 10000); ?> rows per run. You can run this multiple times.</p>
                    <a href="<?php echo site_url('utils/sitelogs_export/start'); ?>" class="btn btn-primary">
                        Start Batch Export
                    </a>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <h6>Process All (Complete Export)</h6>
                    <p class="text-muted">Automatically processes all remaining data. For web access, this will redirect back to the page after each batch until complete.</p>
                    <a href="<?php echo site_url('utils/sitelogs_export/process_all'); ?>" class="btn btn-success">
                        Process All Data
                    </a>
                </div>
            </div>
        </div>
    <?php elseif ($status['table_exists'] && $status['row_count'] == 0): ?>
        <div class="alert alert-info">
            <strong>Table is empty.</strong> No data to export.
            <?php if ($status['csv_file_count'] > 0): ?>
                <br><?php echo $status['csv_file_count']; ?> CSV file(s) available for download below.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <strong>Export Complete!</strong> The sitelogs_legacy table does not exist.
            <?php if ($status['csv_file_count'] > 0): ?>
                <br><?php echo $status['csv_file_count']; ?> CSV file(s) available for download below.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($status['csv_file_count'] > 0): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Download CSV Files</h5>
            </div>
            <div class="card-body">
                <p class="card-text">The exported CSV files are available for download. Files are split at <?php echo number_format(5000000); ?> rows each (~2GB per file).</p>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($status['csv_files'] as $file): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($file['filename']); ?></code></td>
                                    <td><?php echo $file['size_formatted']; ?></td>
                                    <td>
                                        <a href="<?php echo base_url('logs/' . $file['filename']); ?>" class="btn btn-sm btn-outline-primary" download>
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th>Total: <?php echo $status['csv_file_count']; ?> file(s)</th>
                                <th><?php echo $status['csv_total_size_formatted']; ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

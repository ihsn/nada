<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="page-title"><?php echo $page_title; ?></h1>
        </div>
    </div>
    
    <?php if (isset($export_result) && $export_result['complete']): ?>
        <div class="alert alert-success">
            <strong>✓ Export complete!</strong>
            <ul class="mb-0">
                <li>Rows exported: <?php echo number_format($export_result['processed']); ?></li>
                <li>Chunks processed: <?php echo $export_result['chunks']; ?></li>
                <?php if (isset($export_result['files_created']) && $export_result['files_created'] > 0): ?>
                    <li>Files created: <?php echo $export_result['files_created']; ?></li>
                <?php endif; ?>
                <?php if (isset($export_result['current_file_number'])): ?>
                    <li>Current file: #<?php echo $export_result['current_file_number']; ?></li>
                <?php endif; ?>
            </ul>
        </div>
    <?php elseif (isset($remaining) && $remaining > 0): ?>
        <div class="alert alert-info">
            <strong>Progress:</strong> Exported <?php echo number_format($export_result['processed']); ?> rows in <?php echo $export_result['chunks']; ?> chunks.
            <br><strong>Remaining:</strong> <?php echo number_format($remaining); ?> rows
        </div>
    <?php endif; ?>
    
    <?php if (!empty($export_output)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Export Output</h5>
            </div>
            <div class="card-body p-0">
                <pre class="mb-0 p-3 bg-light border-0" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.5; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($export_output); ?></pre>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($status) && $status['csv_file_count'] > 0): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">CSV Files Created</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Total: <?php echo $status['csv_file_count']; ?> file(s), <?php echo $status['csv_total_size_formatted']; ?> total</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Size</th>
                                <th>Download</th>
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
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <a href="<?php echo site_url('utils/sitelogs_export'); ?>" class="btn btn-primary">
                ← Return to Export Status
            </a>
            <?php if (isset($remaining) && $remaining > 0): ?>
                <a href="<?php echo site_url('utils/sitelogs_export/start'); ?>" class="btn btn-success">
                    Continue Export
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
pre {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}
</style>

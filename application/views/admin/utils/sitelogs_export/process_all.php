<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="page-title"><?php echo $page_title; ?></h1>
        </div>
    </div>
    
    <?php if (isset($progress)): ?>
        <div class="alert alert-info">
            <h5>Processing in Progress...</h5>
            <ul class="mb-0">
                <li><strong>Iteration:</strong> <?php echo $progress['iteration']; ?></li>
                <li><strong>Exported this batch:</strong> <?php echo number_format($progress['processed']); ?> rows in <?php echo $progress['chunks']; ?> chunks</li>
                <?php if (isset($progress['total_exported'])): ?>
                    <li><strong>Total exported so far:</strong> <?php echo number_format($progress['total_exported']); ?> rows</li>
                <?php endif; ?>
                <li><strong>Rows remaining:</strong> <?php echo number_format($progress['remaining']); ?></li>
            </ul>
        </div>
        
        <?php if (!empty($progress['output'])): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Last Batch Output</h5>
                </div>
                <div class="card-body p-0">
                    <pre class="mb-0 p-3 bg-light border-0" style="font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.4; max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($progress['output']); ?></pre>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Processing...</span>
                </div>
                <p class="mt-3">Processing next batch... Please wait.</p>
                <p class="text-muted small">This page will automatically redirect to continue processing.</p>
            </div>
        </div>
        
        <script>
        // Auto-redirect after 2 seconds
        setTimeout(function() {
            window.location.href = '<?php echo $redirect_url; ?>';
        }, 2000);
        </script>
        
    <?php else: ?>
        <div class="alert alert-warning">
            No progress information available.
        </div>
        <a href="<?php echo site_url('utils/sitelogs_export'); ?>" class="btn btn-primary">
            ← Return to Export Status
        </a>
    <?php endif; ?>
</div>

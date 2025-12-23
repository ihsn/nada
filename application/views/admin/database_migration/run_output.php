<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="page-title"><?php echo $page_title; ?></h1>
            <p class="text-muted">
                Migration Version: <strong><?php echo htmlspecialchars($version); ?></strong>
                <?php if ($before_version && $after_version): ?>
                    <br>Version: <?php echo htmlspecialchars($before_version); ?> → <?php echo htmlspecialchars($after_version); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <?php if ($migration_success): ?>
        <div class="alert alert-success">
            <strong>✓ Migration completed successfully!</strong>
            <?php if ($db_debug_was_enabled): ?>
                <br><small>Note: Database debug mode was temporarily disabled during migration.</small>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <strong>✗ Migration failed!</strong>
            <?php if ($error_message): ?>
                <br><?php echo htmlspecialchars($error_message); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($migration_output)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Migration Output</h5>
            </div>
            <div class="card-body p-0">
                <pre class="mb-0 p-3 bg-light border-0" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.5; max-height: 600px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($migration_output); ?></pre>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            No output was captured from the migration.
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <a href="<?php echo site_url('admin/database_migration'); ?>" class="btn btn-primary">
                ← Return to Migrations
            </a>
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

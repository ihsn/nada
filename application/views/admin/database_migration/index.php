<div class="container-fluid">
    <h1>Database Migrations</h1>
    
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
    
    <?php if (!$migration_enabled): ?>
        <div class="alert alert-warning">
            <strong>Migrations are currently disabled.</strong>
            <p>To enable migrations, edit <code>application/config/migration.php</code> and set:</p>
            <pre>$config['migration_enabled'] = TRUE;</pre>
            <p><strong>Important:</strong> Re-disable migrations after running them for security.</p>
        </div>
    <?php endif; ?>
    
    <?php if ($db_debug_enabled): ?>
        <div class="alert alert-warning">
            <strong>⚠ Security Warning: Database debug mode is ENABLED</strong>
            <p>Database debug mode can expose sensitive information in error messages during migrations.</p>
            <p>It will be automatically disabled during migration execution, but for production environments, you should permanently disable it:</p>
            <pre>// In application/config/database.php
$db['default']['db_debug'] = FALSE;</pre>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Current Database Version</h5>
            <p class="card-text">
                <strong>Version:</strong> <?php echo $current_version ? $current_version : 'No migrations run yet'; ?>
            </p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Available Migrations</h5>
            
            <?php if (empty($available_migrations)): ?>
                <p>No migration files found in <code>application/migrations/</code></p>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> Migrations are one-way only. Make sure you have a database backup before proceeding!
                </div>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Name</th>
                            <th>File</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_migrations as $migration): ?>
                            <tr>
                                <td><code><?php echo $migration['version']; ?></code></td>
                                <td><?php echo $migration['name']; ?></td>
                                <td><small><?php echo $migration['file']; ?></small></td>
                                <td>
                                    <?php if ($migration_enabled && $migration['version'] > $current_version): ?>
                                        <a href="<?php echo site_url('admin/database_migration/run/' . $migration['version']); ?>" 
                                           class="btn btn-sm btn-primary"
                                           onclick="return confirm('Are you sure you want to run this migration? This cannot be undone!');">
                                            Run Migration
                                        </a>
                                    <?php elseif (!$migration_enabled && $migration['version'] > $current_version): ?>
                                        <button class="btn btn-sm btn-secondary" disabled>Disabled</button>
                                    <?php elseif ($migration['version'] == $current_version): ?>
                                        <span class="badge badge-success">Current</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Already Applied</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="mt-3">
                    <?php if ($migration_enabled): ?>
                        <a href="<?php echo site_url('admin/database_migration/run/latest'); ?>" 
                           class="btn btn-success"
                           onclick="return confirm('Are you sure you want to migrate to the latest version? This cannot be undone!');">
                            Migrate to Latest
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            Migrations Disabled
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Command Line Usage</h5>
            <p>You can also run migrations via command line:</p>
            <pre>php index.php cli/migrate latest</pre>
            <pre>php index.php cli/migrate version <?php echo $available_migrations ? $available_migrations[0]['version'] : 'YYYYMMDDHHIISS'; ?></pre>
        </div>
    </div>
</div>


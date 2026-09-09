<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Resumable File Upload Configuration
|--------------------------------------------------------------------------
|
| resumable_upload_temp_path - Temporary storage path for chunked uploads
|   Can be relative (to FCPATH) or absolute
|   Default: 'datafiles/tmp/uploads'
|
| resumable_upload_max_size - Maximum file size in bytes (0 = unlimited)
|   Default: 16GB (16 * 1024 * 1024 * 1024)
|
|   Chunks are appended sequentially into the destination file as they
|   arrive. Clients must upload chunks in order (0, 1, 2, ...).
|
|   Production PHP-FPM / nginx should not kill long-running chunk requests:
|     php-fpm: request_terminate_timeout = 0 (or >= 3600)
|     nginx:   client_max_body_size large enough for one chunk
|              fastcgi_read_timeout / proxy_read_timeout 3600
|   The uploader itself sets max_execution_time to 0.
|
|   To remove the file-size limit, set to 0
|
| resumable_upload_chunk_size - Recommended chunk size in bytes
|   Default: 10485760 (10MB)
|   Note: Actual maximum is automatically limited by PHP's post_max_size
|         and upload_max_filesize settings (whichever is smaller)
|
| resumable_upload_expiry_hours - Hours before uploads are cleaned up
|   Default: 10
|
| Note: File type validation uses 'allowed_resource_types' from config.php
|
*/
$config['resumable_upload_temp_path'] = 'datafiles/tmp/uploads';
$config['resumable_upload_max_size'] = 16 * 1024 * 1024 * 1024;
$config['resumable_upload_chunk_size'] = 10485760; // 10MB
$config['resumable_upload_expiry_hours'] = 10;

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
|   Default: 2GB (2 * 1024 * 1024 * 1024)
|   
|   To remove the limit, set to 0
|
| resumable_upload_chunk_size - Recommended chunk size in bytes
|   Default: 10485760 (10MB)
|   Note: Actual maximum is automatically limited by PHP's post_max_size
|         and upload_max_filesize settings (whichever is smaller)
|
| resumable_upload_expiry_hours - Hours before uploads are cleaned up
|   Default: 4
|
| Note: File type validation uses 'allowed_resource_types' from config.php
|
*/
$config['resumable_upload_temp_path'] = 'datafiles/tmp/uploads';
//set to 2GB
$config['resumable_upload_max_size'] = 2 * 1024 * 1024 * 1024;
$config['resumable_upload_chunk_size'] = 10485760; // 10MB
$config['resumable_upload_expiry_hours'] = 4;


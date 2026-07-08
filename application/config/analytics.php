<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Analytics Tracking Configuration
|--------------------------------------------------------------------------
*/

// Enable/disable analytics tracking
$config['analytics_enabled'] = true;

// Enable/disable debug mode for analytics tracking
$config['analytics_debug_js'] = false;

// Tracking source: 'builtin' or 'google_analytics'
// When 'builtin', uses client-side JS tracker
// When 'google_analytics', uses GA4 API (future implementation)
$config['analytics_tracking_source'] = 'builtin';

// Session timeout for client-side tracking (minutes)
// If a user is inactive for this period, a new session ID will be generated.
$config['analytics_session_timeout_minutes'] = 5;

// Legacy dedupe fallback (minutes) when pageview/download-specific keys are unset
$config['analytics_dedupe_window_minutes'] = 5;

// Pageview dedupe window (minutes) — server-side and client-side JS tracker
// Dedup key: study_id + session_id + page_url (or section for AJAX)
// Set to 0 to disable server/client pageview deduplication
$config['analytics_pageview_dedupe_window_minutes'] = 5;

// Download dedupe window (minutes) — server-side only
// Dedup key: study_id + file_name + hashed_ip + user_agent
// Shorter window reduces false dedup on shared NAT/proxy IPs; 0 disables dedupe
$config['analytics_download_dedupe_window_minutes'] = 1;

// API URL for client-side tracking
$config['analytics_api_url'] = '/api/analytics';

// Archive storage path for CSV files
// Path where archived analytics events (CSV files) will be stored
// Relative to application root or use FCPATH constant
// Example: './backup/analytics/' or FCPATH . 'backup/analytics/'
$config['analytics_archive_path'] = FCPATH . 'logs/analytics/';


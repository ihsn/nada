<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Analytics Tracking Configuration
|--------------------------------------------------------------------------
*/

// Enable/disable analytics tracking
$config['analytics_enabled'] = false;

// Enable/disable debug mode for analytics tracking
$config['analytics_debug_js'] = false;

// Tracking source: 'builtin' or 'google_analytics'
// When 'builtin', uses client-side JS tracker
// When 'google_analytics', uses GA4 API (future implementation)
$config['analytics_tracking_source'] = 'builtin';

// Session timeout for client-side tracking (minutes)
// If a user is inactive for this period, a new session ID will be generated.
$config['analytics_session_timeout_minutes'] = 5;

// Deduplication window (minutes)
// Pageviews for the same study within this window will be ignored.
// This prevents duplicate tracking from:
// - Page refreshes
// - Tab navigation (hash changes)
// - Multiple rapid visits to the same study
// Set to 0 to disable deduplication (not recommended)
$config['analytics_dedupe_window_minutes'] = 5;

// API URL for client-side tracking
$config['analytics_api_url'] = '/api/analytics';

// Archive storage path for CSV files
// Path where archived analytics events (CSV files) will be stored
// Relative to application root or use FCPATH constant
// Example: './backup/analytics/' or FCPATH . 'backup/analytics/'
$config['analytics_archive_path'] = FCPATH . 'logs/analytics/';


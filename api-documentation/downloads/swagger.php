<?php
/**
 * Dynamic Swagger YAML Generator
 * 
 * Reads swagger.yaml and injects the correct host and scheme
 * based on the current server configuration
 */

// ==================== CONFIGURATION ====================
// Force HTTPS for the API documentation URLs
// Set to true to always use https, false to auto-detect
$force_https = true;
// =======================================================

// Auto-detect the host and protocol
if ($force_https) {
    $protocol = "https";
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
}
$host = $_SERVER['HTTP_HOST'];

// Calculate the base path of the NADA installation
$scriptPath = dirname($_SERVER['SCRIPT_NAME']); // e.g., /api-documentation/downloads
$pathParts = explode('/', trim($scriptPath, '/'));

// Remove the last 2 parts (api-documentation/downloads) to get the base path
if (count($pathParts) >= 2) {
    array_pop($pathParts); // Remove 'downloads'
    array_pop($pathParts); // Remove 'api-documentation'
}

$basePath = '/' . implode('/', $pathParts);
if ($basePath === '/') {
    $basePath = '';
}

// Append the API path
$apiBasePath = $basePath . '/index.php/api/';

// Read the swagger.yaml file
$swagger_file = __DIR__ . '/swagger.yaml';

if (!file_exists($swagger_file)) {
    header('HTTP/1.1 404 Not Found');
    die('Error: swagger.yaml file not found');
}

$swagger_content = file_get_contents($swagger_file);

if ($swagger_content === false) {
    header('HTTP/1.1 500 Internal Server Error');
    die('Error: Could not read swagger.yaml file');
}

// Find the basePath line and replace it with the calculated path
$lines = explode("\n", $swagger_content);
$output_lines = [];
$injected = false;

foreach ($lines as $line) {
    // Inject host and schemes before basePath, and replace basePath
    if (!$injected && preg_match('/^basePath:\s*/', $line)) {
        $output_lines[] = "host: " . $host;
        $output_lines[] = "schemes:";
        $output_lines[] = "  - " . $protocol;
        $output_lines[] = "basePath: " . $apiBasePath;
        $injected = true;
        continue; // Skip the original basePath line
    }
    
    // Skip existing host or schemes lines if they exist in the YAML
    if (preg_match('/^host:\s*/', $line) || preg_match('/^schemes:\s*$/', $line)) {
        continue;
    }
    
    $output_lines[] = $line;
}

// Output as YAML
header('Content-Type: application/x-yaml; charset=utf-8');
header('Access-Control-Allow-Origin: *');
echo implode("\n", $output_lines);

<?php
/**
 * Dynamic OpenAPI YAML wrapper.
 *
 * Serves openapi.yaml with external schema $ref values rewritten to point at
 * the /schemas/ controller endpoint, so schemas are loaded from
 * application/schemas/ regardless of where NADA is installed.
 */

// Derive the NADA base path from this script's location.
// This file lives at <base>/api-documentation/catalog/openapi.php
$script_path = dirname($_SERVER['SCRIPT_NAME']); // e.g. /NADA/api-documentation/catalog
$parts = array_filter(explode('/', $script_path));
// Remove the last two segments (catalog, api-documentation)
if (count($parts) >= 2) {
    array_pop($parts);
    array_pop($parts);
}
$base_path = '/' . implode('/', $parts);
if ($base_path === '/') {
    $base_path = '';
}

// Build a full absolute URL so ReDoc can correctly resolve nested $ref chains
// within schema files (relative refs inside ddi-schema.json, etc.).
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
$schemas_base = $proto . '://' . $host . $base_path . '/index.php/schemas/';

$yaml = file_get_contents(__DIR__ . '/openapi.yaml');

if ($yaml === false) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error: openapi.yaml not found');
}

// Rewrite external schema $ref values to use the controller URL.
// Matches bare refs ('survey-schema.json'), parent-dir refs ('../catalog-admin/survey-schema.json'),
// and any other relative-path refs, preserving JSON Pointer fragments (#/definitions/...).
$yaml = preg_replace_callback(
    '/(\$ref:\s*[\'"])(?:[^\'\"]*\/)?([a-zA-Z0-9_-]+\.json)(#[^\'"]*)?([\'"])/',
    function ($m) use ($schemas_base) {
        return $m[1] . $schemas_base . $m[2] . ($m[3] ?? '') . $m[4];
    },
    $yaml
);

header('Content-Type: application/x-yaml; charset=utf-8');
header('Access-Control-Allow-Origin: *');
echo $yaml;

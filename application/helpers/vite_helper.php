<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Asset Helper for Vite-built applications
 * Automatically reads asset paths from Vite dist directory
 */
if (!function_exists('get_vite_assets')) {
    /**
     * Get Vite assets from dist directory
     * @param string $dist_path Path to dist directory relative to base_url
     * @return array Array with 'css' and 'js' keys containing asset URLs
     */
    function get_vite_assets($dist_path = 'frontend/dist') {
        $CI =& get_instance();
        $base_path = FCPATH . $dist_path;
        $assets_path = $base_path . '/assets/';
        $manifest_path = file_exists($base_path . '/.vite/manifest.json')
            ? $base_path . '/.vite/manifest.json'
            : $base_path . '/manifest.json';
        
        $assets = [
            'css' => [],
            'js' => []
        ];
        
        // Try to use manifest file first (more reliable)
        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
            if ($manifest) {
                foreach ($manifest as $entry => $details) {
                    if (isset($details['file'])) {
                        $file_url = base_url($dist_path . '/' . $details['file']);
                        $extension = strtolower(pathinfo($details['file'], PATHINFO_EXTENSION));
                        
                        switch ($extension) {
                            case 'css':
                                $assets['css'][] = $file_url;
                                break;
                            case 'js':
                                $assets['js'][] = $file_url;
                                break;
                        }
                    }
                }
                return $assets;
            }
        }
        
        // Fallback to directory scanning
        if (!is_dir($assets_path)) {
            log_message('error', 'Vite assets directory not found: ' . $assets_path);
            return $assets;
        }
        
        // Scan assets directory
        $files = scandir($assets_path);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $file_path = $assets_path . $file;
            $file_url = base_url($dist_path . '/assets/' . $file);
            
            // Determine file type based on extension
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            switch ($extension) {
                case 'css':
                    $assets['css'][] = $file_url;
                    break;
                case 'js':
                    $assets['js'][] = $file_url;
                    break;
            }
        }
        
        return $assets;
    }
}

if (!function_exists('render_vite_assets')) {
    /**
     * Render Vite assets as HTML tags
     * @param string $dist_path Path to dist directory relative to base_url
     * @return string HTML string with link and script tags
     */
    function render_vite_assets($dist_path = 'frontend/dist') {
        $assets = get_vite_assets($dist_path);
        $html = '';
        
        // Render CSS files
        foreach ($assets['css'] as $css_url) {
            $html .= '<link rel="stylesheet" href="' . $css_url . '">' . "\n";
        }
        
        // Render JS files
        foreach ($assets['js'] as $js_url) {
            $html .= '<script type="module" src="' . $js_url . '"></script>' . "\n";
        }
        
        return $html;
    }
}

if (!function_exists('get_vite_asset_url')) {
    /**
     * Get a specific asset URL by pattern
     * @param string $pattern File pattern to match (e.g., 'index-*.js')
     * @param string $dist_path Path to dist directory relative to base_url
     * @return string|false Asset URL or false if not found
     */
    function get_vite_asset_url($pattern, $dist_path = 'frontend/dist') {
        $CI =& get_instance();
        $base_path = FCPATH . $dist_path;
        $assets_path = $base_path . '/assets/';
        $manifest_path = file_exists($base_path . '/.vite/manifest.json')
            ? $base_path . '/.vite/manifest.json'
            : $base_path . '/manifest.json';
        
        // Try manifest first
        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
            if ($manifest) {
                $pattern = str_replace('*', '.*', $pattern);
                foreach ($manifest as $entry => $details) {
                    if (isset($details['file']) && preg_match('/^' . $pattern . '$/', $details['file'])) {
                        return base_url($dist_path . '/' . $details['file']);
                    }
                }
            }
        }
        
        // Fallback to directory scanning
        if (!is_dir($assets_path)) {
            return false;
        }
        
        $files = scandir($assets_path);
        $pattern = str_replace('*', '.*', $pattern);
        
        foreach ($files as $file) {
            if (preg_match('/^' . $pattern . '$/', $file)) {
                return base_url($dist_path . '/assets/' . $file);
            }
        }
        
        return false;
    }
}

if (!function_exists('get_vite_entry_assets')) {
    /**
     * Get assets for a specific entry point from manifest
     * @param string $entry Entry point name (e.g., 'admin_catalog')
     * @param string $dist_path Path to dist directory relative to base_url
     * @return array Array with 'css' and 'js' keys containing asset URLs
     */
    function get_vite_entry_assets($entry = 'admin_catalog', $dist_path = 'frontend/dist') {
        $CI = get_instance();
        $base_path = FCPATH . $dist_path;
        $manifest_path = file_exists($base_path . '/.vite/manifest.json')
            ? $base_path . '/.vite/manifest.json'
            : $base_path . '/manifest.json';
        
        $assets = [
            'css' => [],
            'js' => []
        ];
        
        if (!file_exists($manifest_path)) {
            return $assets;
        }
        
        $manifest = json_decode(file_get_contents($manifest_path), true);
        if (!$manifest) {
            return $assets;
        }
        // Vite 5+ manifest keys are entry paths (e.g. "admin/catalog/main.js"); also support name (e.g. "admin_catalog")
        $entry_data = null;
        if (isset($manifest[$entry])) {
            $entry_data = $manifest[$entry];
        } else {
            foreach ($manifest as $details) {
                if (isset($details['name']) && $details['name'] === $entry) {
                    $entry_data = $details;
                    break;
                }
            }
        }
        if (!$entry_data) {
            return $assets;
        }
        
        // Collect CSS from this entry and all imported chunks (e.g. VMain's VMain-CIv52OBw.css)
        $seen_css = [];
        $collect_css_from_chunk = function ($chunk_key) use ($manifest, $dist_path, &$collect_css_from_chunk, &$seen_css) {
            $urls = [];
            if (!isset($manifest[$chunk_key])) {
                return $urls;
            }
            $data = $manifest[$chunk_key];
            if (isset($data['css'])) {
                foreach ($data['css'] as $css_file) {
                    if (!isset($seen_css[$css_file])) {
                        $seen_css[$css_file] = true;
                        $urls[] = base_url($dist_path . '/' . $css_file);
                    }
                }
            }
            if (isset($data['imports'])) {
                foreach ($data['imports'] as $imp) {
                    foreach ($collect_css_from_chunk($imp) as $u) {
                        $urls[] = $u;
                    }
                }
            }
            return $urls;
        };
        // CSS from imported chunks first (e.g. _VMain-B4voAa0Z.js -> VMain-CIv52OBw.css), then entry's own
        if (isset($entry_data['imports'])) {
            foreach ($entry_data['imports'] as $imp) {
                foreach ($collect_css_from_chunk($imp) as $u) {
                    $assets['css'][] = $u;
                }
            }
        }
        if (isset($entry_data['css'])) {
            foreach ($entry_data['css'] as $css_file) {
                if (!isset($seen_css[$css_file])) {
                    $seen_css[$css_file] = true;
                    $assets['css'][] = base_url($dist_path . '/' . $css_file);
                }
            }
        }
        
        // Add main JS file
        if (isset($entry_data['file'])) {
            $file_url = base_url($dist_path . '/' . $entry_data['file']);
            $extension = strtolower(pathinfo($entry_data['file'], PATHINFO_EXTENSION));
            if ($extension === 'js') {
                $assets['js'][] = $file_url;
            }
        }
        
        return $assets;
    }
} 

if (!function_exists('render_vite_entry_assets')) {
    /**
     * Render Vite assets for a specific entry point as HTML tags
     * @param string $entry Entry point name (e.g., 'admin_catalog')
     * @param string $dist_path Path to dist directory relative to base_url
     * @return string HTML string with link and script tags
     */
    function render_vite_entry_assets($entry = 'admin_catalog', $dist_path = 'frontend/dist') {
        $assets = get_vite_entry_assets($entry, $dist_path);
        $html = '';
        
        // Render CSS files
        foreach ($assets['css'] as $css_url) {
            $html .= '<link rel="stylesheet" href="' . $css_url . '">' . "\n";
        }
        
        // Render JS files
        foreach ($assets['js'] as $js_url) {
            $html .= '<script type="module" src="' . $js_url . '"></script>' . "\n";
        }
        
        return $html;
    }
}

if (!function_exists('render_vite_dev_scripts')) {
    /**
     * Render script tags for Vite dev server (development mode with HMR).
     * Use when VUE_ENVIRONMENT is 'development'.
     * @param string $entry Entry path as served by Vite (e.g. 'admin/catalog/main.js')
     * @param string $vite_dev_url Vite dev server URL (e.g. 'http://localhost:5173')
     * @return string HTML string with script tags
     */
    function render_vite_dev_scripts($entry = 'admin/catalog/main.js', $vite_dev_url = 'http://localhost:5173') {
        $base = rtrim($vite_dev_url, '/');
        return '<script type="module" src="' . $base . '/@vite/client"></script>' . "\n"
            . '<script type="module" src="' . $base . '/' . $entry . '"></script>' . "\n";
    }
}

if (!function_exists('get_vite_asset_info')) {
    /**
     * Get detailed information about Vite assets
     * @param string $dist_path Path to dist directory relative to base_url
     * @return array Detailed asset information
     */
    function get_vite_asset_info($dist_path = 'frontend/dist') {
        $CI = get_instance();
        $base_path = FCPATH . $dist_path;
        $manifest_path = file_exists($base_path . '/.vite/manifest.json')
            ? $base_path . '/.vite/manifest.json'
            : $base_path . '/manifest.json';
        
        $info = [
            'manifest_exists' => false,
            'entries' => [],
            'assets' => [],
            'total_files' => 0
        ];
        
        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
            if ($manifest) {
                $info['manifest_exists'] = true;
                $info['entries'] = array_keys($manifest);
                
                foreach ($manifest as $entry => $details) {
                    if (isset($details['isEntry']) && $details['isEntry']) {
                        $info['assets'][$entry] = [
                            'file' => isset($details['file']) ? base_url($dist_path . '/' . $details['file']) : null,
                            'css' => isset($details['css']) ? array_map(function($css) use ($dist_path) {
                                return base_url($dist_path . '/' . $css);
                            }, $details['css']) : [],
                            'assets' => isset($details['assets']) ? $details['assets'] : []
                        ];
                    }
                }
                
                $info['total_files'] = count($manifest);
            }
        }
        
        return $info;
    }
} 
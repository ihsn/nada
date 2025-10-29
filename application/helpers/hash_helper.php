<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NADA Hash Helper
 * 
 * Provides secure hash generation functions for the NADA application
 * 
 * @package     NADA
 * @subpackage  Helpers
 * @category    Security
 * @link        https://github.com/ihsn/nada
 */

if (!function_exists('nada_hash'))
{
    /**
     * Deterministic hash generator (same input → same ID)
     *
     * @param string $input   The string to hash (e.g., file contents, filename)
     * @param int    $length  Desired length of output (default: 32 chars)
     * @return string
     */
    function nada_hash(string $input, int $length = 32): string
    {
        // Strong SHA-256 hash
        $hash = hash('sha256', $input, true); // raw binary
        $id   = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        return substr($id, 0, $length);
    }
}

if (!function_exists('nada_random_hash'))
{
    /**
     * Random hash generator (unique every call)
     *
     * @param int $length Desired length of output (default: 32 chars)
     * @return string
     */
    function nada_random_hash(int $length = 32): string
    {
        $random = random_bytes(32);
        $id = rtrim(strtr(base64_encode($random), '+/', '-_'), '=');

        return substr($id, 0, $length);
    }
}

if (!function_exists('nada_random_slug'))
{
    /**
     * Random URL slug generator (lowercase, UUID-style with hyphens)
     * 
     * Generates a lowercase slug in UUID-style format with hyphen-separated segments
     * Example: "a3f8b2-9d4e1c-7b2a5f-3e9d1b"
     *
     * @param int $segment_length Length of each segment (default: 6)
     * @param int $segments Number of segments (default: 4)
     * @return string Lowercase URL-safe random slug with hyphens
     */
    function nada_random_slug(int $segment_length = 6, int $segments = 4): string
    {
        $parts = array();
        
        for ($i = 0; $i < $segments; $i++) {
            $parts[] = strtolower(nada_random_hash($segment_length));
        }
        
        return implode('-', $parts);
    }
}

/* End of file hash_helper.php */
/* Location: ./application/helpers/hash_helper.php */

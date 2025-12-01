<?php
/**
 * API Key Manager Library
 * 
 * Provides secure key generation, hashing, and validation utilities
 * for managing API keys with enhanced security.
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Api_key_manager {
    
    /**
     * Generate a cryptographically secure API key
     * 
     * @return array Array with 'key' (full key), 'prefix' (12 chars), 'hash' (SHA-256)
     */
    public function generate_secure_key() {
        // Generate 32 random bytes = 64 hex characters
        $key = bin2hex(random_bytes(32));
        
        return [
            'key' => $key,
            'prefix' => $this->extract_prefix($key),
            'hash' => $this->hash_key($key)
        ];
    }
    
    /**
     * Hash a key using SHA-256
     * 
     * @param string $key The API key to hash
     * @return string SHA-256 hash (64 hex characters)
     */
    public function hash_key($key) {
        return hash('sha256', $key);
    }
    
    /**
     * Extract the first 12 characters as prefix
     * 
     * @param string $key The API key
     * @return string First 12 characters
     */
    public function extract_prefix($key) {
        return substr($key, 0, 12);
    }
    
    /**
     * Constant-time hash comparison to prevent timing attacks
     * 
     * @param string $hash1 First hash to compare
     * @param string $hash2 Second hash to compare
     * @return bool TRUE if hashes match, FALSE otherwise
     */
    public function constant_time_compare($hash1, $hash2) {
        return hash_equals($hash1, $hash2);
    }
    
    /**
     * Mask a key for display (show prefix + "...")
     * 
     * @param string $key The API key (or prefix)
     * @return string Masked key (e.g., "4d7f9a2b8c1e...")
     */
    public function mask_key($key) {
        $prefix = strlen($key) > 12 ? $this->extract_prefix($key) : $key;
        return $prefix . '...';
    }
    
    /**
     * Mask a legacy API key for display (show first 12 chars + "...")
     * 
     * @param string $api_key The full legacy API key (40 chars)
     * @return string Masked key (e.g., "4d7f9a2b8c1e...")
     */
    public function mask_legacy_key($api_key) {
        if (empty($api_key) || strlen($api_key) < 12) {
            return $api_key;
        }
        return substr($api_key, 0, 12) . '...';
    }
    
    /**
     * Check if a key is expired
     * 
     * @param int|null $expires_at Unix timestamp of expiration, or NULL if no expiration
     * @return bool TRUE if expired, FALSE otherwise
     */
    public function is_key_expired($expires_at) {
        if ($expires_at === NULL) {
            return FALSE; // No expiration set
        }
        return time() > (int)$expires_at;
    }
    
    /**
     * Check if a key should be rotated based on age
     * 
     * @param int|null $last_used_at Unix timestamp of last use, or NULL if never used
     * @param int $max_age_days Maximum age in days before suggesting rotation
     * @return bool TRUE if key should be rotated, FALSE otherwise
     */
    public function should_rotate_key($last_used_at, $max_age_days = 730) {
        if ($last_used_at === NULL) {
            return FALSE; // Never used, can't determine age
        }
        
        $age_seconds = time() - (int)$last_used_at;
        $max_age_seconds = $max_age_days * 24 * 60 * 60;
        
        return $age_seconds > $max_age_seconds;
    }
    
    /**
     * Check if a key record is a legacy key (not migrated)
     * 
     * @param object|array $key_record Database record of the key
     * @return bool TRUE if legacy key, FALSE otherwise
     */
    public function is_legacy_key($key_record) {
        if (is_object($key_record)) {
            return $key_record->key_hash === NULL;
        } elseif (is_array($key_record)) {
            return !isset($key_record['key_hash']) || $key_record['key_hash'] === NULL;
        }
        return FALSE;
    }
}

/* End of file Api_key_manager.php */
/* Location: ./application/libraries/Api_key_manager.php */


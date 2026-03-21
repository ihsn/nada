<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenSearch Client
 *
 * Singleton wrapper for the OpenSearch PHP client.
 * All other OpenSearch classes obtain the client via OpenSearch_client::get().
 */

$autoload_path = APPPATH . '../modules/opensearch/vendor/autoload.php';
if (!file_exists($autoload_path)) {
    show_error('OpenSearch PHP client not installed. Run: cd modules/opensearch && composer install');
}
require_once $autoload_path;

use OpenSearch\ClientBuilder;

class OpenSearch_client
{
    private static $client = null;

    /**
     * Return the shared OpenSearch client, creating it on first call.
     */
    public static function get(): \OpenSearch\Client
    {
        if (self::$client === null) {
            self::$client = self::build();
        }
        return self::$client;
    }

    /**
     * Return the index name for a given document type.
     *
     * @param string $type  'surveys' | 'variables' | 'citations'
     */
    public static function index(string $type): string
    {
        $ci =& get_instance();
        $ci->config->load('opensearch');

        $key = 'opensearch_index_' . $type;
        $name = $ci->config->item($key);

        if (empty($name)) {
            throw new InvalidArgumentException("Unknown OpenSearch index type: {$type}");
        }
        return $name;
    }

    /**
     * Quick connectivity check.
     */
    public static function ping(): bool
    {
        try {
            self::get()->ping();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // -------------------------------------------------------------------------

    private static function build(): \OpenSearch\Client
    {
        $ci =& get_instance();
        $ci->config->load('opensearch');

        $host     = $ci->config->item('opensearch_host')            ?: 'localhost';
        $port     = $ci->config->item('opensearch_port')            ?: 9200;
        $ssl      = (bool)$ci->config->item('opensearch_use_ssl');
        $user     = $ci->config->item('opensearch_username')        ?: '';
        $pass     = $ci->config->item('opensearch_password')        ?: '';
        $timeout  = (int)($ci->config->item('opensearch_timeout')   ?: 60);
        $ctimeout = (int)($ci->config->item('opensearch_connect_timeout') ?: 10);

        $protocol = $ssl ? 'https' : 'http';
        $hosts    = [$protocol . '://' . $host . ':' . $port];

        $builder = ClientBuilder::create()->setHosts($hosts);

        if ($user !== '' && $pass !== '') {
            $builder->setBasicAuthentication($user, $pass);
        }

        $builder->setConnectionParams([
            'client' => [
                'timeout'         => $timeout,
                'connect_timeout' => $ctimeout,
            ],
        ]);

        return $builder->build();
    }
}

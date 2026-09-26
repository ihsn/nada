<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The connection to nada-ai: one place for the HTTP call, the outage breaker, the outage policy and what nada-ai can do.
 *
 * Outage breaker. A failure that means nada-ai is down (no connection, timeout, HTTP 5xx except 501, HTTP 429) opens
 * a breaker for nada_ai_breaker_cooldown seconds. While it is open, calls fail at once with
 * Nada_ai_unavailable_exception instead of each waiting for its own timeout; the callers treat it like any outage. When
 * the cooldown ends, one request probes nada-ai (the others keep going without it for a few seconds); a success closes
 * the breaker, a failure opens it again. The state is a few entries in the file cache: it is shared by every PHP
 * process on the server, and losing it only costs a failed attempt or two.
 *
 * Outage policy. nada_ai_on_outage says what a search does while nada-ai is down: database (serve it from the
 * catalog database) or error (fail with the reason).
 *
 * Capabilities. Which searches nada-ai can serve comes from its GET /info, cached for a minute (and kept for a day for
 * when nada-ai is down). When /info has never been read, they are derived from the semantic_search_engine setting.
 */

require_once dirname(__FILE__) . '/Semantic_search_api_exception.php';

class Nada_ai_link
{
    const CAP_STUDIES   = 'studies_search';
    const CAP_VARIABLES = 'variables_search';
    const CAP_CITATIONS = 'citations_search';

    const POLICY_DATABASE = 'database';
    const POLICY_ERROR    = 'error';

    /** Seconds the capabilities from /info are used before it is read again. */
    const INFO_FRESH_TTL = 60;
    /** Seconds the last capabilities are kept for when nada-ai cannot be asked. */
    const INFO_STALE_TTL = 86400;
    /** Seconds one request may spend asking /info. */
    const INFO_TIMEOUT = 3;
    /** Seconds a probe marker keeps the other requests away from a nada-ai that is being tried again. */
    const PROBE_TTL = 5;
    /** Seconds the last failure is remembered, to know that the first request after the cooldown is a probe. */
    const FAILURE_TTL = 600;

    private $ci;
    private $cache = false;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('semantic_search');
    }

    // =========================================================================
    // Settings
    // =========================================================================

    public function url()
    {
        return rtrim((string) $this->ci->config->item('semantic_search_url'), '/');
    }

    public function configured()
    {
        return $this->url() !== '';
    }

    public function cooldown()
    {
        $seconds = (int) $this->ci->config->item('nada_ai_breaker_cooldown');
        return $seconds > 0 ? $seconds : 30;
    }

    /** database | error */
    public function policy()
    {
        return strtolower(trim((string) $this->ci->config->item('nada_ai_on_outage'))) === self::POLICY_ERROR
            ? self::POLICY_ERROR
            : self::POLICY_DATABASE;
    }

    // =========================================================================
    // Outage classification and the breaker
    // =========================================================================

    /**
     * nada-ai unreachable, timed out, or failed on its side (5xx, rate limited). HTTP 501 is not an outage: nada-ai
     * answers it when its engine lacks the capability asked for (unsupported_capability), which means this site is
     * configured for something that engine cannot do. That is a configuration error, not something to hide.
     */
    public function is_outage(Semantic_search_api_exception $e)
    {
        return $e->http_status === 0 || $e->http_status === 429 || ($e->http_status >= 500 && $e->http_status !== 501);
    }

    /**
     * Whether a failed call is served without nada-ai: an outage, and the policy is database. Counted for the status
     * display when it is.
     */
    public function falls_back(Semantic_search_api_exception $e)
    {
        if (!$this->is_outage($e) || $this->policy() !== self::POLICY_DATABASE) {
            return false;
        }
        $this->record_fallback($e instanceof Nada_ai_unavailable_exception ? 'breaker_open' : 'outage');
        return true;
    }

    /** Whether calls are being skipped. The one request that finds the cooldown over probes nada-ai and gets false. */
    public function is_open()
    {
        $cache = $this->cache();
        if ($cache === null) {
            return false;
        }
        if ($cache->get('nada_ai_breaker')) {
            return true;
        }
        if ($cache->get('nada_ai_last_failure')) {
            if ($cache->get('nada_ai_probe')) {
                return true;
            }
            $cache->save('nada_ai_probe', 1, self::PROBE_TTL);
        }
        return false;
    }

    /** Throws Nada_ai_unavailable_exception while the breaker is open. */
    public function guard($url = '', array $request = array())
    {
        if ($this->is_open()) {
            $state = $this->status();
            throw new Nada_ai_unavailable_exception(
                'nada-ai is unavailable' . (!empty($state['reason']) ? ' (' . $state['reason'] . ')' : '')
                    . ' and is not being called until ' . date('H:i:s', (int) $state['until']),
                $url, 0, $request, ''
            );
        }
    }

    /** A call failed: open the breaker when it means nada-ai is down. */
    public function note_failure(Semantic_search_api_exception $e)
    {
        if ($e instanceof Nada_ai_unavailable_exception || !$this->is_outage($e)) {
            return;
        }
        $cache = $this->cache();
        if ($cache === null) {
            return;
        }
        $previous = $cache->get('nada_ai_last_failure');
        $now      = time();
        $reason   = $e->http_status === 0 ? $e->getMessage() : ('HTTP ' . $e->http_status);
        $state    = array(
            'since'    => is_array($previous) ? (int) $previous['since'] : $now,
            'until'    => $now + $this->cooldown(),
            'reason'   => $reason,
            'failures' => (is_array($previous) ? (int) $previous['failures'] : 0) + 1,
        );
        $cache->save('nada_ai_breaker', $state, $this->cooldown());
        $cache->save('nada_ai_last_failure', $state, self::FAILURE_TTL);
        $cache->delete('nada_ai_probe');
        log_message('error', 'Nada_ai_link: nada-ai is down (' . $reason . '); not calling it for ' . $this->cooldown() . 's');
    }

    /** A call worked: close the breaker if it was open or being probed. */
    public function note_success()
    {
        $cache = $this->cache();
        if ($cache !== null && $cache->get('nada_ai_last_failure')) {
            $cache->delete('nada_ai_breaker');
            $cache->delete('nada_ai_last_failure');
            $cache->delete('nada_ai_probe');
            log_message('info', 'Nada_ai_link: nada-ai is answering again');
        }
    }

    /**
     * @param string $kind outage | breaker_open
     */
    public function record_fallback($kind)
    {
        $cache = $this->cache();
        if ($cache === null) {
            return;
        }
        $key    = 'nada_ai_fallbacks_' . date('YmdH');
        $counts = $cache->get($key);
        $counts = is_array($counts) ? $counts : array();
        $counts[$kind] = (isset($counts[$kind]) ? $counts[$kind] : 0) + 1;
        $cache->save($key, $counts, 7200);
    }

    /**
     * @return array{open: bool, since: int|null, until: int|null, reason: string|null, failures: int, fallbacks_this_hour: array}
     */
    public function status()
    {
        $cache  = $this->cache();
        $state  = $cache !== null ? $cache->get('nada_ai_breaker') : false;
        $counts = $cache !== null ? $cache->get('nada_ai_fallbacks_' . date('YmdH')) : false;

        return array(
            'open'                => is_array($state),
            'since'               => is_array($state) ? $state['since'] : null,
            'until'               => is_array($state) ? $state['until'] : null,
            'reason'              => is_array($state) ? $state['reason'] : null,
            'failures'            => is_array($state) ? $state['failures'] : 0,
            'fallbacks_this_hour' => is_array($counts) ? $counts : array(),
        );
    }

    // =========================================================================
    // Capabilities
    // =========================================================================

    /**
     * What nada-ai can serve.
     *
     * @return array{engine: string|null, studies_search: bool, variables_search: bool, citations_search: bool, source: string}
     *         source: info (read just now or a minute ago) | stale (nada-ai could not be asked; the last answer) |
     *         configured (never asked; derived from semantic_search_engine)
     */
    public function capabilities()
    {
        $cache = $this->cache();
        $key   = 'nada_ai_info_' . md5($this->url());

        $fresh = $cache !== null ? $cache->get($key . '_fresh') : false;
        if (is_array($fresh)) {
            return $fresh + array('source' => 'info');
        }

        $info = $this->configured() ? $this->read_info() : null;
        if ($info !== null) {
            $caps = $this->capabilities_from_info($info);
            if ($cache !== null) {
                $cache->save($key . '_fresh', $caps, self::INFO_FRESH_TTL);
                $cache->save($key . '_stale', $caps, self::INFO_STALE_TTL);
            }
            return $caps + array('source' => 'info');
        }

        $stale = $cache !== null ? $cache->get($key . '_stale') : false;
        if (is_array($stale)) {
            return $stale + array('source' => 'stale');
        }

        return $this->capabilities_from_setting() + array('source' => 'configured');
    }

    public function supports($capability)
    {
        $caps = $this->capabilities();
        return !empty($caps[$capability]);
    }

    private function capabilities_from_info(array $info)
    {
        $caps = isset($info['capabilities']) && is_array($info['capabilities']) ? $info['capabilities'] : array();
        return array(
            'engine'             => isset($info['engine']) ? (string) $info['engine'] : null,
            self::CAP_STUDIES   => !empty($caps['studies_search']),
            self::CAP_VARIABLES => !empty($caps['variables_search']),
            self::CAP_CITATIONS => !empty($caps['citations_search']),
        );
    }

    /** What the semantic_search_engine setting implies, for when nada-ai has never been asked. */
    private function capabilities_from_setting()
    {
        $engine = strtolower(trim((string) $this->ci->config->item('semantic_search_engine')));
        $all    = $engine === 'opensearch';
        return array(
            'engine'             => $engine !== '' ? $engine : null,
            self::CAP_STUDIES   => true,
            self::CAP_VARIABLES => $all,
            self::CAP_CITATIONS => $all,
        );
    }

    /**
     * GET /info, or null when nada-ai cannot be reached (or the breaker is open). Reading it is itself a probe: it
     * does not take the probe marker a search call needs, and its outcome opens or closes the breaker like a search's.
     */
    private function read_info()
    {
        $cache = $this->cache();
        if ($cache !== null && $cache->get('nada_ai_breaker')) {
            return null;
        }
        $ch = curl_init($this->url() . '/info');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::INFO_TIMEOUT,
            CURLOPT_HTTPHEADER     => array_merge(array('Accept: application/json'), $this->auth_headers()),
        ));
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->note_failure(new Semantic_search_api_exception("nada-ai /info failed: {$err}", $this->url() . '/info', 0, array()));
            return null;
        }
        if ($status < 200 || $status >= 300) {
            $this->note_failure(new Semantic_search_api_exception("nada-ai /info returned HTTP {$status}", $this->url() . '/info', $status, array(), (string) $raw));
            return null;
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return null;
        }
        $this->note_success();
        return $decoded;
    }

    // =========================================================================
    // The call
    // =========================================================================

    /**
     * POST JSON to nada-ai and decode the answer.
     *
     * @param string   $path          e.g. /studies/search
     * @param string[] $required_keys keys the response must have (anything else is an unexpected response)
     * @param string[] $headers       auth headers for this request
     * @throws Semantic_search_api_exception (Nada_ai_unavailable_exception while the breaker is open)
     */
    public function post_json($path, array $body, array $required_keys, array $headers, $timeout, $caller = 'Nada_ai_link', $debug = false)
    {
        $url     = $this->url() . $path;
        $payload = json_encode($body);

        $this->guard($url, $body);

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => array_merge(array('Content-Type: application/json', 'Accept: application/json'), $headers),
        ));

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', "{$caller}::post_json curl error: {$err}");
            $e = new Semantic_search_api_exception("Semantic search API request failed: {$err}", $url, 0, $body, '');
            $this->note_failure($e);
            throw $e;
        }

        if ($status < 200 || $status >= 300) {
            log_message('error', "{$caller}::post_json HTTP {$status} request: {$payload} response: {$raw}");
            $e = new Semantic_search_api_exception($this->error_message($status, (string) $raw), $url, $status, $body, (string) $raw);
            $this->note_failure($e);
            throw $e;
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || count(array_diff($required_keys, array_keys($decoded))) > 0) {
            log_message('error', "{$caller}::post_json unexpected response: {$raw}");
            throw new Semantic_search_api_exception('Semantic search API returned an unexpected response', $url, $status, $body, (string) $raw);
        }

        $this->note_success();

        if ($debug) {
            log_message('debug', "{$caller} request: {$payload}");
            log_message('debug', "{$caller} response: {$raw}");
        }

        return $decoded;
    }

    /** "Semantic search API returned HTTP 422 (invalid_filter_value): ..." from the API's error envelope. */
    private function error_message($status, $raw)
    {
        $decoded = json_decode($raw, true);
        $error   = is_array($decoded) ? ($decoded['error'] ?? null) : null;
        if (is_array($error) && isset($error['code'], $error['message'])) {
            return sprintf('Semantic search API returned HTTP %d (%s): %s', $status, $error['code'], $error['message']);
        }

        return "Semantic search API returned HTTP {$status}";
    }

    /** The key sent to nada-ai's public routes (the admin key is used by the admin dashboard, not here). */
    private function auth_headers()
    {
        $key = (string) $this->ci->config->item('semantic_search_api_key');
        return $key !== '' ? array('X-NADA-Admin-Key: ' . $key) : array();
    }

    /** The file cache, or null when it is not usable here. Everything cached is an optimisation. */
    private function cache()
    {
        if ($this->cache === false) {
            $this->ci->load->driver('cache', array('adapter' => 'file'));
            $this->cache = $this->ci->cache->file->is_supported() ? $this->ci->cache->file : null;
        }
        return $this->cache;
    }
}

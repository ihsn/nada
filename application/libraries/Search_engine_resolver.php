<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Which engine serves which search.
 *
 * The site has one search engine setting (search_engine), and each search - studies, variables, citations - is
 * served by it when it can be, and by the database when it cannot. This class is the only place that knows that:
 * every call site asks it instead of reading the settings itself.
 *
 *   engine()          what search_engine is set to, the engine that serves searches:
 *                       database | solr | opensearch | nada_ai
 *   index_engine()    what search_index_engine is set to, the engine whose index NADA keeps in sync:
 *                       none | solr | opensearch | nada_ai
 *   provider_for()    the engine that serves one capability (studies | variables | citations): database | solr |
 *                     opensearch | nada_ai
 *   tracked()         whether changes to an object type are queued for the index engine
 *
 * The two are separate so an index can be built and kept in sync while the database still serves searches (index
 * everything with search_engine = database, then switch search_engine). search_engine is either database or the index
 * engine: an engine whose index is not kept in sync cannot serve searches (checked when the settings are saved).
 *
 * nada_ai: nada-ai's OpenSearch serves studies, and variables and citations when nada-ai says it can.
 * nada-ai reports which engine it runs (Nada_ai_link); anything but OpenSearch is a configuration error, not
 * something to work around (backend_mismatch()).
 * Until the variable search of Solr and NADA's OpenSearch has been checked against this catalog's variable view,
 * they are not asked for variables and the database serves them (NATIVE_ENGINES_SERVE_VARIABLES).
 */
require_once dirname(__FILE__) . '/Nada_ai_link.php';

class Search_engine_resolver
{
    // the site setting, and what provider_for() answers
    const DATABASE   = 'database';
    const SOLR       = 'solr';
    const OPENSEARCH = 'opensearch';
    const NADA_AI    = 'nada_ai';

    /** index_engine(): no index is kept in sync. */
    const NONE = 'none';

    /** The engine nada-ai must run for NADA. */
    const NADA_AI_BACKEND = 'opensearch';

    const STUDIES   = 'studies';
    const VARIABLES = 'variables';
    const CITATIONS = 'citations';

    const OBJECT_SURVEY   = 'survey';
    const OBJECT_CITATION = 'citation';

    /** Solr and NADA's own OpenSearch have a variable search, but the catalog's variable view has not been checked with it. */
    const NATIVE_ENGINES_SERVE_VARIABLES = false;

    /** Capability => the key in nada-ai's /info capabilities. */
    private static $nada_ai_capability = array(
        self::STUDIES   => Nada_ai_link::CAP_STUDIES,
        self::VARIABLES => Nada_ai_link::CAP_VARIABLES,
        self::CITATIONS => Nada_ai_link::CAP_CITATIONS,
    );

    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('semantic_search');
        $this->ci->load->library('nada_ai_link');
    }

    /** The engine the site is set to. */
    public function engine()
    {
        $engine = strtolower(trim((string) $this->ci->config->item('search_engine')));
        $valid  = array(self::SOLR, self::OPENSEARCH, self::NADA_AI);
        return in_array($engine, $valid, true) ? $engine : self::DATABASE;
    }

    /** The engine whose index NADA keeps in sync. */
    public function index_engine()
    {
        $engine = strtolower(trim((string) $this->ci->config->item('search_index_engine')));
        $valid  = array(self::SOLR, self::OPENSEARCH, self::NADA_AI);
        return in_array($engine, $valid, true) ? $engine : self::NONE;
    }

    /** Whether the site is set to nada-ai. */
    public function uses_nada_ai()
    {
        return $this->engine() === self::NADA_AI;
    }

    /**
     * The engine nada-ai must run for a setting value (opensearch), or null when it is not nada_ai.
     * Without an argument, the site's setting.
     */
    public function expected_backend($engine = null)
    {
        return ($engine !== null ? $engine : $this->engine()) === self::NADA_AI ? self::NADA_AI_BACKEND : null;
    }

    /**
     * The setting for a nada-ai that runs $backend (nada_ai), or null when NADA cannot use that backend. For the admin
     * search test, which runs the catalog search through nada-ai before it is the site's engine.
     */
    public function nada_ai_engine_for($backend)
    {
        return $backend === self::NADA_AI_BACKEND ? self::NADA_AI : null;
    }

    /**
     * Where nada-ai runs another engine than the setting says: array(expected, actual), or null when it matches, is not
     * a nada-ai setting, or nada-ai has not said what it runs.
     */
    public function backend_mismatch($engine = null)
    {
        $expected = $this->expected_backend($engine);
        if ($expected === null) {
            return null;
        }
        $actual = $this->ci->nada_ai_link->backend();
        return ($actual !== null && $actual !== $expected) ? array('expected' => $expected, 'actual' => $actual) : null;
    }

    /**
     * Throws when nada-ai runs another engine than the setting says. Searches call this before using nada-ai: the
     * study driver and its index belong to one engine, so a mismatch would query an index that is empty or of the
     * wrong kind.
     */
    public function assert_backend($engine = null)
    {
        $mismatch = $this->backend_mismatch($engine);
        if ($mismatch !== null) {
            throw new Exception(sprintf(
                'The search engine is set to %s, but NADA-AI is running %s. Change the search engine in Site configurations > Search, or restart NADA-AI with the matching backend.',
                $engine !== null ? $engine : $this->engine(), $mismatch['actual']
            ));
        }
    }

    /** The engine that serves one capability. */
    public function provider_for($capability)
    {
        $engine = $this->engine();

        if ($this->uses_nada_ai()) {
            return $this->nada_ai_supports($capability) ? self::NADA_AI : self::DATABASE;
        }

        if ($capability === self::VARIABLES && !self::NATIVE_ENGINES_SERVE_VARIABLES) {
            return self::DATABASE;
        }

        return $engine;
    }

    public function nada_ai_supports($capability)
    {
        if ($capability === self::STUDIES) {
            return true;
        }
        $link = $this->ci->nada_ai_link;
        return $link->configured() && $link->supports(self::$nada_ai_capability[$capability]);
    }

    /**
     * Whether changes to an object type are queued: every study and citation change is, while an index engine is set,
     * whichever engine serves the searches. Not whether nada-ai can serve citation search right now: its citation
     * index is kept up to date either way.
     *
     * @param string $object_type survey | citation
     */
    public function tracked($object_type)
    {
        return $this->index_engine() !== self::NONE;
    }

    /**
     * @return array{engine: string, index_engine: string, serves: array<string, string>, nada_ai: array|null, backend_mismatch: array|null, breaker: array, policy: string}
     */
    public function summary()
    {
        $link = $this->ci->nada_ai_link;
        $serves = array();
        foreach (array(self::STUDIES, self::VARIABLES, self::CITATIONS) as $capability) {
            $serves[$capability] = $this->provider_for($capability);
        }

        return array(
            'engine'  => $this->engine(),
            'index_engine' => $this->index_engine(),
            'serves'  => $serves,
            'nada_ai' => $link->configured() ? $link->capabilities() : null,
            'backend_mismatch' => $this->backend_mismatch(),
            'breaker' => $link->status(),
            'policy'  => $link->policy(),
        );
    }
}

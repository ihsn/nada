<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Which engine serves which search.
 *
 * The site has one search engine setting (search_engine), and each search - studies, variables, citations - is
 * served by it when it can be, and by the database when it cannot. This class is the only place that knows that:
 * every call site asks it instead of reading the settings itself.
 *
 *   engine            what search_engine selects: database | solr | opensearch_native | nada_ai
 *   provider_for()    the engine that serves one capability (studies | variables | citations)
 *   tracked()         whether changes to an object type must be queued for the engine that serves it
 *
 * nada_ai is the only engine that can lack a capability: which ones it has comes from nada-ai itself (Nada_ai_link).
 * Until the variable search of Solr and NADA's OpenSearch has been checked against this catalog's variable view,
 * they are not asked for variables and the database serves them (NATIVE_ENGINES_SERVE_VARIABLES).
 */
require_once dirname(__FILE__) . '/Nada_ai_link.php';

class Search_engine_resolver
{
    const DATABASE          = 'database';
    const SOLR              = 'solr';
    const OPENSEARCH_NATIVE = 'opensearch_native';
    const NADA_AI           = 'nada_ai';

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
        return in_array($engine, array(self::SOLR, self::OPENSEARCH_NATIVE, self::NADA_AI), true) ? $engine : self::DATABASE;
    }

    /** The engine that serves one capability. */
    public function provider_for($capability)
    {
        $engine = $this->engine();

        if ($engine === self::NADA_AI) {
            return $this->nada_ai_supports($capability) ? self::NADA_AI : self::DATABASE;
        }

        if ($capability === self::VARIABLES && !self::NATIVE_ENGINES_SERVE_VARIABLES) {
            return self::DATABASE;
        }

        return $engine;
    }

    /**
     * The study search driver for a nada-ai that runs OpenSearch or Qdrant: opensearch | qdrant | qdrant_db, or null
     * when nada-ai has never been asked what it runs (and cannot be reached now).
     */
    public function nada_ai_study_driver()
    {
        $link    = $this->ci->nada_ai_link;
        $backend = $link->backend();
        if ($backend === 'opensearch') {
            return 'opensearch';
        }
        if ($backend === 'qdrant') {
            return $link->combine_with_database() ? 'qdrant_db' : 'qdrant';
        }
        return null;
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
     * Whether changes to an object type are queued: NADA keeps an engine other than the database up to date from the
     * queue, so it is needed when the engine that serves that object type is one.
     *
     * @param string $object_type survey | citation
     */
    public function tracked($object_type)
    {
        $capability = $object_type === self::OBJECT_CITATION ? self::CITATIONS : self::STUDIES;
        return $this->provider_for($capability) !== self::DATABASE;
    }

    /**
     * @return array{engine: string, serves: array<string, string>, nada_ai: array|null, breaker: array, policy: string}
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
            'serves'  => $serves,
            'nada_ai' => $link->configured() ? $link->capabilities() : null,
            'breaker' => $link->status(),
            'policy'  => $link->policy(),
        );
    }
}

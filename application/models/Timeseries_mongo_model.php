<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

require_once APPPATH . '../modules/mongodb/vendor/autoload.php';

/**
 * Timeseries_mongo_model
 *
 * MongoDB storage for SDMX-style indicator observations: one collection per DSD
 * (`indicator_ts_{data_structure_id}`), dynamic field names from
 * `data_structure_components`, tenancy via `sid`, uniqueness via `key_hash`.
 *
 * DSD catalogue resolution and export shaping: {@see Timeseries_dsd_model}.
 */
class Timeseries_mongo_model extends CI_Model {

	/** @var Timeseries_dsd_model|null */
	private $timeseries_dsd_model;

	/** column_type values that participate in key_hash (plus time_period). */
	private static $key_identity_column_types = [
		'dimension',
		'geography',
		'periodicity',
		'indicator_id',
		'time_period',
	];

	/** Document keys owned by the platform (not DSD component names). */
	private static $reserved_document_keys = [
		'_id' => true,
		'sid'               => true,
		'idno'              => true,
		'dsd_id'            => true,
		'key_hash'          => true,
		'key_spec_rev'      => true,
		'_ts_year'          => true,
		'_ts_freq'          => true,
		'_ts_subperiod'     => true,
		'_ts_period_start'  => true,
		'_ts_period_end'    => true,
		'created_at'        => true,
		'updated_at'        => true,
	];

	/** @var \MongoDB\Client|null */
	private $mongo_client;

	/** @var string */
	private $db_name;

	/** @var string */
	private $collection_prefix;

	/** @var string */
	private $key_hash_prefix;

	public function __construct()
	{
		parent::__construct();
		$this->config->load('mongo');
		$this->config->load('indicator_timeseries');
		$this->db_name = (string) $this->config->item('mongodb_database');
		$this->collection_prefix = (string) $this->config->item('indicator_timeseries_collection_prefix');
		if ($this->collection_prefix === '') {
			$this->collection_prefix = 'indicator_ts_';
		}
		$this->key_hash_prefix = (string) $this->config->item('indicator_timeseries_key_hash_prefix');
	}

	// -------------------------------------------------------------------------
	// Mongo connection & collection
	// -------------------------------------------------------------------------

	public function get_db_connection()
	{
		if ($this->mongo_client !== null) {
			return $this->mongo_client;
		}

		$username = $this->config->item('mongodb_username');
		$password = $this->config->item('mongodb_password');
		$host     = $this->config->item('mongodb_host');
		$port     = $this->config->item('mongodb_port');

		if (!empty($username) && !empty($password)) {
			$this->mongo_client = new MongoDB\Client(
				"mongodb://{$host}:{$port}",
				[
					'username'   => $username,
					'password'   => $password,
					'db'         => $this->get_mongo_db_name(),
					'authSource' => $this->get_mongo_db_name(),
				]
			);
		} else {
			$this->mongo_client = new MongoDB\Client(
				"mongodb://{$host}:{$port}",
				['db' => $this->get_mongo_db_name()]
			);
		}

		return $this->mongo_client;
	}

	public function get_mongo_db_name()
	{
		if ($this->db_name === '') {
			throw new RuntimeException('MongoDB database name is not configured (mongodb_database).');
		}
		return $this->db_name;
	}

	/**
	 * @param int $dsd_id data_structures.id
	 */
	public function get_collection_name($dsd_id)
	{
		$dsd_id = (int) $dsd_id;
		if ($dsd_id <= 0) {
			throw new InvalidArgumentException('Invalid data structure id.');
		}
		return strtolower($this->collection_prefix . $dsd_id);
	}

	/**
	 * @param int $dsd_id
	 * @return Collection
	 */
	public function get_collection($dsd_id)
	{
		$name = $this->get_collection_name($dsd_id);
		return $this->get_db_connection()->{$this->get_mongo_db_name()}->{$name};
	}

	/**
	 * @return Timeseries_dsd_model
	 */
	private function timeseries_dsd()
	{
		if (!isset($this->timeseries_dsd_model)) {
			$this->load->model('Timeseries_dsd_model');
			$this->timeseries_dsd_model = $this->Timeseries_dsd_model;
		}
		return $this->timeseries_dsd_model;
	}

	/**
	 * @param array $components Data_structure_component_model rows
	 * @return array{name:string,column_type:string,...}|null
	 */
	public function get_time_period_component(array $components)
	{
		$found = [];
		foreach ($components as $c) {
			if (!is_array($c) || empty($c['column_type'])) {
				continue;
			}
			if ($c['column_type'] === 'time_period') {
				$found[] = $c;
			}
		}
		if (count($found) === 0) {
			return null;
		}
		if (count($found) > 1) {
			throw new RuntimeException('DSD defines more than one time_period component.');
		}
		return $found[0];
	}

	/**
	 * CSV import must provide a column for every DSD dimension, geography, and measure, plus
	 * time_period and observation_value when the DSD defines those roles (canonical `name` after header / mapping).
	 *
	 * @param string[] $mapped_component_names Unique canonical component names matched from the CSV header row
	 * @param array    $components             data_structure_components rows
	 * @throws Exception
	 */
	public function assert_csv_import_mapped_fields_cover_required_dsd(array $mapped_component_names, array $components)
	{
		$mapped = [];
		foreach ($mapped_component_names as $n) {
			$n = trim((string) $n);
			if ($n !== '') {
				$mapped[$n] = true;
			}
		}
		$missing = [];
		foreach ($components as $c) {
			if (!is_array($c) || empty($c['name']) || empty($c['column_type'])) {
				continue;
			}
			$ct = (string) $c['column_type'];
			if (!in_array($ct, ['dimension', 'geography', 'measure'], true)) {
				continue;
			}
			$name = (string) $c['name'];
			if (!isset($mapped[$name])) {
				$missing[] = "{$name} ({$ct})";
			}
		}
		$tp = $this->get_time_period_component($components);
		if ($tp !== null && !empty($tp['name'])) {
			$tn = (string) $tp['name'];
			if (!isset($mapped[$tn])) {
				$missing[] = "{$tn} (time_period)";
			}
		}
		$ov = $this->get_component_name_for_column_type($components, 'observation_value');
		if ($ov !== null && $ov !== '') {
			if (!isset($mapped[$ov])) {
				$missing[] = "{$ov} (observation_value)";
			}
		}
		if ($missing !== []) {
			throw new Exception(
				'CSV is missing required DSD column(s): ' . implode(', ', $missing)
				. '. Use each component\'s field name as the header (or the multipart "mapping" JSON from CSV header to DSD name).'
			);
		}
	}

	/**
	 * Sorted component names that participate in key_hash identity.
	 *
	 * @param array $components
	 * @return string[]
	 */
	public function get_key_identity_component_names(array $components)
	{
		$names = [];
		foreach ($components as $c) {
			if (!is_array($c) || empty($c['name']) || empty($c['column_type'])) {
				continue;
			}
			if (in_array($c['column_type'], self::$key_identity_column_types, true)) {
				$names[] = (string) $c['name'];
			}
		}
		$names = array_values(array_unique($names));
		sort($names, SORT_STRING);
		return $names;
	}

	/**
	 * @return string[]
	 */
	public function get_key_identity_column_types()
	{
		return self::$key_identity_column_types;
	}

	/**
	 * All DSD component `name` values (for payload / CSV validation).
	 *
	 * @param array $components
	 * @return string[]
	 */
	public function get_all_component_names(array $components)
	{
		$names = [];
		foreach ($components as $c) {
			if (is_array($c) && !empty($c['name'])) {
				$names[] = (string) $c['name'];
			}
		}
		return array_values(array_unique($names));
	}

	/**
	 * Resolve a field identifier to the DSD component `name` (case-insensitive).
	 * First matching component wins if names differ only by case.
	 *
	 * @param string $name
	 * @param array $components
	 * @return string|null canonical component name, or null if unknown
	 */
	public function resolve_dsd_component_name($name, array $components)
	{
		$name = trim((string) $name);
		if ($name === '') {
			return null;
		}
		$key = strtolower($name);
		foreach ($components as $c) {
			if (!is_array($c) || empty($c['name'])) {
				continue;
			}
			$canonical = (string) $c['name'];
			if (strtolower($canonical) === $key) {
				return $canonical;
			}
		}
		return null;
	}

	/**
	 * Keep only keys that match a DSD component name (drops unknown columns).
	 *
	 * @param array $row
	 * @param array $components
	 * @return array
	 */
	public function filter_row_to_dsd_fields(array $row, array $components)
	{
		$out = [];
		foreach ($row as $k => $v) {
			$canonical = $this->resolve_dsd_component_name((string) $k, $components);
			if ($canonical !== null) {
				$out[$canonical] = $v;
			}
		}
		return $out;
	}

	/**
	 * GET parameter names never used as DSD dimension filters (SDMX-style paging + REST extras).
	 *
	 * @return string[]
	 */
	public static function observation_query_reserved_param_names()
	{
		return [
			'from', 'to', 'sort', 'sort_by', 'skip', 'offset', 'limit', 'id_format', 'api_key', 'callback', '_', '_nocache',
			'c',
			'd',
			'ensure_unique_index',
		];
	}

	/**
	 * Paging offset from query: prefers offset, then legacy skip.
	 *
	 * @param array $query e.g. $_GET / input->get()
	 */
	public static function parse_observation_list_offset(array $query)
	{
		if (isset($query['offset']) && $query['offset'] !== null && $query['offset'] !== '' && is_numeric($query['offset'])) {
			return max(0, (int) $query['offset']);
		}
		if (isset($query['skip']) && $query['skip'] !== null && $query['skip'] !== '' && is_numeric($query['skip'])) {
			return max(0, (int) $query['skip']);
		}
		return 0;
	}

	/**
	 * Map public data API / grid column key to a top-level Mongo field for sort.
	 * Unknown keys return null (caller should fall back to default time sort).
	 *
	 * @param string $apiColumn e.g. sid, period_start, reporting_year, or DSD component name
	 * @param array  $components data_structure_components rows
	 * @return string|null
	 */
	public function resolve_public_observation_list_sort_field($apiColumn, array $components)
	{
		$apiColumn = trim((string) $apiColumn);
		if ($apiColumn === '') {
			return null;
		}
		$fixed = [
			'sid'             => 'sid',
			'period_start'    => '_ts_period_start',
			'period_end'      => '_ts_period_end',
			'reporting_year'  => '_ts_year',
			'reporting_freq'  => '_ts_freq',
		];
		$lower = strtolower($apiColumn);
		foreach ($fixed as $k => $mongo) {
			if (strtolower($k) === $lower) {
				return $mongo;
			}
		}
		$resolved = $this->resolve_dsd_component_name($apiColumn, $components);
		return $resolved !== null ? $resolved : null;
	}

	/**
	 * Mongo sort document for GET …/data (public list).
	 * Adds _id tiebreaker for stable paging.
	 *
	 * @param string|null $sortByApi column key from sort_by query param (null/unknown → default time order)
	 * @param int         $dir      1 or -1
	 * @param array       $components DSD components
	 * @return array<string,int>
	 */
	public function build_public_observations_list_sort($sortByApi, $dir, array $components)
	{
		$dir = ((int) $dir) < 0 ? -1 : 1;
		$field = $sortByApi !== null && $sortByApi !== ''
			? $this->resolve_public_observation_list_sort_field((string) $sortByApi, $components)
			: null;
		if ($field === null) {
			if ($dir < 0) {
				return ['_ts_year' => -1, '_ts_period_start' => -1, '_id' => 1];
			}
			return ['_ts_year' => 1, '_ts_period_start' => 1, '_id' => 1];
		}
		return [$field => $dir, '_id' => 1];
	}

	/**
	 * Public query roles under d[role] → data_structure_components.column_type (one component each).
	 *
	 * @var array<string,string> role => column_type
	 */
	private static function observation_d_role_column_types()
	{
		return [
			'geography'           => 'geography',
			'time_period'         => 'time_period',
			'periodicity'         => 'periodicity',
			'observation_value'   => 'observation_value',
		];
	}

	/**
	 * Component `name` for the single component with the given column_type (null if none).
	 *
	 * @param array $components
	 * @param string $column_type
	 * @return string|null
	 */
	public function get_component_name_for_column_type(array $components, $column_type)
	{
		$want = (string) $column_type;
		foreach ($components as $c) {
			if (!is_array($c) || empty($c['name'])) {
				continue;
			}
			if (!empty($c['column_type']) && (string) $c['column_type'] === $want) {
				return (string) $c['name'];
			}
		}
		return null;
	}

	/**
	 * MongoDB filter for observation count/find from a query string array (e.g. $this->input->get()).
	 *
	 * - Always includes sid.
	 * - from / to constrain derived reporting year on `_ts_year` (inclusive), same role as metadata
	 *   editor chart time range when using internal year (ISO dates map to their UTC calendar year).
	 * - Semantic roles: d[geography], d[time_period], d[periodicity], d[observation_value] map to the
	 *   sole component with that column_type (equality / comma $in).
	 * - Dimensions: if "c" is present and is an array (c[COMPONENT]=value), only those entries are used
	 *   (Tables / SDMX-style). Comma-separated values → $in (OR). Otherwise legacy: any top-level key
	 *   that resolves to a DSD component (excluding reserved names).
	 * - Order: d filters applied, then c or legacy (same field: later wins).
	 *
	 * @param int   $sid
	 * @param array $components DSD components list
	 * @param array $query      Associative query parameters
	 * @return array Mongo filter
	 */
	public function build_observation_query_filter($sid, array $components, array $query)
	{
		$filter = ['sid' => (int) $sid];
		$from    = isset($query['from']) ? $query['from'] : null;
		$to      = isset($query['to']) ? $query['to'] : null;
		$fromSub = ($from !== null && $from !== '') ? $this->_parse_sub_period($from) : null;
		$toSub   = ($to   !== null && $to   !== '') ? $this->_parse_sub_period($to)   : null;

		if ($fromSub !== null || $toSub !== null) {
			// Sub-period mode: filter on _ts_period_start (datetime) for precise quarterly/monthly ranges.
			$tz = new \DateTimeZone('UTC');
			if ($fromSub !== null) {
				$startDt = new \DateTime(
					sprintf('%04d-%02d-01 00:00:00', $fromSub['year'], $fromSub['month']),
					$tz
				);
				$filter['_ts_period_start'] = array_merge(
					isset($filter['_ts_period_start']) ? $filter['_ts_period_start'] : [],
					['$gte' => new UTCDateTime($startDt->getTimestamp() * 1000)]
				);
			} elseif ($from !== null && $from !== '') {
				$y = $this->_parse_observation_query_reporting_year($from);
				if ($y !== null) {
					$startDt = new \DateTime(sprintf('%04d-01-01 00:00:00', $y), $tz);
					$filter['_ts_period_start'] = array_merge(
						isset($filter['_ts_period_start']) ? $filter['_ts_period_start'] : [],
						['$gte' => new UTCDateTime($startDt->getTimestamp() * 1000)]
					);
				}
			}
			if ($toSub !== null) {
				// Exclusive upper bound = start of first period after the to-period.
				$endMonth = $toSub['month'] + $toSub['quarter_span'];
				$endYear  = $toSub['year'];
				if ($endMonth > 12) {
					$endMonth -= 12;
					$endYear  += 1;
				}
				$endDt = new \DateTime(
					sprintf('%04d-%02d-01 00:00:00', $endYear, $endMonth),
					$tz
				);
				$filter['_ts_period_start'] = array_merge(
					isset($filter['_ts_period_start']) ? $filter['_ts_period_start'] : [],
					['$lt' => new UTCDateTime($endDt->getTimestamp() * 1000)]
				);
			} elseif ($to !== null && $to !== '') {
				$y = $this->_parse_observation_query_reporting_year($to);
				if ($y !== null) {
					// Exclusive: start of next year
					$endDt = new \DateTime(sprintf('%04d-01-01 00:00:00', $y + 1), $tz);
					$filter['_ts_period_start'] = array_merge(
						isset($filter['_ts_period_start']) ? $filter['_ts_period_start'] : [],
						['$lt' => new UTCDateTime($endDt->getTimestamp() * 1000)]
					);
				}
			}
		} else {
			// Year-only mode: filter on _ts_year integer (existing behaviour).
			if ($from !== null && $from !== '') {
				$y = $this->_parse_observation_query_reporting_year($from);
				if ($y !== null) {
					$filter['_ts_year'] = array_merge(
						isset($filter['_ts_year']) ? $filter['_ts_year'] : [],
						['$gte' => $y]
					);
				}
			}
			if ($to !== null && $to !== '') {
				$y = $this->_parse_observation_query_reporting_year($to);
				if ($y !== null) {
					$filter['_ts_year'] = array_merge(
						isset($filter['_ts_year']) ? $filter['_ts_year'] : [],
						['$lte' => $y]
					);
				}
			}
		}

		$reserved = array_flip(self::observation_query_reserved_param_names());
		if (isset($query['d']) && is_array($query['d'])) {
			$this->_append_observation_dimension_filters_from_d($filter, $components, $query['d']);
		}
		if (isset($query['c']) && is_array($query['c'])) {
			$this->_append_observation_dimension_filters_from_c($filter, $components, $query['c']);
		} else {
			$this->_append_observation_dimension_filters_legacy($filter, $components, $query, $reserved);
		}

		return $filter;
	}

	/**
	 * Drop keys not meant for public observation payloads: top-level names starting with "_",
	 * platform fields (key_hash, key_spec_rev, created_at, updated_at).
	 *
	 * @param array $doc Decoded observation document (e.g. json_decode(json_encode(bson), true))
	 * @return array
	 */
	public function strip_public_observation_fields(array $doc)
	{
		$hidden = [
			'key_hash'     => true,
			'key_spec_rev' => true,
			'created_at'   => true,
			'updated_at'   => true,
		];
		$out    = [];
		foreach ($doc as $k => $v) {
			if (!is_string($k) || $k === '') {
				$out[$k] = $v;
				continue;
			}
			if ($k[0] === '_') {
				continue;
			}
			if (isset($hidden[$k])) {
				continue;
			}
			$out[$k] = $v;
		}
		return $out;
	}

	/**
	 * UTC epoch milliseconds from a JSON-decoded Mongo date leaf (Extended JSON $date, etc.).
	 *
	 * @param mixed $val
	 * @return int|null
	 */
	public static function utc_ms_from_observation_json_date($val)
	{
		if ($val === null) {
			return null;
		}
		if ($val instanceof UTCDateTime) {
			return (int) round($val->toDateTime()->format('U.u') * 1000);
		}
		if (is_array($val) && isset($val['$date'])) {
			$d = $val['$date'];
			if (is_string($d)) {
				$ts = strtotime($d);
				return $ts !== false ? (int) ($ts * 1000) : null;
			}
			if (is_array($d) && isset($d['$numberLong'])) {
				return (int) $d['$numberLong'];
			}
			if (is_int($d) || is_float($d)) {
				return (int) $d;
			}
		}
		if (is_int($val) || is_float($val)) {
			return (int) $val;
		}
		if (is_string($val)) {
			$ts = strtotime($val);
			return $ts !== false ? (int) ($ts * 1000) : null;
		}
		return null;
	}

	/**
	 * Add public chart/timeline fields derived from internal _ts_* (caller passes raw doc before strip).
	 *
	 * Exposes `reporting_year` / `reporting_freq` from `_ts_year` / `_ts_freq` (metadata editor–aligned).
	 *
	 * @param array $raw_doc   Same row as passed to strip_public_observation_fields (may include _ts_*)
	 * @param array $public_doc Result of strip_public_observation_fields($raw_doc)
	 * @return array
	 */
	public function append_public_observation_timeseries_fields(array $raw_doc, array $public_doc)
	{
		$out              = $public_doc;
		$msStart          = self::utc_ms_from_observation_json_date(
			isset($raw_doc['_ts_period_start']) ? $raw_doc['_ts_period_start'] : null
		);
		$msEnd            = self::utc_ms_from_observation_json_date(
			isset($raw_doc['_ts_period_end']) ? $raw_doc['_ts_period_end'] : null
		);
		$out['period_start'] = $msStart !== null
			? gmdate('c', (int) floor($msStart / 1000))
			: null;
		$out['period_end']   = $msEnd !== null
			? gmdate('c', (int) floor($msEnd / 1000))
			: null;
		if (isset($raw_doc['_ts_year']) && (is_int($raw_doc['_ts_year']) || is_float($raw_doc['_ts_year']))) {
			$out['reporting_year'] = (int) $raw_doc['_ts_year'];
		} elseif (isset($raw_doc['_ts_year']) && is_string($raw_doc['_ts_year']) && ctype_digit(trim($raw_doc['_ts_year']))) {
			$out['reporting_year'] = (int) trim($raw_doc['_ts_year']);
		} else {
			$out['reporting_year'] = null;
		}
		if (isset($raw_doc['_ts_freq']) && $raw_doc['_ts_freq'] !== null && $raw_doc['_ts_freq'] !== '') {
			$out['reporting_freq'] = (string) $raw_doc['_ts_freq'];
		} else {
			$out['reporting_freq'] = null;
		}
		return $out;
	}

	/**
	 * @param mixed $rawVal string, or array of strings from repeated c[key] params
	 * @return string[] non-empty parts (OR list)
	 */
	private function _normalize_c_query_values($rawVal)
	{
		if (is_array($rawVal)) {
			$parts = [];
			foreach ($rawVal as $v) {
				$v = trim((string) $v);
				if ($v !== '') {
					$parts[] = $v;
				}
			}
			return $parts;
		}
		$s = trim((string) $rawVal);
		if ($s === '') {
			return [];
		}
		if (strpos($s, ',') !== false) {
			// Do not use array_filter() without callback: it drops string "0" (falsy).
			$parts = array_map('trim', explode(',', $s));
			$parts = array_values(array_filter($parts, function ($p) {
				return $p !== '';
			}));
			return $parts;
		}
		return [$s];
	}

	private function _append_observation_dimension_filters_from_d(array &$filter, array $components, array $dMap)
	{
		$roles = self::observation_d_role_column_types();
		foreach ($dMap as $rawKey => $rawVal) {
			$role = strtolower(trim((string) $rawKey));
			if (!isset($roles[$role])) {
				continue;
			}
			$canonical = $this->get_component_name_for_column_type($components, $roles[$role]);
			if ($canonical === null) {
				continue;
			}
			$parts = $this->_normalize_c_query_values($rawVal);
			if ($parts === []) {
				continue;
			}
			if (count($parts) === 1) {
				$filter[$canonical] = $parts[0];
			} else {
				$filter[$canonical] = ['$in' => $parts];
			}
		}
	}

	private function _append_observation_dimension_filters_from_c(array &$filter, array $components, array $cMap)
	{
		foreach ($cMap as $rawKey => $rawVal) {
			$canonical = $this->resolve_dsd_component_name((string) $rawKey, $components);
			if ($canonical === null) {
				continue;
			}
			$parts = $this->_normalize_c_query_values($rawVal);
			if ($parts === []) {
				continue;
			}
			if (count($parts) === 1) {
				$filter[$canonical] = $parts[0];
			} else {
				$filter[$canonical] = ['$in' => $parts];
			}
		}
	}

	private function _append_observation_dimension_filters_legacy(array &$filter, array $components, array $query, array $reservedFlip)
	{
		foreach ($query as $key => $val) {
			if (isset($reservedFlip[$key])) {
				continue;
			}
			if ($val === null || $val === '') {
				continue;
			}
			if (is_array($val)) {
				continue;
			}
			$canonical = $this->resolve_dsd_component_name((string) $key, $components);
			if ($canonical === null) {
				continue;
			}
			$filter[$canonical] = $val;
		}
	}

	/**
	 * Calendar year for `from` / `to` query params (Mongo `_ts_year`, inclusive).
	 *
	 * @param string $value e.g. "2010", "2010-01-01", ISO datetime
	 * @return int|null
	 */
	/**
	 * Parse a sub-period string: "2006-Q3" → ['type'=>'quarter','year'=>2006,'month'=>7]
	 *                            "2006-09" → ['type'=>'month','year'=>2006,'month'=>9]
	 * Returns null if value is not a sub-period format (e.g. plain "2006").
	 *
	 * @param string $value
	 * @return array|null
	 */
	private function _parse_sub_period($value)
	{
		$value = trim((string) $value);
		if ($value === '') {
			return null;
		}
		if (preg_match('/^(\d{4})-Q([1-4])$/i', $value, $m)) {
			$year    = (int) $m[1];
			$quarter = (int) $m[2];
			$month   = ($quarter - 1) * 3 + 1;
			return ['type' => 'quarter', 'year' => $year, 'month' => $month, 'quarter_span' => 3];
		}
		if (preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
			$year  = (int) $m[1];
			$month = (int) $m[2];
			if ($month >= 1 && $month <= 12) {
				return ['type' => 'month', 'year' => $year, 'month' => $month, 'quarter_span' => 1];
			}
		}
		return null;
	}

	private function _parse_observation_query_reporting_year($value)
	{
		$value = trim((string) $value);
		if ($value === '') {
			return null;
		}
		if (preg_match('/^(\d{4})$/', $value, $m)) {
			$y = (int) $m[1];
			return ($y >= 1 && $y <= 9999) ? $y : null;
		}
		$dt = date_create_immutable($value, new DateTimeZone('UTC'));
		if ($dt === false) {
			return null;
		}
		return (int) $dt->format('Y');
	}

	// -------------------------------------------------------------------------
	// Phase A — key_hash & normalization
	// -------------------------------------------------------------------------

	/**
	 * @param mixed $value
	 * @return string|int|float|bool|null JSON-serializable normalized
	 */
	public function normalize_scalar_for_hash($value)
	{
		if ($value === null) {
			return '';
		}
		if (is_bool($value)) {
			return $value ? 1 : 0;
		}
		if (is_int($value) || is_float($value)) {
			return $value;
		}
		if (is_string($value)) {
			return trim($value);
		}
		return (string) json_encode($value);
	}

	/**
	 * Canonical JSON for hashing.
	 *
	 * @param int    $sid
	 * @param int    $dsd_id
	 * @param string $key_spec_rev
	 * @param array  $field_values component name => observation value (must include all key identity fields)
	 * @param array  $components
	 */
	public function build_key_hash_material($sid, $dsd_id, $key_spec_rev, array $field_values, array $components)
	{
		$names = $this->get_key_identity_component_names($components);
		$kv    = [];
		foreach ($names as $n) {
			$kv[$n] = $this->normalize_scalar_for_hash(isset($field_values[$n]) ? $field_values[$n] : null);
		}
		ksort($kv, SORT_STRING);
		$payload = [
			'sid'    => (int) $sid,
			'dsd_id' => (int) $dsd_id,
			'rev'    => (string) $key_spec_rev,
			'kv'     => $kv,
		];
		return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function compute_key_hash($material_string)
	{
		$digest = hash('sha256', (string) $material_string, false);
		return $this->key_hash_prefix . $digest;
	}

	/**
	 * @param int    $sid
	 * @param int    $dsd_id
	 * @param string $key_spec_rev
	 * @param array  $field_values
	 * @param array  $components
	 */
	public function compute_observation_key_hash($sid, $dsd_id, $key_spec_rev, array $field_values, array $components)
	{
		$material = $this->build_key_hash_material($sid, $dsd_id, $key_spec_rev, $field_values, $components);
		return $this->compute_key_hash($material);
	}

	// -------------------------------------------------------------------------
	// Phase A — derived time fields
	// -------------------------------------------------------------------------

	/**
	 * Parse reporting period string into `_ts_*` fields (UTC, half-open end).
	 *
	 * @param string $raw_period
	 * @param string|null $time_period_format optional hint from DSD component
	 * @return array<string,mixed>
	 */
	public function derive_ts_fields($raw_period, $time_period_format = null)
	{
		$raw = trim((string) $raw_period);
		$fmt = $time_period_format !== null ? trim((string) $time_period_format) : '';

		$out = [
			'_ts_year'         => null,
			'_ts_freq'         => 'U',
			'_ts_subperiod'    => null,
			'_ts_period_start' => null,
			'_ts_period_end'   => null,
		];

		if ($raw === '') {
			return $out;
		}

		$tzUtc = new DateTimeZone('UTC');

		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
			$y = (int) $m[1];
			$mo = (int) $m[2];
			$d = (int) $m[3];
			$start = new DateTimeImmutable(sprintf('%04d-%02d-%02d 00:00:00', $y, $mo, $d), $tzUtc);
			$end   = $start->modify('+1 day');
			$out['_ts_year']         = $y;
			$out['_ts_freq']         = 'D';
			$out['_ts_subperiod']    = (int) $start->format('z') + 1;
			$out['_ts_period_start'] = new UTCDateTime($start->getTimestamp() * 1000);
			$out['_ts_period_end']   = new UTCDateTime($end->getTimestamp() * 1000);
			return $out;
		}

		if (preg_match('/^(\d{4})-(\d{2})$/', $raw, $m)) {
			$y  = (int) $m[1];
			$mo = (int) $m[2];
			$start = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $y, $mo), $tzUtc);
			$end   = $start->modify('first day of next month');
			$out['_ts_year']         = $y;
			$out['_ts_freq']         = 'M';
			$out['_ts_subperiod']    = $mo;
			$out['_ts_period_start'] = new UTCDateTime($start->getTimestamp() * 1000);
			$out['_ts_period_end']   = new UTCDateTime($end->getTimestamp() * 1000);
			return $out;
		}

		if (preg_match('/^(\d{4})-Q([1-4])$/i', $raw, $m)) {
			$y = (int) $m[1];
			$q = (int) $m[2];
			$startMonth = ($q - 1) * 3 + 1;
			$start = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $y, $startMonth), $tzUtc);
			$end   = $start->modify('+3 months');
			$out['_ts_year']         = $y;
			$out['_ts_freq']         = 'Q';
			$out['_ts_subperiod']    = $q;
			$out['_ts_period_start'] = new UTCDateTime($start->getTimestamp() * 1000);
			$out['_ts_period_end']   = new UTCDateTime($end->getTimestamp() * 1000);
			return $out;
		}

		if (preg_match('/^(\d{4})$/', $raw, $m)) {
			$y = (int) $m[1];
			$start = new DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $y), $tzUtc);
			$end   = $start->modify('+1 year');
			$out['_ts_year']         = $y;
			$out['_ts_freq']         = 'A';
			$out['_ts_subperiod']    = null;
			$out['_ts_period_start'] = new UTCDateTime($start->getTimestamp() * 1000);
			$out['_ts_period_end']   = new UTCDateTime($end->getTimestamp() * 1000);
			return $out;
		}

		if ($fmt !== '' && stripos($fmt, 'YYYY') !== false && stripos($fmt, 'MM') !== false && stripos($fmt, 'DD') === false) {
			$parsed = DateTimeImmutable::createFromFormat('!Y-m', $raw, $tzUtc);
			if ($parsed instanceof DateTimeImmutable) {
				$start = $parsed;
				$end   = $start->modify('first day of next month');
				$out['_ts_year']         = (int) $start->format('Y');
				$out['_ts_freq']         = 'M';
				$out['_ts_subperiod']    = (int) $start->format('n');
				$out['_ts_period_start'] = new UTCDateTime($start->getTimestamp() * 1000);
				$out['_ts_period_end']   = new UTCDateTime($end->getTimestamp() * 1000);
				return $out;
			}
		}

		return $out;
	}

	// -------------------------------------------------------------------------
	// Build full observation document
	// -------------------------------------------------------------------------

	/**
	 * Merge system fields, key_hash, and _ts_* into one document for insert.
	 *
	 * @param int $sid
	 * @param string|null $idno
	 * @param int    $dsd_id
	 * @param array  $field_values DSD field name => value (dimensions, measures, attributes, etc.)
	 * @param array  $components
	 * @param array  $structure_row
	 * @return array
	 */
	public function build_observation_document($sid, $idno, $dsd_id, array $field_values, array $components, array $structure_row)
	{
		$key_spec_rev = $this->timeseries_dsd()->build_key_spec_revision($structure_row);
		$key_hash     = $this->compute_observation_key_hash($sid, $dsd_id, $key_spec_rev, $field_values, $components);

		$timeComp = $this->get_time_period_component($components);
		$rawTime  = '';
		if ($timeComp !== null) {
			$tname   = (string) $timeComp['name'];
			$rawTime = isset($field_values[$tname]) ? (string) $field_values[$tname] : '';
		}
		$tpFormat = $timeComp && !empty($timeComp['time_period_format'])
			? (string) $timeComp['time_period_format']
			: null;
		$tsFields = $this->derive_ts_fields($rawTime, $tpFormat);

		$doc = [];
		foreach ($field_values as $k => $v) {
			if ($this->is_reserved_document_key($k)) {
				continue;
			}
			$doc[$k] = $v;
		}

		$doc['sid']          = (int) $sid;
		$doc['dsd_id']       = (int) $dsd_id;
		$doc['key_spec_rev'] = $key_spec_rev;
		$doc['key_hash']     = $key_hash;
		if ($idno !== null && $idno !== '') {
			$doc['idno'] = (string) $idno;
		}
		$doc['_ts_year']         = $tsFields['_ts_year'];
		$doc['_ts_freq']         = $tsFields['_ts_freq'];
		$doc['_ts_subperiod']    = $tsFields['_ts_subperiod'];
		$doc['_ts_period_start'] = $tsFields['_ts_period_start'];
		$doc['_ts_period_end']   = $tsFields['_ts_period_end'];

		return $doc;
	}

	public function is_reserved_document_key($key)
	{
		$k = (string) $key;
		return isset(self::$reserved_document_keys[$k]);
	}

	/**
	 * Strip reserved keys from an associative array (e.g. rehash input).
	 */
	public function extract_dsd_field_values_from_document(array $doc)
	{
		$out = [];
		foreach ($doc as $k => $v) {
			if ($this->is_reserved_document_key($k)) {
				continue;
			}
			$out[$k] = $v;
		}
		return $out;
	}

	// -------------------------------------------------------------------------
	// Phase B — indexes
	// -------------------------------------------------------------------------

	/**
	 * @param int $dsd_id
	 * @param bool $include_unique_key_hash If false, only non-unique indexes (e.g. import-before-hash workflow).
	 */
	public function ensure_indexes($dsd_id, $include_unique_key_hash = true)
	{
		$coll = $this->get_collection($dsd_id);
		if ($include_unique_key_hash) {
			try {
				$coll->createIndex(
					['sid' => 1, 'key_hash' => 1],
					['unique' => true, 'name' => 'uniq_sid_key_hash']
				);
			} catch (Exception $e) {
				if (stripos($e->getMessage(), 'already exists') === false) {
					throw $e;
				}
			}
		}
		try {
			$coll->createIndex(
				['sid' => 1, '_ts_period_start' => 1],
				['name' => 'idx_sid_ts_period_start']
			);
		} catch (Exception $e) {
			if (stripos($e->getMessage(), 'already exists') === false) {
				throw $e;
			}
		}
	}

	// -------------------------------------------------------------------------
	// Phase B — writes & reads
	// -------------------------------------------------------------------------

	/**
	 * @param int   $dsd_id
	 * @param array $documents list of observation documents (arrays)
	 * @return int inserted count (may run several insertMany calls, each at most indicator_timeseries_max_bulk_insert)
	 */
	public function insert_observations_batch($dsd_id, array $documents, $ordered = false)
	{
		if (empty($documents)) {
			return 0;
		}
		$max = (int) $this->config->item('indicator_timeseries_max_bulk_insert');
		if ($max < 1) {
			$max = 5000;
		}
		$coll  = $this->get_collection($dsd_id);
		$total = 0;
		$opts  = ['ordered' => (bool) $ordered];
		foreach (array_chunk($documents, $max) as $chunk) {
			try {
				$result = $coll->insertMany($chunk, $opts);
				$total += (int) $result->getInsertedCount();
			} catch (MongoDB\Driver\Exception\BulkWriteException $e) {
				throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
			}
		}
		return $total;
	}

	/**
	 * @param int   $dsd_id
	 * @param array $filter e.g. ['sid' => 123]
	 * @param array $options limit, skip, sort
	 * @return array<int,array>
	 */
	public function find_observations($dsd_id, array $filter, array $options = [])
	{
		$coll = $this->get_collection($dsd_id);
		$cursor = $coll->find($filter, $options);
		return $cursor->toArray();
	}

	public function count_observations($dsd_id, array $filter)
	{
		return (int) $this->get_collection($dsd_id)->countDocuments($filter);
	}

	/**
	 * Delete all observations for a study within a DSD collection.
	 *
	 * @param int $dsd_id
	 * @param int $sid
	 * @return int deleted count
	 */
	public function delete_observations_for_sid($dsd_id, $sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return 0;
		}
		$result = $this->get_collection($dsd_id)->deleteMany([
			'$or' => [
				['sid' => $sid],
				['sid' => (string) $sid],
			],
		]);
		return (int) $result->getDeletedCount();
	}

	/**
	 * Count Mongo observation documents for a study across all indicator_ts_* collections.
	 *
	 * @param int $sid surveys.id
	 * @return int
	 */
	public function count_observations_for_sid_all_indicator_collections($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return 0;
		}

		try {
			$db = $this->get_db_connection()->{$this->get_mongo_db_name()};
		} catch (Throwable $e) {
			log_message('error', 'Timeseries_mongo_model::count_observations_for_sid_all_indicator_collections: ' . $e->getMessage());

			return 0;
		}

		$prefix  = strtolower((string) $this->collection_prefix);
		$pattern = '/^' . preg_quote($prefix, '/') . '\d+$/';
		$flt     = [
			'$or' => [
				['sid' => $sid],
				['sid' => (string) $sid],
			],
		];

		$total = 0;
		foreach ($db->listCollectionNames() as $collName) {
			$collName = (string) $collName;
			if (!preg_match($pattern, $collName)) {
				continue;
			}
			try {
				$total += (int) $db->{$collName}->countDocuments($flt);
			} catch (Throwable $e) {
				log_message('error', 'Timeseries_mongo_model: countDocuments failed on ' . $collName . ': ' . $e->getMessage());
			}
		}

		return $total;
	}

	/**
	 * Recompute {@see count_observations_for_sid_all_indicator_collections()} and persist on surveys.ts_data_count.
	 *
	 * @param int $sid surveys.id
	 * @return int Count written (same as live Mongo total)
	 */
	public function refresh_ts_data_count_for_sid($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return 0;
		}

		$count = $this->count_observations_for_sid_all_indicator_collections($sid);

		if (!$this->db->field_exists('ts_data_count', 'surveys')) {
			return $count;
		}

		$row = $this->db->select('id')->get_where('surveys', ['id' => $sid], 1)->row_array();
		if (!$row) {
			return $count;
		}

		$this->db->where('id', $sid);
		$this->db->update('surveys', ['ts_data_count' => $count]);

		return $count;
	}

	/**
	 * Delete indicator observations for a study from every Mongo collection whose name matches
	 * the configured prefix + numeric DSD id (e.g. indicator_ts_42).
	 *
	 * Used when a study is removed from SQL so Mongo does not retain orphaned rows. Also covers
	 * edge cases where observations existed under a different DSD collection than the survey's
	 * current data_structure_id.
	 *
	 * @param int $sid surveys.id
	 * @return int Total documents deleted across all matching collections
	 */
	public function delete_observations_for_sid_all_indicator_collections($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return 0;
		}

		try {
			$db = $this->get_db_connection()->{$this->get_mongo_db_name()};
		} catch (Throwable $e) {
			log_message('error', 'Timeseries_mongo_model::delete_observations_for_sid_all_indicator_collections: ' . $e->getMessage());
			return 0;
		}

		$prefix  = strtolower((string) $this->collection_prefix);
		$pattern = '/^' . preg_quote($prefix, '/') . '\d+$/';

		$total = 0;
		foreach ($db->listCollectionNames() as $collName) {
			$collName = (string) $collName;
			if (!preg_match($pattern, $collName)) {
				continue;
			}
			try {
				$result = $db->{$collName}->deleteMany([
					'$or' => [
						['sid' => $sid],
						['sid' => (string) $sid],
					],
				]);
				$total += (int) $result->getDeletedCount();
			} catch (Throwable $e) {
				log_message('error', 'Timeseries_mongo_model: deleteMany failed on ' . $collName . ': ' . $e->getMessage());
			}
		}

		$this->refresh_ts_data_count_for_sid($sid);

		return $total;
	}

	/**
	 * Find observation documents whose sid does not exist in surveys (MySQL).
	 *
	 * Scans all indicator_ts_* collections; may be slow on large deployments.
	 *
	 * @return array{ok:bool,error?:string,collections_scanned:int,orphans:array<int,array{collection:string,sid:int,documents:int}>,total_orphan_documents:int}
	 */
	public function scan_orphan_indicator_observations()
	{
		$out = [
			'ok'                    => true,
			'collections_scanned'   => 0,
			'orphans'               => [],
			'total_orphan_documents'=> 0,
		];

		try {
			$db = $this->get_db_connection()->{$this->get_mongo_db_name()};
		} catch (Throwable $e) {
			$out['ok']    = false;
			$out['error'] = $e->getMessage();
			return $out;
		}

		$prefix  = strtolower((string) $this->collection_prefix);
		$pattern = '/^' . preg_quote($prefix, '/') . '\d+$/';

		foreach ($db->listCollectionNames() as $collName) {
			$collName = (string) $collName;
			if (!preg_match($pattern, $collName)) {
				continue;
			}

			$out['collections_scanned']++;
			$coll = $db->{$collName};

			try {
				$rawSids = $coll->distinct('sid');
			} catch (Throwable $e) {
				log_message('error', 'Timeseries_mongo_model::scan_orphan_indicator_observations distinct failed on ' . $collName . ': ' . $e->getMessage());
				continue;
			}

			$sidSet = [];
			foreach ($rawSids as $raw) {
				$sid = self::coerce_positive_sid($raw);
				if ($sid !== null) {
					$sidSet[$sid] = true;
				}
			}

			$sidList = array_keys($sidSet);
			sort($sidList, SORT_NUMERIC);

			foreach (array_chunk($sidList, 500) as $chunk) {
				if ($chunk === []) {
					continue;
				}

				$q = $this->db->select('id')->from('surveys')->where_in('id', $chunk)->get();
				$existing = [];
				if ($q) {
					foreach ($q->result_array() as $row) {
						$existing[(int) $row['id']] = true;
					}
				}

				foreach ($chunk as $sid) {
					if (isset($existing[$sid])) {
						continue;
					}
					$n = (int) $coll->countDocuments([
						'$or' => [
							['sid' => $sid],
							['sid' => (string) $sid],
						],
					]);
					if ($n > 0) {
						$out['orphans'][] = [
							'collection' => $collName,
							'sid'        => $sid,
							'documents'  => $n,
						];
						$out['total_orphan_documents'] += $n;
					}
				}
			}
		}

		return $out;
	}

	/**
	 * Delete observation documents reported by {@see scan_orphan_indicator_observations()}.
	 *
	 * @return array{ok:bool,error?:string,deleted:int,groups:int}
	 */
	public function purge_orphan_indicator_observations()
	{
		$scan = $this->scan_orphan_indicator_observations();
		if (!$scan['ok']) {
			return [
				'ok'    => false,
				'error' => isset($scan['error']) ? (string) $scan['error'] : 'scan failed',
				'deleted' => 0,
				'groups'  => 0,
			];
		}

		try {
			$db = $this->get_db_connection()->{$this->get_mongo_db_name()};
		} catch (Throwable $e) {
			return [
				'ok'    => false,
				'error' => $e->getMessage(),
				'deleted' => 0,
				'groups'  => 0,
			];
		}

		$deleted = 0;
		foreach ($scan['orphans'] as $row) {
			$name = isset($row['collection']) ? (string) $row['collection'] : '';
			$sid  = isset($row['sid']) ? (int) $row['sid'] : 0;
			if ($name === '' || $sid <= 0) {
				continue;
			}
			try {
				$r = $db->{$name}->deleteMany([
					'$or' => [
						['sid' => $sid],
						['sid' => (string) $sid],
					],
				]);
				$deleted += (int) $r->getDeletedCount();
			} catch (Throwable $e) {
				log_message('error', 'Timeseries_mongo_model::purge_orphan_indicator_observations failed on ' . $name . ': ' . $e->getMessage());
			}
		}

		return [
			'ok'      => true,
			'deleted' => $deleted,
			'groups'  => count($scan['orphans']),
		];
	}

	/**
	 * @param mixed $raw distinct() value for sid
	 * @return int|null Positive surveys.id, or null if unusable
	 */
	private static function coerce_positive_sid($raw)
	{
		if ($raw === null || $raw === '') {
			return null;
		}
		if (is_int($raw)) {
			return $raw > 0 ? $raw : null;
		}
		if (is_float($raw)) {
			$i = (int) $raw;

			return ($i > 0 && (float) $i === $raw) ? $i : null;
		}
		if (is_string($raw) && ctype_digit($raw)) {
			$i = (int) $raw;

			return $i > 0 ? $i : null;
		}

		return null;
	}

	/**
	 * Min/max calendar year for a study (catalog UI year slider before chart loads).
	 *
	 * Prefer `_ts_year` when set; otherwise UTC calendar year from `_ts_period_start`;
	 * otherwise a leading 4-digit year from the DSD time_period field (same spirit as
	 * {@see self::catalog_chart_time_key()}).
	 *
	 * @param int        $dsd_id
	 * @param int        $sid
	 * @param array|null $components data_structure_components rows (optional; improves coverage)
	 * @return array{min: int|null, max: int|null}
	 */
	public function reporting_year_min_max_for_study($dsd_id, $sid, ?array $components = null)
	{
		$dsd_id = (int) $dsd_id;
		$sid    = (int) $sid;
		if ($dsd_id <= 0 || $sid <= 0) {
			return array('min' => null, 'max' => null);
		}
		$tpName = null;
		if (is_array($components)) {
			$tp = $this->get_component_name_for_column_type($components, 'time_period');
			if ($tp !== null && $tp !== '') {
				$tp = (string) $tp;
				if (preg_match('/^[A-Za-z0-9_]+$/', $tp)) {
					$tpName = $tp;
				}
			}
		}
		try {
			$coll = $this->get_collection($dsd_id);
		} catch (Throwable $e) {
			return array('min' => null, 'max' => null);
		}

		$fromTp = null;
		if ($tpName !== null) {
			$tpPath = '$' . $tpName;
			$fromTp = array(
				'$cond' => array(
					'if' => array(
						'$regexMatch' => array(
							'input' => array('$toString' => array('$ifNull' => array($tpPath, ''))),
							'regex'   => '^\\d{4}\\b',
						),
					),
					'then' => array(
						'$convert' => array(
							'input' => array(
								'$substrCP' => array(
									array('$toString' => array('$ifNull' => array($tpPath, ''))),
									0,
									4,
								),
							),
							'to'      => 'int',
							'onError' => 0,
							'onNull'  => 0,
						),
					),
					'else' => null,
				),
			);
		}

		$tsYearInt = array(
			'$convert' => array(
				'input'   => '$_ts_year',
				'to'      => 'int',
				'onError' => 0,
				'onNull'  => 0,
			),
		);
		$__yr = array(
			'$switch' => array(
				'branches' => array(
					array(
						'case' => array('$gt' => array($tsYearInt, 0)),
						'then' => $tsYearInt,
					),
					array(
						'case' => array(
							'$eq' => array(array('$type' => '$_ts_period_start'), 'date'),
						),
						'then' => array(
							'$year' => array(
								'date'     => '$_ts_period_start',
								'timezone' => 'UTC',
							),
						),
					),
				),
				'default' => $fromTp !== null ? $fromTp : null,
			),
		);

		$matchSid = array(
			'$match' => array(
				'$or' => array(
					array('sid' => $sid),
					array('sid' => (string) $sid),
				),
			),
		);
		$groupMinMaxYr = array(
			'$group' => array(
				'_id'  => null,
				'yMin' => array('$min' => '$__catalog_yr'),
				'yMax' => array('$max' => '$__catalog_yr'),
			),
		);
		// Prefer coerced `_ts_year` first: raw `$min`/`$max` on `_ts_year` is null if any matched doc has null
		// `_ts_year` (BSON null sorts first). Excluding invalid years before `$group` avoids that.
		$pipelineTsYear = array(
			$matchSid,
			array(
				'$addFields' => array(
					'__ts_y' => array(
						'$convert' => array(
							'input'   => '$_ts_year',
							'to'      => 'long',
							'onError' => 0,
							'onNull'  => 0,
						),
					),
				),
			),
			array('$match' => array('__ts_y' => array('$gt' => 0, '$lte' => 9999))),
			array(
				'$group' => array(
					'_id'  => null,
					'yMin' => array('$min' => '$__ts_y'),
					'yMax' => array('$max' => '$__ts_y'),
				),
			),
		);
		$out = $this->reporting_year_min_max_aggregate_result($coll, $pipelineTsYear);
		if ($out['min'] !== null) {
			return $out;
		}
		$pipelinePrimary = array(
			$matchSid,
			array('$addFields' => array('__catalog_yr' => $__yr)),
			array(
				'$match' => array(
					'__catalog_yr' => array(
						'$gte' => 1,
						'$lte' => 9999,
					),
				),
			),
			$groupMinMaxYr,
		);
		$out = $this->reporting_year_min_max_aggregate_result($coll, $pipelinePrimary);
		if ($out['min'] !== null) {
			return $out;
		}
		// Fallback: DSD time period field (e.g. YEAR) as integer year — no $regexMatch (older Mongo).
		if ($tpName !== null) {
			$tpPath = '$' . $tpName;
			$pipelineTpField = array(
				$matchSid,
				array(
					'$addFields' => array(
						'__tp_y' => array(
							'$convert' => array(
								'input'   => $tpPath,
								'to'      => 'int',
								'onError' => 0,
								'onNull'  => 0,
							),
						),
					),
				),
				array('$match' => array('__tp_y' => array('$gte' => 1, '$lte' => 9999))),
				array(
					'$group' => array(
						'_id'  => null,
						'yMin' => array('$min' => '$__tp_y'),
						'yMax' => array('$max' => '$__tp_y'),
					),
				),
			);
			$out = $this->reporting_year_min_max_aggregate_result($coll, $pipelineTpField);
			if ($out['min'] !== null) {
				return $out;
			}
		}
		// Fallback: UTC calendar year from _ts_period_start when BSON date.
		$pipelinePeriod = array(
			$matchSid,
			array(
				'$match' => array(
					'_ts_period_start' => array('$type' => 'date'),
				),
			),
			array(
				'$addFields' => array(
					'__catalog_yr' => array(
						'$year' => array(
							'date'     => '$_ts_period_start',
							'timezone' => 'UTC',
						),
					),
				),
			),
			array(
				'$match' => array(
					'__catalog_yr' => array(
						'$gte' => 1,
						'$lte' => 9999,
					),
				),
			),
			$groupMinMaxYr,
		);
		$out = $this->reporting_year_min_max_aggregate_result($coll, $pipelinePeriod);
		if ($out['min'] !== null) {
			return $out;
		}
		return $this->reporting_year_min_max_scan_ts_year($coll, $sid, $tpName);
	}

	/**
	 * Last-resort min/max when aggregation operators are unavailable or return empty.
	 * Scans observations for sid (bounded) and reads `_ts_year` or DSD time period field in PHP.
	 *
	 * @param Collection    $coll
	 * @param int           $sid
	 * @param string|null   $tpName safe DSD time period column name
	 * @return array{min: int|null, max: int|null}
	 */
	private function reporting_year_min_max_scan_ts_year(Collection $coll, $sid, $tpName)
	{
		$sid = (int) $sid;
		$filter = array(
			'$or' => array(
				array('sid' => $sid),
				array('sid' => (string) $sid),
			),
		);
		$mn = null;
		$mx = null;
		$maxScan = 250000;
		$n     = 0;
		try {
			$cursor = $coll->find(
				$filter,
				array(
					'projection' => array_merge(
						array('_ts_year' => 1),
						$tpName !== null && $tpName !== '' ? array($tpName => 1) : array()
					),
					'noCursorTimeout' => false,
				)
			);
		} catch (Throwable $e) {
			return array('min' => null, 'max' => null);
		}
		foreach ($cursor as $doc) {
			if (++$n > $maxScan) {
				break;
			}
			if (!is_array($doc)) {
				continue;
			}
			$y = null;
			if (isset($doc['_ts_year'])) {
				$y = $this->reporting_year_scalar_to_int($doc['_ts_year']);
			}
			if (($y === null || $y <= 0) && $tpName !== null && $tpName !== '' && isset($doc[$tpName])) {
				$y = $this->reporting_year_scalar_to_int($doc[$tpName]);
			}
			if ($y === null || $y < 1 || $y > 9999) {
				continue;
			}
			if ($mn === null || $y < $mn) {
				$mn = $y;
			}
			if ($mx === null || $y > $mx) {
				$mx = $y;
			}
		}
		if ($mn === null || $mx === null) {
			return array('min' => null, 'max' => null);
		}
		return array('min' => $mn, 'max' => $mx);
	}

	/**
	 * @param mixed $v BSON / scalar from Mongo
	 * @return int|null calendar year
	 */
	private function reporting_year_scalar_to_int($v)
	{
		if ($v === null) {
			return null;
		}
		if ($v instanceof \MongoDB\BSON\Int64 || $v instanceof \MongoDB\BSON\Int32) {
			$y = (int) (string) $v;
			return $y > 0 ? $y : null;
		}
		if (is_int($v) || is_float($v)) {
			$y = (int) $v;
			return $y > 0 ? $y : null;
		}
		if (is_string($v)) {
			$s = trim($v);
			if ($s !== '' && preg_match('/^(\d{4})\b/', $s, $m)) {
				$y = (int) $m[1];
				return $y > 0 ? $y : null;
			}
		}
		return null;
	}

	/**
	 * @param Collection $coll
	 * @param array      $pipeline
	 * @return array{min: int|null, max: int|null}
	 */
	private function reporting_year_min_max_aggregate_result(Collection $coll, array $pipeline)
	{
		try {
			$cursor = $coll->aggregate(
				$pipeline,
				array(
					'allowDiskUse' => true,
					// Default driver typeMap returns BSONDocument; coerce so $row['yMin'] just works.
					'typeMap' => array(
						'root'     => 'array',
						'document' => 'array',
						'array'    => 'array',
					),
				)
			);
			$rows = $cursor->toArray();
		} catch (Throwable $e) {
			return array('min' => null, 'max' => null);
		}
		if (!is_array($rows) || !isset($rows[0])) {
			return array('min' => null, 'max' => null);
		}
		$row = $rows[0];
		if (!is_array($row) && !($row instanceof \ArrayAccess)) {
			return array('min' => null, 'max' => null);
		}
		$mn = isset($row['yMin']) ? $row['yMin'] : null;
		$mx = isset($row['yMax']) ? $row['yMax'] : null;
		if ($mn === null || $mx === null) {
			return array('min' => null, 'max' => null);
		}
		$mn = (int) $mn;
		$mx = (int) $mx;
		if ($mn <= 0 || $mx <= 0 || $mn > $mx) {
			return array('min' => null, 'max' => null);
		}
		return array('min' => $mn, 'max' => $mx);
	}

	/**
	 * Public chart x-axis key for dedupe and labels.
	 *
	 * Prefer the DSD component with column_type `time_period` (e.g. monthly `YYYY-MM`);
	 * otherwise fall back to `reporting_year`, then `period_start`.
	 *
	 * @param array       $row
	 * @param string|null $tpName resolved component name for `time_period`
	 * @return string|null
	 */
	private function catalog_chart_time_key(array $row, $tpName)
	{
		if ($tpName !== null && isset($row[$tpName]) && $row[$tpName] !== '' && $row[$tpName] !== null) {
			return trim((string) $row[$tpName]);
		}
		if (isset($row['reporting_year']) && $row['reporting_year'] !== null && $row['reporting_year'] !== '') {
			$y = (int) $row['reporting_year'];
			if ($y > 0) {
				return (string) $y;
			}
		}
		if (!empty($row['period_start'])) {
			return (string) $row['period_start'];
		}
		return null;
	}

	/**
	 * Build catalog chart rows from public observation payloads (same slice identity as Metadata Editor chart records).
	 *
	 * Each output row: time_period (DSD `time_period` component when present, else reporting year, else period_start), observation_value, series_key and series-dimension fields.
	 * series_key = "GEO=… | DIM=…" segments (geography + dimension column_types only), deduped by (series_key, time_period) last wins.
	 *
	 * @param array $public_observations List of assoc arrays (post strip + append_public_observation_timeseries_fields)
	 * @param array $components          data_structure_components rows
	 * @return array{records: array<int, array<string, mixed>>, time_bounds: array{min: ?string, max: ?string}}
	 */
	public function build_catalog_chart_records(array $public_observations, array $components)
	{
		$geoName = $this->get_component_name_for_column_type($components, 'geography');
		$tpName  = $this->get_component_name_for_column_type($components, 'time_period');
		$ovName  = $this->get_component_name_for_column_type($components, 'observation_value');

		$dimension_names = [];
		foreach ($components as $c) {
			if (!is_array($c) || empty($c['name'])) {
				continue;
			}
			if (empty($c['column_type']) || (string) $c['column_type'] !== 'dimension') {
				continue;
			}
			$n = (string) $c['name'];
			if ($ovName !== null && $n === $ovName) {
				continue;
			}
			if ($tpName !== null && $n === $tpName) {
				continue;
			}
			if ($geoName !== null && $n === $geoName) {
				continue;
			}
			$dimension_names[] = $n;
		}

		$dedupe = [];
		foreach ($public_observations as $row) {
			if (!is_array($row)) {
				continue;
			}
			$series_parts = [];
			$series_fields = [];
			if ($geoName !== null && isset($row[$geoName]) && $row[$geoName] !== '' && $row[$geoName] !== null) {
				$geoVal = (string) $row[$geoName];
				$series_parts[] = $geoName . '=' . $geoVal;
				$series_fields[$geoName] = $geoVal;
			}
			foreach ($dimension_names as $dn) {
				if (isset($row[$dn]) && $row[$dn] !== '' && $row[$dn] !== null) {
					$dnVal = (string) $row[$dn];
					$series_parts[] = $dn . '=' . $dnVal;
					$series_fields[$dn] = $dnVal;
				}
			}
			$series_key = count($series_parts) > 0 ? implode(' | ', $series_parts) : 'Series';

			$time_period = $this->catalog_chart_time_key($row, $tpName);
			if ($time_period === null || $time_period === '') {
				continue;
			}

			if ($ovName === null || !array_key_exists($ovName, $row)) {
				continue;
			}
			$ov = $row[$ovName];
			if ($ov === null || $ov === '') {
				continue;
			}
			if (!is_numeric($ov)) {
				continue;
			}
			$val = (float) $ov;

			$k                    = $series_key . "\x00" . $time_period;
			$dedupe[$k]           = array(
				'time_period'         => $time_period,
				'observation_value'   => $val,
				'series_key'          => $series_key,
			);
			foreach ($series_fields as $fieldName => $fieldValue) {
				$dedupe[$k][$fieldName] = $fieldValue;
			}
		}

		$records = array_values($dedupe);
		usort(
			$records,
			function ($a, $b) {
				$c = strcmp((string) $a['time_period'], (string) $b['time_period']);
				if ($c !== 0) {
					return $c;
				}
				return strcmp((string) $a['series_key'], (string) $b['series_key']);
			}
		);

		$minT = null;
		$maxT = null;
		foreach ($records as $r) {
			if (!is_array($r) || !isset($r['time_period'])) {
				continue;
			}
			$t = (string) $r['time_period'];
			if ($t === '') {
				continue;
			}
			if ($minT === null || strcmp($t, $minT) < 0) {
				$minT = $t;
			}
			if ($maxT === null || strcmp($t, $maxT) > 0) {
				$maxT = $t;
			}
		}

		return array(
			'records'     => $records,
			'time_bounds' => array(
				'min' => $minT,
				'max' => $maxT,
			),
		);
	}

	// -------------------------------------------------------------------------
	// Phase B — rehash & validation helpers
	// -------------------------------------------------------------------------

	/**
	 * Recompute key_hash, key_spec_rev, and _ts_* for documents matching $filter.
	 *
	 * @param int   $dsd_id
	 * @param array $mongo_filter
	 * @param int|null $limit max documents to update (null = all)
	 * @return int number of documents updated
	 */
	public function rehash_documents($dsd_id, array $mongo_filter = [], $limit = null)
	{
		$bundle = $this->timeseries_dsd()->load_dsd_bundle($dsd_id);
		if ($bundle === null) {
			throw new InvalidArgumentException('Unknown data structure id: ' . $dsd_id);
		}
		$structure    = $bundle['structure'];
		$components   = $bundle['components'];
		$key_spec_rev = $this->timeseries_dsd()->build_key_spec_revision($structure);

		$coll   = $this->get_collection($dsd_id);
		$opts   = [];
		if ($limit !== null) {
			$opts['limit'] = (int) $limit;
		}
		$cursor = $coll->find($mongo_filter, $opts);
		$updated = 0;
		$batch = (int) $this->config->item('indicator_timeseries_bulk_batch_size');
		if ($batch < 1) {
			$batch = 500;
		}

		$bulk = [];
		foreach ($cursor as $doc) {
			if (empty($doc['sid'])) {
				continue;
			}
			$sid = (int) $doc['sid'];
			$arr    = json_decode(json_encode($doc), true);
			$fields = $this->extract_dsd_field_values_from_document(is_array($arr) ? $arr : []);
			$key_hash = $this->compute_observation_key_hash($sid, (int) $dsd_id, $key_spec_rev, $fields, $components);

			$timeComp = $this->get_time_period_component($components);
			$rawTime  = '';
			if ($timeComp !== null) {
				$tname = (string) $timeComp['name'];
				$rawTime = isset($fields[$tname]) ? (string) $fields[$tname] : '';
			}
			$tpFormat = $timeComp && !empty($timeComp['time_period_format'])
				? (string) $timeComp['time_period_format']
				: null;
			$tsFields = $this->derive_ts_fields($rawTime, $tpFormat);

			$bulk[] = [
				'updateOne' => [
					['_id' => $doc['_id']],
					[
						'$set' => [
							'key_hash'         => $key_hash,
							'key_spec_rev'     => $key_spec_rev,
							'_ts_year'         => $tsFields['_ts_year'],
							'_ts_freq'         => $tsFields['_ts_freq'],
							'_ts_subperiod'    => $tsFields['_ts_subperiod'],
							'_ts_period_start' => $tsFields['_ts_period_start'],
							'_ts_period_end'   => $tsFields['_ts_period_end'],
						],
						'$unset' => [
							'created_at' => '',
							'updated_at' => '',
						],
					],
				],
			];
			if (count($bulk) >= $batch) {
				$coll->bulkWrite($bulk, ['ordered' => false]);
				$updated += count($bulk);
				$bulk = [];
			}
		}
		if (!empty($bulk)) {
			$coll->bulkWrite($bulk, ['ordered' => false]);
			$updated += count($bulk);
		}

		return $updated;
	}

	/**
	 * Find (sid, key_hash) pairs with count > 1.
	 *
	 * @return array<int,array{sid:int,key_hash:string,count:int}>
	 */
	public function find_duplicate_key_hashes($dsd_id, array $mongo_filter = [])
	{
		$coll = $this->get_collection($dsd_id);
		$pipeline = [
			['$match' => $mongo_filter],
			['$group' => [
				'_id'   => ['sid' => '$sid', 'key_hash' => '$key_hash'],
				'count' => ['$sum' => 1],
			]],
			['$match' => ['count' => ['$gt' => 1]]],
		];
		$out = [];
		foreach ($coll->aggregate($pipeline) as $row) {
			$id = $row['_id'];
			$out[] = [
				'sid'       => (int) $id['sid'],
				'key_hash'  => (string) $id['key_hash'],
				'count'     => (int) $row['count'],
			];
		}
		return $out;
	}

	/**
	 * Build global per-study value counts for selected DSD component types.
	 *
	 * @param int   $dsd_id
	 * @param int   $sid
	 * @param array $components
	 * @param array $allowed_column_types
	 * @return array<int,array{dsd_id:int,component_name:string,code:string,obs_count:int}>
	 */
	public function build_value_counts_for_sid($dsd_id, $sid, array $components, array $allowed_column_types = ['dimension', 'geography', 'periodicity'])
	{
		$dsd_id = (int) $dsd_id;
		$sid    = (int) $sid;
		if ($dsd_id <= 0 || $sid <= 0) {
			return [];
		}

		$component_names = [];
		foreach ($components as $component) {
			if (!is_array($component) || empty($component['name']) || empty($component['column_type'])) {
				continue;
			}
			$columnType = (string) $component['column_type'];
			if (!in_array($columnType, $allowed_column_types, true)) {
				continue;
			}
			$name = trim((string) $component['name']);
			if ($name === '') {
				continue;
			}
			$component_names[$name] = true;
		}
		$component_names = array_keys($component_names);
		if (empty($component_names)) {
			return [];
		}

		$projection = ['sid' => 1];
		foreach ($component_names as $name) {
			$projection[$name] = 1;
		}

		$filter = [
			'$or' => [
				['sid' => $sid],
				['sid' => (string) $sid],
			],
		];
		$rows = $this->find_observations($dsd_id, $filter, ['projection' => $projection]);
		if (empty($rows)) {
			return [];
		}

		$counts = [];
		foreach ($rows as $doc) {
			if (!is_array($doc)) {
				$doc = json_decode(json_encode($doc), true);
				if (!is_array($doc)) {
					continue;
				}
			}
			foreach ($component_names as $component_name) {
				if (!array_key_exists($component_name, $doc)) {
					continue;
				}
				$code = $this->_value_count_code_string($doc[$component_name]);
				if ($code === null) {
					continue;
				}
				if (!isset($counts[$component_name])) {
					$counts[$component_name] = [];
				}
				if (!isset($counts[$component_name][$code])) {
					$counts[$component_name][$code] = 0;
				}
				$counts[$component_name][$code]++;
			}
		}

		$out = [];
		foreach ($counts as $component_name => $by_code) {
			foreach ($by_code as $code => $count) {
				if ($count < 1) {
					continue;
				}
				$out[] = [
					'dsd_id' => $dsd_id,
					'component_name' => (string) $component_name,
					'code' => (string) $code,
					'obs_count' => (int) $count,
				];
			}
		}
		return $out;
	}

	/**
	 * Normalize observation value to a stable code string.
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	private function _value_count_code_string($value)
	{
		if ($value === null) {
			return null;
		}
		if (is_string($value)) {
			$s = trim($value);
			return $s === '' ? null : $s;
		}
		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}
		if (is_int($value) || is_float($value)) {
			return (string) $value;
		}
		if (is_array($value)) {
			return null;
		}
		$s = trim((string) $value);
		return $s === '' ? null : $s;
	}
}

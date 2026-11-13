<?php
/**
 * Citation_crossref
 *
 * DOI lookup: Crossref first (api.crossref.org), then DataCite (api.datacite.org) when Crossref
 * has no record — data-repository DOIs (ICPSR, Zenodo, …) usually live only in DataCite.
 * Both hosts are fixed; nothing else is fetched. Converts the work to CSL-JSON (see Citation_csl).
 * Plain PHP: to_csl(), to_csl_datacite() and normalize_doi() need no CodeIgniter.
 */
class Citation_crossref
{
	const HOST = 'https://api.crossref.org/works/';
	const DATACITE_HOST = 'https://api.datacite.org/dois/';

	/** Crossref work type => CSL type */
	public static $type_map = array(
		'journal-article'    => 'article-journal',
		'journal-issue'      => 'article-journal',
		'book-chapter'       => 'chapter',
		'book-section'       => 'chapter',
		'book-part'          => 'chapter',
		'reference-entry'    => 'entry',
		'proceedings-article'=> 'paper-conference',
		'book'               => 'book',
		'monograph'          => 'book',
		'edited-book'        => 'book',
		'reference-book'     => 'book',
		'book-series'        => 'book',
		'report'             => 'report',
		'report-component'   => 'report',
		'standard'           => 'standard',
		'dissertation'       => 'thesis',
		'dataset'            => 'dataset',
		'posted-content'     => 'article',
		'peer-review'        => 'review',
		'proceedings'        => 'book',
	);

	private $mailto;
	private $timeout;

	public function __construct($params = array())
	{
		$this->mailto  = isset($params['mailto']) ? trim((string) $params['mailto']) : '';
		$this->timeout = isset($params['timeout']) ? (int) $params['timeout'] : 8;
	}

	/**
	 * "10.1000/xyz" from a DOI, doi: prefix or doi.org URL; '' when it isn't a DOI.
	 */
	public static function normalize_doi($doi)
	{
		$doi = trim((string) $doi);
		$doi = preg_replace('#^(https?://)?(dx\.)?doi\.org/#i', '', $doi);
		$doi = preg_replace('#^doi:\s*#i', '', $doi);
		$doi = trim(rawurldecode($doi));
		return preg_match('#^10\.\d{4,9}/\S+$#', $doi) ? $doi : '';
	}

	/**
	 * Look up a DOI (Crossref, then DataCite if Crossref has no record).
	 *
	 * @return array CSL document (type, title, ...)
	 * @throws Exception DOI_INVALID | DOI_NOT_FOUND | CROSSREF_UNAVAILABLE
	 */
	public function lookup($doi)
	{
		$doi = self::normalize_doi($doi);
		if ($doi === '') {
			throw new Exception('DOI_INVALID');
		}

		try {
			return $this->lookup_crossref($doi);
		}
		catch (Exception $e) {
			if ($e->getMessage() !== 'DOI_NOT_FOUND') {
				throw $e;
			}
		}

		return $this->lookup_datacite($doi);
	}

	/**
	 * @return array CSL document
	 * @throws Exception DOI_NOT_FOUND | CROSSREF_UNAVAILABLE
	 */
	public function lookup_crossref($doi)
	{
		$url = self::HOST . rawurlencode($doi);
		if ($this->mailto !== '') {
			$url .= '?mailto=' . rawurlencode($this->mailto);
		}

		list($code, $body) = $this->http_get($url);
		if ($code === 404) {
			throw new Exception('DOI_NOT_FOUND');
		}
		if ($body === false || $code !== 200) {
			throw new Exception('CROSSREF_UNAVAILABLE');
		}

		$data = json_decode($body, true);
		if (!is_array($data) || !isset($data['message']) || !is_array($data['message'])) {
			throw new Exception('CROSSREF_UNAVAILABLE');
		}

		return self::to_csl($data['message']);
	}

	/**
	 * @return array CSL document
	 * @throws Exception DOI_NOT_FOUND | CROSSREF_UNAVAILABLE
	 */
	public function lookup_datacite($doi)
	{
		// DataCite paths are case-insensitive; keep slash unencoded in the path segment style they expect.
		$url = self::DATACITE_HOST . str_replace('%2F', '/', rawurlencode($doi));
		list($code, $body) = $this->http_get($url);
		if ($code === 404) {
			throw new Exception('DOI_NOT_FOUND');
		}
		if ($body === false || $code !== 200) {
			throw new Exception('CROSSREF_UNAVAILABLE');
		}

		$data = json_decode($body, true);
		$attrs = (is_array($data) && isset($data['data']['attributes']) && is_array($data['data']['attributes']))
			? $data['data']['attributes']
			: null;
		if ($attrs === null) {
			throw new Exception('CROSSREF_UNAVAILABLE');
		}

		return self::to_csl_datacite($attrs);
	}

	/**
	 * Fixed-host HTTPS GET used by Crossref and DataCite lookups.
	 *
	 * @return array{0:int,1:string|false} [http_code, body]
	 */
	private function http_get($url)
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => $this->timeout,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
			CURLOPT_USERAGENT      => 'NADA-citations/1.0 (+https://github.com/ihsn/nada)',
			CURLOPT_HTTPHEADER     => array('Accept: application/json'),
			CURLOPT_MAXFILESIZE    => 2 * 1024 * 1024,
		));
		$body = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return array($code, $body);
	}

	/**
	 * Crossref work ("message") -> CSL-JSON citation.
	 */
	public static function to_csl(array $m)
	{
		$doc = array();

		$crossref_type = isset($m['type']) ? (string) $m['type'] : '';
		$doc['type'] = isset(self::$type_map[$crossref_type]) ? self::$type_map[$crossref_type] : 'article';

		$title = self::first($m, 'title');
		$subtitle = self::first($m, 'subtitle');
		if ($title !== '' && $subtitle !== '' && stripos($title, $subtitle) === false) {
			$title .= ': ' . $subtitle;
		}
		$doc['title'] = self::clean_text($title);

		$original = self::first($m, 'original-title');
		if ($original !== '') {
			$doc['original-title'] = self::clean_text($original);
		}

		$container = self::first($m, 'container-title');
		if ($container === '') {
			$container = self::first($m, 'short-container-title');
		}
		if ($container !== '') {
			$doc['container-title'] = self::clean_text($container);
		}

		foreach (array('author', 'editor', 'translator') as $role) {
			$names = self::names(isset($m[$role]) ? $m[$role] : array());
			if (!empty($names)) {
				$doc[$role] = $names;
			}
		}

		foreach (array('volume', 'issue', 'page', 'publisher', 'edition-number' => 'edition', 'article-number' => 'number') as $from => $to) {
			if (is_int($from)) {
				$from = $to;
			}
			if (isset($m[$from]) && is_scalar($m[$from]) && trim((string) $m[$from]) !== '') {
				$doc[$to] = trim((string) $m[$from]);
			}
		}
		if (isset($m['publisher-location']) && trim((string) $m['publisher-location']) !== '') {
			$doc['publisher-place'] = trim((string) $m['publisher-location']);
		}

		$issued = self::date_from($m, array('issued', 'published', 'published-print', 'published-online', 'created'));
		if ($issued) {
			$doc['issued'] = $issued;
		}

		if (!empty($m['DOI'])) {
			$doc['DOI'] = (string) $m['DOI'];
		}
		if (!empty($m['URL'])) {
			$doc['URL'] = (string) $m['URL'];
		}
		if (!empty($m['abstract'])) {
			$doc['abstract'] = self::clean_text((string) $m['abstract']);
		}
		if (!empty($m['subject']) && is_array($m['subject'])) {
			$doc['keyword'] = implode('; ', array_map('strval', $m['subject']));
		}
		if (!empty($m['language'])) {
			$doc['language'] = strtolower((string) $m['language']);
		}

		foreach (array('ISBN', 'ISSN') as $field) {
			if (!empty($m[$field]) && is_array($m[$field])) {
				$doc[$field] = (string) reset($m[$field]);
			}
		}
		if (isset($doc['ISBN']) && !isset($doc['number'])) {
			$doc['number'] = $doc['ISBN'];
		} elseif (isset($doc['ISSN']) && !isset($doc['number'])) {
			$doc['number'] = $doc['ISSN'];
		}

		return $doc;
	}

	/**
	 * DataCite DOI attributes -> CSL-JSON citation.
	 *
	 * @param array $a attributes object from GET /dois/{doi}
	 */
	public static function to_csl_datacite(array $a)
	{
		$doc = array();

		$citeproc = '';
		if (isset($a['types']['citeproc']) && is_scalar($a['types']['citeproc'])) {
			$citeproc = strtolower(trim((string) $a['types']['citeproc']));
		}
		$doc['type'] = $citeproc !== '' ? $citeproc : 'dataset';

		$title = '';
		$subtitle = '';
		foreach ((array) (isset($a['titles']) ? $a['titles'] : array()) as $t) {
			if (!is_array($t) || !isset($t['title'])) {
				continue;
			}
			$text = trim((string) $t['title']);
			if ($text === '') {
				continue;
			}
			$type = isset($t['titleType']) ? (string) $t['titleType'] : '';
			if ($type === 'Subtitle' || $type === 'TranslatedTitle') {
				if ($subtitle === '') {
					$subtitle = $text;
				}
			} elseif ($title === '') {
				$title = $text;
			}
		}
		if ($title !== '' && $subtitle !== '' && stripos($title, $subtitle) === false) {
			$title .= ': ' . $subtitle;
		}
		$doc['title'] = self::clean_text($title);

		$names = self::datacite_names(isset($a['creators']) ? $a['creators'] : array());
		if (!empty($names)) {
			$doc['author'] = $names;
		}
		$editors = self::datacite_names(isset($a['contributors']) ? $a['contributors'] : array(), true);
		if (!empty($editors)) {
			$doc['editor'] = $editors;
		}

		$publisher = '';
		if (isset($a['publisher'])) {
			if (is_array($a['publisher']) && isset($a['publisher']['name'])) {
				$publisher = trim((string) $a['publisher']['name']);
			} elseif (is_scalar($a['publisher'])) {
				$publisher = trim((string) $a['publisher']);
			}
		}
		if ($publisher !== '') {
			$doc['publisher'] = $publisher;
		}

		$year = 0;
		if (isset($a['publicationYear']) && is_numeric($a['publicationYear'])) {
			$year = (int) $a['publicationYear'];
		}
		if ($year >= 1) {
			$doc['issued'] = array('date-parts' => array(array($year)));
		}

		if (!empty($a['doi']) && is_scalar($a['doi'])) {
			$doc['DOI'] = strtolower((string) $a['doi']);
		}
		if (!empty($a['url']) && is_scalar($a['url'])) {
			$doc['URL'] = (string) $a['url'];
		}

		$abstract = '';
		foreach ((array) (isset($a['descriptions']) ? $a['descriptions'] : array()) as $d) {
			if (!is_array($d) || !isset($d['description'])) {
				continue;
			}
			$dtype = isset($d['descriptionType']) ? (string) $d['descriptionType'] : 'Abstract';
			if ($dtype !== '' && $dtype !== 'Abstract') {
				continue;
			}
			$abstract = trim((string) $d['description']);
			if ($abstract !== '') {
				break;
			}
		}
		if ($abstract !== '') {
			$doc['abstract'] = self::clean_text($abstract);
		}

		$keywords = array();
		foreach ((array) (isset($a['subjects']) ? $a['subjects'] : array()) as $s) {
			if (is_array($s) && isset($s['subject']) && trim((string) $s['subject']) !== '') {
				$keywords[] = trim((string) $s['subject']);
			}
		}
		if (!empty($keywords)) {
			$doc['keyword'] = implode('; ', $keywords);
		}

		if (!empty($a['language']) && is_scalar($a['language'])) {
			$doc['language'] = strtolower((string) $a['language']);
		}

		return $doc;
	}

	/**
	 * DataCite creators/contributors -> CSL name objects.
	 * When $editors_only, keep contributorType Editor (and similar).
	 */
	private static function datacite_names($list, $editors_only = false)
	{
		$out = array();
		foreach ((array) $list as $a) {
			if (!is_array($a)) {
				continue;
			}
			if ($editors_only) {
				$ctype = isset($a['contributorType']) ? (string) $a['contributorType'] : '';
				if (!in_array($ctype, array('Editor', 'HostingInstitution'), true)) {
					continue;
				}
			}
			$family = isset($a['familyName']) ? trim((string) $a['familyName']) : '';
			$given  = isset($a['givenName']) ? trim((string) $a['givenName']) : '';
			$name   = isset($a['name']) ? trim((string) $a['name']) : '';
			$name_type = isset($a['nameType']) ? (string) $a['nameType'] : '';
			if ($name_type === 'Organizational' && $name !== '') {
				$out[] = array('literal' => $name);
			} elseif ($family !== '' || $given !== '') {
				$n = array();
				if ($family !== '') {
					$n['family'] = $family;
				}
				if ($given !== '') {
					$n['given'] = $given;
				}
				$out[] = $n;
			} elseif ($name !== '') {
				$out[] = array('literal' => $name);
			}
		}
		return $out;
	}

	private static function first(array $m, $key)
	{
		if (!isset($m[$key])) {
			return '';
		}
		$v = $m[$key];
		if (is_array($v)) {
			$v = reset($v);
		}
		return is_scalar($v) ? trim((string) $v) : '';
	}

	private static function names($list)
	{
		$out = array();
		foreach ((array) $list as $a) {
			if (!is_array($a)) {
				continue;
			}
			$family = isset($a['family']) ? trim((string) $a['family']) : '';
			$given  = isset($a['given']) ? trim((string) $a['given']) : '';
			$name   = isset($a['name']) ? trim((string) $a['name']) : '';
			if ($family !== '' || $given !== '') {
				$n = array();
				if ($family !== '') {
					$n['family'] = $family;
				}
				if ($given !== '') {
					$n['given'] = $given;
				}
				$out[] = $n;
			} elseif ($name !== '') {
				$out[] = array('literal' => $name);
			}
		}
		return $out;
	}

	private static function date_from(array $m, array $keys)
	{
		foreach ($keys as $key) {
			if (!isset($m[$key]['date-parts'][0]) || !is_array($m[$key]['date-parts'][0])) {
				continue;
			}
			$parts = array();
			foreach (array_slice($m[$key]['date-parts'][0], 0, 3) as $p) {
				if (!is_numeric($p) || (int) $p < 1) {
					break;
				}
				$parts[] = (int) $p;
			}
			if (!empty($parts)) {
				return array('date-parts' => array($parts));
			}
		}
		return NULL;
	}

	/**
	 * Crossref titles and abstracts can carry markup (JATS XML, MathML, entities): plain text.
	 */
	private static function clean_text($text)
	{
		// an "Abstract" heading inside the markup is not part of the abstract
		$text = preg_replace('#<(jats:)?title[^>]*>\s*Abstract\s*</(jats:)?title>#i', '', $text);
		$text = preg_replace('#</?(jats:)?(p|title|sec)\b[^>]*>#i', ' ', $text);
		$text = strip_tags($text);
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = preg_replace('/^\s*Abstract\s*[:.]\s*/i', '', $text);
		return trim(preg_replace('/\s+/', ' ', $text));
	}
}

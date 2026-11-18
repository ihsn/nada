<?php
/**
 * Citation_import_export
 *
 * Reads citations from BibTeX, EndNote, RIS and JSON text into CSL documents, and writes citation rows
 * as CSV, JSON, BibTeX or RIS. Used by api/admin/citations (import preview and commit, export).
 *
 * Needs no CodeIgniter; the BibTeX parser needs the composer autoloader.
 */
require_once(dirname(__FILE__) . '/Citation_csl.php');

class Citation_import_export
{
	const FORMATS = 'bibtex|endnote|ris|json';

	/** Columns of the CSV export (rows from Citation_model::get_all_citations) */
	public static $csv_columns = array(
		'id', 'uuid', 'csl_type', 'ctype', 'title', 'subtitle', 'alt_title', 'authors', 'editors', 'translators',
		'volume', 'issue', 'idnumber', 'edition', 'place_publication', 'place_state', 'publisher', 'url',
		'page_from', 'page_to', 'data_accessed', 'organization', 'pub_day', 'pub_month', 'pub_year', 'abstract',
		'keywords', 'doi', 'lang', 'published', 'flag', 'notes', 'owner', 'created', 'changed', 'related_datasets',
	);

	public $max_rows = 500;

	private $csl;

	public function __construct($params = array())
	{
		$this->csl = new Citation_csl();
		if (isset($params['max_rows'])) {
			$this->max_rows = (int) $params['max_rows'];
		}
	}

	// ---------------------------------------------------------------------------------------------
	// Import
	// ---------------------------------------------------------------------------------------------

	/**
	 * Format of a text: bibtex | endnote | ris | json, or '' when it is none of them.
	 */
	public function detect_format($text)
	{
		$trimmed = ltrim((string) $text, "\xEF\xBB\xBF \t\r\n");
		if ($trimmed === '') {
			return '';
		}
		if ($trimmed[0] === '[' || $trimmed[0] === '{') {
			return 'json';
		}
		if (preg_match('/^\s*@\w+\s*[({]/m', $trimmed)) {
			return 'bibtex';
		}
		if (preg_match('/^TY\s{1,2}-/m', $trimmed)) {
			return 'ris';
		}
		if (preg_match('/^%[0A]\s/m', $trimmed)) {
			return 'endnote';
		}
		return '';
	}

	/**
	 * Parse text into citations.
	 *
	 * @param string $format bibtex | endnote | ris | json | auto
	 * @param string $text
	 * @return array {format, rows: [{index, doc, warnings[], errors[]}], errors[]}
	 *         A row with errors can't be imported (e.g. no title); warnings are for the preview.
	 */
	public function parse($format, $text)
	{
		$text = (string) $text;
		if (substr($text, 0, 3) === "\xEF\xBB\xBF") {
			$text = substr($text, 3);
		}

		$format = strtolower((string) $format);
		if ($format === '' || $format === 'auto') {
			$format = $this->detect_format($text);
		}
		if ($format === 'csl') {
			$format = 'json';
		}

		$result = array('format' => $format, 'rows' => array(), 'errors' => array());

		if (!in_array($format, explode('|', self::FORMATS), true)) {
			$result['errors'][] = 'UNKNOWN_FORMAT';
			return $result;
		}

		$docs = array();
		try {
			switch ($format) {
				case 'bibtex':
					$docs = $this->parse_legacy_parser('BibTeX', 'parse_string', $text, 'Bibtex.php', $result);
					break;
				case 'endnote':
					$docs = $this->parse_legacy_parser('Endnote', 'parse', $text, 'Endnote.php', $result);
					break;
				case 'ris':
					$docs = $this->parse_ris($text);
					break;
				case 'json':
					$docs = $this->parse_json($text, $result);
					break;
			}
		}
		catch (Throwable $e) {
			$result['errors'][] = 'PARSE_ERROR: ' . $e->getMessage();
			return $result;
		}

		if (empty($docs) && empty($result['errors'])) {
			$result['errors'][] = 'NO_CITATIONS_FOUND';
			return $result;
		}

		if (count($docs) > $this->max_rows) {
			$result['errors'][] = 'TOO_MANY_CITATIONS: ' . count($docs) . ' (max ' . $this->max_rows . ')';
			return $result;
		}

		foreach ($docs as $i => $doc) {
			$doc = $this->csl->normalize($doc);
			$errors = array();
			$warnings = array();

			foreach ($this->csl->validate($doc) as $field => $code) {
				$errors[] = $field . ': ' . $code;
			}
			if (empty($doc['author']) && empty($doc['editor'])) {
				$warnings[] = 'NO_AUTHORS';
			}
			if (empty($doc['issued'])) {
				$warnings[] = 'NO_DATE';
			}

			$result['rows'][] = array('index' => $i, 'doc' => $doc, 'warnings' => $warnings, 'errors' => $errors);
		}

		return $result;
	}

	/**
	 * @return array CSL documents
	 */
	private function parse_legacy_parser($class, $method, $text, $file, array &$result)
	{
		if (!class_exists($class, false)) {
			require_once(dirname(__FILE__) . '/' . $file);
		}
		$parser = new $class();
		$rows = $parser->$method($text);

		if (!is_array($rows)) {
			$result['errors'][] = 'PARSE_ERROR' . (!empty($parser->errors) ? ': ' . implode('; ', (array) $parser->errors) : '');
			return array();
		}

		$docs = array();
		foreach ($rows as $row) {
			$row = (array) $row;
			// RIS / EndNote parsers keep titles and keywords as lists
			foreach (array('title', 'keywords') as $field) {
				if (isset($row[$field]) && is_array($row[$field])) {
					$row[$field] = implode($field === 'title' ? ' ' : '; ', array_map('strval', $row[$field]));
				}
			}
			$docs[] = $this->csl->from_legacy_row($row);
		}
		return $docs;
	}

	/** RIS reference type => CSL type */
	public static $ris_to_csl = array(
		'JOUR' => 'article-journal', 'JFULL' => 'article-journal', 'EJOUR' => 'article-journal', 'ABST' => 'article-journal',
		'MGZN' => 'article-magazine', 'NEWS' => 'article-newspaper',
		'CHAP' => 'chapter', 'ECHAP' => 'chapter',
		'CPAPER' => 'paper-conference', 'CONF' => 'paper-conference',
		'BOOK' => 'book', 'EBOOK' => 'book', 'EDBOOK' => 'book', 'SER' => 'book',
		'RPRT' => 'report', 'THES' => 'thesis',
		'ELEC' => 'webpage', 'WEB' => 'webpage', 'BLOG' => 'post-weblog',
		'DATA' => 'dataset', 'COMP' => 'software', 'STAND' => 'standard', 'MAP' => 'map',
	);

	/**
	 * RIS text => CSL documents. Tags: TY type, TI/T1 title, T2/JF/JO container, AU/A1 author, A2/ED editor,
	 * TA translator, PY/DA date, VL, IS, SP/EP, PB, CY, ET, SN, DO, UR, AB/N2, KW, LA, Y2 accessed, T3 series.
	 * Notes (N1) are not imported: they are not part of the citation.
	 */
	private function parse_ris($text)
	{
		$text = str_replace(array("\r\n", "\r"), "\n", $text);
		$entries = array();
		$current = NULL;
		$last_tag = NULL;

		foreach (explode("\n", $text) as $line) {
			if (preg_match('/^([A-Z][A-Z0-9])\s{1,2}-\s?(.*)$/', $line, $m)) {
				$tag = $m[1];
				$value = trim($m[2]);
				if ($tag === 'TY') {
					if ($current !== NULL) {
						$entries[] = $current;
					}
					$current = array('TY' => array($value));
				}
				elseif ($tag === 'ER') {
					if ($current !== NULL) {
						$entries[] = $current;
					}
					$current = NULL;
				}
				elseif ($current !== NULL) {
					$current[$tag][] = $value;
				}
				$last_tag = $tag;
			}
			elseif (trim($line) !== '' && $current !== NULL && $last_tag !== NULL && isset($current[$last_tag])) {
				// continuation of the previous value
				$i = count($current[$last_tag]) - 1;
				$current[$last_tag][$i] = trim($current[$last_tag][$i] . ' ' . trim($line));
			}
		}
		if ($current !== NULL) {
			$entries[] = $current;
		}

		$docs = array();
		foreach ($entries as $e) {
			$docs[] = $this->ris_entry_to_csl($e);
		}
		return $docs;
	}

	private function ris_entry_to_csl(array $e)
	{
		$one = function ($tags) use ($e) {
			foreach ((array) $tags as $tag) {
				if (isset($e[$tag])) {
					foreach ($e[$tag] as $v) {
						if (trim($v) !== '') {
							return trim($v);
						}
					}
				}
			}
			return '';
		};

		$ty = strtoupper($one('TY'));
		$doc = array('type' => isset(self::$ris_to_csl[$ty]) ? self::$ris_to_csl[$ty] : 'document');

		$doc['title'] = $one(array('TI', 'T1', 'CT'));
		if ($doc['title'] === '' && $doc['type'] === 'book') {
			$doc['title'] = $one('BT');
		}
		$doc['container-title'] = $one(array('T2', 'JF', 'JO', 'JA', 'J1', 'J2')) ?: ($doc['type'] !== 'book' ? $one('BT') : '');
		$doc['collection-title'] = $one('T3');

		foreach (array('author' => array('AU', 'A1'), 'editor' => array('A2', 'ED'), 'translator' => array('TA')) as $role => $tags) {
			$names = array();
			foreach ($tags as $tag) {
				foreach (isset($e[$tag]) ? $e[$tag] : array() as $value) {
					$n = $this->ris_name($value);
					if ($n) {
						$names[] = $n;
					}
				}
			}
			if ($names) {
				$doc[$role] = $names;
			}
		}

		// dates: PY is the year, DA / Y1 is YYYY/MM/DD/other info
		$py = $one('PY');
		$da = $one(array('DA', 'Y1'));
		$year = preg_match('/^\s*(\d{4})/', $py, $m) ? (int) $m[1] : 0;
		$parts = array();
		if ($da !== '' && preg_match('/^\s*(\d{4})\/?(\d{1,2})?\/?(\d{1,2})?/', $da, $m) && ($year === 0 || (int) $m[1] === $year)) {
			$year = (int) $m[1];
			$parts = array($year);
			if (!empty($m[2]) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
				$parts[] = (int) $m[2];
				if (!empty($m[3]) && (int) $m[3] >= 1) {
					$parts[] = (int) $m[3];
				}
			}
		}
		elseif ($year > 0) {
			$parts = array($year);
		}
		if ($parts) {
			$doc['issued'] = array('date-parts' => array($parts));
		}
		elseif ($py !== '') {
			$doc['issued'] = array('raw' => $py);
		}

		$y2 = $one('Y2');
		if ($y2 !== '' && preg_match('/^\s*(\d{4})\/?(\d{1,2})?\/?(\d{1,2})?/', $y2, $m)) {
			$a = array((int) $m[1]);
			if (!empty($m[2])) {
				$a[] = (int) $m[2];
				if (!empty($m[3])) {
					$a[] = (int) $m[3];
				}
			}
			$doc['accessed'] = array('date-parts' => array($a));
		}

		$sp = $one('SP');
		$ep = $one('EP');
		if ($sp !== '' || $ep !== '') {
			$doc['page'] = ($sp !== '' && $ep !== '' && strpos($sp, '-') === false) ? $sp . '-' . $ep : ($sp !== '' ? $sp : $ep);
		}

		foreach (array('volume' => 'VL', 'issue' => 'IS', 'publisher' => 'PB', 'publisher-place' => 'CY', 'edition' => 'ET',
			'number' => 'SN', 'DOI' => 'DO', 'URL' => 'UR') as $field => $tag) {
			if ($one($tag) !== '') {
				$doc[$field] = $one($tag);
			}
		}
		if (!empty($doc['DOI'])) {
			$doc['DOI'] = $this->csl->normalize_doi($doc['DOI']);
		}

		$abstract = $one(array('AB', 'N2'));
		if ($abstract !== '') {
			$doc['abstract'] = $abstract;
		}
		if (!empty($e['KW'])) {
			$doc['keyword'] = implode('; ', array_filter(array_map('trim', $e['KW']), 'strlen'));
		}
		$language = $this->csl->language_code($one('LA'));
		if ($language !== '') {
			$doc['language'] = $language;
		}

		return $doc;
	}

	/**
	 * "Last, First" is a person; anything else is taken as one name (an organization)
	 */
	private function ris_name($value)
	{
		$value = trim($value);
		if ($value === '') {
			return NULL;
		}
		if (strpos($value, ',') !== false) {
			$parts = array_map('trim', explode(',', $value, 3));
			$given = trim($parts[1] . (isset($parts[2]) && $parts[2] !== '' ? ' ' . $parts[2] : ''));
			$name = array('family' => $parts[0]);
			if ($given !== '') {
				$name['given'] = $given;
			}
			return $name;
		}
		return array('literal' => $value);
	}

	/**
	 * JSON: a list (or {citations: [...]}) of CSL items, NADA exports ({metadata: CSL, ...}) or legacy flat citations.
	 */
	private function parse_json($text, array &$result)
	{
		$data = json_decode($text, true);
		if (!is_array($data)) {
			$result['errors'][] = 'INVALID_JSON';
			return array();
		}
		if (isset($data['citations']) && is_array($data['citations'])) {
			$data = $data['citations'];
		}
		elseif (array_keys($data) !== range(0, count($data) - 1)) {
			$data = array($data); // one item
		}

		$docs = array();
		foreach ($data as $item) {
			if (!is_array($item)) {
				continue;
			}
			if (isset($item['metadata']) && is_array($item['metadata'])) {
				$docs[] = $item['metadata'];
			}
			elseif (!$this->csl->is_legacy_input($item)) {
				$docs[] = $item;
			}
			else {
				foreach (array('authors', 'editors', 'translators') as $field) {
					if (isset($item[$field]) && is_string($item[$field])) {
						$item[$field] = json_decode($item[$field], true);
					}
				}
				$docs[] = $this->csl->from_legacy_row($item);
			}
		}
		return $docs;
	}

	// ---------------------------------------------------------------------------------------------
	// Export
	// ---------------------------------------------------------------------------------------------

	/**
	 * Write citations to a stream.
	 *
	 * @param string   $format csv | json | bibtex | ris
	 * @param resource $fp
	 * @param callable $next   returns the next batch of export rows (Citation_model::get_all_citations()), or FALSE
	 * @return int number of citations written
	 */
	public function stream($format, $fp, $next)
	{
		$count = 0;
		$keys = array();

		switch ($format) {
			case 'csv':
				fwrite($fp, "\xEF\xBB\xBF");
				fputcsv($fp, self::$csv_columns, ',', '"', '\\');
				break;
			case 'json':
				fwrite($fp, '[');
				break;
			case 'bibtex':
			case 'ris':
				break;
			default:
				throw new Exception('UNKNOWN_FORMAT: ' . $format);
		}

		while (($rows = call_user_func($next)) !== false && !empty($rows)) {
			foreach ($rows as $row) {
				switch ($format) {
					case 'csv':
						fputcsv($fp, $this->csv_line($row), ',', '"', '\\');
						break;
					case 'json':
						fwrite($fp, ($count > 0 ? ",\n" : "\n") . json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
						break;
					case 'bibtex':
						$doc = isset($row['metadata']) ? $row['metadata'] : array();
						fwrite($fp, $this->to_bibtex($doc, $this->citation_key($doc, $keys)) . "\n");
						break;
					case 'ris':
						$doc = isset($row['metadata']) ? $row['metadata'] : array();
						fwrite($fp, $this->to_ris($doc) . "\r\n");
						break;
				}
				$count++;
			}
		}

		if ($format === 'json') {
			fwrite($fp, "\n]\n");
		}

		return $count;
	}

	public function csv_line(array $row)
	{
		$line = array();
		foreach (self::$csv_columns as $column) {
			$value = isset($row[$column]) ? $row[$column] : '';
			if (($column === 'created' || $column === 'changed') && is_numeric($value) && $value) {
				$value = date('Y-m-d', (int) $value);
			}
			$line[] = $this->csv_safe(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value);
		}
		return $line;
	}

	/**
	 * A cell that starts with = + - @ is read as a formula by spreadsheet programs: prefix an apostrophe.
	 */
	private function csv_safe($value)
	{
		if ($value !== '' && strpos("=+-@\t\r", $value[0]) !== false && !is_numeric($value)) {
			return "'" . $value;
		}
		return $value;
	}

	/** BibTeX entry type for a CSL type */
	public static $bibtex_types = array(
		'article-journal'   => 'article',
		'article-magazine'  => 'article',
		'article-newspaper' => 'article',
		'chapter'           => 'incollection',
		'paper-conference'  => 'inproceedings',
		'book'              => 'book',
		'report'            => 'techreport',
		'thesis'            => 'phdthesis',
	);

	public function to_bibtex(array $doc, $key = 'citation')
	{
		$type = isset($doc['type']) ? $doc['type'] : 'book';
		$entry_type = isset(self::$bibtex_types[$type]) ? self::$bibtex_types[$type] : 'misc';
		$genre = isset($doc['genre']) ? (string) $doc['genre'] : '';
		if ($type === 'thesis' && preg_match('/master/i', $genre)) {
			$entry_type = 'mastersthesis';
		}

		$fields = array();
		$fields['author'] = $this->bibtex_names(isset($doc['author']) ? $doc['author'] : array());
		$fields['editor'] = $this->bibtex_names(isset($doc['editor']) ? $doc['editor'] : array());
		$fields['title'] = isset($doc['title']) ? '{' . $this->tex($doc['title']) . '}' : '';

		$container = isset($doc['container-title']) ? $doc['container-title'] : '';
		if ($container !== '') {
			if ($type === 'chapter' || $type === 'paper-conference') {
				$fields['booktitle'] = $this->tex($container);
			}
			elseif (in_array($type, array('article-journal', 'article-magazine', 'article-newspaper'), true)) {
				$fields['journal'] = $this->tex($container);
			}
			else {
				$fields['howpublished'] = $this->tex($container);
			}
		}

		$parts = $this->date_parts(isset($doc['issued']) ? $doc['issued'] : NULL);
		if (isset($parts[0])) {
			$fields['year'] = (string) $parts[0];
		}
		if (isset($parts[1]) && $parts[1] >= 1 && $parts[1] <= 12) {
			$fields['month'] = strtolower(date('M', mktime(0, 0, 0, $parts[1], 1, 2000)));
		}

		foreach (array('volume' => 'volume', 'issue' => 'number', 'edition' => 'edition', 'publisher' => 'publisher',
			'publisher-place' => 'address', 'DOI' => 'doi', 'URL' => 'url', 'abstract' => 'abstract', 'keyword' => 'keywords') as $csl_field => $bib_field) {
			if (isset($doc[$csl_field]) && $doc[$csl_field] !== '') {
				$fields[$bib_field] = ($csl_field === 'DOI' || $csl_field === 'URL') ? $this->bib_raw($doc[$csl_field]) : $this->tex($doc[$csl_field]);
			}
		}
		if (!empty($doc['page'])) {
			$fields['pages'] = preg_replace('/\s*[-\x{2013}\x{2014}]+\s*/u', '--', $this->tex($doc['page']));
		}
		if (!empty($doc['number'])) {
			$number = $this->tex($doc['number']);
			if ($type === 'report') {
				$fields['number'] = $number;
			}
			elseif (preg_match('/^[0-9Xx-]{8,17}$/', $doc['number'])) {
				$fields[strlen(str_replace('-', '', $doc['number'])) === 8 ? 'issn' : 'isbn'] = $number;
			}
			else {
				$fields['note'] = $number;
			}
		}
		if ($type === 'thesis' && $genre !== '') {
			$fields['type'] = $this->tex($genre);
		}
		if (in_array($type, array('thesis'), true) && !empty($doc['publisher'])) {
			$fields['school'] = $this->tex($doc['publisher']);
			unset($fields['publisher']);
		}
		if ($type === 'report' && !empty($doc['publisher'])) {
			$fields['institution'] = $this->tex($doc['publisher']);
			unset($fields['publisher']);
		}

		$lines = array();
		foreach ($fields as $name => $value) {
			if ($value === '' || $value === NULL) {
				continue;
			}
			$lines[] = sprintf('  %s = {%s}', $name, $value);
		}

		return '@' . $entry_type . '{' . $key . ",\n" . implode(",\n", $lines) . "\n}\n";
	}

	private function bibtex_names($names)
	{
		$out = array();
		foreach ((array) $names as $n) {
			if (isset($n['literal'])) {
				$out[] = '{' . $this->tex($n['literal']) . '}';
			}
			else {
				$family = isset($n['family']) ? $this->tex($n['family']) : '';
				$given = isset($n['given']) ? $this->tex($n['given']) : '';
				$out[] = $given !== '' ? $family . ', ' . $given : $family;
			}
		}
		return implode(' and ', array_filter($out, 'strlen'));
	}

	/**
	 * Make text safe inside a BibTeX field: braces are removed (they would unbalance the entry) and the other
	 * characters BibTeX treats as markup are escaped.
	 */
	private function tex($text)
	{
		$text = str_replace(array("\r", "\n", '{', '}'), array(' ', ' ', '', ''), (string) $text);
		return str_replace(array('\\', '&', '%', '$', '#', '_'), array('\\textbackslash{}', '\\&', '\\%', '\\$', '\\#', '\\_'), $text);
	}

	/** DOI and URL go in as they are; only braces would break the entry */
	private function bib_raw($text)
	{
		return str_replace(array('{', '}', "\r", "\n"), '', (string) $text);
	}

	/**
	 * Unique BibTeX key: first author's family name + year + first long word of the title.
	 */
	public function citation_key(array $doc, array &$used)
	{
		$author = '';
		if (!empty($doc['author'][0])) {
			$a = $doc['author'][0];
			$author = isset($a['family']) ? $a['family'] : (isset($a['literal']) ? $a['literal'] : '');
		}
		$parts = $this->date_parts(isset($doc['issued']) ? $doc['issued'] : NULL);
		$year = isset($parts[0]) ? (string) $parts[0] : '';

		$word = '';
		if (!empty($doc['title']) && preg_match_all('/[\p{L}\p{N}]{4,}/u', $doc['title'], $m)) {
			$word = $m[0][0];
		}

		$key = $this->ascii($author) . $year . $this->ascii($word);
		if ($key === '') {
			$key = 'citation';
		}

		// same author, year and word: a, b, c ... then 27, 28 ...
		$base = $key;
		$i = 0;
		while (isset($used[$key])) {
			$i++;
			$key = $base . ($i <= 26 ? chr(96 + $i) : $i);
		}
		$used[$key] = true;
		return $key;
	}

	private function ascii($text)
	{
		$text = (string) $text;
		if (function_exists('iconv')) {
			$t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
			if ($t !== false) {
				$text = $t;
			}
		}
		return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $text));
	}

	/** RIS reference type for a CSL type */
	public static $ris_types = array(
		'article-journal'   => 'JOUR',
		'article-magazine'  => 'MGZN',
		'article-newspaper' => 'NEWS',
		'chapter'           => 'CHAP',
		'paper-conference'  => 'CPAPER',
		'book'              => 'BOOK',
		'report'            => 'RPRT',
		'thesis'            => 'THES',
		'webpage'           => 'ELEC',
		'dataset'           => 'DATA',
		'article'           => 'JOUR',
	);

	public function to_ris(array $doc)
	{
		$type = isset($doc['type']) ? $doc['type'] : 'book';
		$lines = array('TY  - ' . (isset(self::$ris_types[$type]) ? self::$ris_types[$type] : 'GEN'));

		$add = function ($tag, $value) use (&$lines) {
			$value = trim(preg_replace('/\s+/', ' ', (string) $value));
			if ($value !== '') {
				$lines[] = sprintf('%-2s  - %s', $tag, $value);
			}
		};

		$add('TI', isset($doc['title']) ? $doc['title'] : '');
		foreach (array('author' => 'AU', 'editor' => 'ED') as $role => $tag) {
			foreach ((array) (isset($doc[$role]) ? $doc[$role] : array()) as $n) {
				if (isset($n['literal'])) {
					$add($tag, $n['literal']);
				}
				else {
					$add($tag, trim((isset($n['family']) ? $n['family'] : '') . ((isset($n['given']) && $n['given'] !== '') ? ', ' . $n['given'] : '')));
				}
			}
		}
		$add('T2', isset($doc['container-title']) ? $doc['container-title'] : '');

		$parts = $this->date_parts(isset($doc['issued']) ? $doc['issued'] : NULL);
		if (isset($parts[0])) {
			$add('PY', $parts[0]);
			$add('DA', sprintf('%04d/%02d/%02d/', $parts[0], isset($parts[1]) ? $parts[1] : 0, isset($parts[2]) ? $parts[2] : 0));
		}

		$add('VL', isset($doc['volume']) ? $doc['volume'] : '');
		$add('IS', isset($doc['issue']) ? $doc['issue'] : '');
		if (!empty($doc['page'])) {
			$pages = preg_split('/\s*[-\x{2013}\x{2014}]+\s*/u', $doc['page'], 2);
			$add('SP', $pages[0]);
			$add('EP', isset($pages[1]) ? $pages[1] : '');
		}
		$add('PB', isset($doc['publisher']) ? $doc['publisher'] : '');
		$add('CY', isset($doc['publisher-place']) ? $doc['publisher-place'] : '');
		$add('ET', isset($doc['edition']) ? $doc['edition'] : '');
		$add('SN', isset($doc['number']) ? $doc['number'] : '');
		$add('DO', isset($doc['DOI']) ? $doc['DOI'] : '');
		$add('UR', isset($doc['URL']) ? $doc['URL'] : '');
		$add('AB', isset($doc['abstract']) ? $doc['abstract'] : '');
		if (!empty($doc['keyword'])) {
			foreach (preg_split('/\s*[;,]\s*/', $doc['keyword']) as $keyword) {
				$add('KW', $keyword);
			}
		}
		$add('LA', isset($doc['language']) ? $doc['language'] : '');

		$lines[] = 'ER  - ';
		return implode("\r\n", $lines) . "\r\n";
	}

	private function date_parts($date)
	{
		return $this->csl->date_parts($date);
	}
}

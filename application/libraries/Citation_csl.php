<?php
/**
 * Citation_csl
 *
 * Converts citations between the legacy NADA flat row (the columns of the `citations` table before
 * CSL, plus authors/editors/translators as [{fname, lname, initial}]) and the CSL-JSON document stored in
 * `citations.metadata`. Also computes the columns that are copied out of the document for filtering,
 * sorting and full-text search.
 *
 * Plain PHP (no CodeIgniter dependency) so it can be unit tested on its own: tests/citation_csl_test.php.
 * Mapping spec: CITATIONS_MIGRATION_PLAN.md §1.2.
 *
 * Round trip: to_legacy_row(from_legacy_row($row)) returns the same values for every mapped legacy field.
 * Values with no clean CSL equivalent are kept under $doc['custom']['nada'] so the round trip is exact.
 */
class Citation_csl
{
	/** NADA citation type => CSL type */
	public static $type_map = array(
		'book'                 => 'book',
		'report'               => 'report',
		'working-paper'        => 'report',
		'corporate-author'     => 'report',
		'anthology-editor'     => 'book',
		'anthology-translator' => 'book',
		'book-section'         => 'chapter',
		'journal'              => 'article-journal',
		'conference-paper'     => 'paper-conference',
		'magazine'             => 'article-magazine',
		'newspaper'            => 'article-newspaper',
		'website'              => 'webpage',
		'website-doc'          => 'document',
		'thesis'               => 'thesis',
	);

	/** NADA types whose `subtitle` field holds the name of the containing publication (journal, book, ...) */
	public static $container_ctypes = array(
		'journal', 'working-paper', 'book-section', 'conference-paper', 'magazine', 'newspaper', 'website-doc',
	);

	/** NADA types whose `organization` field is the publisher/institution (form label: institute, website organization) */
	public static $organization_publisher_ctypes = array('thesis', 'website', 'website-doc');

	/** CSL 1.0.2 item types */
	public static $csl_types = array(
		'article', 'article-journal', 'article-magazine', 'article-newspaper', 'bill', 'book', 'broadcast',
		'chapter', 'classic', 'collection', 'dataset', 'document', 'entry', 'entry-dictionary',
		'entry-encyclopedia', 'event', 'figure', 'graphic', 'hearing', 'interview', 'legal_case', 'legislation',
		'manuscript', 'map', 'motion_picture', 'musical_score', 'pamphlet', 'paper-conference', 'patent',
		'performance', 'periodical', 'personal_communication', 'post', 'post-weblog', 'regulation', 'report',
		'review', 'review-book', 'software', 'song', 'speech', 'standard', 'thesis', 'treaty', 'webpage',
	);

	/** Language names used by the legacy edit form => ISO 639-1. Keys are the exact form values. */
	public static $lang_codes = array(
		'Afrikanns' => 'af', 'Albanian' => 'sq', 'Arabic' => 'ar', 'Armenian' => 'hy', 'Basque' => 'eu',
		'Bengali' => 'bn', 'Bulgarian' => 'bg', 'Catalan' => 'ca', 'Cambodian' => 'km', 'Chinese (Mandarin)' => 'zh',
		'Croation' => 'hr', 'Czech' => 'cs', 'Danish' => 'da', 'Dutch' => 'nl', 'English' => 'en', 'Estonian' => 'et',
		'Fiji' => 'fj', 'Finnish' => 'fi', 'French' => 'fr', 'Georgian' => 'ka', 'German' => 'de', 'Greek' => 'el',
		'Gujarati' => 'gu', 'Hebrew' => 'he', 'Hindi' => 'hi', 'Hungarian' => 'hu', 'Icelandic' => 'is',
		'Indonesian' => 'id', 'Irish' => 'ga', 'Italian' => 'it', 'Japanese' => 'ja', 'Javanese' => 'jv',
		'Korean' => 'ko', 'Latin' => 'la', 'Latvian' => 'lv', 'Lithuanian' => 'lt', 'Macedonian' => 'mk',
		'Malay' => 'ms', 'Malayalam' => 'ml', 'Maltese' => 'mt', 'Maori' => 'mi', 'Marathi' => 'mr',
		'Mongolian' => 'mn', 'Nepali' => 'ne', 'Norwegian' => 'no', 'Persian' => 'fa', 'Polish' => 'pl',
		'Portuguese' => 'pt', 'Punjabi' => 'pa', 'Quechua' => 'qu', 'Romanian' => 'ro', 'Russian' => 'ru',
		'Samoan' => 'sm', 'Serbian' => 'sr', 'Slovak' => 'sk', 'Slovenian' => 'sl', 'Spanish' => 'es',
		'Swahili' => 'sw', 'Swedish ' => 'sv', 'Tamil' => 'ta', 'Tatar' => 'tt', 'Telugu' => 'te', 'Thai' => 'th',
		'Tibetan' => 'bo', 'Tonga' => 'to', 'Turkish' => 'tr', 'Ukranian' => 'uk', 'Urdu' => 'ur', 'Uzbek' => 'uz',
		'Vietnamese' => 'vi', 'Welsh' => 'cy', 'Xhosa' => 'xh',
	);

	/** Legacy flat fields produced by to_legacy_row() (besides authors/editors/translators). */
	public static $legacy_fields = array(
		'ctype', 'title', 'subtitle', 'alt_title', 'volume', 'issue', 'idnumber', 'edition', 'place_publication',
		'place_state', 'publisher', 'publication_medium', 'url', 'page_from', 'page_to', 'data_accessed',
		'organization', 'pub_day', 'pub_month', 'pub_year', 'abstract', 'keywords', 'doi', 'lang', 'country',
	);

	/** Columns of the `citations` table computed from the document (project_columns()). */
	public static $projected_columns = array(
		'ctype', 'title', 'container_title', 'authors_text', 'search_text', 'pub_year', 'doi', 'url', 'lang',
	);

	const TITLE_MAX = 1000;
	const CONTAINER_TITLE_MAX = 500;
	const URL_MAX = 2048;
	const DOI_MAX = 255;

	private static $month_names = array(
		'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
		'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
	);

	// ---------------------------------------------------------------------------------------------
	// Legacy row -> CSL
	// ---------------------------------------------------------------------------------------------

	/**
	 * @param array      $row          legacy flat row (form post, import parser output, or a pre-CSL db row)
	 * @param array|null $author_rows  citation_authors rows (cid, fname, lname, initial, author_type) ordered by id;
	 *                                 when NULL the names come from $row['authors'|'editors'|'translators']
	 *                                 (arrays of {fname,lname,initial} or their JSON text)
	 * @return array CSL document
	 */
	public function from_legacy_row(array $row, $author_rows = NULL)
	{
		$ctype = strtolower($this->str($row, 'ctype'));
		$doc = array();
		$nada = array();

		if (isset(self::$type_map[$ctype])) {
			$doc['type'] = self::$type_map[$ctype];
			$nada['legacy_ctype'] = $ctype;
		} elseif (in_array($ctype, self::$csl_types, true)) {
			$doc['type'] = $ctype;
		} else {
			$doc['type'] = 'book';
			$nada['legacy_ctype'] = 'book';
			if ($ctype !== '') {
				$nada['unknown_ctype'] = $ctype;
			}
		}
		$legacy = isset($nada['legacy_ctype']) ? $nada['legacy_ctype'] : $ctype;

		// title / subtitle
		$title = $this->str($row, 'title');
		$subtitle = $this->str($row, 'subtitle');
		if ($subtitle !== '') {
			if (in_array($legacy, self::$container_ctypes, true)) {
				$doc['container-title'] = $subtitle;
			} elseif ($legacy === 'thesis') {
				$doc['genre'] = $subtitle;
			} else {
				$title = ($title === '') ? $subtitle : $title . ': ' . $subtitle;
				$nada['subtitle'] = $subtitle;
			}
		}
		$doc['title'] = $title;

		if ($this->str($row, 'alt_title') !== '') {
			$doc['original-title'] = $this->str($row, 'alt_title');
		}

		// contributors
		$names = $this->legacy_names($row, $author_rows);
		foreach (array('author' => 'author', 'editor' => 'editor', 'translator' => 'translator') as $role => $csl_role) {
			if (!empty($names[$role])) {
				$doc[$csl_role] = $names[$role];
			}
		}

		// organization
		$organization = $this->str($row, 'organization');
		$publisher = $this->str($row, 'publisher');
		if ($organization !== '') {
			if ($legacy === 'corporate-author') {
				$doc['author'] = array_merge(array(array('literal' => $organization)), isset($doc['author']) ? $doc['author'] : array());
				$nada['organization_author'] = true;
			} elseif (in_array($legacy, self::$organization_publisher_ctypes, true) && $publisher === '') {
				$publisher = $organization;
				$nada['organization_publisher'] = true;
			} else {
				$doc['authority'] = $organization;
			}
		}
		if ($publisher !== '') {
			$doc['publisher'] = $publisher;
		}

		// place
		$place = $this->str($row, 'place_publication');
		$state = $this->str($row, 'place_state');
		if ($place !== '' || $state !== '') {
			$doc['publisher-place'] = ($place !== '' && $state !== '') ? $place . ', ' . $state : ($place !== '' ? $place : $state);
			if ($state !== '') {
				$nada['place_state'] = $state;
				if ($place === '') {
					$nada['place_state_only'] = true;
				}
			}
		}

		foreach (array('volume' => 'volume', 'issue' => 'issue', 'edition' => 'edition', 'idnumber' => 'number') as $from => $to) {
			if ($this->str($row, $from) !== '') {
				$doc[$to] = $this->str($row, $from);
			}
		}

		// pages
		$page_from = $this->str($row, 'page_from');
		$page_to = $this->str($row, 'page_to');
		if ($page_from !== '' || $page_to !== '') {
			$doc['page'] = ($page_from !== '' && $page_to !== '') ? $page_from . '-' . $page_to : ($page_from !== '' ? $page_from : $page_to);
			if ($page_from === '' || strpos($page_from, '-') !== false || strpos($page_to, '-') !== false) {
				$nada['page_from'] = $page_from;
				$nada['page_to'] = $page_to;
			}
		}

		// issued date
		$issued = $this->legacy_date($this->str($row, 'pub_year'), $this->str($row, 'pub_month'), $this->str($row, 'pub_day'), $nada);
		if ($issued) {
			$doc['issued'] = $issued;
		}

		// accessed date (kept as raw text plus parsed parts when possible)
		$accessed = $this->str($row, 'data_accessed');
		if ($accessed !== '') {
			$doc['accessed'] = array('raw' => $accessed);
			$ts = strtotime(str_replace('/', '-', $accessed));
			if ($ts !== false) {
				$doc['accessed']['date-parts'] = array(array((int) date('Y', $ts), (int) date('n', $ts), (int) date('j', $ts)));
			}
		}

		if ($this->str($row, 'url') !== '') {
			$doc['URL'] = $this->str($row, 'url');
		}
		if ($this->str($row, 'doi') !== '') {
			$doc['DOI'] = $this->normalize_doi($this->str($row, 'doi'));
			if ($doc['DOI'] !== $this->str($row, 'doi')) {
				$nada['doi_raw'] = $this->str($row, 'doi');
			}
		}
		if ($this->str($row, 'keywords') !== '') {
			$doc['keyword'] = $this->str($row, 'keywords');
		}
		if ($this->str($row, 'abstract') !== '') {
			$doc['abstract'] = $this->str($row, 'abstract');
		}

		// language
		$lang = $this->str($row, 'lang');
		if ($lang !== '') {
			$code = $this->language_code($lang);
			if ($code !== '') {
				$doc['language'] = $code;
				if ($this->language_label($code) !== $lang) {
					$nada['language_label'] = $lang;
				}
			} else {
				$nada['language_label'] = $lang;
			}
		}

		if ((string) $this->str($row, 'publication_medium') === '1') {
			$doc['medium'] = 'online';
		}

		if ($this->str($row, 'country') !== '') {
			$nada['country'] = $this->str($row, 'country');
		}

		if (!empty($nada)) {
			$doc['custom'] = array('nada' => $nada);
		}

		return $this->normalize($doc);
	}

	// ---------------------------------------------------------------------------------------------
	// CSL -> legacy row
	// ---------------------------------------------------------------------------------------------

	/**
	 * @param array $doc CSL document
	 * @return array legacy flat row: every field in $legacy_fields (strings, '' when empty) and
	 *               authors/editors/translators as [{fname, lname, initial}]
	 */
	public function to_legacy_row(array $doc)
	{
		$nada = isset($doc['custom']['nada']) && is_array($doc['custom']['nada']) ? $doc['custom']['nada'] : array();
		$ctype = $this->legacy_ctype($doc);

		$out = array_fill_keys(self::$legacy_fields, '');
		$out['ctype'] = $ctype;
		$out['publication_medium'] = (isset($doc['medium']) && $doc['medium'] === 'online') ? 1 : 0;

		// title / subtitle
		$title = $this->str($doc, 'title');
		if (isset($nada['subtitle']) && $nada['subtitle'] !== '') {
			$suffix = ': ' . $nada['subtitle'];
			if ($title === $nada['subtitle']) {
				$title = '';
			} elseif (substr($title, -strlen($suffix)) === $suffix) {
				$title = substr($title, 0, -strlen($suffix));
			}
			$out['subtitle'] = $nada['subtitle'];
		} elseif (in_array($ctype, self::$container_ctypes, true)) {
			$out['subtitle'] = $this->str($doc, 'container-title');
		} elseif ($ctype === 'thesis') {
			$out['subtitle'] = $this->str($doc, 'genre');
		}
		$out['title'] = $title;
		$out['alt_title'] = $this->str($doc, 'original-title');

		// contributors
		$authors = isset($doc['author']) && is_array($doc['author']) ? array_values($doc['author']) : array();
		if (!empty($nada['organization_author']) && !empty($authors) && isset($authors[0]['literal'])) {
			$out['organization'] = (string) $authors[0]['literal'];
			array_shift($authors);
		}
		$out['authors'] = $this->names_to_legacy($authors);
		$out['editors'] = $this->names_to_legacy(isset($doc['editor']) ? $doc['editor'] : array());
		$out['translators'] = $this->names_to_legacy(isset($doc['translator']) ? $doc['translator'] : array());

		// organization / publisher
		$publisher = $this->str($doc, 'publisher');
		if (!empty($nada['organization_publisher'])) {
			$out['organization'] = $publisher;
			$publisher = '';
		} elseif ($this->str($doc, 'authority') !== '') {
			$out['organization'] = $this->str($doc, 'authority');
		}
		$out['publisher'] = $publisher;

		// place
		$place = $this->str($doc, 'publisher-place');
		if (isset($nada['place_state']) && $nada['place_state'] !== '') {
			$state = $nada['place_state'];
			if (!empty($nada['place_state_only'])) {
				$out['place_state'] = $state;
				$place = '';
			} elseif (substr($place, -strlen(', ' . $state)) === ', ' . $state) {
				$out['place_state'] = $state;
				$place = substr($place, 0, -strlen(', ' . $state));
			}
		}
		$out['place_publication'] = $place;

		$out['volume'] = $this->str($doc, 'volume');
		$out['issue'] = $this->str($doc, 'issue');
		$out['edition'] = $this->str($doc, 'edition');
		$out['idnumber'] = $this->str($doc, 'number');

		// pages
		if (array_key_exists('page_from', $nada) || array_key_exists('page_to', $nada)) {
			$out['page_from'] = isset($nada['page_from']) ? (string) $nada['page_from'] : '';
			$out['page_to'] = isset($nada['page_to']) ? (string) $nada['page_to'] : '';
		} else {
			$page = $this->str($doc, 'page');
			if ($page !== '') {
				$parts = preg_split('/\s*[-\x{2013}]+\s*/u', $page, 2);
				$out['page_from'] = $parts[0];
				$out['page_to'] = isset($parts[1]) ? $parts[1] : '';
			}
		}

		// issued
		$parts = $this->date_parts(isset($doc['issued']) ? $doc['issued'] : NULL);
		$out['pub_year'] = isset($parts[0]) ? (string) $parts[0] : '';
		$out['pub_month'] = isset($parts[1]) ? (string) $parts[1] : '';
		$out['pub_day'] = isset($parts[2]) ? (string) $parts[2] : '';
		if (isset($nada['pub_year'])) {
			$out['pub_year'] = (string) $nada['pub_year'];
		}
		if (isset($nada['pub_month'])) {
			$out['pub_month'] = (string) $nada['pub_month'];
		}
		if (isset($nada['pub_day'])) {
			$out['pub_day'] = (string) $nada['pub_day'];
		}

		// accessed
		if (isset($doc['accessed']['raw'])) {
			$out['data_accessed'] = (string) $doc['accessed']['raw'];
		} else {
			$acc = $this->date_parts(isset($doc['accessed']) ? $doc['accessed'] : NULL);
			if ($acc) {
				$out['data_accessed'] = implode('-', array_map(function ($v) { return sprintf('%02d', $v); }, $acc));
			}
		}

		$out['url'] = $this->str($doc, 'URL');
		$out['doi'] = isset($nada['doi_raw']) ? (string) $nada['doi_raw'] : $this->str($doc, 'DOI');
		$out['keywords'] = $this->str($doc, 'keyword');
		$out['abstract'] = $this->str($doc, 'abstract');

		if (isset($nada['language_label'])) {
			$out['lang'] = (string) $nada['language_label'];
		} elseif ($this->str($doc, 'language') !== '') {
			$label = $this->language_label($this->str($doc, 'language'));
			$out['lang'] = $label !== '' ? $label : $this->str($doc, 'language');
		}

		$out['country'] = isset($nada['country']) ? (string) $nada['country'] : '';

		return $out;
	}

	/**
	 * NADA citation type for a CSL document: the stored legacy type when there is one, else inferred.
	 */
	public function legacy_ctype(array $doc)
	{
		if (isset($doc['custom']['nada']['legacy_ctype']) && isset(self::$type_map[$doc['custom']['nada']['legacy_ctype']])) {
			return $doc['custom']['nada']['legacy_ctype'];
		}

		$type = $this->str($doc, 'type');
		switch ($type) {
			case 'article-journal':
			case 'article':           return 'journal';
			case 'chapter':           return 'book-section';
			case 'paper-conference':  return 'conference-paper';
			case 'article-magazine':  return 'magazine';
			case 'article-newspaper': return 'newspaper';
			case 'webpage':
			case 'post':
			case 'post-weblog':       return 'website';
			case 'document':          return 'website-doc';
			case 'thesis':            return 'thesis';
			case 'book':
				if (empty($doc['author']) && !empty($doc['editor'])) {
					return 'anthology-editor';
				}
				if (!empty($doc['translator'])) {
					return 'anthology-translator';
				}
				return 'book';
			case 'report':
			case 'dataset':
			case 'software':
				if ($type === 'report' && preg_match('/working paper/i', $this->str($doc, 'genre'))) {
					return 'working-paper';
				}
				if ($type === 'report' && !empty($doc['author']) && $this->all_literal($doc['author'])) {
					return 'corporate-author';
				}
				return 'report';
			default:
				return 'report';
		}
	}

	/**
	 * CSL types for a list of type names that may be legacy NADA types or CSL types.
	 */
	public static function to_csl_types($types)
	{
		$out = array();
		foreach ((array) $types as $type) {
			$type = strtolower(trim((string) $type));
			if ($type === '') {
				continue;
			}
			if (isset(self::$type_map[$type])) {
				$out[] = self::$type_map[$type];
			} elseif (in_array($type, self::$csl_types, true)) {
				$out[] = $type;
			}
		}
		return array_values(array_unique($out));
	}

	// ---------------------------------------------------------------------------------------------
	// Columns copied from the document
	// ---------------------------------------------------------------------------------------------

	/**
	 * @return array values for the projected columns of the citations table
	 */
	public function project_columns(array $doc)
	{
		$parts = $this->date_parts(isset($doc['issued']) ? $doc['issued'] : NULL);
		$language = strtolower($this->str($doc, 'language'));
		if ($language !== '') {
			$language = preg_split('/[-_]/', $language)[0];
		}

		$authors_text = $this->authors_text($doc);

		$search = array();
		foreach (array('title', 'original-title', 'container-title', 'collection-title', 'publisher',
			'publisher-place', 'authority', 'genre', 'abstract', 'keyword', 'DOI', 'number') as $field) {
			if ($this->str($doc, $field) !== '') {
				$search[] = $this->str($doc, $field);
			}
		}
		if ($authors_text !== '') {
			$search[] = $authors_text;
		}

		return array(
			'ctype'           => $this->str($doc, 'type') !== '' ? $this->str($doc, 'type') : 'book',
			'title'           => $this->truncate($this->str($doc, 'title'), self::TITLE_MAX),
			'container_title' => $this->truncate($this->str($doc, 'container-title'), self::CONTAINER_TITLE_MAX),
			'authors_text'    => $authors_text,
			'search_text'     => implode(' ', $search),
			'pub_year'        => (isset($parts[0]) && $parts[0] > 0) ? (int) $parts[0] : NULL,
			'doi'             => $this->truncate($this->str($doc, 'DOI'), self::DOI_MAX),
			'url'             => $this->truncate($this->str($doc, 'URL'), self::URL_MAX),
			'lang'            => substr($language, 0, 10),
		);
	}

	/**
	 * "Smith, John; World Bank; Doe, A. (ed.)" — authors, then editors and translators with a role suffix.
	 */
	public function authors_text(array $doc)
	{
		$parts = array();
		$authors = $this->format_names($doc, 'author');
		if ($authors !== '') {
			$parts[] = $authors;
		}
		$editors = $this->format_names($doc, 'editor');
		if ($editors !== '') {
			$parts[] = $editors . ' (ed.)';
		}
		$translators = $this->format_names($doc, 'translator');
		if ($translators !== '') {
			$parts[] = $translators . ' (trans.)';
		}
		return implode('; ', $parts);
	}

	public function format_names(array $doc, $role = 'author')
	{
		if (empty($doc[$role]) || !is_array($doc[$role])) {
			return '';
		}
		$names = array();
		foreach ($doc[$role] as $name) {
			if (!is_array($name)) {
				continue;
			}
			if (isset($name['literal']) && trim((string) $name['literal']) !== '') {
				$names[] = trim((string) $name['literal']);
				continue;
			}
			$family = isset($name['family']) ? trim((string) $name['family']) : '';
			$given = isset($name['given']) ? trim((string) $name['given']) : '';
			if ($family !== '' && $given !== '') {
				$names[] = $family . ', ' . $given;
			} elseif ($family . $given !== '') {
				$names[] = $family . $given;
			}
		}
		return implode('; ', $names);
	}

	// ---------------------------------------------------------------------------------------------
	// Normalize / validate
	// ---------------------------------------------------------------------------------------------

	/**
	 * Trim strings, drop empty values, normalize DOI, names and dates, default the type.
	 */
	public function normalize(array $doc)
	{
		unset($doc['id']);

		foreach ($doc as $key => $value) {
			if (in_array($key, array('author', 'editor', 'translator', 'container-author', 'collection-editor',
				'composer', 'director', 'interviewer', 'illustrator', 'original-author', 'recipient', 'reviewed-author'), true)) {
				$doc[$key] = $this->normalize_names($value);
			} elseif (in_array($key, array('issued', 'accessed', 'event-date', 'original-date', 'submitted'), true)) {
				$doc[$key] = $this->normalize_date($value);
			} elseif ($key === 'custom') {
				$doc[$key] = is_array($value) ? $this->drop_empty($value) : array();
			} elseif (is_string($value) || is_numeric($value)) {
				$doc[$key] = trim((string) $value);
			}
		}

		if (isset($doc['DOI'])) {
			$doc['DOI'] = $this->normalize_doi($doc['DOI']);
		}

		if (!isset($doc['type']) || !in_array($doc['type'], self::$csl_types, true)) {
			$doc['type'] = isset($doc['type']) && isset(self::$type_map[$doc['type']]) ? self::$type_map[$doc['type']] : 'book';
		}

		// the stored NADA type is only a hint: drop it when the type was changed to something it doesn't map to
		if (isset($doc['custom']['nada']['legacy_ctype'])) {
			$hint = $doc['custom']['nada']['legacy_ctype'];
			if (!isset(self::$type_map[$hint]) || self::$type_map[$hint] !== $doc['type']) {
				unset($doc['custom']['nada']['legacy_ctype']);
			}
		}

		$doc = $this->drop_empty($doc);
		if (!isset($doc['title'])) {
			$doc['title'] = '';
		}

		// stable key order: type, title first
		$ordered = array('type' => $doc['type'], 'title' => $doc['title']);
		foreach ($doc as $key => $value) {
			if ($key !== 'type' && $key !== 'title') {
				$ordered[$key] = $value;
			}
		}
		return $ordered;
	}

	/**
	 * Structural checks (the JSON schema in application/schemas/citation-csl-schema.json covers the rest).
	 *
	 * @return array field => message; empty when valid
	 */
	public function validate(array $doc)
	{
		$errors = array();

		if (!isset($doc['type']) || !in_array($doc['type'], self::$csl_types, true)) {
			$errors['type'] = 'INVALID_TYPE';
		}
		if (!isset($doc['title']) || trim((string) $doc['title']) === '') {
			$errors['title'] = 'TITLE_REQUIRED';
		} elseif (function_exists('mb_strlen') ? mb_strlen($doc['title']) > self::TITLE_MAX : strlen($doc['title']) > self::TITLE_MAX) {
			$errors['title'] = 'TITLE_TOO_LONG';
		}
		foreach (array('author', 'editor', 'translator') as $role) {
			if (isset($doc[$role]) && !is_array($doc[$role])) {
				$errors[$role] = 'NAMES_MUST_BE_A_LIST';
			}
		}
		if (isset($doc['URL']) && strlen($doc['URL']) > self::URL_MAX) {
			$errors['URL'] = 'URL_TOO_LONG';
		}
		if (isset($doc['issued']) && !$this->date_parts($doc['issued']) && empty($doc['issued']['raw']) && empty($doc['issued']['literal'])) {
			$errors['issued'] = 'INVALID_DATE';
		}

		return $errors;
	}

	/**
	 * True when the body is a legacy flat citation (has ctype / fname-style authors) rather than CSL.
	 */
	public function is_legacy_input(array $body)
	{
		if (isset($body['metadata']) && is_array($body['metadata'])) {
			return false;
		}
		if (isset($body['type']) && in_array($body['type'], self::$csl_types, true) && !isset($body['ctype'])) {
			return false;
		}
		return true;
	}

	public function normalize_doi($doi)
	{
		$doi = trim((string) $doi);
		$doi = preg_replace('#^(https?://)?(dx\.)?doi\.org/#i', '', $doi);
		$doi = preg_replace('#^doi:\s*#i', '', $doi);
		return trim($doi);
	}

	/**
	 * ISO 639-1 code for a language name or code, '' when unknown.
	 */
	public function language_code($lang)
	{
		$lang = trim((string) $lang);
		if ($lang === '') {
			return '';
		}
		foreach (self::$lang_codes as $name => $code) {
			if (strcasecmp(trim($name), $lang) === 0) {
				return $code;
			}
		}
		if (preg_match('/^[a-z]{2,3}([-_][A-Za-z0-9]{2,8})*$/', $lang)) {
			return $lang;
		}
		return '';
	}

	/**
	 * Legacy form label for an ISO code ('' when the form has no such language).
	 */
	public function language_label($code)
	{
		$code = strtolower(preg_split('/[-_]/', trim((string) $code))[0]);
		$name = array_search($code, self::$lang_codes, true);
		return $name === false ? '' : $name;
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	private function str(array $arr, $key)
	{
		if (!isset($arr[$key]) || is_array($arr[$key]) || is_object($arr[$key])) {
			return '';
		}
		return trim((string) $arr[$key]);
	}

	private function truncate($value, $max)
	{
		if ($value === '') {
			return '';
		}
		return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
	}

	/**
	 * Names by role from citation_authors rows or from the row's author fields.
	 */
	private function legacy_names(array $row, $author_rows)
	{
		$out = array('author' => array(), 'editor' => array(), 'translator' => array());

		if (is_array($author_rows)) {
			foreach ($author_rows as $a) {
				$a = (array) $a;
				$role = isset($a['author_type']) ? $a['author_type'] : 'author';
				if (!isset($out[$role])) {
					continue;
				}
				$name = $this->legacy_name_to_csl($a);
				if ($name) {
					$out[$role][] = $name;
				}
			}
			return $out;
		}

		foreach (array('author' => 'authors', 'editor' => 'editors', 'translator' => 'translators') as $role => $field) {
			if (!isset($row[$field])) {
				continue;
			}
			$list = $row[$field];
			if (is_string($list)) {
				$decoded = json_decode($list, true);
				$list = is_array($decoded) ? $decoded : array();
			}
			if (!is_array($list)) {
				continue;
			}
			foreach ($list as $a) {
				$name = $this->legacy_name_to_csl((array) $a);
				if ($name) {
					$out[$role][] = $name;
				}
			}
		}
		return $out;
	}

	/**
	 * {fname, lname, initial} -> {family, given} or {literal} (no first name and no initial: an organization).
	 */
	private function legacy_name_to_csl(array $a)
	{
		$fname = isset($a['fname']) ? trim((string) $a['fname']) : '';
		$lname = isset($a['lname']) ? trim((string) $a['lname']) : '';
		$initial = isset($a['initial']) ? trim((string) $a['initial']) : '';

		if ($fname === '' && $lname === '' && $initial === '') {
			return NULL;
		}
		if ($fname === '' && $initial === '') {
			return array('literal' => $lname);
		}

		$name = array();
		if ($lname !== '') {
			$name['family'] = $lname;
		}
		$given = trim($fname . ' ' . $initial);
		if ($given !== '') {
			$name['given'] = $given;
		}
		return $name;
	}

	private function names_to_legacy($names)
	{
		$out = array();
		foreach ((array) $names as $n) {
			if (!is_array($n)) {
				continue;
			}
			if (isset($n['literal'])) {
				$out[] = array('fname' => '', 'lname' => (string) $n['literal'], 'initial' => '');
				continue;
			}
			// "John A" / "John A." -> fname John, initial A (the legacy form keeps the middle initial apart)
			$given = isset($n['given']) ? trim((string) $n['given']) : '';
			$initial = '';
			if (preg_match('/^(.+?)\s+(\p{L}\.?)$/u', $given, $m)) {
				$given = $m[1];
				$initial = $m[2];
			}
			$out[] = array(
				'fname'   => $given,
				'lname'   => isset($n['family']) ? (string) $n['family'] : '',
				'initial' => $initial,
			);
		}
		return $out;
	}

	private function normalize_names($names)
	{
		if (!is_array($names)) {
			return array();
		}
		$out = array();
		foreach ($names as $n) {
			if (is_string($n)) {
				$n = array('literal' => $n);
			}
			if (!is_array($n)) {
				continue;
			}
			$clean = array();
			foreach ($n as $k => $v) {
				if (is_string($v) || is_numeric($v)) {
					$v = trim((string) $v);
					if ($v !== '') {
						$clean[$k] = $v;
					}
				} elseif (is_bool($v)) {
					$clean[$k] = $v;
				}
			}
			if (isset($clean['literal']) || isset($clean['family']) || isset($clean['given'])) {
				$out[] = $clean;
			}
		}
		return $out;
	}

	private function all_literal(array $names)
	{
		foreach ($names as $n) {
			if (!is_array($n) || !isset($n['literal'])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * CSL date object from legacy year/month/day text. Values that don't survive as integers are kept
	 * in $nada so to_legacy_row() returns the original text.
	 */
	private function legacy_date($year, $month, $day, array &$nada)
	{
		if ($year === '' && $month === '' && $day === '') {
			return NULL;
		}

		$y = preg_match('/^\d{1,4}$/', $year) ? (int) $year : 0;
		$m = $this->parse_month($month);
		$d = preg_match('/^\d{1,2}$/', $day) ? (int) $day : 0;
		if ($d < 1 || $d > 31) {
			$d = 0;
		}

		if ($year !== '' && (string) $y !== $year) {
			$nada['pub_year'] = $year;
		}
		if ($month !== '' && (string) $m !== $month) {
			$nada['pub_month'] = $month;
		}
		if ($day !== '' && (string) $d !== $day) {
			$nada['pub_day'] = $day;
		}

		if ($y <= 0) {
			// no usable year: no date-parts, so keep every part as given
			foreach (array('pub_year' => $year, 'pub_month' => $month, 'pub_day' => $day) as $k => $v) {
				if ($v !== '') {
					$nada[$k] = $v;
				}
			}
			$raw = trim($year . ' ' . $month . ' ' . $day);
			return $raw === '' ? NULL : array('raw' => $raw);
		}

		$parts = array($y);
		if ($m > 0) {
			$parts[] = $m;
			if ($d > 0) {
				$parts[] = $d;
			}
		} elseif ($day !== '') {
			// a day without a usable month can't go in date-parts
			$nada['pub_day'] = $day;
		}
		return array('date-parts' => array($parts));
	}

	private function parse_month($month)
	{
		$month = trim((string) $month);
		if ($month === '') {
			return 0;
		}
		if (preg_match('/^\d{1,2}$/', $month)) {
			$m = (int) $month;
			return ($m >= 1 && $m <= 12) ? $m : 0;
		}
		$key = strtolower(substr($month, 0, 3));
		return isset(self::$month_names[$key]) ? self::$month_names[$key] : 0;
	}

	/**
	 * [year, month?, day?] as ints from a CSL date object; empty array when there are none.
	 */
	public function date_parts($date)
	{
		if (!is_array($date) || !isset($date['date-parts'][0]) || !is_array($date['date-parts'][0])) {
			return array();
		}
		$parts = array();
		foreach (array_slice($date['date-parts'][0], 0, 3) as $p) {
			if (!is_numeric($p)) {
				break;
			}
			$parts[] = (int) $p;
		}
		return $parts;
	}

	private function normalize_date($date)
	{
		if (is_string($date) || is_numeric($date)) {
			$date = trim((string) $date);
			if (preg_match('/^(\d{4})(?:-(\d{1,2}))?(?:-(\d{1,2}))?$/', $date, $m)) {
				$parts = array((int) $m[1]);
				if (!empty($m[2])) {
					$parts[] = (int) $m[2];
					if (!empty($m[3])) {
						$parts[] = (int) $m[3];
					}
				}
				return array('date-parts' => array($parts));
			}
			return $date === '' ? array() : array('raw' => $date);
		}
		if (!is_array($date)) {
			return array();
		}
		$out = array();
		if (isset($date['date-parts']) && is_array($date['date-parts'])) {
			$ranges = array();
			foreach ($date['date-parts'] as $range) {
				if (!is_array($range)) {
					continue;
				}
				$r = array();
				foreach (array_slice($range, 0, 3) as $p) {
					if (!is_numeric($p) || (int) $p === 0) {
						break;
					}
					$r[] = (int) $p;
				}
				if ($r) {
					$ranges[] = $r;
				}
			}
			if ($ranges) {
				$out['date-parts'] = $ranges;
			}
		}
		foreach (array('raw', 'literal', 'season', 'circa') as $k) {
			if (isset($date[$k]) && $date[$k] !== '' && $date[$k] !== NULL) {
				$out[$k] = is_bool($date[$k]) ? $date[$k] : trim((string) $date[$k]);
			}
		}
		return $out;
	}

	private function drop_empty(array $arr)
	{
		foreach ($arr as $k => $v) {
			if (is_array($v)) {
				$v = $this->drop_empty($v);
				if ($v === array()) {
					unset($arr[$k]);
				} else {
					$arr[$k] = $v;
				}
			} elseif ($v === NULL || $v === '') {
				unset($arr[$k]);
			}
		}
		return $arr;
	}
}

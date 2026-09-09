<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * RDF Parser Class
 *
 * Parses Dublin Core RDF/XML (Nesstar/NADA resource files) into positional
 * arrays matching $this->fields.
 *
 * @package		NADA
 * @subpackage	Libraries
 * @category	RDF Parser
 */
class RDF_Parser{

	const NS_RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	const NS_DC = 'http://purl.org/dc/elements/1.1/';
	const NS_DCTERMS = 'http://purl.org/dc/terms/';

	// List of study fields in the order returned by parse()
	var $fields=array
			(
				'title'=>0,
				'author'=>1,
				'dcdate'=>2,
				'country'=>3,
				'language'=>4,
				'contributor'=>5,
				'publisher'=>6,
				'description'=>7,
				'abstract'=>8,
				'toc'=>9,
				'filename'=>10,
				'format'=>11,
				'type'=>12,
				'subtitle'=>13
			);

	/**
	 * Import RDF into an array of positional rows.
	 *
	 * @param string $rdf_str RDF/XML document
	 * @return array|null
	 **/
	function parse($rdf_str)
	{
		if ($rdf_str === null || $rdf_str === '') {
			return NULL;
		}

		if (strncmp($rdf_str, "\xEF\xBB\xBF", 3) === 0) {
			$rdf_str = substr($rdf_str, 3);
		}

		$previous = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($rdf_str, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if ($xml === false) {
			return NULL;
		}

		$xml->registerXPathNamespace('rdf', self::NS_RDF);
		$descriptions = $xml->xpath('//rdf:Description');
		if ($descriptions === false || $descriptions === null) {
			return NULL;
		}

		$result = array();
		foreach ($descriptions as $desc) {
			$row = $this->parse_description($desc);
			if ($row === null) {
				continue;
			}
			$result[] = $row;
		}

		if (empty($result)) {
			return NULL;
		}

		return $result;
	}

	/**
	 * @param SimpleXMLElement $desc
	 * @return array|null
	 */
	private function parse_description($desc)
	{
		$dc = $desc->children(self::NS_DC);
		$dcterms = $desc->children(self::NS_DCTERMS);

		$title = $this->normalize_space((string) $dc->title);
		$filename = $this->limit($this->normalize_space($this->rdf_about($desc)), 255);

		if ($title === '' && $filename === '') {
			return NULL;
		}

		return array(
			$title,
			$this->limit($this->normalize_space($this->join_elements($dc->creator)), 254),
			$this->limit($this->normalize_space((string) $dcterms->created), 25),
			$this->limit($this->normalize_space((string) $dcterms->spatial), 100),
			$this->limit($this->normalize_space($this->join_elements($dc->language)), 50),
			$this->limit($this->normalize_space($this->join_elements($dc->contributor)), 254),
			$this->limit($this->normalize_space($this->join_elements($dc->publisher)), 254),
			(string) $dc->description,
			(string) $dcterms->abstract,
			(string) $dcterms->tableOfContents,
			$filename,
			$this->limit($this->normalize_space((string) $dc->format), 255),
			$this->limit($this->normalize_space((string) $dc->type), 255),
			$this->normalize_space((string) $dcterms->alternative),
		);
	}

	/**
	 * @param SimpleXMLElement $desc
	 * @return string
	 */
	private function rdf_about($desc)
	{
		$attrs = $desc->attributes(self::NS_RDF);
		if (isset($attrs['about'])) {
			return (string) $attrs['about'];
		}
		if (isset($desc['about'])) {
			return (string) $desc['about'];
		}
		return '';
	}

	/**
	 * @param SimpleXMLElement|null $nodes
	 * @return string
	 */
	private function join_elements($nodes)
	{
		if ($nodes === null) {
			return '';
		}

		$parts = array();
		foreach ($nodes as $node) {
			$value = (string) $node;
			if ($value !== '') {
				$parts[] = $value;
			}
		}

		return implode(', ', $parts);
	}

	/**
	 * @param string $value
	 * @return string
	 */
	private function normalize_space($value)
	{
		$normalized = preg_replace('/\s+/u', ' ', (string) $value);
		if ($normalized === null) {
			$normalized = preg_replace('/\s+/', ' ', (string) $value);
		}
		return trim((string) $normalized);
	}

	/**
	 * @param string $value
	 * @param int $max
	 * @return string
	 */
	private function limit($value, $max)
	{
		if (function_exists('mb_substr')) {
			return mb_substr($value, 0, $max, 'UTF-8');
		}
		return substr($value, 0, $max);
	}

}
// END RDF Parser Class

/* End of file RDF_Parser.php */
/* Location: ./application/libraries/RDF_Parser.php */

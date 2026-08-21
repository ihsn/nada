<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Prepare catalog display-template HTML for mPDF.
 *
 * Nested tables and block markup inside spans corrupt mPDF's serialized
 * inline objects (unserialize extra-data warnings in Mpdf::_getObjAttr).
 */

if (!function_exists('pdf_prepare_mpdf_html')) {
	/**
	 * @param string $html
	 * @return string
	 */
	function pdf_prepare_mpdf_html($html)
	{
		if (!is_string($html) || $html === '') {
			return '';
		}

		$html = preg_replace(
			'#<(script|style|noscript|iframe|object|embed|svg|canvas|video|audio)(\s[^>]*)?>.*?</\1>#is',
			'',
			$html
		);
		$html = preg_replace(
			'#<(script|style|noscript|iframe|object|embed|svg|canvas|video|audio|img|link|source)(\s[^>]*)?/?>#is',
			'',
			$html
		);
		$html = preg_replace('#<i\s+class="[^"]*\bfa[srlb]?[\s"][^>]*>\s*</i>#i', '', $html);

		if (!is_string($html) || $html === '' || !class_exists('DOMDocument')) {
			return is_string($html) ? $html : '';
		}

		$previous = libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$wrapped = '<div id="nada-pdf-root">' . $html . '</div>';
		$loaded = $dom->loadHTML(
			'<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'
			. $wrapped
			. '</body></html>',
			LIBXML_HTML_NODEFDTD
		);

		if ($loaded) {
			$xpath = new DOMXPath($dom);
			$roots = $xpath->query('//*[@id="nada-pdf-root"]');
			$root = ($roots && $roots->length > 0) ? $roots->item(0) : null;
			if ($root instanceof DOMElement) {
				pdf_html_promote_block_spans($dom, $root);
				pdf_html_flatten_nested_tables($dom, $root);
				$html = '';
				foreach ($root->childNodes as $child) {
					$html .= $dom->saveHTML($child);
				}
			}
		}

		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		return is_string($html) ? $html : '';
	}
}

if (!function_exists('pdf_html_promote_block_spans')) {
	/**
	 * @param DOMDocument $dom
	 * @param DOMElement $root
	 * @return void
	 */
	function pdf_html_promote_block_spans(DOMDocument $dom, DOMElement $root)
	{
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query(
			'.//span[p or div or table or ul or ol or h1 or h2 or h3 or h4 or h5 or h6 or blockquote or pre]',
			$root
		);
		if (!$nodes || $nodes->length === 0) {
			return;
		}

		$spans = array();
		foreach ($nodes as $node) {
			$spans[] = $node;
		}

		foreach ($spans as $span) {
			if (!$span->parentNode) {
				continue;
			}
			$div = $dom->createElement('div');
			if ($span->hasAttribute('class')) {
				$div->setAttribute('class', $span->getAttribute('class'));
			}
			while ($span->firstChild) {
				$div->appendChild($span->firstChild);
			}
			$span->parentNode->replaceChild($div, $span);
		}
	}
}

if (!function_exists('pdf_html_flatten_nested_tables')) {
	/**
	 * @param DOMDocument $dom
	 * @param DOMElement $root
	 * @return void
	 */
	function pdf_html_flatten_nested_tables(DOMDocument $dom, DOMElement $root)
	{
		$guard = 30;
		while ($guard-- > 0) {
			$xpath = new DOMXPath($dom);
			$nodes = $xpath->query('.//td//table[not(.//table)] | .//th//table[not(.//table)]', $root);
			if (!$nodes || $nodes->length === 0) {
				break;
			}

			$tables = array();
			foreach ($nodes as $node) {
				$tables[] = $node;
			}

			foreach ($tables as $table) {
				if (!$table->parentNode) {
					continue;
				}
				$table->parentNode->replaceChild(pdf_html_table_to_div($dom, $table), $table);
			}
		}
	}
}

if (!function_exists('pdf_html_table_to_div')) {
	/**
	 * @param DOMDocument $dom
	 * @param DOMElement $table
	 * @return DOMElement
	 */
	function pdf_html_table_to_div(DOMDocument $dom, DOMElement $table)
	{
		$wrap = $dom->createElement('div');
		$wrap->setAttribute('class', 'pdf-nested-array');

		$rows = array();
		foreach ($table->getElementsByTagName('tr') as $row) {
			$rows[] = $row;
		}

		foreach ($rows as $row) {
			$parts = array();
			foreach ($row->childNodes as $cell) {
				if ($cell->nodeType !== XML_ELEMENT_NODE) {
					continue;
				}
				$name = strtolower($cell->nodeName);
				if ($name !== 'td' && $name !== 'th') {
					continue;
				}
				$txt = trim(preg_replace('/\s+/u', ' ', $cell->textContent));
				if ($txt !== '') {
					$parts[] = $txt;
				}
			}
			if (count($parts) === 0) {
				continue;
			}
			$line = $dom->createElement('div');
			$line->setAttribute('class', 'pdf-nested-row');
			$line->appendChild($dom->createTextNode(implode(' — ', $parts)));
			$wrap->appendChild($line);
		}

		return $wrap;
	}
}

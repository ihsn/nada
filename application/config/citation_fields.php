<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Citation types the admin form (api/admin/citations/options) offers, and the fields each one shows.
 *
 * Keys are CSL types (citations.ctype). Field names are CSL variables of the document in citations.metadata;
 * 'issued' and 'accessed' are dates. 'roles' are the contributor lists the type has.
 * Labels are language keys (application/language/english/citations_lang.php): "cit_f_<field>" or, when the
 * label depends on the type, "cit_f_<field>_<type>".
 *
 * Title, abstract, keyword and language are on every type (see $config['citation_common_fields']).
 * Other CSL types can still be stored through the API; types without an entry here use a generic field set.
 */

$config['citation_common_fields'] = array('abstract', 'keyword', 'language');

$config['citation_field_inputs'] = array(
	'title'           => 'text',
	'original-title'  => 'text',
	'container-title' => 'text',
	'collection-title'=> 'text',
	'volume'          => 'text',
	'issue'           => 'text',
	'page'            => 'text',
	'number'          => 'text',
	'edition'         => 'text',
	'publisher'       => 'text',
	'publisher-place' => 'text',
	'genre'           => 'text',
	'issued'          => 'date',
	'accessed'        => 'date',
	'DOI'             => 'text',
	'URL'             => 'text',
	'abstract'        => 'textarea',
	'keyword'         => 'textarea',
	'language'        => 'language',
);

$config['citation_types'] = array(
	'article-journal' => array(
		'legacy' => 'journal',
		'roles'  => array('author'),
		'fields' => array('container-title', 'volume', 'issue', 'page', 'issued', 'number', 'DOI', 'URL'),
	),
	'article-magazine' => array(
		'legacy' => 'magazine',
		'roles'  => array('author'),
		'fields' => array('container-title', 'volume', 'issue', 'page', 'issued', 'number', 'DOI', 'URL'),
	),
	'article-newspaper' => array(
		'legacy' => 'newspaper',
		'roles'  => array('author'),
		'fields' => array('container-title', 'volume', 'page', 'issued', 'URL'),
	),
	'article' => array(
		'legacy' => 'journal',
		'roles'  => array('author'),
		'fields' => array('container-title', 'volume', 'issue', 'page', 'issued', 'number', 'DOI', 'URL'),
	),
	'chapter' => array(
		'legacy' => 'book-section',
		'roles'  => array('author', 'editor', 'translator'),
		'fields' => array('container-title', 'edition', 'volume', 'page', 'number', 'publisher', 'publisher-place', 'issued', 'DOI', 'URL'),
	),
	'paper-conference' => array(
		'legacy' => 'conference-paper',
		'roles'  => array('author'),
		'fields' => array('container-title', 'publisher-place', 'issued', 'page', 'DOI', 'URL'),
	),
	'book' => array(
		'legacy' => 'book',
		'roles'  => array('author', 'editor', 'translator'),
		'fields' => array('original-title', 'edition', 'volume', 'number', 'publisher', 'publisher-place', 'issued', 'DOI', 'URL'),
	),
	'report' => array(
		'legacy' => 'report',
		'roles'  => array('author', 'editor'),
		'fields' => array('genre', 'collection-title', 'number', 'edition', 'volume', 'publisher', 'publisher-place', 'issued', 'DOI', 'URL'),
	),
	'thesis' => array(
		'legacy' => 'thesis',
		'roles'  => array('author'),
		'fields' => array('genre', 'publisher', 'issued', 'DOI', 'URL'),
	),
	'dataset' => array(
		'legacy' => 'report',
		'roles'  => array('author'),
		'fields' => array('genre', 'container-title', 'number', 'publisher', 'publisher-place', 'issued', 'accessed', 'DOI', 'URL'),
	),
	'software' => array(
		'legacy' => 'report',
		'roles'  => array('author'),
		'fields' => array('genre', 'number', 'publisher', 'issued', 'DOI', 'URL'),
	),
	'webpage' => array(
		'legacy' => 'website',
		'roles'  => array('author'),
		'fields' => array('container-title', 'publisher', 'issued', 'accessed', 'URL'),
	),
	'document' => array(
		'legacy' => 'website-doc',
		'roles'  => array('author'),
		'fields' => array('container-title', 'publisher', 'issued', 'accessed', 'URL'),
	),
);

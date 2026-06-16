<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Export NADA data structures as SDMX-ML 2.1 Structure messages.
 *
 * Produces a self-contained Structure message containing:
 *   - mes:Header
 *   - str:Concepts     — synthetic ConceptScheme (NADA_CONCEPTS) with one Concept per component
 *   - str:Codelists    — all referenced codelists with items (when include_codelists=true)
 *   - str:DataStructures — the DSD with DimensionList / AttributeList / MeasureList
 *
 * column_type → SDMX element mapping:
 *   dimension / geography / periodicity / indicator_id  → str:Dimension
 *   time_period                                         → str:TimeDimension
 *   attribute / annotation / indicator_name             → str:Attribute (assignmentStatus=Conditional)
 *   observation_value / measure                         → str:PrimaryMeasure
 *
 * Non-standard types (geography, periodicity, indicator_id, indicator_name, annotation)
 * carry a com:Annotation (nada:column_type) so the original type survives round-trip.
 */
class Sdmx_structure_xml_export {

	const NS_MES = 'http://www.sdmx.org/resources/sdmxml/schemas/v2_1/message';
	const NS_STR = 'http://www.sdmx.org/resources/sdmxml/schemas/v2_1/structure';
	const NS_COM = 'http://www.sdmx.org/resources/sdmxml/schemas/v2_1/common';
	const NS_XML = 'http://www.w3.org/XML/1998/namespace';
	const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

	private static $dimension_types = ['dimension', 'geography', 'time_period', 'periodicity', 'indicator_id'];
	private static $attribute_types = ['attribute', 'annotation', 'indicator_name'];
	private static $measure_types   = ['observation_value', 'measure'];

	/** Standard types that need no nada:column_type annotation. */
	private static $standard_types  = ['dimension', 'time_period', 'attribute', 'measure', 'observation_value'];

	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model('Codelist_model');
		$this->CI->load->model('Codelist_item_model');
	}

	/**
	 * Build SDMX-ML 2.1 Structure XML string.
	 *
	 * @param array $structure   data_structures row
	 * @param array $components  data_structure_components rows (sorted by sort_order)
	 * @param array $options     include_codelists (bool, default true)
	 * @return string UTF-8 XML
	 */
	public function build_xml(array $structure, array $components, array $options = [])
	{
		$includeCodelists = !array_key_exists('include_codelists', $options) || !empty($options['include_codelists']);
		$agency  = !empty($structure['agency'])  ? (string) $structure['agency']  : 'NADA';
		$version = !empty($structure['version']) ? (string) $structure['version'] : '1.0';

		$codelistRows = $this->_load_codelist_rows($components);

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;

		$root = $dom->createElementNS(self::NS_MES, 'mes:Structure');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mes', self::NS_MES);
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:str', self::NS_STR);
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:com', self::NS_COM);
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', self::NS_XSI);
		$root->setAttributeNS(self::NS_XSI, 'xsi:schemaLocation',
			self::NS_MES . ' https://registry.sdmx.org/schemas/v2_1/SDMXMessage.xsd');
		$dom->appendChild($root);

		$root->appendChild($this->_build_header($dom));

		$structures = $dom->createElementNS(self::NS_MES, 'mes:Structures');
		$root->appendChild($structures);

		// ConceptSchemes — synthetic scheme so ConceptIdentity refs resolve
		$conceptsSection = $dom->createElementNS(self::NS_STR, 'str:Concepts');
		$conceptsSection->appendChild($this->_build_concept_scheme($dom, $components, $agency));
		$structures->appendChild($conceptsSection);

		// Codelists
		if (!empty($codelistRows)) {
			$clSection = $dom->createElementNS(self::NS_STR, 'str:Codelists');
			foreach ($codelistRows as $clId => $clRow) {
				$items = $includeCodelists
					? $this->CI->Codelist_item_model->get_items_by_codelist((int) $clId, false)
					: [];
				$clSection->appendChild($this->_build_codelist_element($dom, $clRow, $items));
			}
			$structures->appendChild($clSection);
		}

		// DataStructures
		$dsSection = $dom->createElementNS(self::NS_STR, 'str:DataStructures');
		$dsSection->appendChild($this->_build_data_structure($dom, $structure, $components, $codelistRows, $agency));
		$structures->appendChild($dsSection);

		return $dom->saveXML();
	}

	// -------------------------------------------------------------------------
	// Header
	// -------------------------------------------------------------------------

	private function _build_header(DOMDocument $dom)
	{
		$header = $dom->createElementNS(self::NS_MES, 'mes:Header');

		$id = $dom->createElementNS(self::NS_MES, 'mes:ID');
		$id->appendChild($dom->createTextNode('NADA_' . gmdate('Ymd\THis')));
		$header->appendChild($id);

		$test = $dom->createElementNS(self::NS_MES, 'mes:Test');
		$test->appendChild($dom->createTextNode('false'));
		$header->appendChild($test);

		$prepared = $dom->createElementNS(self::NS_MES, 'mes:Prepared');
		$prepared->appendChild($dom->createTextNode(gmdate('Y-m-d\TH:i:s\Z')));
		$header->appendChild($prepared);

		$sender = $dom->createElementNS(self::NS_MES, 'mes:Sender');
		$sender->setAttribute('id', 'NADA');
		$header->appendChild($sender);

		$receiver = $dom->createElementNS(self::NS_MES, 'mes:Receiver');
		$receiver->setAttribute('id', 'not_supplied');
		$header->appendChild($receiver);

		return $header;
	}

	// -------------------------------------------------------------------------
	// ConceptScheme
	// -------------------------------------------------------------------------

	private function _build_concept_scheme(DOMDocument $dom, array $components, $agency)
	{
		$cs = $dom->createElementNS(self::NS_STR, 'str:ConceptScheme');
		$cs->setAttribute('id', 'NADA_CONCEPTS');
		$cs->setAttribute('agencyID', $agency);
		$cs->setAttribute('version', '1.0');
		$cs->setAttribute('isFinal', 'true');

		$csName = $dom->createElementNS(self::NS_COM, 'com:Name');
		$csName->setAttributeNS(self::NS_XML, 'xml:lang', 'en');
		$csName->appendChild($dom->createTextNode('NADA Concepts'));
		$cs->appendChild($csName);

		foreach ($components as $c) {
			if (empty($c['name'])) {
				continue;
			}
			$concept = $dom->createElementNS(self::NS_STR, 'str:Concept');
			$concept->setAttribute('id', (string) $c['name']);

			$cName = $dom->createElementNS(self::NS_COM, 'com:Name');
			$cName->setAttributeNS(self::NS_XML, 'xml:lang', 'en');
			$cName->appendChild($dom->createTextNode(
				!empty($c['label']) ? (string) $c['label'] : (string) $c['name']
			));
			$concept->appendChild($cName);
			$cs->appendChild($concept);
		}

		return $cs;
	}

	// -------------------------------------------------------------------------
	// Codelist
	// -------------------------------------------------------------------------

	private function _build_codelist_element(DOMDocument $dom, array $clRow, array $items)
	{
		$cl = $dom->createElementNS(self::NS_STR, 'str:Codelist');
		$cl->setAttribute('id', (string) $clRow['name']);
		$cl->setAttribute('agencyID', !empty($clRow['agency'])  ? (string) $clRow['agency']  : 'NADA');
		$cl->setAttribute('version',  !empty($clRow['version']) ? (string) $clRow['version'] : '1.0');
		$cl->setAttribute('isFinal', 'true');

		$label = !empty($clRow['description']) ? (string) $clRow['description'] : (string) $clRow['name'];
		$clName = $dom->createElementNS(self::NS_COM, 'com:Name');
		$clName->setAttributeNS(self::NS_XML, 'xml:lang', 'en');
		$clName->appendChild($dom->createTextNode($label));
		$cl->appendChild($clName);

		foreach ($items as $item) {
			if (empty($item['code'])) {
				continue;
			}
			$code = $dom->createElementNS(self::NS_STR, 'str:Code');
			$code->setAttribute('id', (string) $item['code']);

			$codeName = $dom->createElementNS(self::NS_COM, 'com:Name');
			$codeName->setAttributeNS(self::NS_XML, 'xml:lang', 'en');
			$codeName->appendChild($dom->createTextNode(
				isset($item['title']) && (string) $item['title'] !== ''
					? (string) $item['title']
					: (string) $item['code']
			));
			$code->appendChild($codeName);
			$cl->appendChild($code);
		}

		return $cl;
	}

	// -------------------------------------------------------------------------
	// DataStructure
	// -------------------------------------------------------------------------

	private function _build_data_structure(DOMDocument $dom, array $structure, array $components, array $codelistRows, $agency)
	{
		$ds = $dom->createElementNS(self::NS_STR, 'str:DataStructure');
		$ds->setAttribute('id',       (string) $structure['name']);
		$ds->setAttribute('agencyID', $agency);
		$ds->setAttribute('version',  !empty($structure['version']) ? (string) $structure['version'] : '1.0');
		$ds->setAttribute('isFinal', 'true');

		$title = !empty($structure['title']) ? (string) $structure['title'] : (string) $structure['name'];
		$dsName = $dom->createElementNS(self::NS_COM, 'com:Name');
		$dsName->setAttributeNS(self::NS_XML, 'xml:lang', 'en');
		$dsName->appendChild($dom->createTextNode($title));
		$ds->appendChild($dsName);

		$dsc = $dom->createElementNS(self::NS_STR, 'str:DataStructureComponents');
		$ds->appendChild($dsc);

		$dimensions = [];
		$attributes = [];
		$measures   = [];
		foreach ($components as $c) {
			if (empty($c['column_type'])) {
				continue;
			}
			$ct = (string) $c['column_type'];
			if (in_array($ct, self::$dimension_types, true)) {
				$dimensions[] = $c;
			} elseif (in_array($ct, self::$attribute_types, true)) {
				$attributes[] = $c;
			} elseif (in_array($ct, self::$measure_types, true)) {
				$measures[] = $c;
			}
		}

		if (!empty($dimensions)) {
			$dimList = $dom->createElementNS(self::NS_STR, 'str:DimensionList');
			$dimList->setAttribute('id', 'DimensionDescriptor');
			$pos = 1;
			foreach ($dimensions as $c) {
				$clRow = $this->_resolve_codelist($c, $codelistRows);
				if ((string) $c['column_type'] === 'time_period') {
					$dimList->appendChild($this->_build_time_dimension($dom, $c, $agency));
				} else {
					$dimList->appendChild($this->_build_dimension($dom, $c, $pos, $clRow, $agency));
					$pos++;
				}
			}
			$dsc->appendChild($dimList);
		}

		if (!empty($attributes)) {
			$attrList = $dom->createElementNS(self::NS_STR, 'str:AttributeList');
			$attrList->setAttribute('id', 'AttributeDescriptor');
			foreach ($attributes as $c) {
				$clRow = $this->_resolve_codelist($c, $codelistRows);
				$attrList->appendChild($this->_build_attribute($dom, $c, $clRow, $agency));
			}
			$dsc->appendChild($attrList);
		}

		if (!empty($measures)) {
			$measList = $dom->createElementNS(self::NS_STR, 'str:MeasureList');
			$measList->setAttribute('id', 'MeasureDescriptor');
			foreach ($measures as $c) {
				$measList->appendChild($this->_build_primary_measure($dom, $c, $agency));
			}
			$dsc->appendChild($measList);
		}

		return $ds;
	}

	// -------------------------------------------------------------------------
	// Component elements
	// -------------------------------------------------------------------------

	private function _build_dimension(DOMDocument $dom, array $c, $position, $clRow, $agency)
	{
		$el = $dom->createElementNS(self::NS_STR, 'str:Dimension');
		$el->setAttribute('id', (string) $c['name']);
		$el->setAttribute('position', (string) $position);

		$this->_append_concept_identity($dom, $el, $c, $agency);
		$this->_append_local_representation($dom, $el, $clRow);
		$this->_maybe_append_column_type_annotation($dom, $el, (string) $c['column_type']);

		return $el;
	}

	private function _build_time_dimension(DOMDocument $dom, array $c, $agency)
	{
		$el = $dom->createElementNS(self::NS_STR, 'str:TimeDimension');
		$el->setAttribute('id', (string) $c['name']);

		$this->_append_concept_identity($dom, $el, $c, $agency);

		$lr = $dom->createElementNS(self::NS_STR, 'str:LocalRepresentation');
		$tf = $dom->createElementNS(self::NS_STR, 'str:TextFormat');
		$tf->setAttribute('textType', 'ObservationalTimePeriod');
		$lr->appendChild($tf);
		$el->appendChild($lr);

		return $el;
	}

	private function _build_attribute(DOMDocument $dom, array $c, $clRow, $agency)
	{
		$el = $dom->createElementNS(self::NS_STR, 'str:Attribute');
		$el->setAttribute('id', (string) $c['name']);
		$el->setAttribute('assignmentStatus', 'Conditional');

		$this->_append_concept_identity($dom, $el, $c, $agency);
		$this->_append_local_representation($dom, $el, $clRow);
		$this->_maybe_append_column_type_annotation($dom, $el, (string) $c['column_type']);

		return $el;
	}

	private function _build_primary_measure(DOMDocument $dom, array $c, $agency)
	{
		$el = $dom->createElementNS(self::NS_STR, 'str:PrimaryMeasure');
		$el->setAttribute('id', (string) $c['name']);

		$this->_append_concept_identity($dom, $el, $c, $agency);

		$lr = $dom->createElementNS(self::NS_STR, 'str:LocalRepresentation');
		$tf = $dom->createElementNS(self::NS_STR, 'str:TextFormat');
		$tf->setAttribute('textType', 'Double');
		$lr->appendChild($tf);
		$el->appendChild($lr);

		return $el;
	}

	// -------------------------------------------------------------------------
	// Shared sub-element builders
	// -------------------------------------------------------------------------

	private function _append_concept_identity(DOMDocument $dom, DOMElement $parent, array $c, $agency)
	{
		$ci  = $dom->createElementNS(self::NS_STR, 'str:ConceptIdentity');
		$ref = $dom->createElement('Ref');
		$ref->setAttribute('id', (string) $c['name']);
		$ref->setAttribute('maintainableParentID', 'NADA_CONCEPTS');
		$ref->setAttribute('maintainableParentVersion', '1.0');
		$ref->setAttribute('agencyID', $agency);
		$ref->setAttribute('package', 'conceptscheme');
		$ref->setAttribute('class', 'Concept');
		$ci->appendChild($ref);
		$parent->appendChild($ci);
	}

	private function _append_local_representation(DOMDocument $dom, DOMElement $parent, $clRow)
	{
		$lr = $dom->createElementNS(self::NS_STR, 'str:LocalRepresentation');
		if ($clRow) {
			$enum = $dom->createElementNS(self::NS_STR, 'str:Enumeration');
			$ref  = $dom->createElement('Ref');
			$ref->setAttribute('id',       (string) $clRow['name']);
			$ref->setAttribute('agencyID', !empty($clRow['agency'])  ? (string) $clRow['agency']  : 'NADA');
			$ref->setAttribute('version',  !empty($clRow['version']) ? (string) $clRow['version'] : '1.0');
			$ref->setAttribute('package',  'codelist');
			$ref->setAttribute('class',    'Codelist');
			$enum->appendChild($ref);
			$lr->appendChild($enum);
		} else {
			$tf = $dom->createElementNS(self::NS_STR, 'str:TextFormat');
			$tf->setAttribute('textType', 'String');
			$lr->appendChild($tf);
		}
		$parent->appendChild($lr);
	}

	private function _maybe_append_column_type_annotation(DOMDocument $dom, DOMElement $parent, $columnType)
	{
		if (in_array($columnType, self::$standard_types, true)) {
			return;
		}
		$anns    = $dom->createElementNS(self::NS_COM, 'com:Annotations');
		$ann     = $dom->createElementNS(self::NS_COM, 'com:Annotation');

		$annType = $dom->createElementNS(self::NS_COM, 'com:AnnotationType');
		$annType->appendChild($dom->createTextNode('nada:column_type'));
		$ann->appendChild($annType);

		$annText = $dom->createElementNS(self::NS_COM, 'com:AnnotationText');
		$annText->setAttributeNS(self::NS_XML, 'xml:lang', 'en');
		$annText->appendChild($dom->createTextNode($columnType));
		$ann->appendChild($annText);

		$anns->appendChild($ann);
		$parent->appendChild($anns);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function _load_codelist_rows(array $components)
	{
		$ids = [];
		foreach ($components as $c) {
			if (!empty($c['codelist_id'])) {
				$ids[(int) $c['codelist_id']] = true;
			}
		}
		$rows = [];
		foreach (array_keys($ids) as $id) {
			$row = $this->CI->Codelist_model->get_codelist_by_id($id);
			if ($row) {
				$rows[$id] = $row;
			}
		}
		return $rows;
	}

	private function _resolve_codelist(array $c, array $codelistRows)
	{
		if (!empty($c['codelist_id'])) {
			$id = (int) $c['codelist_id'];
			return isset($codelistRows[$id]) ? $codelistRows[$id] : null;
		}
		return null;
	}
}

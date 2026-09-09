<?php

namespace Nada\DdiParser\ValueObjects;

class DdiVariable
{
    private $namespaces    = [];
    private $metadata      = [];
    private $variable      = [];
    private $variable_metadata;

    public function __construct(\SimpleXMLElement $xmlObj)
    {
        $this->variable = $this->tranform_ddi_variable($xmlObj);
    }

    private function variable_xml_to_array(\SimpleXMLElement $xml_obj)
    {
        return self::simplexml_to_array($xml_obj, true);
    }

    private static function simplexml_to_array(\SimpleXMLElement $element, $is_root)
    {
        $array        = [];
        $has_children = false;

        foreach ($element as $tag => $child) {
            $has_children = true;
            $temp         = self::simplexml_to_array($child, false);
            $attributes   = [];

            foreach ($child->attributes() as $name => $value) {
                $attributes[(string)$name] = (string)$value;
            }

            if (!empty($attributes)) {
                $array[(string)$tag][] = array_merge($temp, ['@attr' => $attributes]);
            } else {
                $array[(string)$tag][] = $temp;
            }
        }

        if (!$has_children) {
            $text = trim((string)$element);
            if ($text !== '') {
                $array['_text'] = $text;
            }
        }

        if ($is_root) {
            $name   = $element->getName();
            $result = [$name => $array];
            foreach ($element->attributes() as $attr_name => $attr_value) {
                $result[$name]['@attr'][(string)$attr_name] = (string)$attr_value;
            }
            return $result;
        }

        return $array;
    }

    private function get_element_value($path, $metadata = null)
    {
        if (!$metadata) {
            $metadata = $this->variable_metadata;
        }

        $element = null;
        $output  = null;

        if (isset($metadata[$path])) {
            $element = $metadata[$path];
        }

        if (!empty($element) && !is_array($element)) {
            return $element;
        }

        if (!empty($element) && count($element) > 1) {
            $output = [];
            foreach ($element as $item) {
                $output[] = $this->get_element_text($item);
            }
        } elseif (!empty($element) && count($element) == 1) {
            if (isset($element[0])) {
                $output = $this->get_element_text($element[0]);
            }
        }

        return $output;
    }

    private function get_element_text($element)
    {
        if (isset($element['_text'])) {
            return $element['_text'];
        }
    }

    private function get_simple_element($name, $metadata = null)
    {
        if (!$metadata) {
            $metadata = $this->variable_metadata;
        }

        if (isset($metadata[$name])) {
            if (isset($metadata[$name][0])) {
                return $metadata[$name][0];
            }
        }
    }

    private function get_repeatable_element($name, $metadata = null)
    {
        if (!$metadata) {
            $metadata = $this->variable_metadata;
        }

        if (isset($metadata[$name])) {
            return $metadata[$name];
        }
    }

    private function get_attribute_value($element, $att_name)
    {
        if (!empty($element) && isset($element['@attr'][$att_name])) {
            return $element['@attr'][$att_name];
        }
    }

    private function tranform_ddi_variable(\SimpleXMLElement $xml_obj)
    {
        $var_array              = $this->variable_xml_to_array($xml_obj);
        $var_array              = $var_array['var'];
        $this->variable_metadata = $var_array;

        $output = [
            'file_id'    => $this->get_attribute_value($var_array, 'files'),
            'vid'        => $this->get_attribute_value($var_array, 'ID'),
            'name'       => $this->get_attribute_value($var_array, 'name'),
            'var_intrvl' => $this->get_attribute_value($var_array, 'intrvl'),
            'var_dcml'   => $this->get_attribute_value($var_array, 'dcml'),
            'var_wgt'    => $this->get_attribute_value($var_array, 'wgt-var'),
            'var_is_wgt' => $this->get_attribute_value($var_array, 'wgt'),
        ];

        $location = $this->get_simple_element('location');
        $output['loc_start_pos']  = $this->get_attribute_value($location, 'StartPos');
        $output['loc_end_pos']    = $this->get_attribute_value($location, 'EndPos');
        $output['loc_width']      = $this->get_attribute_value($location, 'width');
        $output['loc_rec_seg_no'] = $this->get_attribute_value($location, 'RecSegNo');

        $output['labl']               = $this->get_element_value('labl');
        $output['var_imputation']     = $this->get_element_value('imputation');
        $output['var_security']       = $this->get_element_value('security');
        $output['var_resp_unit']      = $this->get_element_value('respUnit');
        $output['var_analysis_unit']  = $this->get_element_value('anlysUnit');

        $question = $this->get_simple_element('qstn');
        $output['var_qstn_preqtxt']  = $this->get_element_value('preQTxt', $question);
        $output['var_qstn_qstnlit']  = $this->get_element_value('qstnLit', $question);
        $output['var_qstn_postqtxt'] = $this->get_element_value('postQTxt', $question);
        $output['var_qstn_ivulnstr'] = $this->get_element_value('ivuInstr', $question);

        $output['var_universe']         = $this->get_element_value('universe');
        $output['var_universe_clusion'] = $this->get_attribute_value($this->get_simple_element('universe'), 'clusion');

        $sum_stats      = [];
        $sum_stats_list = (array) $this->get_repeatable_element('sumStat');
        foreach ($sum_stats_list as $item) {
            $sum_stats[] = [
                'value' => $this->get_element_text($item),
                'type'  => $this->get_attribute_value($item, 'type'),
                'wgtd'  => $this->get_attribute_value($item, 'wgtd'),
            ];
        }
        $output['var_sumstat'] = $sum_stats;
        $output['var_txt']     = $this->get_element_value('txt');

        $categories    = [];
        $category_list = (array) $this->get_repeatable_element('catgry');
        foreach ($category_list as $item) {
            $category_stats = [];

            if (isset($item['catStat'])) {
                $cat_stat_list = isset($item['catStat'][0]) ? $item['catStat'] : [$item['catStat']];
                foreach ($cat_stat_list as $cat_stat) {
                    $val = $this->get_element_text($cat_stat);
                    if ($val !== null && $val !== '') {
                        $category_stats[] = [
                            'value' => $val,
                            'type'  => $this->get_attribute_value($cat_stat, 'type'),
                            'wgtd'  => $this->get_attribute_value($cat_stat, 'wgtd'),
                        ];
                    }
                }
            }

            $categories[] = [
                'value'      => $this->get_element_text($this->get_simple_element('catValu', $item)),
                'labl'       => $this->get_element_text($this->get_simple_element('labl', $item)),
                'is_missing' => $this->get_attribute_value($item, 'missing'),
                'stats'      => $category_stats,
            ];
        }
        $output['var_catgry'] = $categories;

        $output['var_codinstr'] = $this->get_element_value('codInstr');

        $concepts      = [];
        $concept_list  = (array) $this->get_repeatable_element('concept');
        foreach ($concept_list as $item) {
            $concepts[] = [
                'title' => $this->get_element_text($item),
                'vocab' => $this->get_attribute_value($item, 'vocab'),
                'uri'   => $this->get_attribute_value($item, 'vocabURI'),
            ];
        }
        $output['var_concept'] = $concepts;

        $var_format = $this->get_simple_element('varFormat');
        $output['var_format'] = [
            'type'     => $this->get_attribute_value($var_format, 'type'),
            'schema'   => $this->get_attribute_value($var_format, 'schema'),
            'category' => $this->get_attribute_value($var_format, 'category'),
            'name'     => $this->get_attribute_value($var_format, 'formatname'),
        ];

        $output['var_notes'] = $this->get_element_value('notes');

        $range = $this->get_simple_element('range', $this->get_simple_element('valrng'));
        $output['var_val_range'] = [
            'min'   => $this->get_attribute_value($range, 'min'),
            'max'   => $this->get_attribute_value($range, 'max'),
            'units' => $this->get_attribute_value($range, 'UNITS'),
        ];

        return $output;
    }

    public function get_id()           { return $this->get_key('vid'); }
    public function get_file_id()      { return $this->get_key('file_id'); }
    public function get_name()         { return $this->get_key('name'); }
    public function get_label()        { return $this->get_key('labl'); }
    public function get_question()     { return $this->get_key('var_qstn_qstnlit'); }
    public function get_categories()   { return $this->get_key('var_catgry'); }
    public function get_notes()        { return $this->get_key('var_notes'); }
    public function get_txt()          { return $this->get_key('var_txt'); }
    public function get_metadata_array() { return $this->variable; }

    public function get_categories_str()
    {
        $categories = $this->get_categories();

        if (!is_array($categories) || empty($categories)) {
            return null;
        }

        $labels = array_filter(array_merge(
            array_column($categories, 'labl'),
            array_column($categories, 'label')
        ));
        $labels = array_values(array_unique($labels));

        return implode(' ', $labels);
    }

    public function get_key($key)
    {
        if (array_key_exists($key, $this->variable)) {
            return $this->variable[$key];
        }
        return false;
    }
}

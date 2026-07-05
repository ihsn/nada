<?php

namespace Nada\GisParser\Parsing;

use Nada\DdiParser\Contracts\ReaderInterface;

class GisReader implements ReaderInterface
{
    private $file;
    private $isoreader;
    private $metadata;

    public function __construct($file, $metadata_key_mappings = null)
    {
        $this->file = $file;
        require_once dirname(__DIR__, 3) . '/libraries/Metadata_parser/classes/ISO19115_Parser.php';
        $this->isoreader = new \ISO19115_Parser();
        $this->isoreader->initialize($file);
        $this->metadata = $this->isoreader->xml_to_array();
    }

    public function get_key($key)
    {
        if (array_key_exists($key, $this->metadata)) {
            return $this->metadata[$key];
        }
        return false;
    }

    public function array_to_string($data, $type = 'text')
    {
        if (!$data) {
            return null;
        }

        if ($type == 'text' || $type == 'string') {
            return implode("\r\n", $data);
        }

        if (in_array($type, ['table', 'array'])) {
            $output = [];
            foreach ($data as $row) {
                $row_output = [];
                foreach ($row as $field_value) {
                    if (trim($field_value) != '') {
                        $row_output[] = $field_value;
                    }
                }
                $output[] = implode(', ', $row_output);
            }
            return implode("\r\n", $output);
        }

        throw new \Exception("TYPE_NOT_SUPPORTED: " . $type);
    }

    public function get_id()
    {
        $title = $this->get_title();
        if ($title) {
            return md5($title);
        }
        return null;
    }

    public function get_title()
    {
        return $this->get_key('ident_title');
    }

    public function get_abbreviation() {}

    public function get_authenty()
    {
        $data = $this->get_key('ident_contacts');
        if (!$data) { return null; }
        $names  = array_unique(array_column($data, 'org_name'));
        $output = [];
        foreach ($names as $name) {
            $output[] = ['name' => $name];
        }
        return $output;
    }

    public function get_producers()
    {
        $data = $this->get_key('metadata_contacts');
        if (!$data) { return null; }
        $names  = array_unique(array_column($data, 'org_name'));
        $output = [];
        foreach ($names as $name) {
            $output[] = ['name' => $name];
        }
        return $output;
    }

    public function get_sponsors() {}

    public function get_start_year()
    {
        $years = $this->get_years();
        return min($years);
    }

    public function get_end_year()
    {
        $years = $this->get_years();
        return max($years);
    }

    public function get_years()
    {
        $data = $this->get_key('ident_dates');
        if (!$data) { return 0; }

        $years = [];
        foreach ($data as $row) {
            if (!$row) { continue; }
            $years[] = (int) substr($row['date'], 0, 4);
        }

        if (count($years) > 0) {
            $years = range(min($years), max($years));
        }

        return $years;
    }

    public function get_bounding_box()
    {
        return $this->get_key('ident_extent_bbox');
    }

    public function get_countries()
    {
        $data = $this->get_key('ident_contacts');
        if (!$data) { return null; }
        $output = [];
        foreach (array_column($data, 'country') as $country) {
            $output[] = ['name' => $country];
        }
        return $output;
    }

    public function get_languages()
    {
        $data = $this->get_key('ident_language');
        if ($data) {
            return (array) $data;
        }
    }

    public function get_countries_str()
    {
        $countries = $this->get_countries();
        if (!$countries) { return null; }
        return implode(', ', array_column($countries, 'name'));
    }

    public function get_topics()  {}
    public function get_keywords() {}

    public function get_metadata_array()
    {
        return $this->metadata;
    }

    public function get_data_files() {}

    public function get_variable_iterator() {}
}

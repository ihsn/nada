<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Nada\DdiParser\Factory\ReaderFactory;

class Metadata_parser {

    private $type;
    private $file_path;
    private $key_mappings;

    public function __construct($params)
    {
        if (!isset($params['file_type']) || !isset($params['file_path'])) {
            throw new Exception("METADATA_PARSER::MISSING_PARAMS: file_type or file_path");
        }

        $this->ci =& get_instance();
        $this->ci->config->load("metadata_parser", TRUE);

        $this->type         = $params['file_type'];
        $this->file_path    = $params['file_path'];
        $this->key_mappings = $this->ci->config->item($this->type, "metadata_parser", TRUE);
    }

    public function get_reader()
    {
        return ReaderFactory::getReader($this->type, $this->file_path, $this->key_mappings);
    }
}
// End Class

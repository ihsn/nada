<?php

class DdiVariable{

    private $table_elements=array();
    private $namespaces = array();
    private $metadata=array();
    private $metadata_keys_map=array(
        "var/@ID" => "id",
        "var/@name" => "name",
        "var/@files" => "files",
        "var/@dcml" => "dcml",
        "var/@intrvl" => "intrvl",
        "var/location/@StartPos" => "loc_start_pos",
        "var/location/@EndPos" => "loc_end_pos",
        "var/location/@width" => "loc_width",
        "var/location/@RecSegNo" => "loc_rec_seg_no",
        "var/labl" => "labl",
        "var/imputation" => "imputation",
        "var/security" => "security",
        "var/respUnit" => "resp_unit",
        "var/qstn/preQTxt" => "preqtxt",
        "var/qstn/qstnLit" => "qstnlit",
        "var/qstn/postQTxt" => "postqtxt",
        "var/qstn/ivuInstr" => "ivuinstr",
        "var/universe" => "universe",
        "var/universe/@clusion" => "universe_clusion",
        "var/sumStat" => "sumstat",
        "var/txt" => "txt",
        "var/catgry" => "catgry",
        "var/codInstr" => "codinstr",
        "var/concept" => "concept",
        "var/varFormat/@type" => "var_format_type",
        "var/notes" => "notes",
        "var/varFormat/@formatname" => "var_format_name",
        "var/varFormat/@schema" => "var_format_schema",
        "var/varFormat/@category" => "var_format_category",
        "var/valrng/range" =>"var_val_range"
    );

    public function __construct($xmlObj)
    {
        $xpath_group['var/catgry'] = array(
            'label' => 'Variable category',
            'type' => 'table',
            'cols' => array(
                'catValu' => 'value',
                'labl' => 'labl',
                'catStat' => 'stats',
                'catStat/@type' => 'type',
            )
        );


        $xpath_group['var/sumStat'] = array(
            'label' => 'sumStat',
            'type' => 'table',
            'cols' => array(
                '.' => 'value',
                '@type' => 'type',
            )
        );

        $xpath_group['var/concept'] = array(
            'label' => 'sumStat',
            'type' => 'table',
            'cols' => array(
                '.' => 'name',
                '@vocab' => 'vocab',
                '@uri' => 'uri'
            )
        );

        $xpath_group['var/valrng/range'] = array(
            'label' => 'range',
            'type' => 'table',
            'cols' => array(
                '@UNITS' => 'units',
                '@min' => 'min',
                '@max' => 'max'
            )
        );

        $this->table_elements = $xpath_group;
        $this->get_child_elements_array($xmlObj,"var",$this->metadata);
        $this->transform_metadata_keys();
    }

    private function transform_metadata_keys()
    {
        $output=array();

        foreach($this->metadata as $key=>$value)
        {
            if (array_key_exists($key,$this->metadata_keys_map))
            {
                $output[$this->metadata_keys_map[$key]]=$value;
            }
        }

        $this->metadata=$output;
    }


    public function get_id(){
        return $this->array_to_string($this->get_key('id'),$type='text');
    }

    public function get_file_id(){
        return $this->array_to_string($this->get_key('files'),$type='text');
    }

    public function get_name(){
        return $this->array_to_string($this->get_key('name'),$type='text');
    }

    public function get_label(){
        return $this->array_to_string($this->get_key('labl'),$type='text');
    }

    public function get_question(){
        return $this->array_to_string($this->get_key('qstnlit'),$type='text');
    }

    public function get_categories(){
        return $this->get_key('catgry');
    }

    public function get_categories_str()
    {
        $categories=$this->get_categories();
        if(!is_array($categories))
        {
            return null;
        }
        $categories=array_column($categories,"value");
        return implode(" ",$categories);
    }

    public function get_metadata_array(){
        return $this->metadata;
    }

    /*
        *
        * return an array of all child elements with values
        *
        */
    public function get_child_elements_array(&$xml_obj, $parent_path = "/", &$elements_array)
    {
        if (array_key_exists($parent_path, $this->table_elements)) {
            $result=array();
            //$result = $this->get_element_flattened($xml_obj, $element_parent_path = NULL, $result = array());
            $this->get_element_flattened($xml_obj, $element_parent_path = NULL, $result);
            $cols = $this->table_elements[$parent_path]['cols'];

            //remove keys not registered
            foreach ($result as $key => $value) {
                if (!array_key_exists($key, $cols)) {
                    unset($result[$key]);
                }
            }

            //use column names instead of the xpaths to table type elements
            $column_data = array();
            foreach ($cols as $xpath => $name) {
                $column_data[$name] = @$result[$xpath];
            }

            $result = $column_data;

            $elements_array[$parent_path][] = $result;

            return $elements_array;//to avoid duplicate items
        } else {
            if (trim((string)$xml_obj) != "") {
                $elements_array[$parent_path][] = trim((string)$xml_obj);
            }
        }


        //add attributes
        foreach ($xml_obj->attributes() as $att_name => $att_value) {
            $xpath_ = $parent_path . '/@' . $att_name;

            $elements_array[$xpath_] = (string)$att_value;
        }


        //get namespaces for the element
        $this->namespaces = $xml_obj->getNamespaces(true);

        foreach ($this->namespaces as $ns_key => $ns_value) {
            foreach ($xml_obj->children($ns_value) as $child) {
                $this->get_child_elements_array($child, $parent_path . '/' . $child->getName(), $elements_array);
            }
        }

        return $elements_array;
    }


    //flatten the element values and attributes into an flat array
    function get_element_flattened(&$xml_obj, $parent_path = NULL, &$output)
    {
        //element value?
        if ($parent_path) {
            $output[$parent_path] = trim((string)$xml_obj);
        } else {
            $output["."] = trim((string)$xml_obj);
        }

        //attributes
        foreach ($xml_obj->attributes() as $att_name => $att_value) {
            $xpath_ = $this->make_path($parent_path, '@' . $att_name);
            $output[$xpath_] = (string)$att_value;
        }

        $namespaces = $xml_obj->getNamespaces(true);
        foreach ($namespaces as $ns_key => $ns_value) {
            foreach ($xml_obj->children($ns_value) as $child) {
                $this->get_element_flattened($child, $this->make_path($parent_path, $child->getName()), $output);
            }

        }
        return $output;
    }


    //creates and normalizes the path based on parent and child element
    function make_path($parent_path, $child_element)
    {
        if (!$parent_path) {
            return $child_element;
        } else {
            return $parent_path . '/' . $child_element;
        }
    }


    //find an item by their key
    public function get_key($key)
    {
        if (array_key_exists($key,$this->metadata))
        {
            return $this->metadata[$key];
        }

        return false;
    }



    /**
     *
     * Creates a string value out of array type elements
     *
     * Note: uses \r\n for line breaks between multiple rows
     *
     **/
    public function array_to_string($data,$type='text')
    {
        if(!$data)
        {
            return NULL;
        }

        if ($type=='text' || $type=='string')
        {
            if (!is_array($data)){
                return $data;
            }

            return implode("\r\n",$data);
        }
        else if(in_array($type, array('table','array')))
        {
            $output=NULL;
            foreach($data as $row)
            {
                $row_output=array();

                foreach($row as $field_name=>$field_value)
                {
                    if(trim($field_value)!=''){
                        $row_output[]=$field_value;
                    }
                }

                //concat a single row
                $output[]=implode(", ",$row_output);
            }

            //combine all rows with line break
            return implode("\r\n",$output);
        }

        throw new Exception("TYPE_NOT_SUPPORTED: ".$type);
    }
}


<?php

class DDIReader implements ReaderInterface
{
    private $file;
    private $xml_obj;
    private $namespaces     = [];
    private $elements       = [];
    private $table_elements = [];
    private $metadata_array = [];
    private $labels         = [];

    // --- public API state ---
    private $metadata;
    private $variable_groups;

    private $metadata_short_names = [
        'ddi_version'              => 'codeBook/@version',
        'ddi_id'                   => 'codeBook/@ID',
        'ddi_lang'                 => 'codeBook/@lang',
        'doc_producer'             => 'codeBook/docDscr/citation/prodStmt/producer',
        'doc_version'              => 'codeBook/docDscr/citation/verStmt/version',
        'doc_idno'                 => 'codeBook/docDscr/citation/titlStmt/IDNO',
        'doc_titl'                 => 'codeBook/docDscr/citation/titlStmt/titl',
        'stdy_titl'                => 'codeBook/stdyDscr/citation/titlStmt/titl',
        'stdy_sub_titl'            => 'codeBook/stdyDscr/citation/titlStmt/subTitl',
        'stdy_alt_titl'            => 'codeBook/stdyDscr/citation/titlStmt/altTitl',
        'stdy_par_titl'            => 'codeBook/stdyDscr/citation/titlStmt/parTitl',
        'stdy_id'                  => 'codeBook/stdyDscr/citation/titlStmt/IDNo',
        'stdy_authenty'            => 'codeBook/stdyDscr/citation/rspStmt/AuthEnty',
        'stdy_othid'               => 'codeBook/stdyDscr/citation/rspStmt/othId',
        'stdy_producer'            => 'codeBook/stdyDscr/citation/prodStmt/producer',
        'stdy_copyright'           => 'codeBook/stdyDscr/citation/prodStmt/copyright',
        'stdy_fundag'              => 'codeBook/stdyDscr/citation/prodStmt/fundAg',
        'stdy_contact'             => 'codeBook/stdyDscr/citation/distStmt/contact',
        'stdy_sername'             => 'codeBook/stdyDscr/citation/serStmt/serName',
        'stdy_serinfo'             => 'codeBook/stdyDscr/citation/serStmt/serInfo',
        'stdy_version'             => 'codeBook/stdyDscr/citation/verStmt/version',
        'stdy_version_date'        => 'codeBook/stdyDscr/citation/verStmt/version/@date',
        'stdy_version_notes'       => 'codeBook/stdyDscr/citation/verStmt/notes',
        'stdy_keyword'             => 'codeBook/stdyDscr/stdyInfo/subject/keyword',
        'stdy_topic'               => 'codeBook/stdyDscr/stdyInfo/subject/topcClas',
        'stdy_abstract'            => 'codeBook/stdyDscr/stdyInfo/abstract',
        'stdy_time_prd'            => 'codeBook/stdyDscr/stdyInfo/sumDscr/timePrd',
        'stdy_coll_date'           => 'codeBook/stdyDscr/stdyInfo/sumDscr/collDate',
        'stdy_nation'              => 'codeBook/stdyDscr/stdyInfo/sumDscr/nation',
        'stdy_geogcover'           => 'codeBook/stdyDscr/stdyInfo/sumDscr/geogCover',
        'stdy_anlyunit'            => 'codeBook/stdyDscr/stdyInfo/sumDscr/anlyUnit',
        'stdy_universe'            => 'codeBook/stdyDscr/stdyInfo/sumDscr/universe',
        'stdy_datakind'            => 'codeBook/stdyDscr/stdyInfo/sumDscr/dataKind',
        'stdy_notes'               => 'codeBook/stdyDscr/stdyInfo/notes',
        'stdy_data_collector'      => 'codeBook/stdyDscr/method/dataColl/dataCollector',
        'stdy_data_coll_freq'      => 'codeBook/stdyDscr/method/dataColl/frequenc',
        'stdy_data_coll_src'       => 'codeBook/stdyDscr/method/dataColl/sources/dataSrc',
        'stdy_samp_proc'           => 'codeBook/stdyDscr/method/dataColl/sampProc',
        'stdy_deviat'              => 'codeBook/stdyDscr/method/dataColl/deviat',
        'stdy_collmode'            => 'codeBook/stdyDscr/method/dataColl/collMode',
        'stdy_resinstru'           => 'codeBook/stdyDscr/method/dataColl/resInstru',
        'stdy_collsite'            => 'codeBook/stdyDscr/method/dataColl/collSitu',
        'stdy_actmin'              => 'codeBook/stdyDscr/method/dataColl/actMin',
        'stdy_weight'              => 'codeBook/stdyDscr/method/dataColl/weight',
        'stdy_cleanops'            => 'codeBook/stdyDscr/method/dataColl/cleanOps',
        'stdy_method_notes'        => 'codeBook/stdyDscr/method/notes',
        'stdy_resprate'            => 'codeBook/stdyDscr/method/anlyInfo/respRate',
        'stdy_estsamperr'          => 'codeBook/stdyDscr/method/anlyInfo/EstSmpErr',
        'stdy_data_appr'           => 'codeBook/stdyDscr/method/anlyInfo/dataAppr',
        'stdy_dataaccs_confdec'    => 'codeBook/stdyDscr/dataAccs/useStmt/confDec',
        'stdy_dataaccs_contact'    => 'codeBook/stdyDscr/dataAccs/useStmt/contact',
        'stdy_dataaccs_citreq'     => 'codeBook/stdyDscr/dataAccs/useStmt/citReq',
        'stdy_dataaccs_conditions' => 'codeBook/stdyDscr/dataAccs/useStmt/conditions',
        'stdy_dataaccs_disclaimer' => 'codeBook/stdyDscr/dataAccs/useStmt/disclaimer',
    ];


    public function __construct($file, $metadata_keys_map = null)
    {
        require_once dirname(__FILE__) . '/DdiVariableIterator.php';

        // XPath mapping table (controls which elements are parsed and how)
        $xpath_group = [];

        $xpath_group['codeBook/fileDscr'] = [
            'label' => 'file description',
            'type'  => 'table',
            'cols'  => [
                '@ID'                    => 'id',
                '@URI'                   => 'uri',
                'fileTxt/fileName'       => 'filename',
                'fileTxt/fileName/@ID'   => 'file_id',
                'fileTxt/dimensns/caseQnty' => 'caseQnty',
                'fileTxt/dimensns/varQnty'  => 'varQnty',
                'fileTxt/fileType'       => 'filetype',
                'fileTxt/fileCont'       => 'fileCont',
                'fileTxt/filePlac'       => 'filePlac',
            ],
        ];

        $xpath_group['codeBook/dataDscr/varGrp'] = [
            'label' => 'Variable group',
            'type'  => 'table',
            'cols'  => [
                '@ID'      => 'vgid',
                '@type'    => 'group_type',
                '@varGrp'  => 'variable_groups',
                '@var'     => 'variables',
                'labl'     => 'label',
                'defntn'   => 'definition',
            ],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/sumDscr/collDate'] = [
            'label' => 'file description',
            'type'  => 'table',
            'cols'  => ['@date' => 'date', '@event' => 'event', '@cycle' => 'cycle'],
        ];

        $xpath_group['/ddi:codeBook/ddi:stdyDscr/ddi:stdyInfo/ddi:subject/ddi:topcClas'] = [
            'label' => 'Topics Classifications',
            'type'  => 'table',
            'cols'  => ['.' => 'topic', '@vocabURI' => 'uri', '@vocab' => 'vocab'],
        ];

        $xpath_group['codeBook/docDscr/citation/prodStmt/producer'] = [
            'label' => 'Producers',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbr', '@affiliation' => 'affiliation', '@role' => 'role'],
        ];

        $xpath_group['codeBook/stdyDscr/citation/distStmt/depositr'] = [
            'label' => 'Depositors',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbr', '@affiliation' => 'affiliation', '@URI' => 'uri'],
        ];

        $xpath_group['codeBook/stdyDscr/citation/distStmt/distrbtr'] = [
            'label' => 'Depositors',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbr', '@affiliation' => 'affiliation', '@URI' => 'uri'],
        ];

        $xpath_group['codeBook/stdyDscr/citation/holdings'] = [
            'label' => 'Holdings',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@location' => 'location', '@callno' => 'callno', '@URI' => 'uri'],
        ];

        $xpath_group['codeBook/stdyDscr/studyAuthorization/authorizingAgency'] = [
            'label' => 'Agency',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbr', '@affiliation' => 'affiliation'],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/sumDscr/geoBndBox'] = [
            'label' => 'BBOX',
            'type'  => 'table',
            'cols'  => ['westBL' => 'west', 'eastBL' => 'east', 'southBL' => 'south', 'northBL' => 'north'],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/sumDscr/boundPoly/polygon/point'] = [
            'label' => 'Polygon points',
            'type'  => 'table',
            'cols'  => ['gringLat' => 'lat', 'gringLon' => 'lon'],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/exPostEvaluation/evaluator'] = [
            'label' => 'Evaluator',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbr', '@affiliation' => 'affiliation', '@role' => 'role'],
        ];

        $xpath_group['codeBook/stdyDscr/studyDevelopment/developmentActivity'] = [
            'label'     => 'Study development activity',
            'type'      => 'table',
            'is_nested' => true,
            'cols'      => [
                '@type'       => 'activity_type',
                'description' => 'activity_description',
                'participant' => 'participants',
                'resource'    => 'resources',
                'outcome'     => 'outcome',
            ],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/qualityStatement/standardsCompliance/standard'] = [
            'label' => 'Standard',
            'type'  => 'table',
            'cols'  => ['standardName' => 'name', 'producer' => 'producer'],
        ];

        $xpath_group['codeBook/stdyDscr/studyDevelopment/developmentActivity/resource'] = [
            'label' => 'Resource',
            'type'  => 'table',
            'cols'  => ['dataSrc' => 'name', 'srcOrig' => 'origin', 'srcChar' => 'characteristics'],
        ];

        $xpath_group['codeBook/stdyDscr/studyDevelopment/developmentActivity/participant'] = [
            'label' => 'Participant',
            'type'  => 'table',
            'cols'  => ['.' => 'name', 'affiliation' => 'affiliation', 'role' => 'role'],
        ];

        $xpath_group['codeBook/stdyDscr/dataAccs/useStmt/confDec'] = [
            'label' => 'Data access confidentiality',
            'type'  => 'table',
            'cols'  => ['.' => 'txt', '@required' => 'required', '@formNo' => 'form_no', '@URI' => 'form_uri'],
        ];

        $xpath_group['codeBook/stdyDscr/method/dataColl/collectorTraining'] = [
            'label' => 'Collector training',
            'type'  => 'table',
            'cols'  => ['.' => 'txt', '@type' => 'type', '.' => 'training'],
        ];

        $xpath_group['codeBook/stdyDscr/dataAccs/useStmt/specPerm'] = [
            'label' => 'Special permissions',
            'type'  => 'table',
            'cols'  => ['.' => 'txt', '@required' => 'required', '@formNo' => 'form_no', '@URI' => 'form_uri'],
        ];

        $xpath_group['codeBook/stdyDscr/method/codingInstructions'] = [
            'label' => 'Data access confidentiality',
            'type'  => 'table',
            'cols'  => [
                '@relatedProcesses'    => 'related_processes',
                '@type'                => 'type',
                'txt'                  => 'txt',
                'command'              => 'command',
                'command/@formalLanguage' => 'formal_language',
            ],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/subject/topcClas'] = [
            'label' => 'Topics',
            'type'  => 'table',
            'cols'  => ['.' => 'topic', '@vocab' => 'vocab', '@vocabURI' => 'uri'],
        ];

        $xpath_group['codeBook/stdyDscr/citation/prodStmt/fundAg'] = [
            'label' => 'Funding',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbr', '@role' => 'role'],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/sumDscr/timePrd'] = [
            'label' => 'Time Periods',
            'type'  => 'table',
            'cols'  => ['@date' => 'date', '@event' => 'event', '@cycle' => 'cycle'],
        ];

        $xpath_group['codeBook/stdyDscr/method/dataProcessing'] = [
            'label' => 'Data processing',
            'type'  => 'table',
            'cols'  => ['.' => 'description', '@type' => 'type'],
        ];

        $xpath_group['codeBook/stdyDscr/method/dataColl/sampleFrame/validPeriod'] = [
            'label' => 'Valid period',
            'type'  => 'table',
            'cols'  => ['.' => 'date', '@event' => 'event'],
        ];

        $xpath_group['codeBook/stdyDscr/method/dataColl/sampleFrame/referencePeriod'] = [
            'label' => 'reference period',
            'type'  => 'table',
            'cols'  => ['.' => 'date', '@event' => 'event'],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/sumDscr/nation'] = [
            'label' => 'Countries',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbreviation'],
        ];

        $xpath_group['codeBook/stdyDscr/method/dataColl/sources'] = [
            'label' => 'Sources',
            'type'  => 'table',
            'cols'  => ['dataSrc' => 'name', 'srcOrig' => 'origin', 'srcChar' => 'characteristics'],
        ];

        $xpath_group['codeBook/stdyDscr/method/dataColl/dataCollector'] = [
            'label' => 'Data Collectors',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbr', '@role' => 'role', '@affiliation' => 'affiliation'],
        ];

        $xpath_group['codeBook/stdyDscr/dataAccs/useStmt/contact'] = [
            'label' => 'Data Collectors',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@affiliation' => 'affiliation', '@email' => 'email', '@URI' => 'uri'],
        ];

        $xpath_group['codeBook/stdyDscr/citation/rspStmt/AuthEnty'] = [
            'label' => 'authenty',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@affiliation' => 'affiliation'],
        ];

        $xpath_group['codeBook/stdyDscr/citation/rspStmt/othId'] = [
            'label' => 'othid',
            'type'  => 'table',
            'cols'  => ['p' => 'name', '@affiliation' => 'affiliation', '@email' => 'email', '@role' => 'role'],
        ];

        $xpath_group['codeBook/stdyDscr/citation/prodStmt/producer'] = [
            'label' => 'othid',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@abbr' => 'abbr', '@affiliation' => 'affiliation', '@role' => 'role'],
        ];

        $xpath_group['codeBook/stdyDscr/citation/distStmt/contact'] = [
            'label' => 'Data Collectors',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@affiliation' => 'affiliation', '@email' => 'email', '@URI' => 'uri'],
        ];

        $xpath_group['codeBook/stdyDscr/dataAccs/setAvail/accsPlac'] = [
            'label' => 'Data Collection Location',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@URI' => 'uri'],
        ];

        $xpath_group['codeBook/stdyDscr/stdyInfo/subject/keyword'] = [
            'label' => 'Keyword',
            'type'  => 'table',
            'cols'  => ['.' => 'keyword', '@vocab' => 'vocab', '@vocabURI' => 'uri'],
        ];

        $xpath_group['var/catgry'] = [
            'label' => 'Variable category',
            'type'  => 'table',
            'cols'  => ['catValu' => 'value', 'labl' => 'label', 'catStat' => 'stats', 'catStat/@type' => 'type'],
        ];

        $xpath_group['var/sumStat'] = [
            'label' => 'sumStat',
            'type'  => 'table',
            'cols'  => ['.' => 'value', '@type' => 'type'],
        ];

        $xpath_group['var/concept'] = [
            'label' => 'sumStat',
            'type'  => 'table',
            'cols'  => ['.' => 'name', '@vocab' => 'vocab', '@uri' => 'uri'],
        ];

        $this->table_elements = $xpath_group;

        // Validate and store file path
        if (!file_exists($file)) {
            throw new Exception("file not found: " . $file);
        }
        $this->file = $file;

        // Load all metadata sections
        $this->metadata       = array_merge(
            $this->get_ddi_part_array('docDscr'),
            $this->get_ddi_part_array('stdyDscr')
        );
        $this->metadata_array = $this->get_ddi_part_array('stdyDscr');
        $this->variable_groups = $this->extract_var_groups_array();

        // Schema validation requires empty strings rather than nulls
        array_walk_recursive($this->metadata, function (&$item) {
            if ($item === null) $item = '';
        });
    }


    // =========================================================================
    // ReaderInterface — public API
    // =========================================================================

    public function get_id(): string
    {
        // Priority 1: IDNo (mixed case)
        $idno = $this->array_to_string($this->get_key('stdy_id'), 'text');
        if (!empty(trim((string)$idno))) {
            return $idno;
        }

        // Priority 2: codeBook/@ID attribute
        $codebook = $this->get_ddi_part_array('codeBook');
        if (isset($codebook['ID']) && !empty(trim($codebook['ID']))) {
            return trim($codebook['ID']);
        }

        // Priority 3: IDNO (all uppercase)
        $xpath = 'codeBook/stdyDscr/citation/titlStmt/IDNO';
        if (isset($this->metadata[$xpath])) {
            $val = $this->metadata[$xpath];
            if (is_array($val) && !empty(trim($val[0]))) {
                return trim($val[0]);
            }
            if (!is_array($val) && !empty(trim($val))) {
                return trim($val);
            }
        }

        return '';
    }

    public function get_title(): ?string
    {
        return $this->array_to_string($this->get_key('stdy_titl'), 'text');
    }

    public function get_abbreviation(): ?string
    {
        return $this->array_to_string($this->get_key('stdy_par_titl'), 'text');
    }

    public function get_authenty()
    {
        return $this->get_key('stdy_authenty');
    }

    public function get_producers()
    {
        return $this->get_key('stdy_producer');
    }

    public function get_sponsors()
    {
        return $this->get_key('stdy_fundag');
    }

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
        $data = $this->get_key('stdy_coll_date');

        if (!$data) {
            return 0;
        }

        $years = [];
        foreach ($data as $row) {
            if (!$row) { continue; }
            $years[] = (int) $row['start'];
            $years[] = (int) $row['end'];
        }

        if (count($years) > 0) {
            $year_min = min($years);
            $year_max = max($years);
            if ($year_min == 0) {
                $year_min = $year_max;
            }
            $years = range($year_min, $year_max);
        }

        return $years;
    }

    public function get_countries()
    {
        return $this->get_key('stdy_nation');
    }

    public function get_countries_str(): ?string
    {
        $countries = $this->get_countries();
        if (!$countries) {
            return null;
        }
        return implode(', ', array_column($countries, 'name'));
    }

    public function get_topics(): array   { return []; }
    public function get_keywords(): array { return []; }

    public function get_metadata_array(): array
    {
        return $this->metadata;
    }

    public function get_bounding_box()
    {
        return null;
    }

    public function get_languages()
    {
        return null;
    }

    public function get_data_files()
    {
        $files = $this->get_ddi_part_array('fileDscr');
        if (isset($files['codeBook/fileDscr'])) {
            return $files['codeBook/fileDscr'];
        }
        return null;
    }

    public function get_variable_iterator(): DdiVariableIterator
    {
        return new DdiVariableIterator($this->file);
    }

    public function get_variable_groups()
    {
        return $this->variable_groups;
    }

    public function to_array(): array
    {
        return $this->metadata;
    }

    // =========================================================================
    // Kept public for backward compatibility (e.g. Catalog_admin.php)
    // =========================================================================

    public function get_study_IDNO(): ?string
    {
        $study = $this->get_ddi_part_array('stdyDscr');

        // Priority 1: IDNo (mixed case)
        if (!empty($study['codeBook/stdyDscr/citation/titlStmt/IDNo'][0])) {
            $idno = trim($study['codeBook/stdyDscr/citation/titlStmt/IDNo'][0]);
            if ($idno !== '') { return $idno; }
        }

        // Priority 2: codeBook/@ID
        $codebook = $this->get_ddi_part_array('codeBook');
        if (isset($codebook['ID']) && !empty(trim($codebook['ID']))) {
            return trim($codebook['ID']);
        }

        // Priority 3: IDNO (uppercase)
        if (!empty($study['codeBook/stdyDscr/citation/titlStmt/IDNO'][0])) {
            $idno = trim($study['codeBook/stdyDscr/citation/titlStmt/IDNO'][0]);
            if ($idno !== '') { return $idno; }
        }

        return null;
    }

    public function extract_study_meta_array(): array
    {
        return $this->get_ddi_part_array('stdyDscr');
    }

    public function extract_doc_meta_array(): array
    {
        return $this->get_ddi_part_array('docDscr');
    }

    public function extract_file_meta_array(): array
    {
        return $this->get_ddi_part_array('fileDscr');
    }

    public function extract_codebook_meta_array(): array
    {
        return $this->get_ddi_part_array('codeBook');
    }

    public function extract_var_groups_array(): array
    {
        $reader = new XMLReader();

        if (!$reader->open($this->file, null, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return [];
        }

        $groups = [];

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == 'varGrp') {
                $xml_obj     = simplexml_load_string($reader->readOuterXML());
                $parent_path = 'codeBook/dataDscr/' . $xml_obj->getName();
                $output      = [];
                $var_grp     = $this->get_child_elements_array($xml_obj, $parent_path, $output);
                $groups[]    = $var_grp['codeBook/dataDscr/varGrp'][0];
            }
        }

        $reader->close();
        return $groups;
    }


    // =========================================================================
    // Helpers — public API
    // =========================================================================

    public function get_key($key)
    {
        $el_name = $this->metadata_short_names[$key];
        if (array_key_exists($el_name, $this->metadata)) {
            return $this->metadata[$el_name];
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

        throw new Exception('TYPE_NOT_SUPPORTED: ' . $type);
    }


    // =========================================================================
    // Parsing internals (private)
    // =========================================================================

    private function get_ddi_part_array(string $section): array
    {
        $xml_reader = new XMLReader();

        if (!$xml_reader->open($this->file, null, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return [];
        }

        $key_values = [];

        while ($xml_reader->read()) {
            if ($xml_reader->nodeType == XMLReader::ELEMENT
                && $xml_reader->localName == 'codeBook'
                && $section == 'codeBook'
            ) {
                $key_values['ID']      = $xml_reader->getAttribute('ID');
                $key_values['xmlns']   = $xml_reader->getAttribute('xmlns');
                $key_values['version'] = $xml_reader->getAttribute('version');
                break;

            } elseif ($xml_reader->nodeType == XMLReader::ELEMENT
                && in_array($xml_reader->localName, ['docDscr', 'stdyDscr'])
                && $section === $xml_reader->localName
            ) {
                $xml_obj     = simplexml_load_string($xml_reader->readOuterXML());
                $parent_path = 'codeBook/' . $xml_obj->getName();
                $key_values  = $this->get_child_elements_array($xml_obj, $parent_path, $key_values);
                break;

            } elseif ($xml_reader->nodeType == XMLReader::ELEMENT
                && $xml_reader->localName == 'fileDscr'
                && $section == 'fileDscr'
            ) {
                $xml_obj     = simplexml_load_string($xml_reader->readOuterXML());
                $parent_path = 'codeBook/' . $xml_obj->getName();
                $key_values  = $this->get_child_elements_array($xml_obj, $parent_path, $key_values);
                // continue — file may have multiple fileDscr elements

            } elseif ($xml_reader->nodeType == XMLReader::ELEMENT
                && $xml_reader->localName === 'dataDscr'
            ) {
                if ($section !== 'fileDscr') {
                    break;
                }
            }
        }

        $xml_reader->close();

        // Transform date fields from event-based to start/end pairs
        foreach (['codeBook/stdyDscr/stdyInfo/sumDscr/timePrd', 'codeBook/stdyDscr/stdyInfo/sumDscr/collDate'] as $date_field) {
            if (array_key_exists($date_field, $key_values)) {
                $key_values[$date_field] = $this->transform_collection_dates($key_values[$date_field]);
            }
        }

        // Normalise access place — schema expects a single scalar value
        $access_place_key = 'codeBook/stdyDscr/dataAccs/setAvail/accsPlac';
        if (array_key_exists($access_place_key, $key_values)) {
            $ap = $key_values[$access_place_key];
            if (is_array($ap)) {
                $key_values[$access_place_key]          = @$ap[0]['name'];
                $key_values[$access_place_key . '_url'] = @$ap[0]['uri'];
            } else {
                $key_values[$access_place_key] = '';
            }
        }

        // Ensure IDNo is populated from any of the three fallback locations
        if ($section == 'stdyDscr') {
            $idno_xpath   = 'codeBook/stdyDscr/citation/titlStmt/IDNo';
            $idno_upper   = 'codeBook/stdyDscr/citation/titlStmt/IDNO';
            $has_idno     = isset($key_values[$idno_xpath][0]) && !empty(trim($key_values[$idno_xpath][0]));

            if (!$has_idno) {
                $codebook = $this->get_ddi_part_array('codeBook');
                if (isset($codebook['ID']) && !empty(trim($codebook['ID']))) {
                    $key_values[$idno_xpath] = [trim($codebook['ID'])];
                } elseif (isset($key_values[$idno_upper][0]) && !empty(trim($key_values[$idno_upper][0]))) {
                    $key_values[$idno_xpath] = [trim($key_values[$idno_upper][0])];
                }
            }
        }

        return $key_values;
    }


    private function get_child_elements_array(&$xml_obj, $parent_path, &$elements_array): array
    {
        if (array_key_exists($parent_path, $this->table_elements)) {

            if (!empty($this->table_elements[$parent_path]['is_nested'])) {
                if ($parent_path == 'codeBook/stdyDscr/studyDevelopment/developmentActivity') {
                    $elements_array[$parent_path][] = $this->get_development_activity($xml_obj);
                }
                return $elements_array;
            }

            $result = [];
            $this->get_element_flattened($xml_obj, null, $result);
            $cols = $this->table_elements[$parent_path]['cols'];

            foreach ($result as $key => $value) {
                if (!array_key_exists($key, $cols)) {
                    unset($result[$key]);
                }
            }

            $column_data = [];
            foreach ($cols as $xpath => $name) {
                $column_data[$name] = @$result[$xpath];
            }

            $elements_array[$parent_path][] = $column_data;
            return $elements_array;
        }

        if (trim((string)$xml_obj) != '') {
            $elements_array[$parent_path][] = trim((string)$xml_obj);
        }

        foreach ($xml_obj->attributes() as $att_name => $att_value) {
            $elements_array[$parent_path . '/@' . $att_name] = (string)$att_value;
        }

        $this->namespaces = $xml_obj->getNamespaces(true);
        foreach ($this->namespaces as $ns_value) {
            foreach ($xml_obj->children($ns_value) as $child) {
                $this->get_child_elements_array($child, $parent_path . '/' . $child->getName(), $elements_array);
            }
        }

        return $elements_array;
    }


    private function get_element_flattened(&$xml_obj, $parent_path, &$output): array
    {
        if ($parent_path) {
            $output[$parent_path] = trim((string)$xml_obj);
        } else {
            $output['.'] = trim((string)$xml_obj);
        }

        foreach ($xml_obj->attributes() as $att_name => $att_value) {
            $output[$this->make_path($parent_path, '@' . $att_name)] = (string)$att_value;
        }

        $namespaces = $xml_obj->getNamespaces(true);
        foreach ($namespaces as $ns_value) {
            foreach ($xml_obj->children($ns_value) as $child) {
                $this->get_element_flattened($child, $this->make_path($parent_path, $child->getName()), $output);
            }
        }

        return $output;
    }


    private function make_path($parent_path, $child_element): string
    {
        return $parent_path ? $parent_path . '/' . $child_element : $child_element;
    }


    private function transform_collection_dates(array $data): array
    {
        $output  = [];
        $current = null;

        foreach ($data as $item) {
            $event = $item['event'] ?? null;
            $date  = $item['date']  ?? null;
            $cycle = $item['cycle'] ?? null;

            if ($event === 'start' || $event === null) {
                if ($current !== null) {
                    $output[] = $current;
                }
                $current = ['start' => $date, 'end' => null, 'cycle' => $cycle];
            } elseif ($event === 'end') {
                if ($current !== null) {
                    $current['end'] = $date;
                    $output[]       = $current;
                    $current        = null;
                } else {
                    $output[] = ['start' => null, 'end' => $date, 'cycle' => $cycle];
                }
            }
        }

        if ($current !== null) {
            $output[] = $current;
        }

        return $output;
    }


    private function get_development_activity($node): array
    {
        return [
            'activity_type'        => $this->get_attribute_value($node, 'type'),
            'activity_description' => strval($node->description),
            'participants'         => $this->get_development_activity_participants($node),
            'resources'            => $this->get_development_activity_resources($node),
            'outcome'              => strval($node->outcome),
        ];
    }

    private function get_development_activity_resources($node): array
    {
        $output = [];
        foreach ($node->resource as $resource) {
            $output[] = [
                'name'            => strval($resource->dataSrc),
                'origin'          => strval($resource->srcOrig),
                'characteristics' => strval($resource->srcChar),
            ];
        }
        return $output;
    }

    private function get_development_activity_participants($node): array
    {
        $output = [];
        foreach ($node->participant as $participant) {
            $output[] = [
                'affiliation' => $this->get_attribute_value($participant, 'affiliation'),
                'role'        => $this->get_attribute_value($participant, 'role'),
                'name'        => strval($participant),
            ];
        }
        return $output;
    }

    private function get_attribute_value($node, string $attribute_name): ?string
    {
        foreach ($node->attributes() as $att_name => $att_value) {
            if ($att_name == $attribute_name) {
                return strval($att_value);
            }
        }
        return null;
    }
}

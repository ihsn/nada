<?php
/**
 * Solr Configuration
 * 
 * Configuration for Solr search integration and schema management
 */

// Connection settings
$config['solr_host'] = "localhost";
$config['solr_port'] = "8983";
$config['solr_collection'] = "nada";

// Timeout settings (in seconds)
$config['solr_timeout'] = 300; // 5 minutes for large variable batches
$config['solr_connect_timeout'] = 10; // 10 seconds connection timeout

// Debug mode
$config['solr_debug'] = false;


// EDisMax query options for survey/document search
$config['solr_edismax_options'] = array(
    'qf' => 'title^20.0 nation^20.0 years^30.0 authoring_entity idno^40 keywords abstract methodology var_keywords^15.0',
    'pf' => 'title^20.0 nation^20.0 years^30.0 authoring_entity idno^40 keywords abstract methodology var_keywords^15.0',
    'mm' => '2<90%',
    'ps' => '0',
    'qs' => '0',
    'tie' => '0.5',
    'bq' => '',
    'bf' => ''
);

// EDisMax query options for variable search
$config['solr_edismax_variable_options'] = array(
    'qf' => 'var_label^20.0 var_name^20.0 var_categories var_question title nation years idno^30',
    'pf' => 'var_label^20.0 var_name^20.0 var_categories var_question title nation years idno^30',
    'mm' => '1',
    'ps' => '0',
    'qs' => '0',
    'tie' => '0.5',
    'bq' => '',
    'bf' => ''
);

// Variable indexing configuration
$config['solr_variable_include_survey_metadata'] = true;

// Survey metadata fields to embed in variable documents
$config['solr_survey_metadata_fields'] = array(
    'title',
    'nation',
    'idno',
    'year_start',
    'year_end',
    'dataset_type',
    'repositories',
    'countries',
    'regions',
    'methodology',
    'keywords',
    'authoring_entity',
    'formid'
);

/**
 * Solr Schema Field Definitions
 * Used by Solr_schema_manager to programmatically manage schema
 */
$config['solr_schema_fields'] = array(
    // Variable fields with var_ prefix
    'variable_fields' => array(
        array(
            'name' => 'var_name',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'var_label',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'var_question',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'var_categories',
            'type' => 'string',
            'indexed' => true,
            'stored' => false,
            'multiValued' => false
        ),
        array(
            'name' => 'var_survey_id',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'var_uid',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'vid',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'fid',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        )
    ),
    
    // Survey document fields (doctype=1) - comprehensive schema for actual survey documents
    // Also used for fields embedded in variable documents (denormalized model)
    'survey_document_fields' => array(
        // Core identification fields
        array(
            'name' => 'doctype',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'survey_uid',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'idno',
            'type' => 'text_general',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'doi',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        // Text fields
        array(
            'name' => 'title',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        // Sortable version of title for sorting (string type with docValues)
        array(
            'name' => 'title_sort',
            'type' => 'string',
            'indexed' => true,
            'stored' => false,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'nation',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        // Sortable version of nation for sorting (string type with docValues)
        array(
            'name' => 'nation_sort',
            'type' => 'string',
            'indexed' => true,
            'stored' => false,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'authoring_entity',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'abstract',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => false,
            'multiValued' => false
        ),
        array(
            'name' => 'keywords',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => false,
            'multiValued' => false
        ),
        array(
            'name' => 'methodology',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => false,
            'multiValued' => false
        ),
        array(
            'name' => 'var_keywords',
            'type' => 'text_en',
            'indexed' => true,
            'stored' => false,
            'multiValued' => false
        ),
        // Date/Year fields
        array(
            'name' => 'year_start',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'year_end',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'years',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => true,
            'docValues' => true
        ),
        // Repository fields
        array(
            'name' => 'repositoryid',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'repo_title',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'repositories',
            'type' => 'text_general',
            'indexed' => true,
            'stored' => true,
            'multiValued' => true
        ),
        // Classification fields
        array(
            'name' => 'dataset_type',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'formid',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'form_model',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        // Multi-valued classification fields
        array(
            'name' => 'countries',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => true,
            'docValues' => true
        ),
        array(
            'name' => 'regions',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => true,
            'docValues' => true
        ),
        // Status and metadata fields
        array(
            'name' => 'published',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'created',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'changed',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        // Statistics fields
        array(
            'name' => 'varcount',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'total_views',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        array(
            'name' => 'total_downloads',
            'type' => 'pint',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false,
            'docValues' => true
        ),
        // Display fields
        array(
            'name' => 'thumbnail',
            'type' => 'string',
            'indexed' => false,
            'stored' => true,
            'multiValued' => false
        ),
        array(
            'name' => 'link_da',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        )
    )
);

// Dynamic fields for custom user facets
// Format: fq_* where * is the facet name
// Type: pint (positive integer) - stores numeric term IDs
// Example: fq_topic, fq_region, etc.

/**
 * XML Schema Reference (Backup for Manual Setup)
 * 
 * Use this XML schema when the Schema API is unavailable or for manual schema setup.
 * Copy the relevant sections to your Solr managed-schema.xml file.
 * 
 * Location: {solr_home}/{collection}/conf/managed-schema.xml
 */

$config['solr_xml_schema_reference'] = <<<'XML'
<!--
  Custom Field Type: text_en_var
  Specialized text analysis for variable keywords (removes numbers and short words)
-->
<fieldType name="text_en_var" class="solr.TextField" positionIncrementGap="100">
    <analyzer type="index">
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.StopFilterFactory" words="lang/stopwords_en.txt" ignoreCase="true"/>
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.EnglishPossessiveFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.PorterStemFilterFactory"/>
        <!-- Remove all numeric values except when numbers are part of text -->
        <filter class="solr.PatternReplaceFilterFactory" pattern="\b([0-9]+)\b" replacement="" replace="all" />
        <!-- Remove strings with length <3 -->
        <filter class="solr.PatternReplaceFilterFactory" pattern="\b\w{1,2}\b" replacement="" replace="all" />
    </analyzer>
    <analyzer type="query">
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.SynonymFilterFactory" expand="true" ignoreCase="true" synonyms="synonyms.txt"/>
        <filter class="solr.StopFilterFactory" words="lang/stopwords_en.txt" ignoreCase="true"/>
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.EnglishPossessiveFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.PorterStemFilterFactory"/>
    </analyzer>
</fieldType>

<!-- Variable Fields (var_ prefix) -->
<field name="var_name" type="text_en" indexed="true" stored="true" multiValued="false"/>
<field name="var_label" type="text_en" indexed="true" stored="true" multiValued="false"/>
<field name="var_question" type="text_en" indexed="true" stored="true" multiValued="false"/>
<field name="var_categories" type="string" indexed="true" stored="false" multiValued="false"/>
<field name="var_survey_id" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="var_uid" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="vid" type="string" indexed="true" stored="true" multiValued="false"/>
<field name="fid" type="string" indexed="true" stored="true" multiValued="false"/>

<!-- Survey Document Fields (doctype=1) -->
<field name="doctype" type="pint" indexed="true" stored="true" multiValued="false"/>
<field name="survey_uid" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="idno" type="text_general" indexed="true" stored="true" multiValued="false"/>
<field name="doi" type="string" indexed="true" stored="true" multiValued="false"/>
<field name="title" type="text_en" indexed="true" stored="true" multiValued="false"/>
<field name="nation" type="text_en" indexed="true" stored="true" multiValued="false"/>
<field name="authoring_entity" type="text_en" indexed="true" stored="true" multiValued="false"/>
<field name="keywords" type="text_en" indexed="true" stored="false" multiValued="false"/>
<field name="methodology" type="text_en" indexed="true" stored="false" multiValued="false"/>
<field name="var_keywords" type="text_en_var" indexed="true" stored="false" multiValued="false"/>
<field name="year_start" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="year_end" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="years" type="pint" indexed="true" stored="true" multiValued="true" docValues="true"/>
<field name="repositoryid" type="string" indexed="true" stored="true" multiValued="false"/>
<field name="repo_title" type="string" indexed="true" stored="true" multiValued="false"/>
<field name="repositories" type="text_general" indexed="true" stored="true" multiValued="true"/>
<field name="dataset_type" type="string" indexed="true" stored="true" multiValued="false"/>
<field name="formid" type="pint" indexed="true" stored="true" multiValued="false"/>
<field name="form_model" type="string" indexed="true" stored="true" multiValued="false"/>
<field name="countries" type="pint" indexed="true" stored="true" multiValued="true" docValues="true"/>
<field name="regions" type="pint" indexed="true" stored="true" multiValued="true" docValues="true"/>
<field name="published" type="pint" indexed="true" stored="true" multiValued="false"/>
<field name="created" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="changed" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="varcount" type="pint" indexed="true" stored="true" multiValued="false"/>
<field name="total_views" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="total_downloads" type="pint" indexed="true" stored="true" multiValued="false" docValues="true"/>
<field name="thumbnail" type="string" indexed="false" stored="true" multiValued="false"/>
<field name="link_da" type="string" indexed="true" stored="true" multiValued="false"/>

<!-- Additional fields for citations and other document types -->
<field name="abstract" type="text_en" indexed="true" stored="false" multiValued="false"/>
<field name="citation_id" type="pint" indexed="true" stored="true" multiValued="false"/>
<field name="citation_uuid" type="text_general" indexed="true" stored="true" multiValued="false"/>

<!-- Dynamic field for custom user facets (fq_*) -->
<dynamicField name="fq_*" type="pint" indexed="true" stored="true" multiValued="true"/>
XML;
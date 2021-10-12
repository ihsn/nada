<?php
/**
 *
 *  SOLR configurations for catalog search
 *
 */

/*
|--------------------------------------------------------------------------
| SOLR connection configurations
|--------------------------------------------------------------------------
|
| solr_host         SOLR host name
| solr_port         Port used by SOLR
| solr_collection   Collection path
|
| 
*/
$config['solr_host'] = "localhost";
$config['solr_port'] = "8983";
$config['solr_collection'] = "nada_dev";

$config['solr_edismax_options']=array(
  'qf'=>'title^20.0 nation^20.0 years^30.0 authoring_entity idno keywords', //query fields - qf="title^20 nation keywords"
  'pf'=>'', //phrase fields
  'mm'=>'3<90%', //minimum match - 3, 2, 75%, -25% 
  'ps'=>'',
  'qs'=>'',
  'bq'=>'',
  'bf'=>''
);

$config['solr_debug']=false;


/**
 * 
 * SOLR - Create core
 * 
 * #from the CLI run: 
 * solr create_core -c nada
 * 
 * The core folder e.g. (nada), will include a 'config' folder with the default configurations including the schema file.
 * 
 */


 /**
  *
  * SOLR Schema
  * 
  * edit managed-schema  - add following fields
  *
  **/ 
/*

  <fieldType name="text_en_var" class="solr.TextField" positionIncrementGap="100">
  <analyzer type="index">
    <tokenizer class="solr.StandardTokenizerFactory"/>
    <filter class="solr.StopFilterFactory" words="stopwords_en.txt" ignoreCase="true"/>
    <filter class="solr.LowerCaseFilterFactory"/>
    <filter class="solr.EnglishPossessiveFilterFactory"/>
    <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
    <filter class="solr.PorterStemFilterFactory"/>
    <!-- remove all numeric values except when numbers are part of text -->
    <filter class="solr.PatternReplaceFilterFactory" pattern="\b([0-9]+)\b" replacement="" replace="all" />
    <!-- remove strings with length <3 -->
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

*/
/*
    <fieldType name="text_en_var" class="solr.TextField" positionIncrementGap="100">
      <analyzer type="index">
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.StopFilterFactory" words="stopwords.txt" ignoreCase="true"/>
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.EnglishPossessiveFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.PorterStemFilterFactory"/>
        <!-- remove all numeric values except when numbers are part of text -->
        <filter class="solr.PatternReplaceFilterFactory" pattern="\b([0-9]+)\b" replacement="" replace="all" />
        <!-- remove strings with length <3 -->
        <filter class="solr.PatternReplaceFilterFactory" pattern="\b\w{1,2}\b" replacement="" replace="all" />        
      </analyzer>
      <analyzer type="query">
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.SynonymGraphFilterFactory" expand="true" ignoreCase="true" synonyms="synonyms.txt"/>
        <filter class="solr.StopFilterFactory" words="lang/stopwords_en.txt" ignoreCase="true"/>
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.EnglishPossessiveFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.PorterStemFilterFactory"/>
      </analyzer>
    </fieldType>

    
  <field name="_nest_path_" type="_nest_path_"/>
  <field name="_root_" type="string" docValues="false" indexed="true" stored="false"/>
  <field name="_text_" type="text_en" multiValued="true" indexed="true" stored="false"/>
  <field name="_version_" type="plong" indexed="false" stored="false"/>
  <field name="abstract" type="text_en" indexed="true" stored="false"/>
  <field name="alt_title" type="text_en" indexed="true" stored="false"/>
  <field name="authenty" type="text_en" indexed="true" stored="true"/>
  <field name="authoring_entity" type="text_en" multiValued="false" indexed="true" stored="true"/>
  <field name="authors" type="text_en" multiValued="true" indexed="true" stored="false"/>
  <field name="catgry" type="text_en" multiValued="false" indexed="true" stored="false"/>
  <field name="changed" type="pint" indexed="true" stored="true"/>
  <field name="citation_id" type="pint" indexed="true" stored="true"/>
  <field name="citation_uuid" type="text_general" multiValued="false" indexed="true" stored="true"/>
  <field name="countries" type="pint" multiValued="true" indexed="true" stored="true"/>
  <field name="country" type="text_general" indexed="true" stored="false"/>
  <field name="created" type="pint" indexed="true" stored="true"/>
  <field name="ctype" type="string" indexed="true" stored="true"/>
  <field name="data_coll_end" type="pint" indexed="true" stored="true"/>
  <field name="data_coll_start" type="pint" indexed="true" stored="true"/>
  <field name="dataset_type" type="text_general" multiValued="false" indexed="true" stored="true"/>
  <field name="doctype" type="pint" indexed="true" stored="true"/>
  <field name="doi" type="string" indexed="true" stored="true"/>
  <field name="flag" type="string" indexed="true" stored="true"/>
  <field name="form_model" type="string" indexed="true" stored="true"/>
  <field name="formid" type="pint" indexed="true" stored="true"/>
  <field name="ft_keywords" type="text_en" indexed="true" stored="false"/>
  <field name="id" type="string" multiValued="false" indexed="true" required="true" stored="true"/>
  <field name="idno" type="text_general" multiValued="false" indexed="true" stored="true"/>
  <field name="idnumber" type="text_general" indexed="true" stored="true"/>
  <field name="ihsn_id" type="pint" indexed="true" stored="true"/>
  <field name="keywords" type="text_en" indexed="true" stored="false"/>
  <field name="labl" type="text_en" indexed="true" stored="true"/>
  <field name="link_da" type="string" indexed="true" stored="true"/>
  <field name="metadata" type="text_en" indexed="true" stored="false"/>
  <field name="name" type="text_en" indexed="true" stored="true"/>
  <field name="nation" type="text_en" multiValued="false" indexed="true" stored="true"/>
  <field name="notes" type="text_en" indexed="true" stored="false"/>
  <field name="organization" type="text_en" indexed="true" stored="false"/>
  <field name="owner" type="text_general" indexed="true" stored="false"/>
  <field name="place_publication" type="text_en" indexed="true" stored="false"/>
  <field name="proddate" type="pint" indexed="true" stored="true"/>
  <field name="producer" type="text_en" indexed="true" stored="true"/>
  <field name="pub_date" type="string" indexed="true" stored="false"/>
  <field name="published" type="pint" indexed="true" stored="true"/>
  <field name="publisher" type="text_en" indexed="true" stored="false"/>
  <field name="qstn" type="text_en" multiValued="false" indexed="true" stored="true"/>
  <field name="related_surveys" type="pint" multiValued="true" indexed="true" stored="true"/>
  <field name="repo_title" type="string" multiValued="false" indexed="true" stored="true"/>
  <field name="repositories" type="text_general" multiValued="true" indexed="true" stored="true"/>
  <field name="repositoryid" type="string" indexed="true" stored="true"/>
  <field name="sid" type="pint" indexed="true" stored="true"/>
  <field name="sponsor" type="text_en" indexed="true" stored="true"/>
  <field name="subtitle" type="text_en" indexed="true" stored="false"/>
  <field name="survey_uid" type="pint" indexed="true" stored="true"/>
  <field name="surveyid" type="text_general" indexed="true" stored="true"/>
  <field name="text" type="text_en" multiValued="true" indexed="true" stored="false"/>
  <field name="thumbnail" type="string" multiValued="false" indexed="false" stored="true"/>
  <field name="title" type="text_en" multiValued="false" indexed="true" stored="true"/>
  <field name="topics" type="pint" multiValued="true" indexed="true" stored="false"/>
  <field name="total_downloads" type="pint" indexed="true" stored="true"/>
  <field name="total_views" type="pint" indexed="true" stored="true"/>
  <field name="type" type="string" indexed="true" stored="true"/>
  <field name="var_keywords" type="text_en_var" multiValued="false" indexed="true" stored="false"/>
  <field name="var_uid" type="pint" indexed="true" stored="true"/>
  <field name="varcount" type="pint" indexed="true" stored="true"/>
  <field name="varid" type="string" indexed="false" stored="true"/>
  <field name="vid" type="string" multiValued="false" indexed="true" stored="true"/>
  <field name="volume" type="string" indexed="true" multiValued="false" />
  <field name="issue" type="string" indexed="true" multiValued="false" />
  <field name="edition" type="string" indexed="true" multiValued="false" />

  <field name="year_end" type="pint" indexed="true" stored="true"/>
  <field name="year_start" type="pint" indexed="true" stored="true"/>
  <field name="years" type="pint" multiValued="true" indexed="true" stored="true"/>
  <dynamicField name="*_txt_en_split_tight" type="text_en_splitting_tight" indexed="true" stored="true"/>
  <dynamicField name="*_descendent_path" type="descendent_path" indexed="true" stored="true"/>
  <dynamicField name="*_ancestor_path" type="ancestor_path" indexed="true" stored="true"/>
  <dynamicField name="*_txt_en_split" type="text_en_splitting" indexed="true" stored="true"/>
  <dynamicField name="*_txt_sort" type="text_gen_sort" indexed="true" stored="true"/>
  <dynamicField name="ignored_*" type="ignored"/>
  <dynamicField name="*_txt_rev" type="text_general_rev" indexed="true" stored="true"/>
  <dynamicField name="*_phon_en" type="phonetic_en" indexed="true" stored="true"/>
  <dynamicField name="*_s_lower" type="lowercase" indexed="true" stored="true"/>
  <dynamicField name="*_txt_cjk" type="text_cjk" indexed="true" stored="true"/>
  <dynamicField name="random_*" type="random"/>
  <dynamicField name="*_t_sort" type="text_gen_sort" multiValued="false" indexed="true" stored="true"/>
  <dynamicField name="*_txt_en" type="text_en" indexed="true" stored="true"/>
  <dynamicField name="*_txt_ar" type="text_ar" indexed="true" stored="true"/>
  <dynamicField name="*_txt_bg" type="text_bg" indexed="true" stored="true"/>
  <dynamicField name="*_txt_ca" type="text_ca" indexed="true" stored="true"/>
  <dynamicField name="*_txt_cz" type="text_cz" indexed="true" stored="true"/>
  <dynamicField name="*_txt_da" type="text_da" indexed="true" stored="true"/>
  <dynamicField name="*_txt_de" type="text_de" indexed="true" stored="true"/>
  <dynamicField name="*_txt_el" type="text_el" indexed="true" stored="true"/>
  <dynamicField name="*_txt_es" type="text_es" indexed="true" stored="true"/>
  <dynamicField name="*_txt_et" type="text_et" indexed="true" stored="true"/>
  <dynamicField name="*_txt_eu" type="text_eu" indexed="true" stored="true"/>
  <dynamicField name="*_txt_fa" type="text_fa" indexed="true" stored="true"/>
  <dynamicField name="*_txt_fi" type="text_fi" indexed="true" stored="true"/>
  <dynamicField name="*_txt_fr" type="text_fr" indexed="true" stored="true"/>
  <dynamicField name="*_txt_ga" type="text_ga" indexed="true" stored="true"/>
  <dynamicField name="*_txt_gl" type="text_gl" indexed="true" stored="true"/>
  <dynamicField name="*_txt_hi" type="text_hi" indexed="true" stored="true"/>
  <dynamicField name="*_txt_hu" type="text_hu" indexed="true" stored="true"/>
  <dynamicField name="*_txt_hy" type="text_hy" indexed="true" stored="true"/>
  <dynamicField name="*_txt_id" type="text_id" indexed="true" stored="true"/>
  <dynamicField name="*_txt_it" type="text_it" indexed="true" stored="true"/>
  <dynamicField name="*_txt_ja" type="text_ja" indexed="true" stored="true"/>
  <dynamicField name="*_txt_ko" type="text_ko" indexed="true" stored="true"/>
  <dynamicField name="*_txt_lv" type="text_lv" indexed="true" stored="true"/>
  <dynamicField name="*_txt_nl" type="text_nl" indexed="true" stored="true"/>
  <dynamicField name="*_txt_no" type="text_no" indexed="true" stored="true"/>
  <dynamicField name="*_txt_pt" type="text_pt" indexed="true" stored="true"/>
  <dynamicField name="*_txt_ro" type="text_ro" indexed="true" stored="true"/>
  <dynamicField name="*_txt_ru" type="text_ru" indexed="true" stored="true"/>
  <dynamicField name="*_txt_sv" type="text_sv" indexed="true" stored="true"/>
  <dynamicField name="*_txt_th" type="text_th" indexed="true" stored="true"/>
  <dynamicField name="*_txt_tr" type="text_tr" indexed="true" stored="true"/>
  <dynamicField name="*_point" type="point" indexed="true" stored="true"/>
  <dynamicField name="*_srpt" type="location_rpt" indexed="true" stored="true"/>
  <dynamicField name="attr_*" type="text_general" multiValued="true" indexed="true" stored="true"/>
  <dynamicField name="*_txt" type="text_general" indexed="true" stored="true"/>
  <dynamicField name="*_str" type="strings" docValues="true" indexed="false" stored="false" useDocValuesAsStored="false"/>
  <dynamicField name="*_dts" type="pdate" multiValued="true" indexed="true" stored="true"/>
  <dynamicField name="*_dpf" type="delimited_payloads_float" indexed="true" stored="true"/>
  <dynamicField name="*_dpi" type="delimited_payloads_int" indexed="true" stored="true"/>
  <dynamicField name="*_dps" type="delimited_payloads_string" indexed="true" stored="true"/>
  <dynamicField name="*_is" type="pints" indexed="true" stored="true"/>
  <dynamicField name="*_ss" type="strings" indexed="true" stored="true"/>
  <dynamicField name="*_ls" type="plongs" indexed="true" stored="true"/>
  <dynamicField name="*_bs" type="booleans" indexed="true" stored="true"/>
  <dynamicField name="*_fs" type="pfloats" indexed="true" stored="true"/>
  <dynamicField name="*_ds" type="pdoubles" indexed="true" stored="true"/>
  <dynamicField name="fq_*" type="pint" multiValued="true" indexed="true" stored="true"/>
  <dynamicField name="*_dt" type="pdate" indexed="true" stored="true"/>
  <dynamicField name="*_ws" type="text_ws" indexed="true" stored="true"/>
  <dynamicField name="*_i" type="pint" indexed="true" stored="true"/>
  <dynamicField name="*_s" type="string" indexed="true" stored="true"/>
  <dynamicField name="*_l" type="plong" indexed="true" stored="true"/>
  <dynamicField name="*_t" type="text_general" multiValued="false" indexed="true" stored="true"/>
  <dynamicField name="*_b" type="boolean" indexed="true" stored="true"/>
  <dynamicField name="*_f" type="pfloat" indexed="true" stored="true"/>
  <dynamicField name="*_d" type="pdouble" indexed="true" stored="true"/>
  <dynamicField name="*_p" type="location" indexed="true" stored="true"/>
  <copyField source="authoring_entity" dest="_text_"/>
  <copyField source="nation" dest="_text_"/>
  <copyField source="title" dest="_text_"/>
*/
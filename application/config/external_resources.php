<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| External resoruces
|--------------------------------------------------------------------------
|
| 
| 
| 
|
*/

//external resources document types
$config['external_resources']['dctypes'] = array(
    "doc/adm"=>"Document, Administrative",
    "doc/anl"=>"Document, Analytical",
    "doc/qst"=>"Document, Questionnaire",
    "doc/ref"=>"Document, Reference",
    "doc/rep"=>"Document, Report",
    "doc/tec"=>"Document, Technical",
    "doc/oth"=>"Document, Other",
    "dat"=>"Database",
    "dat/micro"=>"Microdata",
    "map"=>"Map",
    "prg"=>"Program / script",
    "tbl"=>"Table",
    "pic"=>"Photo / image",
    "vid"=>"Video",
    "aud"=>"Audio",
    "web"=>"Web Site",
    "final"=>"Final"    
);


//group resources
$config['external_resources']['dctype_groups']= array(
    'questionnaires'=>array(
        'doc/qst'
    ),
    'reports'=>array(
        'doc/rep'
    ),
    'technical'=>array(
        'doc/tec'
    ),
    'reproducible'=>array(
        'repro'
    )  
);

?>
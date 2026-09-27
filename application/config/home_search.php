<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Home page example searches
|--------------------------------------------------------------------------
|
| Shown only when NADA-AI serves catalog search (see home_search_phrases() in catalog_helper.php):
|
| - "Try:" under the search box: up to 5 phrases in the visitor's language, or in home_search_language when
|   NADA-AI's model is not multilingual (the nada_ai_multilingual setting).
| - When it is multilingual, the search box also types out the first phrase of each language, the visitor's first.
|
| Pick searches your catalog answers well. 'lang' is an ISO 639-1 code from iso_languages.php, which also gives
| each phrase its text direction.
|
*/

//the language of the examples when NADA-AI's embedding model only understands one language
$config['home_search_language'] = 'en';

$config['home_search_phrases'] = array(
    array('lang' => 'en', 'text' => 'child nutrition in rural areas'),
    array('lang' => 'en', 'text' => 'poverty among rural households'),
    array('lang' => 'en', 'text' => 'how many children attend school'),
    array('lang' => 'en', 'text' => 'household spending on food'),
    array('lang' => 'en', 'text' => 'access to electricity in Africa'),
    array('lang' => 'en', 'text' => 'women in the labour force'),

    array('lang' => 'es', 'text' => 'acceso a la electricidad en África'),
    array('lang' => 'es', 'text' => 'pobreza en los hogares rurales'),
    array('lang' => 'es', 'text' => 'cuántos niños asisten a la escuela'),
    array('lang' => 'es', 'text' => 'gasto de los hogares en alimentos'),
    array('lang' => 'es', 'text' => 'mujeres en el mercado laboral'),

    array('lang' => 'fr', 'text' => 'dépenses des ménages en alimentation'),
    array('lang' => 'fr', 'text' => 'pauvreté des ménages ruraux'),
    array('lang' => 'fr', 'text' => 'scolarisation des enfants'),
    array('lang' => 'fr', 'text' => "accès à l'électricité en Afrique"),
    array('lang' => 'fr', 'text' => 'les femmes sur le marché du travail'),

    array('lang' => 'ar', 'text' => 'الفقر في المناطق الريفية'),
    array('lang' => 'ar', 'text' => 'التحاق الأطفال بالمدارس'),
    array('lang' => 'ar', 'text' => 'إنفاق الأسر على الغذاء'),
    array('lang' => 'ar', 'text' => 'الحصول على الكهرباء في أفريقيا'),
    array('lang' => 'ar', 'text' => 'المرأة في سوق العمل'),

    array('lang' => 'zh', 'text' => '女性劳动力参与率'),
    array('lang' => 'ru', 'text' => 'безработица среди молодежи'),
    array('lang' => 'pt', 'text' => 'matrícula escolar das crianças'),
);

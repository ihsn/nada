<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Search engines and Bots ignored by sitelogs
|--------------------------------------------------------------------------
|
| Site logs can grow very quickly and fill up the database. If you don't want to log everything
| you can exclude logging by user-agent. The most common search engine user-agent headers are 
| provided below:
|
*/

//enable/disable logging for search engines, by default everything is logged
$config['ignore_bot_logging'] = FALSE;

//user-agents used by search engines and bots
$config['bot_ignore'][] = 'google';
$config['bot_ignore'][] = 'ia_archiver';
$config['bot_ignore'][] = 'jeeves/teoma';
$config['bot_ignore'][] = 'googlebot';
$config['bot_ignore'][] = 'bot.html';
$config['bot_ignore'][] = 'msnbot';
$config['bot_ignore'][] = 'search.msn.com';
$config['bot_ignore'][] = 'msnbot.htm';
$config['bot_ignore'][] = 'Yahoo! Slurp';
$config['bot_ignore'][] = 'help.yahoo.com/help/us/ysearch/slurp';
$config['bot_ignore'][] = 'bot';
$config['bot_ignore'][] = 'spider';
$config['bot_ignore'][] = 'crawler';
$config['bot_ignore'][] = 'crawl';
$config['bot_ignore'][] = 'slurp';
$config['bot_ignore'][] = 'bingpreview';
$config['bot_ignore'][] = 'wget';
$config['bot_ignore'][] = 'curl';
$config['bot_ignore'][] = 'python-requests';
$config['bot_ignore'][] = 'axios';
$config['bot_ignore'][] = 'headless';
$config['bot_ignore'][] = 'playwright';
$config['bot_ignore'][] = 'puppeteer';
$config['bot_ignore'][] = 'lighthouse';
$config['bot_ignore'][] = 'httpclient';
$config['bot_ignore'][] = 'phantom';
$config['bot_ignore'][] = 'node-superagent';

?>
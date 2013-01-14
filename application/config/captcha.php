<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

//folder where images will be created. It needs to be an absolute path.
$config['captcha_img_path'] = APPPATH.'/../cache/captcha';

//url path to load captcha images
$config['captcha_img_url'] = base_url().'cache/captcha';

//path to the TTF font to use with captcha. 
$config['captcha_font_path'] = APPPATH.'/../modules/captcha/Merienda/Merienda-Bold.ttf';

//image width
$config['captcha_img_width'] = 300;

//image height
$config['captcha_img_height'] = 100;

//captcha expiration time (seconds)
$config['captcha_expiration'] = 7200;
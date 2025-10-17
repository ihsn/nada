<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Email Helper
 *
 * @package		  CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 */

// ------------------------------------------------------------------------

/**
 * Load email settings from email.php config file
 *
 *
 * @access	public
 * @return	array
 */	
if ( ! function_exists('load_email_settings'))
{
  
function load_email_settings()
{
  $ci =& get_instance();
  
  // Load email configuration from email.php config file
  $ci->config->load('email');
  
  // Get email driver (new system) or fallback to legacy useragent
  $email_driver = $ci->config->item("email_driver");
  if ($email_driver === FALSE) {
    // Fallback to legacy useragent setting
    $useragent = $ci->config->item("useragent");
    if ($useragent === FALSE) {
      return FALSE;
    }
    // Map legacy useragent to driver (default to SMTP)
    if (strpos(strtolower($useragent), 'phpmailer') !== false) {
      $email_driver = 'smtp';
    } else {
      $email_driver = 'smtp'; // Default to SMTP instead of sendmail
    }
  }
  
  $config['email_driver'] = $email_driver;
  $config['useragent'] = $ci->config->item("useragent");
  $config['protocol'] = $ci->config->item("protocol");
  $config['mailpath'] = $ci->config->item("mailpath");
  $config['smtp_host'] = $ci->config->item("smtp_host");
  $config['smtp_user'] = $ci->config->item("smtp_user");
  $config['smtp_pass'] = $ci->config->item("smtp_pass");
  $config['smtp_port'] = $ci->config->item("smtp_port");
  $config['smtp_auth'] = $ci->config->item("smtp_auth");
  $config['smtp_crypto'] = $ci->config->item("smtp_crypto");
  $config['smtp_timeout'] = $ci->config->item("smtp_timeout");
  $config['smtp_email'] = $ci->config->item("smtp_email");
  $config['smtp_display_name'] = $ci->config->item("smtp_display_name");
  $config['smtp_keepalive'] = $ci->config->item("smtp_keepalive");
  $config['smtp_auto_tls'] = $ci->config->item("smtp_auto_tls");
  $config['smtp_conn_options'] = $ci->config->item("smtp_conn_options");
  $config['smtp_debug'] = $ci->config->item("smtp_debug");
  $config['debug_output'] = $ci->config->item("debug_output");
  $config['wordwrap'] = $ci->config->item("wordwrap");
  $config['wrapchars'] = $ci->config->item("wrapchars");
  $config['mailtype'] = $ci->config->item("mailtype");
  $config['charset'] = $ci->config->item("charset");
  $config['multipart'] = $ci->config->item("multipart");
  $config['alt_message'] = $ci->config->item("alt_message");
  $config['validate'] = $ci->config->item("validate");
  $config['priority'] = $ci->config->item("priority");
  $config['newline'] = $ci->config->item("newline");
  $config['crlf'] = $ci->config->item("crlf");
  $config['dsn'] = $ci->config->item("dsn");
  $config['send_multipart'] = $ci->config->item("send_multipart");
  $config['bcc_batch_mode'] = $ci->config->item("bcc_batch_mode");
  $config['bcc_batch_size'] = $ci->config->item("bcc_batch_size");
  $config['encoding'] = $ci->config->item("encoding");
  
  // DKIM configuration
  $config['dkim_domain'] = $ci->config->item("dkim_domain");
  $config['dkim_private'] = $ci->config->item("dkim_private");
  $config['dkim_private_string'] = $ci->config->item("dkim_private_string");
  $config['dkim_selector'] = $ci->config->item("dkim_selector");
  $config['dkim_passphrase'] = $ci->config->item("dkim_passphrase");
  $config['dkim_identity'] = $ci->config->item("dkim_identity");
  
  // Driver-specific configurations
  if ($email_driver === 'sendgrid') {
    $config['sendgrid_api_key'] = $ci->config->item("sendgrid_api_key");
  } elseif ($email_driver === 'microsoft_graph') {
    $config['microsoft_graph_client_id'] = $ci->config->item("microsoft_graph_client_id");
    $config['microsoft_graph_client_secret'] = $ci->config->item("microsoft_graph_client_secret");
    $config['microsoft_graph_tenant_id'] = $ci->config->item("microsoft_graph_tenant_id");
  }
  
  return $config;
}
  
}

/**
 * 
 * source: https://github.com/ivantcholakov/codeigniter-phpmailer/blob/master/helpers/MY_email_helper.php
 * 
 */
if (!function_exists('valid_email')) {

    // This function has been borrowed from PHPMailer Version 5.2.9.
    /**
     * Check that a string looks like an email address.
     * @param string $address The email address to check
     * @param string $patternselect A selector for the validation pattern to use :
     * * `auto` Pick strictest one automatically;
     * * `pcre8` Use the squiloople.com pattern, requires PCRE > 8.0, PHP >= 5.3.2, 5.2.14;
     * * `pcre` Use old PCRE implementation;
     * * `php` Use PHP built-in FILTER_VALIDATE_EMAIL; same as pcre8 but does not allow 'dotless' domains;
     * * `html5` Use the pattern given by the HTML5 spec for 'email' type form input elements.
     * * `noregex` Don't use a regex: super fast, really dumb.
     * @return boolean
     * @static
     * @access public
     */
    // Modified by Ivan Tcholakov, 24-DEC-2013.
    //public static function validateAddress($address, $patternselect = 'auto')
    //{
    function valid_email($address) {
        $patternselect = 'auto';
    //

        // Added by Ivan Tcholakov, 17-OCT-2015.
        if (function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46') && $atpos = strpos($address, '@')) {
            $address = substr($address, 0, ++$atpos).idn_to_ascii(substr($address, $atpos), 0, INTL_IDNA_VARIANT_UTS46);
        }
        //

        if (!$patternselect or $patternselect == 'auto') {
            //Check this constant first so it works when extension_loaded() is disabled by safe mode
            //Constant was added in PHP 5.2.4
            if (defined('PCRE_VERSION')) {
                //This pattern can get stuck in a recursive loop in PCRE <= 8.0.2
                if (version_compare(PCRE_VERSION, '8.0.3') >= 0) {
                    $patternselect = 'pcre8';
                } else {
                    $patternselect = 'pcre';
                }
            } elseif (function_exists('extension_loaded') and extension_loaded('pcre')) {
                //Fall back to older PCRE
                $patternselect = 'pcre';
            } else {
                //Filter_var appeared in PHP 5.2.0 and does not require the PCRE extension
                if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
                    $patternselect = 'php';
                } else {
                    $patternselect = 'noregex';
                }
            }
        }
        switch ($patternselect) {
            case 'pcre8':
                /**
                 * Uses the same RFC5322 regex on which FILTER_VALIDATE_EMAIL is based, but allows dotless domains.
                 * @link http://squiloople.com/2009/12/20/email-address-validation/
                 * @copyright 2009-2010 Michael Rushton
                 * Feel free to use and redistribute this code. But please keep this copyright notice.
                 */
                return (boolean)preg_match(
                    '/^(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){255,})(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){65,}@)' .
                    '((?>(?>(?>((?>(?>(?>\x0D\x0A)?[\t ])+|(?>[\t ]*\x0D\x0A)?[\t ]+)?)(\((?>(?2)' .
                    '(?>[\x01-\x08\x0B\x0C\x0E-\'*-\[\]-\x7F]|\\\[\x00-\x7F]|(?3)))*(?2)\)))+(?2))|(?2))?)' .
                    '([!#-\'*+\/-9=?^-~-]+|"(?>(?2)(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\x7F]))*' .
                    '(?2)")(?>(?1)\.(?1)(?4))*(?1)@(?!(?1)[a-z0-9-]{64,})(?1)(?>([a-z0-9](?>[a-z0-9-]*[a-z0-9])?)' .
                    '(?>(?1)\.(?!(?1)[a-z0-9-]{64,})(?1)(?5)){0,126}|\[(?:(?>IPv6:(?>([a-f0-9]{1,4})(?>:(?6)){7}' .
                    '|(?!(?:.*[a-f0-9][:\]]){8,})((?6)(?>:(?6)){0,6})?::(?7)?))|(?>(?>IPv6:(?>(?6)(?>:(?6)){5}:' .
                    '|(?!(?:.*[a-f0-9]:){6,})(?8)?::(?>((?6)(?>:(?6)){0,4}):)?))?(25[0-5]|2[0-4][0-9]|1[0-9]{2}' .
                    '|[1-9]?[0-9])(?>\.(?9)){3}))\])(?1)$/isD',
                    $address
                );
            case 'pcre':
                //An older regex that doesn't need a recent PCRE
                return (boolean)preg_match(
                    '/^(?!(?>"?(?>\\\[ -~]|[^"])"?){255,})(?!(?>"?(?>\\\[ -~]|[^"])"?){65,}@)(?>' .
                    '[!#-\'*+\/-9=?^-~-]+|"(?>(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\xFF]))*")' .
                    '(?>\.(?>[!#-\'*+\/-9=?^-~-]+|"(?>(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\xFF]))*"))*' .
                    '@(?>(?![a-z0-9-]{64,})(?>[a-z0-9](?>[a-z0-9-]*[a-z0-9])?)(?>\.(?![a-z0-9-]{64,})' .
                    '(?>[a-z0-9](?>[a-z0-9-]*[a-z0-9])?)){0,126}|\[(?:(?>IPv6:(?>(?>[a-f0-9]{1,4})(?>:' .
                    '[a-f0-9]{1,4}){7}|(?!(?:.*[a-f0-9][:\]]){8,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?' .
                    '::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?))|(?>(?>IPv6:(?>[a-f0-9]{1,4}(?>:' .
                    '[a-f0-9]{1,4}){5}:|(?!(?:.*[a-f0-9]:){6,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4})?' .
                    '::(?>(?:[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4}):)?))?(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}' .
                    '|[1-9]?[0-9])(?>\.(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}))\])$/isD',
                    $address
                );
            case 'html5':
                /**
                 * This is the pattern used in the HTML5 spec for validation of 'email' type form input elements.
                 * @link http://www.whatwg.org/specs/web-apps/current-work/#e-mail-state-(type=email)
                 */
                return (boolean)preg_match(
                    '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}' .
                    '[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/sD',
                    $address
                );
            case 'noregex':
                //No PCRE! Do something _very_ approximate!
                //Check the address is 3 chars or longer and contains an @ that's not the first or last char
                return (strlen($address) >= 3
                    and strpos($address, '@') >= 1
                    and strpos($address, '@') != strlen($address) - 1);
            case 'php':
            default:
                return (boolean)filter_var($address, FILTER_VALIDATE_EMAIL);
        }
    }

}

if (!function_exists('name_email_format')) {

    function name_email_format($name, $email) {
        return $name.' <'.$email.'>';
    }

}

/* End of file MY_email_helper.php */
/* Location: ./system/helpers/MY_email_helper.php */
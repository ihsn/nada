<?php

/**
* Google Recaptcha library
*
* @author https://github.com/theylmz
* @link https://github.com/theylmz/CodeIgniter-reCaptcha-Library
**/

class recaptcha{
    public $recaptcha;
    
    function __construct(){
        $CI =&get_instance();
		$CI->config->load("captcha");//load captcha configurations
        $this->recaptcha=config_item('recaptcha');
    }
    /**
     * Gets the challenge HTML (javascript and non-javascript version).
     * This is called from the browser, and the resulting reCAPTCHA HTML widget
     * is embedded within the HTML form it was called from.
     * @param string $pubkey A public key for reCAPTCHA
     * @param string $error The error given by reCAPTCHA (optional, default is null)
     * @param boolean $use_ssl Should the request be made over ssl? (optional, default is false)

     * @return string - The HTML to be embedded in the user's form.
     */
    function recaptcha_get_html ($error = null, $use_ssl = false)
    {            
			if ($this->recaptcha['publickey'] == null || $this->recaptcha['publickey'] == '') {
                    die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
            }

            if ($use_ssl) {
                    $server = $this->recaptcha['apisecureserver'];
            } else {
                    $server = $this->recaptcha['apiserver'];
            }

            $errorpart = "";
            if ($error) {
               $errorpart = "&amp;error=" . $error;
            }
            return '
                <script type="text/javascript">
				var RecaptchaOptions = {
				   lang : \''.$this->recaptcha['language'].'\',
				   theme : \''.$this->recaptcha['theme'].'\'
				};

				</script><script type="text/javascript" src="'. $server . '/challenge?k=' . $this->recaptcha['publickey'] . $errorpart . '"></script>

				<noscript>
						<iframe src="'. $server . '/noscript?k=' . $this->recaptcha['publickey'] . $errorpart . '" height="300" width="500" frameborder="0"></iframe><br/>
						<textarea name="recaptcha_challenge_field" rows="3" cols="40" class="required"></textarea>
						<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
				</noscript>';
    }
    
    /**
      * Calls an HTTP POST function to verify if the user's guess was correct
      * @param string $privkey
      * @param string $remoteip
      * @param string $challenge
      * @param string $response
      * @param array $extra_params an array of extra variables to post to the server
      * @param boolean $debug if true this var, always return ReCaptchaResponse['is_valid']=true 
      * @return ReCaptchaResponse
      */
    function recaptcha_check_answer ($remoteip, $challenge, $response, $extra_params = array(),$debug=false)
    {
            if ($this->recaptcha['privatekey'] == null || $this->recaptcha['privatekey'] == '') {
                    die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
            }

            if ($remoteip == null || $remoteip == '') {
                    die ("For security reasons, you must pass the remote ip to reCAPTCHA");
            }

            //discard spam submissions
            if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0) {
                    $recaptcha_response = array(
                        'is_valid'=>false,
                        'error'=>'incorrect-captcha-sol'
                    );
                    return $recaptcha_response;
            }

            $response = self::_recaptcha_http_post ($this->recaptcha['verifyserver'], "/recaptcha/api/verify",
                                              array (
                                                     'privatekey' => $this->recaptcha['privatekey'],
                                                     'remoteip' => $remoteip,
                                                     'challenge' => $challenge,
                                                     'response' => $response
                                                     ) + $extra_params
                                              );

            $answers = explode ("\n", $response [1]);
            $recaptcha_response = array();
			
            if (trim ($answers [0]) == 'true') {
                    $recaptcha_response['is_valid'] = true;
            }
            else {
                    $recaptcha_response['is_valid'] = false;
                    $recaptcha_response['error'] = $answers [1];
            }
            if($debug===TRUE){
                $recaptcha_response['is_valid'] = true;
            }
            return $recaptcha_response;

    }
     /**
     * Submits an HTTP POST to a reCAPTCHA server
     * @param string $host
     * @param string $path
     * @param array $data
     * @param int port
     * @return array response
     */
    function _recaptcha_http_post($host, $path, $data, $port = 80) {

            $req = self::_recaptcha_qsencode ($data);

            $http_request  = "POST $path HTTP/1.0\r\n";
            $http_request .= "Host: $host\r\n";
            $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
            $http_request .= "Content-Length: " . strlen($req) . "\r\n";
            $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
            $http_request .= "\r\n";
            $http_request .= $req;

            $response = '';
            if( false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
                    die ('Could not open socket');
            }

            fwrite($fs, $http_request);

            while ( !feof($fs) )
                    $response .= fgets($fs, 1160); // One TCP-IP packet
            fclose($fs);
            $response = explode("\r\n\r\n", $response, 2);

            return $response;
    }
    /**
     * Encodes the given data into a query string format
     * @param $data - array of string elements to be encoded
     * @return string - encoded request
     */
    function _recaptcha_qsencode ($data) {
            $req = "";
            foreach ( $data as $key => $value )
                    $req .= $key . '=' . urlencode( stripslashes($value) ) . '&';

            // Cut the last '&'
            $req=substr($req,0,strlen($req)-1);
            return $req;
    }
    
    /**
     * gets a URL where the user can sign up for reCAPTCHA. If your application
     * has a configuration page where you enter a key, you should provide a link
     * using this function.
     * @param string $domain The domain where the page is hosted
     * @param string $appname The name of your application
     */
    function recaptcha_get_signup_url ($domain = null, $appname = null) {
            return "https://www.google.com/recaptcha/admin/create?" .  self::_recaptcha_qsencode (array ('domains' => $domain, 'app' => $appname));
    }
    
    function _recaptcha_aes_pad($val) {
	$block_size = 16;
	$numpad = $block_size - (strlen ($val) % $block_size);
	return str_pad($val, strlen ($val) + $numpad, chr($numpad));
    }
    /* Mailhide related code */

    function _recaptcha_aes_encrypt($val,$ky) {
            if (! function_exists ("mcrypt_encrypt")) {
                    die ("To use reCAPTCHA Mailhide, you need to have the mcrypt php module installed.");
            }
            $mode=MCRYPT_MODE_CBC;   
            $enc=MCRYPT_RIJNDAEL_128;
            $val=self::_recaptcha_aes_pad($val);
            return mcrypt_encrypt($enc, $ky, $val, $mode, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");
    }


    function _recaptcha_mailhide_urlbase64 ($x) {
            return strtr(base64_encode ($x), '+/', '-_');
    }
    /* gets the reCAPTCHA Mailhide url for a given email, public key and private key */
    function recaptcha_mailhide_url($email) {
            if ($this->recaptcha['mailhide']['publickey'] == '' || $this->recaptcha['mailhide']['publickey'] == null || $this->recaptcha['mailhide']['privatekey'] == "" || $this->recaptcha['mailhide']['privatekey'] == null) {
                    die ("To use reCAPTCHA Mailhide, you have to sign up for a public and private key, " .
                         "you can do so at <a href='http://www.google.com/recaptcha/mailhide/apikey'>http://www.google.com/recaptcha/mailhide/apikey</a>");
            }


            $ky = pack('H*', $this->recaptcha['mailhide']['privatekey']);
            $cryptmail = self::_recaptcha_aes_encrypt ($email, $ky);

            return "http://www.google.com/recaptcha/mailhide/d?k=" . $this->recaptcha['mailhide']['publickey'] . "&c=" . self::_recaptcha_mailhide_urlbase64 ($cryptmail);
    }
    
    /**
     * gets the parts of the email to expose to the user.
     * eg, given johndoe@example,com return ["john", "example.com"].
     * the email is then displayed as john...@example.com
     */
    function _recaptcha_mailhide_email_parts ($email) {
            $arr = preg_split("/@/", $email );

            if (strlen ($arr[0]) <= 4) {
                    $arr[0] = substr ($arr[0], 0, 1);
            } else if (strlen ($arr[0]) <= 6) {
                    $arr[0] = substr ($arr[0], 0, 3);
            } else {
                    $arr[0] = substr ($arr[0], 0, 4);
            }
            return $arr;
    }

    /**
     * Gets html to display an email address given a public an private key.
     * to get a key, go to:
     *
     * http://www.google.com/recaptcha/mailhide/apikey
     */
    function recaptcha_mailhide_html($email) {
            $emailparts = self::_recaptcha_mailhide_email_parts ($email);
            $url = self::recaptcha_mailhide_url ($this->recaptcha['mailhide']['publickey'], $this->recaptcha['mailhide']['privatekey'], $email);

            return htmlentities($emailparts[0]) . "<a href='" . htmlentities ($url) .
                    "' onclick=\"window.open('" . htmlentities ($url) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Reveal this e-mail address\">...</a>@" . htmlentities ($emailparts [1]);

    }
}
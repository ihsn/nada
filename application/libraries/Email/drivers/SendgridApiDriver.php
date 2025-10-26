<?php

namespace Email\Drivers;

defined('BASEPATH') OR exit('No direct script access allowed');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SendgridApiDriver implements EmailInterface {
    private $api_key;
    private $from_email;
    private $from_name;
    private $to_emails = array();
    private $cc_emails = array();
    private $bcc_emails = array();
    private $subject = '';
    private $message = '';
    private $is_html = true;
    private $attachments = array();
    private $error_info = '';
    private $client;
    
    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://api.sendgrid.com/v3/',
            'timeout' => 30,
        ]);
    }
    
    public function initialize($config) {
        try {
            if (isset($config['sendgrid_api_key']) && !empty($config['sendgrid_api_key'])) {
                $this->api_key = $config['sendgrid_api_key'];
            } else {
                $this->error_info = 'SendGrid API key is required';
                return false;
            }
            
            if (isset($config['smtp_email']) && !empty($config['smtp_email'])) {
                $this->from_email = $config['smtp_email'];
            }
            
            if (isset($config['smtp_display_name']) && !empty($config['smtp_display_name'])) {
                $this->from_name = $config['smtp_display_name'];
            }
            
            if (isset($config['mailtype'])) {
                $this->is_html = ($config['mailtype'] === 'html');
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return false;
        }
    }
    
    public function from($email, $name = '') {
        $this->from_email = $email;
        $this->from_name = $name;
        return $this;
    }
    
    public function to($email) {
        if (is_array($email)) {
            foreach ($email as $addr) {
                if (!empty($addr)) {
                    $this->to_emails[] = array('email' => $addr);
                }
            }
        } else {
            if (!empty($email)) {
                $this->to_emails[] = array('email' => $email);
            }
        }
        return $this;
    }
    
    public function cc($email) {
        if (is_array($email)) {
            foreach ($email as $addr) {
                if (!empty($addr)) {
                    $this->cc_emails[] = array('email' => $addr);
                }
            }
        } else {
            if (!empty($email)) {
                $this->cc_emails[] = array('email' => $email);
            }
        }
        return $this;
    }
    
    public function bcc($email) {
        if (is_array($email)) {
            foreach ($email as $addr) {
                if (!empty($addr)) {
                    $this->bcc_emails[] = array('email' => $addr);
                }
            }
        } else {
            if (!empty($email)) {
                $this->bcc_emails[] = array('email' => $email);
            }
        }
        return $this;
    }
    
    public function subject($subject) {
        $this->subject = $subject;
        return $this;
    }
    
    public function message($message) {
        $this->message = $message;
        return $this;
    }
    
    public function attach($file, $disposition = '', $newname = null, $mime = '') {
        if (file_exists($file)) {
            $content = base64_encode(file_get_contents($file));
            $filename = $newname ? $newname : basename($file);
            
            if (empty($mime)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file);
                finfo_close($finfo);
            }
            
            $this->attachments[] = array(
                'content' => $content,
                'filename' => $filename,
                'type' => $mime,
                'disposition' => $disposition ?: 'attachment'
            );
        }
        return $this;
    }
    
    public function send() {
        try {
            if (empty($this->api_key)) {
                $this->error_info = 'SendGrid API key is not set';
                return false;
            }
            
            if (empty($this->from_email)) {
                $this->error_info = 'From email is not set';
                return false;
            }
            
            if (empty($this->to_emails)) {
                $this->error_info = 'No recipients specified';
                return false;
            }
            
            $personalizations = array(
                array(
                    'to' => $this->to_emails
                )
            );
            
            if (!empty($this->cc_emails)) {
                $personalizations[0]['cc'] = $this->cc_emails;
            }
            
            if (!empty($this->bcc_emails)) {
                $personalizations[0]['bcc'] = $this->bcc_emails;
            }
            
            $payload = array(
                'personalizations' => $personalizations,
                'from' => array(
                    'email' => $this->from_email,
                    'name' => $this->from_name
                ),
                'subject' => $this->subject,
                'content' => array(
                    array(
                        'type' => $this->is_html ? 'text/html' : 'text/plain',
                        'value' => $this->message
                    )
                )
            );
            
            if (!empty($this->attachments)) {
                $payload['attachments'] = $this->attachments;
            }
            
            $response = $this->client->request('POST', 'mail/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);
            
            $status_code = $response->getStatusCode();
            
            if ($status_code >= 200 && $status_code < 300) {
                return true;
            } else {
                $this->error_info = 'SendGrid API returned status code: ' . $status_code;
                return false;
            }
            
        } catch (RequestException $e) {
            $this->error_info = 'SendGrid API Error: ' . $e->getMessage();
            if ($e->hasResponse()) {
                $response_body = (string) $e->getResponse()->getBody();
                $this->error_info .= ' | Response: ' . $response_body;
            }
            return false;
        } catch (Exception $e) {
            $this->error_info = 'Error sending email: ' . $e->getMessage();
            return false;
        }
    }
    
    public function clear() {
        $this->to_emails = array();
        $this->cc_emails = array();
        $this->bcc_emails = array();
        $this->subject = '';
        $this->message = '';
        $this->attachments = array();
        $this->error_info = '';
        return $this;
    }
    
    public function getErrorInfo() {
        return $this->error_info;
    }
    
    public function setHeader($header, $value) {
        return $this;
    }
    
    public function setCharset($charset) {
        return $this;
    }
    
    public function setMailtype($mailtype) {
        $this->is_html = ($mailtype === 'html');
        return $this;
    }
    
    public function setNewline($newline) {
        return $this;
    }
    
    public function setDebug($debug) {
        return $this;
    }
    
    public function getDebugOutput() {
        return $this->error_info;
    }
    
    public function reply_to($email, $name = '') {
        return $this;
    }
    
    public function alt_message($message) {
        return $this;
    }
    
    public function set_crlf($crlf) {
        return $this;
    }
}


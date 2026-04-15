<?php

namespace Email\Drivers;

defined('BASEPATH') OR exit('No direct script access allowed');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AcsDriver implements EmailInterface {

    private $client;
    private $endpoint = '';
    private $access_key = '';
    private $api_version = '2025-09-01';
    private $sender_address = '';
    private $charset = 'UTF-8';
    private $mailtype = 'text';
    private $newline = "\n";
    private $debug = false;
    private $error_info = '';
    private $debug_output = '';

    private $from = array('email' => '', 'name' => '');
    private $to = array();
    private $cc = array();
    private $bcc = array();
    private $reply_to = array();
    private $headers = array();
    private $subject = '';
    private $message = '';

    public function __construct() {
        $this->client = new Client(array(
            'http_errors' => false,
            'timeout' => 30,
        ));
    }

    public function initialize($config) {
        $this->error_info = '';
        $this->debug_output = '';

        $this->endpoint = rtrim(isset($config['acs_endpoint']) ? (string)$config['acs_endpoint'] : '', '/');
        $this->access_key = isset($config['acs_access_key']) ? (string)$config['acs_access_key'] : '';
        $this->sender_address = isset($config['smtp_email']) ? (string)$config['smtp_email'] : '';

        if (!empty($config['acs_api_version'])) {
            $this->api_version = (string)$config['acs_api_version'];
        }

        if (!empty($config['acs_connection_string'])) {
            $this->apply_connection_string($config['acs_connection_string']);
        }

        if (!empty($config['charset'])) {
            $this->charset = (string)$config['charset'];
        }

        if (!empty($config['mailtype'])) {
            $this->mailtype = (string)$config['mailtype'];
        }

        if (isset($config['newline'])) {
            $this->newline = (string)$config['newline'];
        }

        if (isset($config['debug'])) {
            $this->debug = (bool)$config['debug'];
        }

        return true;
    }

    public function from($email, $name = '') {
        $this->from = array(
            'email' => trim((string)$email),
            'name' => trim((string)$name)
        );
        return $this;
    }

    public function to($email) {
        $this->to[] = $this->normalize_address($email);
        return $this;
    }

    public function cc($email) {
        $this->cc[] = $this->normalize_address($email);
        return $this;
    }

    public function bcc($email) {
        $this->bcc[] = $this->normalize_address($email);
        return $this;
    }

    public function subject($subject) {
        $this->subject = (string)$subject;
        return $this;
    }

    public function message($message) {
        $this->message = (string)$message;
        return $this;
    }

    public function send() {
        try {
            if (empty($this->endpoint)) {
                throw new \Exception('ACS endpoint is not configured');
            }

            if (empty($this->access_key)) {
                throw new \Exception('ACS access key is not configured');
            }

            if (empty($this->to)) {
                throw new \Exception('At least one recipient is required');
            }

            $sender_address = $this->from['email'] ?: $this->sender_address;

            if (empty($sender_address)) {
                throw new \Exception('ACS sender address is not configured');
            }

            $request_path = '/emails:send?api-version=' . rawurlencode($this->api_version);
            $request_url = $this->endpoint . $request_path;
            $payload = $this->build_payload($sender_address);
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

            if ($body === false) {
                throw new \Exception('Failed to encode ACS email payload');
            }

            $date = gmdate('D, d M Y H:i:s') . ' GMT';
            $content_hash = base64_encode(hash('sha256', $body, true));
            $host = parse_url($this->endpoint, PHP_URL_HOST);

            if (!$host) {
                throw new \Exception('Invalid ACS endpoint');
            }

            $string_to_sign = "POST\n" . $request_path . "\n" . $date . ';' . $host . ';' . $content_hash;
            $signature = base64_encode(hash_hmac('sha256', $string_to_sign, base64_decode($this->access_key), true));
            $authorization = 'HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature=' . $signature;

            $headers = array(
                'Authorization' => $authorization,
                'x-ms-date' => $date,
                'x-ms-content-sha256' => $content_hash,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            );

            $response = $this->client->post($request_url, array(
                'headers' => $headers,
                'body' => $body,
            ));

            $status_code = $response->getStatusCode();
            $response_body = (string)$response->getBody();
            $operation_location = $response->getHeaderLine('Operation-Location');
            $retry_after = $response->getHeaderLine('retry-after');

            $this->debug_output = "ACS endpoint: " . $request_url . "\n";
            $this->debug_output .= "HTTP status: " . $status_code . "\n";

            if ($operation_location) {
                $this->debug_output .= "Operation-Location: " . $operation_location . "\n";
            }

            if ($retry_after) {
                $this->debug_output .= "retry-after: " . $retry_after . "\n";
            }

            if ($this->debug && $response_body !== '') {
                $this->debug_output .= "Response body: " . $response_body . "\n";
            }

            if ($status_code === 202) {
                return true;
            }

            $this->error_info = 'ACS send failed with HTTP status ' . $status_code;

            if ($response_body !== '') {
                $this->error_info .= ': ' . $response_body;
            }

            return false;
        } catch (RequestException $e) {
            $this->error_info = $e->getMessage();
            if ($e->hasResponse()) {
                $this->debug_output .= "HTTP status: " . $e->getResponse()->getStatusCode() . "\n";
                $this->debug_output .= "Response body: " . (string)$e->getResponse()->getBody() . "\n";
            }
            return false;
        } catch (\Exception $e) {
            $this->error_info = $e->getMessage();
            return false;
        }
    }

    public function clear() {
        $this->from = array('email' => '', 'name' => '');
        $this->to = array();
        $this->cc = array();
        $this->bcc = array();
        $this->reply_to = array();
        $this->headers = array();
        $this->subject = '';
        $this->message = '';
        $this->error_info = '';
        $this->debug_output = '';
        return $this;
    }

    public function getErrorInfo() {
        return $this->error_info;
    }

    public function setHeader($header, $value) {
        $header = trim((string)$header);
        $value = trim(str_replace(array("\r", "\n"), '', (string)$value));

        if (strcasecmp($header, 'Reply-To') === 0) {
            $this->reply_to[] = $this->normalize_address($value);
            return $this;
        }

        $this->headers[$header] = $value;
        return $this;
    }

    public function setCharset($charset) {
        $this->charset = (string)$charset;
        return $this;
    }

    public function setMailtype($mailtype) {
        $this->mailtype = (string)$mailtype;
        return $this;
    }

    public function setNewline($newline) {
        $this->newline = (string)$newline;
        return $this;
    }

    public function setDebug($debug) {
        $this->debug = (bool)$debug;
        return $this;
    }

    public function getDebugOutput() {
        return $this->debug_output;
    }

    private function apply_connection_string($connection_string) {
        $parts = explode(';', $connection_string);
        $parsed = array();

        foreach ($parts as $part) {
            if (strpos($part, '=') === false) {
                continue;
            }

            list($key, $value) = array_map('trim', explode('=', $part, 2));
            $parsed[strtolower($key)] = $value;
        }

        if (empty($this->endpoint) && isset($parsed['endpoint'])) {
            $this->endpoint = rtrim($parsed['endpoint'], '/');
        }

        if (empty($this->access_key) && isset($parsed['accesskey'])) {
            $this->access_key = $parsed['accesskey'];
        }
    }

    private function build_payload($sender_address) {
        $content = array(
            'subject' => $this->subject,
        );

        if ($this->mailtype === 'html') {
            $content['html'] = $this->message;
            $content['plainText'] = $this->html_to_text($this->message);
        } else {
            $content['plainText'] = $this->message;
        }

        $payload = array(
            'senderAddress' => $sender_address,
            'content' => $content,
            'recipients' => array(
                'to' => $this->filter_addresses($this->to),
            ),
        );

        $cc = $this->filter_addresses($this->cc);
        if (!empty($cc)) {
            $payload['recipients']['cc'] = $cc;
        }

        $bcc = $this->filter_addresses($this->bcc);
        if (!empty($bcc)) {
            $payload['recipients']['bcc'] = $bcc;
        }

        $reply_to = $this->filter_addresses($this->reply_to);
        if (!empty($reply_to)) {
            $payload['replyTo'] = $reply_to;
        }

        if (!empty($this->headers)) {
            $payload['headers'] = $this->headers;
        }

        return $payload;
    }

    private function filter_addresses($addresses) {
        $output = array();

        foreach ($addresses as $address) {
            if (!empty($address['address'])) {
                $output[] = $address;
            }
        }

        return $output;
    }

    private function normalize_address($email) {
        $email = trim((string)$email);
        $display_name = '';

        if (preg_match('/^(.*)<(.+)>$/', $email, $matches)) {
            $display_name = trim(trim($matches[1]), '"\'');
            $email = trim($matches[2]);
        }

        $address = array('address' => $email);

        if ($display_name !== '') {
            $address['displayName'] = $display_name;
        }

        return $address;
    }

    private function html_to_text($html) {
        $text = str_replace(array('{unwrap}', '{/unwrap}'), '', $html);
        $text = preg_replace('/<br\s*\/?>/i', $this->newline, $text);
        $text = preg_replace('/<\/p>/i', $this->newline . $this->newline, $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, $this->charset ?: 'UTF-8');
        return trim($text);
    }
}

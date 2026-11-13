<?php
/**
 * Citation_url_checker
 *
 * Checks that a citation's URL answers, for the "check link" button of the citation form (one citation at a
 * time, run by an editor). The URL is user data, so the check is guarded against server-side request forgery:
 *  - http and https only, on ports 80, 443, 8080, 8443
 *  - the host is resolved here and refused if any address is private, loopback, link-local or reserved
 *  - the connection is pinned to the address that was checked (no second DNS lookup, so no rebinding)
 *  - redirects are not followed; a redirect counts as "link works"
 *
 * Status codes (citations.url_status): 0 = not found / error response, 1 = link works,
 * 2 = could not be checked (no answer, blocked address, bad URL), 3 = no URL.
 */
class Citation_url_checker
{
	const STATUS_NOT_FOUND = 0;
	const STATUS_OK        = 1;
	const STATUS_UNKNOWN   = 2;
	const STATUS_NO_URL    = 3;

	private static $allowed_ports = array(80, 443, 8080, 8443);

	/** Resolver for tests: function ($host) => string[] of IP addresses */
	public $resolver = NULL;

	/** Requester for tests: function ($method, $url, $ip, $port) => int HTTP status (0 = no answer) */
	public $requester = NULL;

	/**
	 * @return array{status:int, http_code:int|null, message:string}
	 */
	public function check($url)
	{
		$url = trim((string) $url);
		if ($url === '') {
			return $this->result(self::STATUS_NO_URL, NULL, 'NO_URL');
		}

		$parts = parse_url(str_replace(' ', '%20', $url));
		if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
			return $this->result(self::STATUS_UNKNOWN, NULL, 'INVALID_URL');
		}

		$scheme = strtolower($parts['scheme']);
		if ($scheme !== 'http' && $scheme !== 'https') {
			return $this->result(self::STATUS_UNKNOWN, NULL, 'SCHEME_NOT_ALLOWED');
		}
		if (isset($parts['user']) || isset($parts['pass'])) {
			return $this->result(self::STATUS_UNKNOWN, NULL, 'CREDENTIALS_NOT_ALLOWED');
		}

		$port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
		if (!in_array($port, self::$allowed_ports, true)) {
			return $this->result(self::STATUS_UNKNOWN, NULL, 'PORT_NOT_ALLOWED');
		}

		$host = trim($parts['host'], '[]');
		$ips = $this->resolve($host);
		if (empty($ips)) {
			return $this->result(self::STATUS_UNKNOWN, NULL, 'HOST_NOT_FOUND');
		}
		foreach ($ips as $ip) {
			if (!self::is_public_ip($ip)) {
				return $this->result(self::STATUS_UNKNOWN, NULL, 'ADDRESS_NOT_ALLOWED');
			}
		}

		// HEAD first; some servers refuse it, then a one-byte GET
		$code = $this->request('HEAD', $url, $host, $port, $ips[0]);
		if (in_array($code, array(400, 403, 405, 501), true)) {
			$code = $this->request('GET', $url, $host, $port, $ips[0]);
		}

		if ($code === 0) {
			return $this->result(self::STATUS_UNKNOWN, NULL, 'NO_RESPONSE');
		}
		if ($code >= 200 && $code < 400) {
			return $this->result(self::STATUS_OK, $code, 'OK');
		}
		return $this->result(self::STATUS_NOT_FOUND, $code, 'HTTP_' . $code);
	}

	/**
	 * True for a public internet address (IPv4 or IPv6).
	 */
	public static function is_public_ip($ip)
	{
		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			return false;
		}
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return false;
		}
		if (strpos($ip, ':') !== false) {
			$packed = inet_pton($ip);
			// IPv4-mapped (::ffff:a.b.c.d): judge the embedded IPv4 address
			if (substr($packed, 0, 12) === "\0\0\0\0\0\0\0\0\0\0\xff\xff") {
				return self::is_public_ip(inet_ntop(substr($packed, 12)));
			}
			// unique local fc00::/7, link-local fe80::/10, multicast ff00::/8, loopback and unspecified
			$first = ord($packed[0]);
			$second = ord($packed[1]);
			if (($first & 0xfe) === 0xfc || ($first === 0xfe && ($second & 0xc0) === 0x80) || $first === 0xff) {
				return false;
			}
			if ($packed === inet_pton('::1') || $packed === inet_pton('::')) {
				return false;
			}
			// forms that carry an IPv4 address inside: 6to4 2002::/16, Teredo 2001::/32, NAT64 64:ff9b::/96
			$prefix2 = substr($packed, 0, 2);
			if ($prefix2 === "\x20\x02" || substr($packed, 0, 4) === "\x20\x01\x00\x00" || substr($packed, 0, 12) === substr(inet_pton('64:ff9b::'), 0, 12)) {
				return false;
			}
		}
		else {
			$long = ip2long($ip);
			if ($long !== false) {
				// 100.64.0.0/10 (carrier-grade NAT) and 224.0.0.0/4 (multicast) are not covered by the PHP filter flags
				if (($long & 0xffc00000) === 0x64400000 || ($long & 0xf0000000) === 0xe0000000) {
					return false;
				}
			}
		}
		return true;
	}

	private function resolve($host)
	{
		if ($this->resolver) {
			return call_user_func($this->resolver, $host);
		}
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return array($host);
		}
		$ips = array();
		$a = @dns_get_record($host, DNS_A);
		foreach ((array) $a as $r) {
			if (!empty($r['ip'])) {
				$ips[] = $r['ip'];
			}
		}
		$aaaa = @dns_get_record($host, DNS_AAAA);
		foreach ((array) $aaaa as $r) {
			if (!empty($r['ipv6'])) {
				$ips[] = $r['ipv6'];
			}
		}
		return $ips;
	}

	private function request($method, $url, $host, $port, $ip)
	{
		if ($this->requester) {
			return (int) call_user_func($this->requester, $method, $url, $ip, $port);
		}

		$ch = curl_init(str_replace(' ', '%20', $url));
		$opts = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 8,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_USERAGENT      => 'NADA-citations-link-check/1.0',
			// connect to the address that was checked, whatever DNS says now
			CURLOPT_RESOLVE        => array($host . ':' . $port . ':' . (strpos($ip, ':') !== false ? '[' . $ip . ']' : $ip)),
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_MAXFILESIZE    => 1024 * 1024,
		);
		if ($method === 'HEAD') {
			$opts[CURLOPT_NOBODY] = true;
		}
		else {
			$opts[CURLOPT_RANGE] = '0-0';
		}
		curl_setopt_array($ch, $opts);
		curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $code;
	}

	private function result($status, $http_code, $message)
	{
		return array('status' => $status, 'http_code' => $http_code, 'message' => $message);
	}
}

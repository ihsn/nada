<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Extends CI_Input to support configurable reverse-proxy client IP headers.
 */
class MY_Input extends CI_Input {

	/**
	 * @return string
	 */
	public function ip_address()
	{
		if ($this->ip_address !== FALSE)
		{
			return $this->ip_address;
		}

		$proxy_ips = config_item('proxy_ips');
		if ( ! empty($proxy_ips) && ! is_array($proxy_ips))
		{
			$proxy_ips = explode(',', str_replace(' ', '', $proxy_ips));
		}

		$this->ip_address = $this->server('REMOTE_ADDR');

		if ($proxy_ips)
		{
			$spoof = NULL;

			foreach ($this->get_proxy_client_ip_headers() as $header)
			{
				if (($spoof = $this->server($header)) !== NULL)
				{
					sscanf($spoof, '%[^,]', $spoof);
					$spoof = trim($spoof);

					if ( ! $this->valid_ip($spoof))
					{
						$spoof = NULL;
					}
					else
					{
						break;
					}
				}
			}

			if ($spoof)
			{
				for ($i = 0, $c = count($proxy_ips); $i < $c; $i++)
				{
					if (strpos($proxy_ips[$i], '/') === FALSE)
					{
						if ($proxy_ips[$i] === $this->ip_address)
						{
							$this->ip_address = $spoof;
							break;
						}

						continue;
					}

					isset($separator) OR $separator = $this->valid_ip($this->ip_address, 'ipv6') ? ':' : '.';

					if (strpos($proxy_ips[$i], $separator) === FALSE)
					{
						continue;
					}

					if ( ! isset($ip, $sprintf))
					{
						if ($separator === ':')
						{
							$ip = explode(':',
								str_replace('::',
									str_repeat(':', 9 - substr_count($this->ip_address, ':')),
									$this->ip_address
								)
							);

							for ($j = 0; $j < 8; $j++)
							{
								$ip[$j] = intval($ip[$j], 16);
							}

							$sprintf = '%016b%016b%016b%016b%016b%016b%016b%016b';
						}
						else
						{
							$ip = explode('.', $this->ip_address);
							$sprintf = '%08b%08b%08b%08b';
						}

						$ip = vsprintf($sprintf, $ip);
					}

					sscanf($proxy_ips[$i], '%[^/]/%d', $netaddr, $masklen);

					if ($separator === ':')
					{
						$netaddr = explode(':', str_replace('::', str_repeat(':', 9 - substr_count($netaddr, ':')), $netaddr));
						for ($j = 0; $j < 8; $j++)
						{
							$netaddr[$j] = intval($netaddr[$j], 16);
						}
					}
					else
					{
						$netaddr = explode('.', $netaddr);
					}

					if (strncmp($ip, vsprintf($sprintf, $netaddr), $masklen) === 0)
					{
						$this->ip_address = $spoof;
						break;
					}
				}
			}
		}

		if ( ! $this->valid_ip($this->ip_address))
		{
			return $this->ip_address = '0.0.0.0';
		}

		return $this->ip_address;
	}

	/**
	 * @return array
	 */
	private function get_proxy_client_ip_headers()
	{
		$headers = config_item('proxy_client_ip_headers');

		if ( ! empty($headers) && is_array($headers))
		{
			return $headers;
		}

		return array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'HTTP_X_CLIENT_IP',
			'HTTP_X_CLUSTER_CLIENT_IP',
		);
	}
}

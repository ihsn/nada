<?php

/**
 * Omniture Measurement Library (PHP)
 * Copyright 1996-2010. Adobe, Inc. All Rights Reserved
 */

class OmnitureMeasurement
{
	// private variables (internal use only)
	private $version;
	private $requiredVarList;
	private $accountVarList;
	private $accountConfigList;
	private $manageVisitorID;
	private $persistVisitorIDInLinks;
	private static $singletonInstance;	// only used if the singleton interface is invoked

	// private account variables (internal use only)
	private $pe;
	private $pev1;
	private $pev2;
	
	// constants (internal use only)
	const PARAM_PREFIX = 's_';

	// public variables (properties)
	public $account;
	public $linkURL;
	public $linkName;
	public $linkType;
	public $linkTrackVars;
	public $linkTrackEvents;
	public $dc;
	public $trackingServer;
	public $trackingServerSecure;
	public $userAgent;
	public $dynamicVariablePrefix;
	public $visitorID;
	public $vmk;
	public $visitorMigrationKey;
	public $visitorMigrationServer;
	public $visitorMigrationServerSecure;
	public $charSet;
	public $visitorNamespace;
	public $cookieDomainPeriods;
	public $cookieLifetime;
	public $pageName;
	public $pageURL;
	public $referrer;
	public $currencyCode;
	public $purchaseID;
	public $variableProvider;
	public $channel;
	public $server;
	public $pageType;
	public $transactionID;
	public $campaign;
	public $state;
	public $zip;
	public $events;
	public $products;
	public $hier1;
	public $hier2;
	public $hier3;
	public $hier4;
	public $hier5;
	public $prop1;
	public $prop2;
	public $prop3;
	public $prop4;
	public $prop5;
	public $prop6;
	public $prop7;
	public $prop8;
	public $prop9;
	public $prop10;
	public $prop11;
	public $prop12;
	public $prop13;
	public $prop14;
	public $prop15;
	public $prop16;
	public $prop17;
	public $prop18;
	public $prop19;
	public $prop20;
	public $prop21;
	public $prop22;
	public $prop23;
	public $prop24;
	public $prop25;
	public $prop26;
	public $prop27;
	public $prop28;
	public $prop29;
	public $prop30;
	public $prop31;
	public $prop32;
	public $prop33;
	public $prop34;
	public $prop35;
	public $prop36;
	public $prop37;
	public $prop38;
	public $prop39;
	public $prop40;
	public $prop41;
	public $prop42;
	public $prop43;
	public $prop44;
	public $prop45;
	public $prop46;
	public $prop47;
	public $prop48;
	public $prop49;
	public $prop50;
	public $eVar1;
	public $eVar2;
	public $eVar3;
	public $eVar4;
	public $eVar5;
	public $eVar6;
	public $eVar7;
	public $eVar8;
	public $eVar9;
	public $eVar10;
	public $eVar11;
	public $eVar12;
	public $eVar13;
	public $eVar14;
	public $eVar15;
	public $eVar16;
	public $eVar17;
	public $eVar18;
	public $eVar19;
	public $eVar20;
	public $eVar21;
	public $eVar22;
	public $eVar23;
	public $eVar24;
	public $eVar25;
	public $eVar26;
	public $eVar27;
	public $eVar28;
	public $eVar29;
	public $eVar30;
	public $eVar31;
	public $eVar32;
	public $eVar33;
	public $eVar34;
	public $eVar35;
	public $eVar36;
	public $eVar37;
	public $eVar38;
	public $eVar39;
	public $eVar40;
	public $eVar41;
	public $eVar42;
	public $eVar43;
	public $eVar44;
	public $eVar45;
	public $eVar46;
	public $eVar47;
	public $eVar48;
	public $eVar49;
	public $eVar50;
	public $list1;
	public $list2;
	public $list3;
	public $timestamp;
	public $ssl;
	public $linkLeaveQueryString;
	public $mobile;
	public $debugTracking;
	public $debugFilename;
	public $usePlugins;
	public $sendFromServer;
	public $botDetection;
	public $imageDimensions;

	/**
	 * Singleton interface to obtain a single OmnitureMeasurement
	 * instance within the entire request.  Do not invoke this method
	 * if multiple OmnitureMeasurement instances are being used within
	 * the request.
	 *
	 * @return Singleton OmnitureMeasurement instance
	 */
	public static function getInstance()
	{
		if (self::$singletonInstance == NULL) {
			self::$singletonInstance = new OmnitureMeasurement();
		}

		return self::$singletonInstance;
	}

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$s =& $this;

		// setup required var list
		$s->requiredVarList = array(
			"dynamicVariablePrefix",
			"visitorID",
			"vmk",
			"visitorMigrationKey",
			"visitorMigrationServer",
			"visitorMigrationServerSecure",
			"charSet",
			"visitorNamespace",
			"cookieDomainPeriods",
			"cookieLifetime",
			"pageName",
			"pageURL",
			"referrer",
			"currencyCode",
			"timestamp",
			"pe",
			"pev1",
			"pev2"
		);

		// setup account var list
		$s->accountVarList = array_merge($s->requiredVarList, array(
			"purchaseID",
			"variableProvider",
			"channel",
			"server",
			"pageType",
			"transactionID",
			"campaign",
			"state",
			"zip",
			"events",
			"products"
		));

		for ($i = 1 ; $i <= 5 ; $i++) {
			$s->accountVarList[] = "hier$i";
		}
		
		for ($i = 1 ; $i <= 50 ; $i++) {
			$s->accountVarList[] = "prop$i";
		}
		
		for ($i = 1 ; $i <= 50 ; $i++) {
			$s->accountVarList[] = "eVar$i";
		}
		
		for ($i = 1 ; $i <= 3 ; $i++) {
			$s->accountVarList[] = "list$i";
		}

		// setup account config list
		$s->accountConfigList = array(
			"linkURL",
			"linkName",
			"linkType",
			"linkTrackVars",
			"linkTrackEvents",
			"dc",
			"trackingServer",
			"trackingServerSecure",
			"userAgent"
		);

		// set default account vars
		$s->linkLeaveQueryString = false;
		$s->debugTracking = false;
		$s->debugFilename = '';
		$s->usePlugins = false;
		$s->mobile = false;
		$s->manageVisitorID = false;
		$s->persistVisitorIDInLinks = false;
		$s->ssl = $s->getDefaultSSL();
		$s->charSet = $s->getDefaultCharSet();
		$s->botDetection = false;
		$s->sendFromServer = false;	// default to rendering an IMG tag
		$s->userAgent = $s->getDefaultUserAgent();  // technically only used if sendFromServer is true, but this lets people append to it
		
		$s->version = "PHP-1.1";	// PHP
	}

	// public methods

	/**
	 * Track the current state of the OmnitureMeasurement instance
	 * (which either prints out an img tag or sends a direct request
	 * to Omniture Collection Servers), using an associative array
	 * of variable overrides.
	 *
	 * @param variableOverrides array of key-value pairs (strings) representing variables and their values to be applied only for this track call.
	 */
	public function track($variableOverrides = NULL)
	{
		$s =& $this;

		// build cacheBusting
		$cacheBusting = 's' . rand(1, 1000000000);

		// build timestamp (start of query string)
		$queryString = 't=' . $s->escape($s->getFormattedTimestamp());

		// apply variable overrides (if applicable)
		if ($variableOverrides != NULL) {
			$variableOverridesBackup = array();
			$s->variableOverridesBuild($variableOverridesBackup);
			$s->variableOverridesApply($variableOverrides);
		}

		// call delegate (doPlugins)
		if ($s->usePlugins && $s->doPlugins && is_callable($s->doPlugins)) {
			call_user_func($s->doPlugins, $s);
		}

		$s->validateRequiredRequestVariables();

		// build query string and make request (bot detection if applicable)
		if ($s->isSetString($s->account) && !($s->botDetection && $s->isBot())) {
			$s->handleLinkTracking();
			$queryString .= $s->getQueryString();
			$s->makeRequest($cacheBusting, $queryString);
		}

		// restore variables (if applicable)
		if ($variableOverrides != NULL) {
			$s->variableOverridesApply($variableOverridesBackup);
		}

		// reset one-timers
		$s->referrer = '';
		$s->pe = '';
		$s->pev1 = '';
		$s->pev2 = '';
		$s->linkURL = '';
		$s->linkName = '';
		$s->linkType = '';
	}

	/**
	 * Track the current state of the OmnitureMeasurement instance
	 * (which either prints out an img tag or sends a direct request
	 * to Omniture Collection Servers), corresponding to a specific
	 * link or event, using an associative array of variable overrides.
	 *
	 * @param linkURL URL of the link (may be empty/null if not applicable)
	 * @param linkType Letter indicating the type of link
	 * @param linkName Name of the link	 
	 * @param variableOverrides array of key-value pairs (strings) representing variables and their values to be applied only for this track call.
	 */
	public function trackLink($linkURL, $linkType, $linkName, $variableOverrides = NULL)
	{
		$s =& $this;

		$s->linkURL = $linkURL;
		$s->linkType = $linkType;
		$s->linkName = $linkName;

		$s->track($variableOverrides);
	}

	/**
	 * Clear the majority of variables currently set on the OmnitureMeasurement instance.
	 */
	public function clearVars()
	{
		$s =& $this;
		
		$varPrefix = NULL;
		foreach ($s->accountVarList as $varKey) {

			// get key prefix for prop/eVar/hier (if applicable)
			if (strlen($varKey) > 4) {
				$varPrefix = substr($varKey, 0, 4);
			} else {
				$varPrefix = '';
			}

			// only clear certain vars
			if ($varKey == "channel" ||
				$varKey == "events" ||
				$varKey == "purchaseID" ||
				$varKey == "transactionID" ||
				$varKey == "products" ||
				$varKey == "state" ||
				$varKey == "zip" ||
				$varKey == "campaign" ||
				$varPrefix == "prop" ||
				$varPrefix == "eVar" ||
				$varPrefix == "list" ||
				$varPrefix == "hier") {

				$s->setAccountVar($varKey, NULL);
			}
		}
	}

	/**
	 * Allow the OmnitureMeasurement instance to manage visitor identification
	 * instead of Omniture Collection Servers.
	 * 
	 * @param persistVisitorIDInLinks persist the visitor id in all links/forms on the page
	 * @param redirectOnGeneratedVisitorID when a new visitor id is generated, redirect to self with the new id in the query string
	 * @param additionalRedirectVars array of key-value pairs of which will be added to the query string of the redirect url
	 */
	public function manageVisitorID($persistVisitorIDInLinks = false, $redirectOnGeneratedVisitorID = false, $additionalRedirectVars = array())
	{
		$s =& $this;
		
		$s->manageVisitorID = true;
		
		// setup output buffer/callback from persisting visitor ID in links (at end of request)
		if ($persistVisitorIDInLinks) {
			ob_start(array($s, 'rewriteLinksCallback'));	// does not affect headers (cookie/redirect)
			$s->persistVisitorIDInLinks = true;
		}
		
		// populate visitor id (if not already manually set)
		if (!$s->isSetString($s->visitorID)) {
			if ($s->getManagedVisitorID()) {
				$s->visitorID = $s->getManagedVisitorID();
			} elseif (!$s->getOmnitureQueryParam('vid')) {	// only generate an id if there is no managed id available and vid is not on the query string (if persistVisitorIDInLinks is off, this may be on the query string)
				$s->visitorID = $s->generateVisitorID();
				$generated_visitor_id = true;
			}	// otherwise, visitor id is left blank, and the ip address is used
		}

		// set visitor id in cookie -- always set the cookie (even if one already exists), because may need to overwrite old cookie value
		$s->setVisitorIDCookie();
		
		// redirect to self (with generated visitor id) if visitor id was generated and redirection parameter was specified
		if ($generated_visitor_id && $redirectOnGeneratedVisitorID) {
			// build redirect vars string
			$redirect_vars_string = $s->getOmnitureParamName('vid') . '=' . $s->escape($s->visitorID);	// add generated visitor id (also acts as flag to not redirect again)
			foreach ($additionalRedirectVars as $key => $value) {										// add additional redirect vars
				$redirect_vars_string .= '&' . $s->escape($key) . '=' . $s->escape($value);
			}
			
			// prepend redirect vars string to beginning of query string (because end of query string has more potential to be cutoff - url length restrictions)
			$page_url = $s->getDefaultPageURL();
			$query_string_start = strpos($page_url, '?');
			if ($query_string_start === false) {
				$redirect_url = $page_url . '?' . $redirect_vars_string;
			} else {
				$redirect_url = substr($page_url, 0, $query_string_start + 1) . $redirect_vars_string . '&' . substr($page_url, $query_string_start + 1);
			}
			
			// redirect
			header("Location: $redirect_url");
			exit;
		}
	}
	
	// private methods
	
	private function getManagedVisitorID()
	{
		$s =& $this;
		
		if ($s->mobile && $s->getMobileSubscriberID()) {
			return $s->getMobileSubscriberID();
		} elseif ($s->getOmnitureCookie('vid')) {
			return $s->getOmnitureCookie('vid');
		} elseif ($s->persistVisitorIDInLinks && $s->getOmnitureQueryParam('vid')) {
			return $s->getOmnitureQueryParam('vid');
		} else {
			return '';
		}
	}
	
	private function getMobileSubscriberID()
	{
		$s =& $this;
	
		$subscriber_id_headers = array(
			'callinglineid',
			'subno',
			'clientid',
			'uid',
			'clid',
			'deviceid',
			'xid',
			'userid',
			'imsi',
			'alias',
			'subnym',
			'orangeid',
			'identity',
			'asid'
		);
	
		$request_headers = $s->getHTTPHeaders();
		foreach ($request_headers as $header_name => $header_value) {
			$matchable_header_name = preg_replace("/[^A-Za-z0-9]/", '', strtolower(trim($header_name)));
			foreach ($subscriber_id_headers as $subscriber_id_header) {
				if (strpos($matchable_header_name, $subscriber_id_header) === (strlen($matchable_header_name) - strlen($subscriber_id_header))) {
					return md5($header_value);
				}
			}
		}
		
		return '';	// no mobile subscriber id present in request headers
	}

	private function getOmnitureCookie($name)
	{
		$s =& $this;
		
		return $_COOKIE[$s->getOmnitureParamName($name)];
	}

	private function getOmnitureQueryParam($name)
	{
		$s =& $this;
		
		return $_REQUEST[$s->getOmnitureParamName($name)];	// could be post/get
	}
	
	private function getOmnitureParamName($name)
	{
		return self::PARAM_PREFIX . $name;
	}
	
	private function generateVisitorID()
	{
		return str_replace('.', mt_rand(0, 9), uniqid(md5(mt_rand()), true));
	}

	private function setVisitorIDCookie()
	{
		$s =& $this;

		// set cookie lifetime
		if (!$s->isSetString($s->cookieLifetime)) {
			$cookie_expiration = strtotime('+5 years');			// default to five years (if not set)
		} elseif (strtolower($s->cookieLifetime) == 'session') {
			$cookie_expiration = 0;								// session cookie
		} else {
			$cookie_expiration = time() + $s->cookieLifetime;	// number of seconds specified
		}
		
		// set cookie domain
		$domain_parts = explode('.', $_SERVER['SERVER_NAME']);
		if (count($domain_parts) == 1) {	// ie, 'localhost'
			$cookie_domain = '';			// allow cookie to be set on domains without periods
		} else {
			if (!$s->isSetString($s->cookieDomainPeriods) || !$s->isNumber($s->cookieDomainPeriods) || $s->cookieDomainPeriods < 2) {
				$num_domain_parts_requested = 2;
			} elseif ($s->cookieDomainPeriods > count($domain_parts)) {
				$num_domain_parts_requested = count($domain_parts);
			} else {
				$num_domain_parts_requested = $s->cookieDomainPeriods;
			}
			$cookie_domain = implode('.', array_slice($domain_parts, -1 * $num_domain_parts_requested));
			if ($num_domain_parts_requested < count($domain_parts)) {
				$cookie_domain = ".$cookie_domain";
			}
		}
		
		setcookie($s->getOmnitureParamName('vid'), $s->visitorID, $cookie_expiration, '/', $cookie_domain);
	}

	private function variableOverridesBuild(&$variableOverrides)
	{
		$s =& $this;

		foreach ($s->accountVarList as $varKey) {
			$varValue = $s->getAccountVar($varKey);
			$overrideValue = $variableOverrides[$varKey];

			// overwrite the override value if it's not set
			if (!$s->isSetString($overrideValue)) {
				if ($s->isSetString($varValue)) {
					$variableOverrides[$varKey] = $varValue;
				} else {
					$variableOverrides["!$varKey"] = '1';	// "1" simply acts as a mark that the variable was not set
				}
			}
		}

		foreach ($s->accountConfigList as $varKey) {
			$varValue = $s->getAccountVar($varKey);
			$overrideValue = $variableOverrides[$varKey];

			// overwrite the override value if it's not set
			if (!$s->isSetString($overrideValue)) {
				if ($s->isSetString($varValue)) {
					$variableOverrides[$varKey] = $varValue;
				} else {
					$variableOverrides["!$varKey"] = '1';	// "1" simply acts as a mark that the variable was not set
				}
			}
		}
	}

	private function variableOverridesApply($variableOverrides)
	{
		$s =& $this;

		foreach ($s->accountVarList as $varKey) {
			$overrideValue = $variableOverrides[$varKey];
			if ($s->isSetString($overrideValue) || $s->isSetString($variableOverrides["!$varKey"])) {
				$s->setAccountVar($varKey, $overrideValue);
			}
		}
		
		foreach ($s->accountConfigList as $varKey) {
			$overrideValue = $variableOverrides[$varKey];
			if ($s->isSetString($overrideValue) || $s->isSetString($variableOverrides["!$varKey"])) {
				$s->setAccountVar($varKey, $overrideValue);
			}
		}
	}

	private function handleLinkTracking()
	{
		$s =& $this;

		$linkType = $s->linkType;
		$linkURL = $s->linkURL;
		$linkName = $s->linkName;

		if ($s->isSetString($linkType) && ($s->isSetString($linkURL) || $s->isSetString($linkName))) {
			$linkType = strtolower($linkType);

			// force linkType as custom link ("o") if not download ("d") / exit ("e")
			if ($linkType != 'd' && $linkType != 'e') {
				$linkType = 'o';
			}

			// strip query string from linkURL
			if ($s->isSetString($linkURL) && !$s->linkLeaveQueryString) {
				$queryStringStart = strpos($linkURL, '?');
				if ($queryStringStart !== false) {
					$linkURL = substr($linkURL, 0, $queryStringStart);
				}
			}

			// setup page event (pe) variables to be picked up later
			$s->pe = 'lnk_' . $s->escape($linkType);
			$s->pev1 = $s->escape($linkURL);
			$s->pev2 = $s->escape($linkName);
		}
	}

	private function getQueryString()
	{
		$s =& $this;
		$varFilter=NULL;
		$eventFilter=NULL;

		// initialize query string - when sending from server or managing visitor ID, turn off mod_stats cookie handshake
		if ($s->sendFromServer || $s->manageVisitorID) {
			$queryString = '&cl=none';		// cookie lifetime => no cookies
			$ignoreCookieLifetime = true;	// ignore any user setting for cookie lifetime
		} else {
			$queryString = '';
		}

		// setup filters
		if ($s->isSetString($s->linkType)) {
			$varFilter = $s->linkTrackVars;
			$eventFilter = $s->linkTrackEvents;
		}

		if ($s->isSetString($varFilter)) {
			$varFilter = ',' . $varFilter . ',' . implode(',', $s->requiredVarList) . ',';
		}
		if ($s->isSetString($eventFilter)) {
			$eventFilter = ",$eventFilter,";
		}

		// go through all account variables and add to query string
		foreach ($s->accountVarList as $varKey) {
			$varValue = $s->getAccountVar($varKey);

			if (strlen($varKey) > 4) {
				$varPrefix = substr($varKey, 0, 4);
				$varSuffix = substr($varKey, 4);
			} else {
				$varPrefix = '';
				$varSuffix = '';
			}

			if ($s->isSetString($varValue)) {

				// check for key in var filter
				if ($s->isSetString($varFilter) && strpos($varFilter, ",$varKey,") === false) {
					continue;	// var key is filtered out
				}

				// key transformation (and applying event filters, etc.)
				if ($varKey == "dynamicVariablePrefix") {
					$varKey = "D";
				} else if ($varKey == "visitorID") {
					$varKey = "vid";
				} else if ($varKey == "pageURL") {
					$varKey = "g";
					$varValue = substr($varValue, 0, 255);
				} else if ($varKey == "referrer") {
					$varKey = "r";
					$varValue = substr($varValue, 0, 255);
				} else if ($varKey == "vmk" || $varKey == "visitorMigrationKey") {
					$varKey = "vmt";
				} else if ($varKey == "visitorMigrationServer") {
					$varKey = "vmf";
					if ($s->ssl && $s->visitorMigrationServerSecure) {
						$varValue = "";
					}
				} else if ($varKey == "visitorMigrationServerSecure") {
					$varKey = "vmf";
					if (!$s->ssl && $s->visitorMigrationServer) {
						$varValue = "";
					}
				} else if ($varKey == "timestamp") {
					$varKey = "ts";
				} else if ($varKey == "pageName") {
					$varKey = "gn";
				} else if ($varKey == "pageType") {
					$varKey = "gt";
				} else if ($varKey == "products") {
					$varKey = "pl";
				} else if ($varKey == "purchaseID") {
					$varKey = "pi";
				} else if ($varKey == "server") {
					$varKey = "sv";
				} else if ($varKey == "charSet") {
					$varKey = "ce";
				} else if ($varKey == "visitorNamespace") {
					$varKey = "ns";
				} else if ($varKey == "cookieDomainPeriods") {
					$varKey = "cdp";
				} else if ($varKey == "cookieLifetime") {
					if ($ignoreCookieLifetime) {	// ignore cookie lifetime when overridden above
						continue;
					}
					$varKey = "cl";
				} else if ($varKey == "currencyCode") {
					$varKey = "cc";
				} else if ($varKey == "channel") {
					$varKey = "ch";
				} else if ($varKey == "transactionID") {
					$varKey = "xact";
				} else if ($varKey == "campaign") {
					$varKey = "v0";
				} else if ($varKey == "events") {
					$varKey = "ev";
					
					// filter events if needed
					if ($s->isSetString($eventFilter)) {
						$varValueParts = explode(',', $varValue);
						$validEvents = '';
						foreach ($varValueParts as $varValuePart) {
							if (strpos($eventFilter, ",$varValuePart,") !== false) {
								$validEvents .= ($s->isSetString($validEvents) ? ',' : '') . $varValuePart;
							}
						}
						$varValue = $validEvents;
					}
				} else if ($s->isNumber($varSuffix)) {
					if ($varPrefix == "prop") {
						$varKey = "c$varSuffix";
					} else if ($varPrefix == "eVar") {
						$varKey = "v$varSuffix";
					} else if ($varPrefix == "list") {
						$varKey = "l$varSuffix";
					} else if ($varPrefix == "hier") {
						$varKey = "h$varSuffix";
						$varValue = substr($varValue, 0, 255);
					}
				}

				// do not escape pev1/pev2 values because they were already escaped beforehand (this is to only escape if we have to)
				if ($s->isSetString($varValue)) {
					$queryString .= '&' . $s->escape($varKey) . '=' . (substr($varKey, 0, 3) == 'pev' ? $varValue : $s->escape($varValue));
				}
			}
		}

		return $queryString;
	}

	private function makeRequest($cacheBusting, $queryString)
	{
		$s =& $this;
		
		$trackingServer = $s->trackingServer;
		$prefix = $s->visitorNamespace;
		$dc = $s->dc;

		// setup tracking server if not overridden by client
		if (!$s->isSetString($trackingServer)) {

			// get prefix from account (if visitorNamespace is not set)
			if (!$s->isSetString($prefix)) {
				$prefix = $s->account;

				// get first account in list of accounts (if applicable)
				$firstComma = strpos($prefix, ',');
				if ($firstComma !== false) {
					$prefix = substr($prefix, 0, $firstComma);
				}

				// sanitize for DNS
				$prefix = str_replace('.', '-', str_replace('_', '-', $prefix));
			}

			// get valid data center
			if ($s->isSetString($dc)) {
				$dc = strtolower($dc);
				if ($dc == "dc2" || $dc == "122") {
					$dc = "122";
				} else {
					$dc = "112";
				}
			} else {
				$dc = "112";	// default to SJO
			}

			$trackingServer = "$prefix.$dc.2o7.net";
		} else if ($s->ssl && $s->isSetString($s->trackingServerSecure)) {
			$trackingServer = $s->trackingServerSecure;	// use trackingServerSecure if specified
		}

		// build request
		$requestProtocol = ($s->ssl ? "https" : "http");
		$returnType = $s->getReturnType();
		$version = ($s->sendFromServer ? $s->version . 'S' : $s->version);	// differentiate between img / server-to-server requests
		$requestString = "$requestProtocol://$trackingServer/b/ss/$s->account/$returnType/$version/$cacheBusting?AQB=1&ndh=1&$queryString&AQE=1";
		
		// send request or render image
		if ($s->sendFromServer) {
			// output debug if applicable (do not output debugging data for img requests because javascript debugger can be used instead)
			if ($s->debugTracking) {
				$s->logRequest($requestString);
			}

			$s->sendRequest($requestString);
		} else {
			$s->renderImage($requestString);
		}
	}

	private function renderImage($requestString)
	{
		$s =& $this;
		
		// get image dimensions
		list($width, $height) = explode('x', strtolower($s->imageDimensions));
		if (!$width || !$s->isNumber($width)) {
			$width = 1;
		}
		if (!$height || !$s->isNumber($height)) {
			$height = 1;
		}
		
		echo "<img width='$width' height='$height' border='0' alt='' src='$requestString' />";
	}

	private function sendRequest($requestString)
	{
		$s =& $this;
	
		// check for cURL installation
		if (!function_exists('curl_init')) {
			$s->log('cURL', 'cURL not installed -- request will not be sent.');
			return;
		}
	
		// init curl (only once)
		static $ch = NULL;
		if ($ch == NULL) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $requestString);
		curl_setopt($ch, CURLOPT_USERAGENT, $s->userAgent);
		if ($s->isSetString($s->userAgent)) {
			$s->log('User-Agent', $s->userAgent);
		}

		// HTTP headers to send
		$sendHeaders = array();
		
		// if there is a remote IP or x-forwarded-for header on the request, send it through as the x-forwarded-for header to be processed as client ip (GeoSegmentation reports)
		if ($s->getXForwardedFor()) {
			$sendHeaders[] = 'X-Forwarded-For: ' . $s->getXForwardedFor();
			$s->log('X-Forwarded-For', $s->getXForwardedFor());
		}
		
		// pass through Accept-Language header (languages reports)
		$requestHeaders = $s->getHTTPHeaders();
		if ($requestHeaders['Accept-Language']) {
			$sendHeaders[] = 'Accept-Language: ' . $requestHeaders['Accept-Language']; 
			$s->log('Accept-Language', $requestHeaders['Accept-Language']);
		}
		
		// set additional HTTP headers to send
		curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
		
		// send request and report on success
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
            $s->log('Transmission Error', curl_error($ch));
        } else {
        	$s->log('Omniture HTTP Server Response', trim($response));
        }
		
		// do not close curl handle (curl_close()) -- so it can be used for subsequent requests within the same process (automatically called at the end of the script)
	}

	private function getReturnType()
	{
		$s =& $this;
		
		if ($s->sendFromServer) {
			return '0';			// return an html space
		} else {
			if ($s->mobile) {
				return '5.1';	// use mobile headers and return a gif
			} else {
				return '1';		// return a gif
			}
		}
	}

	private function getAccountVar($key)
	{
		$s =& $this;
		return $s->$key;
	}

	private function setAccountVar($key, $value)
	{
		$s =& $this;
		$s->$key = $value;
	}

	private function validateRequiredRequestVariables()
	{
		$s =& $this;

		// pageURL/referrer are not "required", but they should be automatically generated every track request if not manually set (and meeting other certain conditions)

		// build pageURL if not already set (do not build for mobile img -- to reserve url length)
		if (!$s->isSetString($s->pageURL)) {
			if (!$s->isMobileImageTag()) {
				$s->pageURL = $s->getDefaultPageURL();
			}
		}

		// build referrer if not already set
		if (!$s->isSetString($s->referrer)) {
			if (!$s->isMobileImageTag() || !$s->isReferrerOnSameDomain()) {	// only set s.referrer for mobile img if the referrer is on a different domain (reserve url length)
				$s->referrer = $s->getDefaultReferrer();
			}
		}
		
		// set image dimensions if not set
		if (!$s->sendFromServer && !$s->isSetString($s->imageDimensions)) {
			$s->imageDimensions = ($s->mobile ? '5x5' : '1x1');
		}

		// The following variables must be set to make the track
		// request. Permanently set the variables back to their
		// respective default values if they have been unset by
		// the client.

		if (!$s->isSetString($s->charSet)) {
			$s->charSet = $s->getDefaultCharSet();
		}

		// userAgent - only required when sending from the server
		if (!$s->isSetString($s->userAgent)) {
			if ($s->sendFromServer) {
				$s->userAgent = $s->getDefaultUserAgent();
			}
		}
		
		// either a pageName or pageURL is required -- otherwise the hit
		// will be thrown away.  Use the script name as a fallback.
		if (!$s->isSetString($s->pageName) && !$s->isSetString($s->pageURL)) {
			$s->pageName = $_SERVER['SCRIPT_NAME'];
		}
	}
	
	private function isMobileImageTag()
	{
		$s =& $this;
		
		return (!$s->sendFromServer && $s->mobile); 
	}
	
	private function isReferrerOnSameDomain()
	{
		$s =& $this;
		
		if (!$_SERVER['HTTP_REFERER']) {
			return false;
		}
		
		$referrer_url_parts = parse_url($_SERVER['HTTP_REFERER']);
		
		return ($s->getTopLevelDomain($_SERVER['SERVER_NAME']) == $s->getTopLevelDomain($referrer_url_parts['host']));		
	}
	
	private function getTopLevelDomain($domain)
	{
		$domain_elements = explode('.', $domain);
		if (count($domain_elements) == 1) {	// ie, 'localhost'
			return $domain;
		}
		
		$last_element = array_pop($domain_elements);
		if (strpos($last_element, ':') !== false) {	// remove port if necessary
			$last_element = substr($last_element, 0, strpos($last_element, ':'));
		}
		return array_pop($domain_elements) . '.' . $last_element;
	}

	private function isSetString($var)
	{
		return ($var != '' && $var != NULL);	// '0' is valid
	}

	private function isNumber($value)
	{
		return is_numeric($value);
	}

	private function escape($unescapedString)
	{
		return rawurlencode($unescapedString);		
	}

	private function unescape($escapedString)
	{
		return rawurldecode($escapedString);
	}

	private function logRequest($requestString)
	{
		$s =& $this;

		$debugString = $requestString;
		$requestComponents = explode('&', $requestString);
		foreach ($requestComponents as $keyValuePair) {
			$debugString .= "\n\t" . $s->unescape($keyValuePair);
		}

		$s->log('Request', $debugString);
	}

	private function log($title, $message)
	{
		$s =& $this;
		
		// only log messages if debug tracking is turned on
		if ($s->debugTracking) {
			$debugContents = "Omniture Measurement Debug [$title]: $message\n"; 
			if ($s->isSetString($s->debugFilename)) {
				file_put_contents($s->debugFilename, $debugContents, FILE_APPEND);
			} else {
				echo $debugContents;
			}
		}
	}

	private function getFormattedTimestamp()
	{
		return date('j/') . (date('n') - 1) . date('/Y H:i:s w ') . (date('Z') / -60);
	}

	private function getDefaultCharSet()
	{
		return 'UTF-8';
	}

	private function getDefaultUserAgent()
	{
		if ($_SERVER['HTTP_USER_AGENT']) {
			return $_SERVER['HTTP_USER_AGENT']; 
		} else {
			return '';
		}
	}
	
	private function getDefaultPageURL()
	{
		if ($_SERVER['REQUEST_URI']) {
			return ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		} else {
			return '';
		}
	}
	
	private function getDefaultSSL()
	{
		return (bool)$_SERVER['HTTPS'];
	}
	
	private function getDefaultReferrer()
	{
		if (isset($_SERVER['HTTP_REFERER'])) {
			return $_SERVER['HTTP_REFERER']; 
		} else {
			return '';
		}
	}
	
	private function getXForwardedFor()
	{
		$s =& $this;
	
		$xff_ips = array();
	
		$headers = $s->getHTTPHeaders();	
		if (isset($headers['X-Forwarded-For'])) {
			$xff_ips[] = $headers['X-Forwarded-For'];
		}
		
		if ($_SERVER['REMOTE_ADDR']) {
			$xff_ips[] = $_SERVER['REMOTE_ADDR'];
		}
		
		return implode(', ', $xff_ips);	// will return blank if not on a web server
	}
	
	private function getHTTPHeaders()
	{
		// getallheaders is an Apache-specific function call
		if (function_exists('getallheaders')) {
			return getallheaders();
		} else {
			// HTTP headers are server variables ($_SERVER)
			$httpHeaders = array();
			foreach ($_SERVER as $name => $value) {
				// Find all HTTP headers, transform name to match standard HTTP header format, and add to set of headers
				// HTTP header name example: HTTP_X_FORWARDED_FOR
				if (strpos($name, 'HTTP_') === 0) {
					$nameParts = explode('_', $name);
					array_shift($nameParts);	// remove 'HTTP'
					$newNameParts = array();
					foreach ($nameParts as $namePart) {
						$newNameParts[] = ucfirst(strtolower($namePart));
					}
					$name = implode('-', $newNameParts);
					$httpHeaders[$name] = $value;
				}
			}
			return $httpHeaders;
		}
	}
	
	private function isBot()
	{
		$s =& $this;
		
		$user_agent = strtolower($s->getDefaultUserAgent());
		$bots = $s->getBots();
		foreach ($bots as $bot) {
			if (strpos($user_agent, $bot) !== false) return true;
		}
		
		return false;
	}
	
	// protected methods
	
	protected function getBots()
	{
		return array(
			'googlebot',
			'mediapartners',
			'yahooysmcm',
			'baiduspider',
			'msnbot',
			'slurp',
			'teoma',
			'spider',
			'heritrix',
			'attentio',
			'twiceler',
			'irlbot',
			'fast crawler',
			'fastmobilecrawl',
			'jumpbot',
			'yahooseeker',
			'motionbot',
			'mediobot',
			'chtml generic'
		);
	}
	
	// callback methods -- should not be called manually
	
	// NOTE - should not be called manually (only made 'public' for callback purposes)
	public function rewriteLinksCallback($contents)
	{
		$s =& $this;
		
		$replaced_links = preg_replace_callback('/(href\s*=\s*["\']?)([^"\'\>\s]*)(["\'\>\s])/i', array($s, 'rewriteLinkCallback'), $contents);
		return preg_replace_callback('/(<\/form>)/i', array($s, 'addFormElementCallback'), $replaced_links);
	}
	
	// NOTE - should not be called manually (only made 'public' for callback purposes)
	public function rewriteLinkCallback($matches)
	{
		$s =& $this;
	
		return $matches[1] . $matches[2] . (strpos($matches[2], '?') !== false ? '&' : '?') . $s->getOmnitureParamName('vid') . '=' . $s->escape($s->visitorID) . $matches[3];
	}

	// NOTE - should not be called manually (only made 'public' for callback purposes)
	public function addFormElementCallback($matches)
	{
		$s =& $this;
	
		return "\t<input type='hidden' name='" . $s->getOmnitureParamName('vid') . "' value='" . $s->escape($s->visitorID) . "' />\n</form>";
	}
}
?>
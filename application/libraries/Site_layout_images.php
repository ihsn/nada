<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Header images (logo and background) stored under files/site/.
 *
 * Shared by the header upload (api/admin/site_layout/upload) and the Site layout import
 * (api/admin/site_layout/import), so both apply the same checks:
 *   - allowed type and size per kind,
 *   - the content really is that image type,
 *   - SVG without scripts, event attributes or outside references.
 *
 * Files are named <kind>-<first 16 hex of sha1>.<ext>, so the same image is stored once.
 */
class Site_layout_images {

	const UPLOAD_DIR = 'files/site';

	/**
	 * Apache: an SVG opened directly (not through <img>) is sandboxed and may not load anything, as a second
	 * line of defence behind svg_is_safe(). Other servers need the same headers in their own config.
	 */
	const HTACCESS = "<FilesMatch \"(?i)\\.svg$\">\n"
		. "  <IfModule mod_headers.c>\n"
		. "    Header set Content-Security-Policy \"default-src 'none'; style-src 'unsafe-inline'; sandbox\"\n"
		. "    Header set X-Content-Type-Options \"nosniff\"\n"
		. "  </IfModule>\n"
		. "</FilesMatch>\n";

	/** kind => max bytes and allowed extensions */
	public static $limits = array(
		'logo'       => array('max' => 2097152, 'types' => array('png', 'jpg', 'jpeg', 'gif', 'webp', 'svg')),
		'background' => array('max' => 5242880, 'types' => array('png', 'jpg', 'jpeg', 'gif', 'webp')),
	);

	/** Lower-case extension of a file name, jpeg as jpg */
	public function extension($name)
	{
		$ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
		return $ext === 'jpeg' ? 'jpg' : $ext;
	}

	/**
	 * Check a file on disk for the given kind.
	 *
	 * @return string '' when it can be used, else an error code:
	 *                UNKNOWN_UPLOAD, FILE_TOO_LARGE, FILE_TYPE_NOT_ALLOWED, FILE_CONTENT_MISMATCH, SVG_NOT_SAFE
	 */
	public function check($path, $ext, $kind)
	{
		if (!isset(self::$limits[$kind])) {
			return 'UNKNOWN_UPLOAD';
		}
		$limits = self::$limits[$kind];
		if (!is_file($path) || filesize($path) > $limits['max']) {
			return 'FILE_TOO_LARGE';
		}
		if (!in_array($ext, $limits['types'], true)) {
			return 'FILE_TYPE_NOT_ALLOWED';
		}
		if (!$this->matches_type($path, $ext)) {
			return 'FILE_CONTENT_MISMATCH';
		}
		if ($ext === 'svg' && !$this->svg_is_safe((string) file_get_contents($path))) {
			return 'SVG_NOT_SAFE';
		}
		return '';
	}

	/**
	 * Store a checked file under files/site/ and return its relative path (files/site/<name>).
	 * $move: move an uploaded file (move_uploaded_file) instead of copying.
	 *
	 * @throws Exception UPLOAD_NOT_WRITABLE or UPLOAD_FAILED
	 */
	public function store($source, $kind, $ext, $move = false)
	{
		$dir = $this->upload_dir();
		$name = $kind . '-' . substr(sha1_file($source), 0, 16) . '.' . $ext;
		$target = $dir . '/' . $name;
		if (!is_file($target)) {
			$ok = $move ? move_uploaded_file($source, $target) : copy($source, $target);
			if (!$ok) {
				throw new Exception('UPLOAD_FAILED');
			}
			@chmod($target, 0644);
		}
		return self::UPLOAD_DIR . '/' . $name;
	}

	/** Absolute path of files/site/, created when missing */
	public function upload_dir()
	{
		$dir = rtrim(FCPATH, '/\\') . '/' . self::UPLOAD_DIR;
		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
			throw new Exception('UPLOAD_NOT_WRITABLE');
		}
		if (!is_writable($dir)) {
			throw new Exception('UPLOAD_NOT_WRITABLE');
		}
		if (!is_file($dir . '/index.html')) {
			@file_put_contents($dir . '/index.html', '');
		}
		if (!is_file($dir . '/.htaccess')) {
			@file_put_contents($dir . '/.htaccess', self::HTACCESS);
		}
		return $dir;
	}

	/** The file has to be the image type its name says */
	private function matches_type($path, $ext)
	{
		if ($ext === 'svg') {
			$svg = (string) @file_get_contents($path);
			// UTF-8 only: in UTF-16 the text checks in svg_is_safe() would not see the markup
			return stripos(substr($svg, 0, 4096), '<svg') !== false
				&& strpos($svg, "\0") === false
				&& (!function_exists('mb_check_encoding') || mb_check_encoding($svg, 'UTF-8'));
		}
		$info = @getimagesize($path);
		if (!$info) {
			return false;
		}
		$types = array(
			'png'  => IMAGETYPE_PNG,
			'jpg'  => IMAGETYPE_JPEG,
			'gif'  => IMAGETYPE_GIF,
			'webp' => IMAGETYPE_WEBP,
		);
		return isset($types[$ext]) && $info[2] === $types[$ext];
	}

	private function svg_is_safe($svg)
	{
		$patterns = array(
			// elements, also with a namespace prefix (<x:script xmlns:x="http://www.w3.org/2000/svg">)
			'/<\s*([a-z0-9_.-]+:)?script/i',
			'/<\s*([a-z0-9_.-]+:)?foreignObject/i',
			'/<\s*([a-z0-9_.-]+:)?(iframe|embed|object|audio|video|animate|set|handler|listener)\b/i',
			// event attributes after a space, / or quote (<svg/onload=...>)
			'/(^|[^a-z0-9_.:-])on[a-z]+\s*=/i',
			'/javascript\s*:/i',
			'/<!ENTITY/i',
			'/<!DOCTYPE[^>]*\[/i',
			'/(xlink:)?href\s*=\s*["\']?\s*(?!#)[^"\'\s>]/i',
			'/@import/i',
			'/url\s*\(\s*["\']?\s*(?!#)/i',
		);
		foreach ($patterns as $p) {
			if (preg_match($p, $svg)) {
				return false;
			}
		}
		return true;
	}
}

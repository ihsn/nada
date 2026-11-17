<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The published file for one part of Admin > Settings > Site layout (header, footer or styles).
 *
 * Public pages read only this file: when it is missing they fall back to the theme. The database
 * holds the editable settings; only Publish (and Reset) write or remove the file, so the file and the
 * published settings change together.
 *
 * Location: {catalog_root}/{subdir}/{file}, e.g. datafiles/cache/site_header/published.json.
 * Contents: JSON object written by the builder ('format', 'hash' and the builder's own keys).
 *
 * Writes are staged: stage() writes a temporary file, commit() moves it into place, so a failed
 * publish never leaves a half-written file.
 *
 * Not a CodeIgniter library: builders create one per part with `new Site_layout_file(...)`.
 */
class Site_layout_file {

	private $ci;
	private $subdir;
	private $file;
	private $legacy;
	private $label;
	private $loaded = false;
	private $data = null;

	/**
	 * @param string $subdir  folder under catalog_root, e.g. 'cache/site_header'
	 * @param string $file    file name, e.g. 'published.json'
	 * @param array  $legacy  older file names in the same folder, removed on commit and delete
	 * @param string $label   used in log messages
	 */
	public function __construct($subdir, $file, array $legacy = array(), $label = 'Site layout')
	{
		$this->ci =& get_instance();
		$this->subdir = trim($subdir, '/');
		$this->file = $file;
		$this->legacy = $legacy;
		$this->label = $label;
	}

	/**
	 * Absolute path of the published file.
	 *
	 * @throws Exception when catalog_root is not set (from get_catalog_root())
	 */
	public function path()
	{
		$this->ci->load->helper('file');
		$root = rtrim(str_replace('\\', '/', get_catalog_root()), '/');
		return $root . '/' . $this->subdir . '/' . $this->file;
	}

	/**
	 * The decoded file, or null when it is missing or unreadable. Read once per request.
	 *
	 * @return array|null
	 */
	public function read()
	{
		if ($this->loaded) {
			return $this->data;
		}
		$this->loaded = true;
		$this->data = null;

		try {
			$path = $this->path();
		}
		catch (Exception $e) {
			return null;
		}
		if (!is_file($path) || !is_readable($path)) {
			return null;
		}
		$data = json_decode((string) file_get_contents($path), true);
		if (!is_array($data)) {
			log_message('error', $this->label . ': ' . $path . ' is not valid JSON; the theme is used instead.');
			return null;
		}
		$this->data = $data;
		return $data;
	}

	/**
	 * Write $data to a temporary file next to the published file.
	 *
	 * @return string|false temporary path to pass to commit() or discard()
	 */
	public function stage(array $data)
	{
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			log_message('error', $this->label . ': cannot encode the published file');
			return false;
		}
		try {
			$path = $this->path();
		}
		catch (Exception $e) {
			log_message('error', $this->label . ': ' . $e->getMessage());
			return false;
		}
		$dir = dirname($path);
		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
			log_message('error', $this->label . ': cannot create ' . $dir);
			return false;
		}
		$this->protect_dir($dir);

		$tmp = $path . '.tmp.' . getmypid() . '.' . mt_rand();
		if (@file_put_contents($tmp, $json) === false) {
			log_message('error', $this->label . ': cannot write ' . $tmp);
			@unlink($tmp);
			return false;
		}
		@chmod($tmp, 0644);
		return $tmp;
	}

	/** Move a staged file into place */
	public function commit($tmp)
	{
		try {
			$path = $this->path();
		}
		catch (Exception $e) {
			$this->discard($tmp);
			return false;
		}
		if (!@rename($tmp, $path)) {
			// Windows cannot rename over an existing file
			@unlink($path);
			if (!@rename($tmp, $path)) {
				$this->discard($tmp);
				log_message('error', $this->label . ': cannot replace ' . $path);
				return false;
			}
		}
		$this->remove_legacy(dirname($path));
		$this->loaded = false;
		return true;
	}

	/**
	 * Publish: replace the file together with the settings that $save stores.
	 *
	 *   1. stage $data (null = the file is to be removed) — on failure nothing is saved;
	 *   2. $save() — its exceptions propagate and the staged file is dropped;
	 *   3. move the staged file into place, or remove the file — on failure $undo() restores the
	 *      previous settings.
	 *
	 * @param array|null $data
	 * @return bool false when the file could not be written, moved or removed
	 */
	public function publish($data, callable $save, callable $undo)
	{
		$tmp = null;
		if ($data !== null) {
			$tmp = $this->stage($data);
			if ($tmp === false) {
				return false;
			}
		}

		try {
			$save();
		}
		catch (Throwable $e) {
			$this->discard($tmp);
			throw $e;
		}

		$ok = $tmp !== null ? $this->commit($tmp) : $this->delete();
		if (!$ok) {
			$undo();
		}
		return $ok;
	}

	/** Remove a staged file that will not be committed */
	public function discard($tmp)
	{
		if (is_string($tmp) && $tmp !== '' && is_file($tmp)) {
			@unlink($tmp);
		}
	}

	/**
	 * Remove the published file (the public site then uses the theme).
	 *
	 * @return bool true when no published file is left
	 */
	public function delete()
	{
		try {
			$path = $this->path();
		}
		catch (Exception $e) {
			// no catalog_root: there can be no published file either
			return true;
		}
		$this->loaded = false;
		$dir = dirname($path);
		$this->remove_legacy($dir);
		foreach (glob($path . '.tmp.*') ?: array() as $tmp) {
			@unlink($tmp);
		}
		if (is_file($path) && !@unlink($path)) {
			log_message('error', $this->label . ': cannot remove ' . $path);
			return false;
		}
		return !is_file($path);
	}

	/**
	 * Whether the published file can be written.
	 *
	 * @return array{ok:bool,code:string,path:string,catalog_root:string}
	 */
	public function status()
	{
		$configured = trim((string) $this->ci->config->item('catalog_root'));
		$relative = $this->subdir . '/' . $this->file;

		try {
			$this->ci->load->helper('file');
			$root = rtrim(str_replace('\\', '/', get_catalog_root()), '/');
		}
		catch (Exception $e) {
			return array('ok' => false, 'code' => 'CATALOG_ROOT_NOT_SET', 'path' => $relative, 'catalog_root' => $configured);
		}
		if (!is_dir($root)) {
			return array('ok' => false, 'code' => 'CATALOG_ROOT_MISSING', 'path' => $root . '/' . $relative, 'catalog_root' => $root);
		}
		$path = $root . '/' . $relative;
		if (!$this->path_is_writable(dirname($path))) {
			return array('ok' => false, 'code' => 'CACHE_NOT_WRITABLE', 'path' => $path, 'catalog_root' => $root);
		}
		return array('ok' => true, 'code' => '', 'path' => $path, 'catalog_root' => $root);
	}

	/** True when $dir exists and is writable, or can be created under a writable parent */
	private function path_is_writable($dir)
	{
		$dir = rtrim(str_replace('\\', '/', $dir), '/');
		if (is_dir($dir)) {
			return is_writable($dir);
		}
		$check = $dir;
		while ($check !== '' && $check !== '/' && !preg_match('#^[A-Za-z]:$#', $check) && !is_dir($check)) {
			$parent = dirname($check);
			if ($parent === $check) {
				break;
			}
			$check = $parent;
		}
		return is_dir($check) && is_writable($check);
	}

	private function remove_legacy($dir)
	{
		foreach ($this->legacy as $name) {
			$old = $dir . '/' . $name;
			if (is_file($old)) {
				@unlink($old);
			}
		}
	}

	/** Deny direct HTTP access to the folder (Apache); harmless elsewhere */
	private function protect_dir($dir)
	{
		$htaccess = rtrim(str_replace('\\', '/', $dir), '/') . '/.htaccess';
		if (is_file($htaccess)) {
			return;
		}
		$rules = "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n"
			. "<IfModule !authz_core_module>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
		@file_put_contents($htaccess, $rules);
	}
}

<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-develop',
    'version' => 'dev-develop',
    'aliases' => 
    array (
    ),
    'reference' => '5bb0ff4537e90105cdb08ba6055f44b065e776fb',
    'name' => '__root__',
  ),
  'versions' => 
  array (
    '__root__' => 
    array (
      'pretty_version' => 'dev-develop',
      'version' => 'dev-develop',
      'aliases' => 
      array (
      ),
      'reference' => '5bb0ff4537e90105cdb08ba6055f44b065e776fb',
    ),
    'adbario/php-dot-notation' => 
    array (
      'pretty_version' => '2.2.0',
      'version' => '2.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'eee4fc81296531e6aafba4c2bbccfc5adab1676e',
    ),
    'justinrainbow/json-schema' => 
    array (
      'pretty_version' => '5.2.10',
      'version' => '5.2.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '2ba9c8c862ecd5510ed16c6340aa9f6eadb4f31b',
    ),
    'league/event' => 
    array (
      'pretty_version' => '2.2.0',
      'version' => '2.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd2cc124cf9a3fab2bb4ff963307f60361ce4d119',
    ),
    'mpdf/mpdf' => 
    array (
      'pretty_version' => 'v8.0.7',
      'version' => '8.0.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '7daf07f15334ed59a276bd52131dcca48794cdbd',
    ),
    'myclabs/deep-copy' => 
    array (
      'pretty_version' => '1.10.1',
      'version' => '1.10.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '969b211f9a51aa1f6c01d1d2aef56d3bd91598e5',
      'replaced' => 
      array (
        0 => '1.10.1',
      ),
    ),
    'paragonie/random_compat' => 
    array (
      'pretty_version' => 'v9.99.99',
      'version' => '9.99.99.0',
      'aliases' => 
      array (
      ),
      'reference' => '84b4dfb120c6f9b4ff7b3685f9b8f1aa365a0c95',
    ),
    'phpmailer/phpmailer' => 
    array (
      'pretty_version' => 'v6.1.6',
      'version' => '6.1.6.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c2796cb1cb99d7717290b48c4e6f32cb6c60b7b3',
    ),
    'psr/event-dispatcher-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-message' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f6561bf28d520154e4b0ec72be95418abe6d9363',
    ),
    'psr/log' => 
    array (
      'pretty_version' => '1.1.3',
      'version' => '1.1.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '0f73288fd15629204f9d42b7055f72dacbe811fc',
    ),
    'salsify/json-streaming-parser' => 
    array (
      'pretty_version' => 'v8.2.0',
      'version' => '8.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '4ae5394136a037e09402ba8b48dbf4e20475e69d',
    ),
    'setasign/fpdi' => 
    array (
      'pretty_version' => 'v2.3.4',
      'version' => '2.3.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '2b5fb811c04f937ef257ef3f798cebeded33c136',
    ),
    'slim/csrf' => 
    array (
      'pretty_version' => '0.8.3',
      'version' => '0.8.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '5f2bcf5d89adf86dc0455a32bea84d912ab466a7',
    ),
    'solarium/solarium' => 
    array (
      'pretty_version' => '4.2.0',
      'version' => '4.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '20cf9e906a4e8e4a29c1ff48a2b145fa31d1064d',
    ),
    'spatie/array-to-xml' => 
    array (
      'pretty_version' => '2.15.0',
      'version' => '2.15.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '1795afad4e5a1f4b7af2e0e09802550eaa00a6f8',
    ),
    'symfony/event-dispatcher' => 
    array (
      'pretty_version' => 'v4.4.11',
      'version' => '4.4.11.0',
      'aliases' => 
      array (
      ),
      'reference' => '6140fc7047dafc5abbe84ba16a34a86c0b0229b8',
    ),
    'symfony/event-dispatcher-contracts' => 
    array (
      'pretty_version' => 'v1.1.9',
      'version' => '1.1.9.0',
      'aliases' => 
      array (
      ),
      'reference' => '84e23fdcd2517bf37aecbd16967e83f0caee25a7',
    ),
    'symfony/event-dispatcher-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.1',
      ),
    ),
    'violet/streaming-json-encoder' => 
    array (
      'pretty_version' => 'v1.1.1',
      'version' => '1.1.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '5aa8bc3494c4a24116b46c0d461c409eeb8bf313',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}

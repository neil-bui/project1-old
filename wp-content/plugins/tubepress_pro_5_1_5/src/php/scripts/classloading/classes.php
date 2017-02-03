<?php 
/**
 * For performance purposes, this is a concatenation of the following 74 classes:
 *
 *		tubepress_internal_boot_helper_ContainerSupplier
 *		tubepress_api_boot_BootSettingsInterface
 *		tubepress_internal_boot_BootSettings
 *		tubepress_api_ioc_ContainerInterface
 *		tubepress_internal_ioc_Container
 *		tubepress_api_log_LoggerInterface
 *		tubepress_internal_logger_BootLogger
 *		tubepress_options_impl_listeners_StringMagicListener
 *		tubepress_api_options_ReferenceInterface
 *		tubepress_api_options_Reference
 *		tubepress_app_api_options_Reference
 *		puzzle_AbstractHasData
 *		puzzle_ToArrayInterface
 *		puzzle_Collection
 *		puzzle_Query
 *		puzzle_Url
 *		tubepress_api_collection_MapInterface
 *		tubepress_api_contrib_ContributableInterface
 *		tubepress_api_contrib_RegistryInterface
 *		tubepress_api_environment_EnvironmentInterface
 *		tubepress_api_event_EventDispatcherInterface
 *		tubepress_lib_api_event_EventInterface
 *		tubepress_api_event_EventInterface
 *		tubepress_api_event_Events
 *		tubepress_api_html_HtmlGeneratorInterface
 *		tubepress_api_http_AjaxInterface
 *		tubepress_api_http_RequestParametersInterface
 *		tubepress_api_http_ResponseCodeInterface
 *		tubepress_app_api_options_ContextInterface
 *		tubepress_api_options_ContextInterface
 *		tubepress_api_options_Names
 *		tubepress_spi_options_PersistenceBackendInterface
 *		tubepress_api_options_PersistenceInterface
 *		tubepress_api_shortcode_ParserInterface
 *		tubepress_api_template_TemplatingInterface
 *		tubepress_api_theme_ThemeInterface
 *		tubepress_api_translation_TranslatorInterface
 *		tubepress_api_url_QueryInterface
 *		tubepress_platform_api_url_UrlFactoryInterface
 *		tubepress_api_url_UrlFactoryInterface
 *		tubepress_api_url_UrlInterface
 *		tubepress_platform_api_util_StringUtilsInterface
 *		tubepress_api_util_StringUtilsInterface
 *		tubepress_api_version_Version
 *		tubepress_environment_impl_Environment
 *		tubepress_html_impl_CssAndJsGenerationHelper
 *		tubepress_html_impl_HtmlGenerator
 *		tubepress_html_impl_listeners_HtmlListener
 *		tubepress_http_impl_PrimaryAjaxHandler
 *		tubepress_http_impl_RequestParameters
 *		tubepress_http_impl_ResponseCode
 *		tubepress_internal_boot_helper_uncached_contrib_SerializedRegistry
 *		tubepress_internal_boot_helper_uncached_Serializer
 *		tubepress_internal_collection_Map
 *		tubepress_internal_contrib_AbstractContributable
 *		tubepress_internal_theme_AbstractTheme
 *		tubepress_internal_theme_FilesystemTheme
 *		tubepress_logger_impl_HtmlLogger
 *		tubepress_options_impl_Context
 *		tubepress_options_impl_DispatchingReference
 *		tubepress_options_impl_Persistence
 *		tubepress_shortcode_impl_Parser
 *		tubepress_theme_impl_CurrentThemeService
 *		tubepress_url_impl_puzzle_PuzzleBasedQuery
 *		tubepress_url_impl_puzzle_PuzzleBasedUrl
 *		tubepress_url_impl_puzzle_UrlFactory
 *		tubepress_util_impl_StringUtils
 *		tubepress_wordpress_impl_EntryPoint
 *		tubepress_wordpress_impl_listeners_html_WpHtmlListener
 *		tubepress_wordpress_impl_options_WpPersistence
 *		tubepress_internal_translation_AbstractTranslator
 *		tubepress_wordpress_impl_translation_WpTranslator
 *		tubepress_wordpress_impl_listeners_wp_ShortcodeListener
 *		tubepress_wordpress_impl_wp_WpFunctions
 */

namespace
{
class tubepress_internal_boot_helper_ContainerSupplier
{
private $_logger;
private $_bootSettings;
private $_logEnabled = false;
private $_uncachedContainerSupplier;
private $_temporaryClassLoader;
public function __construct(tubepress_api_log_LoggerInterface $logger,
tubepress_api_boot_BootSettingsInterface $bootSettings)
{
$this->_logger = $logger;
$this->_bootSettings = $bootSettings;
$this->_logEnabled = $logger->isEnabled();
}
public function getServiceContainer()
{
if ($this->_canBootFromCache()) {
if ($this->_logEnabled) {
$this->_logDebug('System cache is available. Excellent!');
}
try {
return $this->_getTubePressContainerFromCache();
} catch (RuntimeException $e) {
}
}
return $this->_getNewTubePressContainer();
}
private function _canBootFromCache()
{
if ($this->_logEnabled) {
$this->_logDebug('Determining if system cache is available.');
}
if (!$this->_bootSettings->isSystemCacheEnabled()) {
if ($this->_logEnabled) {
$this->_logDebug('System cache is disabled by user settings.php');
}
return false;
}
if ($this->_tubePressContainerClassExists()) {
return true;
}
$file = $this->_getPathToContainerCacheFile();
if (!is_readable($file)) {
if ($this->_logEnabled) {
$this->_logDebug(sprintf('<code>%s</code> is not a readable file.', $file));
}
return false;
}
if ($this->_logEnabled) {
$this->_logDebug(sprintf('<code>%s</code> is a readable file. Now including it.', $file));
}
require $file;
$iocContainerHit = $this->_tubePressContainerClassExists();
if ($this->_logEnabled) {
if ($iocContainerHit) {
$this->_logDebug(sprintf('Service container found in cache? <code>%s</code>', $iocContainerHit ?'yes':'no'));
}
}
return $iocContainerHit;
}
private function _getTubePressContainerFromCache()
{
if ($this->_logEnabled) {
$this->_logDebug('Rehydrating cached service container.');
}
$symfonyContainer = new TubePressServiceContainer();
if ($this->_logEnabled) {
$this->_logDebug('Done rehydrating cached service container.');
}
$tubePressContainer = new tubepress_internal_ioc_Container($symfonyContainer);
$this->_setEphemeralServicesToContainer($tubePressContainer, $symfonyContainer);
return $tubePressContainer;
}
private function _tubePressContainerClassExists()
{
return class_exists('TubePressServiceContainer', false);
}
private function _getNewTubePressContainer()
{
if ($this->_logEnabled) {
$this->_logDebug('We cannot boot from cache. Will perform a full boot instead.');
}
$this->_buildTemporaryClassLoader();
$this->_buildUncachedContainerSupplier();
$result = $this->_uncachedContainerSupplier->getNewSymfonyContainer();
$tubePressContainer = new tubepress_internal_ioc_Container($result);
spl_autoload_unregister(array($this->_temporaryClassLoader,'loadClass'));
$this->_setEphemeralServicesToContainer($tubePressContainer, $result);
return $tubePressContainer;
}
private function _setEphemeralServicesToContainer(tubepress_api_ioc_ContainerInterface $tubePressContainer,
\Symfony\Component\DependencyInjection\ContainerInterface $symfonyContainer)
{
$tubePressContainer->set('tubepress_api_ioc_ContainerInterface', $tubePressContainer);
$tubePressContainer->set('symfony_service_container', $symfonyContainer);
$tubePressContainer->set('tubepress_internal_logger_BootLogger', $this->_logger);
$tubePressContainer->set(tubepress_api_boot_BootSettingsInterface::_, $this->_bootSettings);
}
private function _getPathToContainerCacheFile()
{
$cachePath = $this->_bootSettings->getPathToSystemCacheDirectory();
return sprintf('%s%sTubePressServiceContainer.php', $cachePath, DIRECTORY_SEPARATOR);
}
private function _buildTemporaryClassLoader()
{
if (!class_exists('Symfony\Component\ClassLoader\MapClassLoader', false)) {
require TUBEPRESS_ROOT .'/vendor/symfony/class-loader/MapClassLoader.php';
}
$fullClassMap = require TUBEPRESS_ROOT .'/src/php/scripts/classloading/classmap.php';
$this->_temporaryClassLoader = new \Symfony\Component\ClassLoader\MapClassLoader($fullClassMap);
$this->_temporaryClassLoader->register();
}
private function _buildUncachedContainerSupplier()
{
if (isset($this->_uncachedContainerSupplier)) {
return;
}
$finderFactory = new tubepress_internal_finder_FinderFactory();
$urlFactory = new tubepress_url_impl_puzzle_UrlFactory();
$langUtils = new tubepress_util_impl_LangUtils();
$stringUtils = new tubepress_util_impl_StringUtils();
$addonFactory = new tubepress_internal_boot_helper_uncached_contrib_AddonFactory(
$this->_logger, $urlFactory, $langUtils, $stringUtils, $this->_bootSettings
);
$manifestFinder = new tubepress_internal_boot_helper_uncached_contrib_ManifestFinder(
TUBEPRESS_ROOT . DIRECTORY_SEPARATOR .'src'. DIRECTORY_SEPARATOR .'add-ons',
DIRECTORY_SEPARATOR .'add-ons','manifest.json',
$this->_logger,
$this->_bootSettings,
$finderFactory
);
$uncached = new tubepress_internal_boot_helper_uncached_UncachedContainerSupplier(
$this->_logger, $manifestFinder, $addonFactory,
new tubepress_internal_boot_helper_uncached_Compiler($this->_logger),
$this->_bootSettings
);
$this->_uncachedContainerSupplier = $uncached;
}
private function _logDebug($msg)
{
$this->_logger->debug(sprintf('(Container Supplier) %s', $msg));
}
public function ___setUncachedContainerSupplier(tubepress_internal_boot_helper_uncached_UncachedContainerSupplier $supplier)
{
$this->_uncachedContainerSupplier = $supplier;
}
}
}
namespace
{
interface tubepress_api_boot_BootSettingsInterface
{
const _ ='tubepress_api_boot_BootSettingsInterface';
function shouldClearCache();
function getAddonBlacklistArray();
function isClassLoaderEnabled();
function isSystemCacheEnabled();
function getPathToSystemCacheDirectory();
function getUserContentDirectory();
function getSerializationEncoding();
function getUrlBase();
function getUrlUserContent();
function getUrlAjaxEndpoint();
}
}
namespace
{
class tubepress_internal_boot_BootSettings implements tubepress_api_boot_BootSettingsInterface
{
private static $_TOP_LEVEL_KEY_SYSTEM ='system';
private static $_TOP_LEVEL_KEY_USER ='user';
private static $_2ND_LEVEL_KEY_CLASSLOADER ='classloader';
private static $_2ND_LEVEL_KEY_CACHE ='cache';
private static $_2ND_LEVEL_KEY_ADDONS ='add-ons';
private static $_2ND_LEVEL_KEY_URLS ='urls';
private static $_3RD_LEVEL_KEY_CLASSLOADER_ENABLED ='enabled';
private static $_3RD_LEVEL_KEY_CACHE_KILLERKEY ='killerKey';
private static $_3RD_LEVEL_KEY_CACHE_ENABLED ='enabled';
private static $_3RD_LEVEL_KEY_CACHE_DIR ='directory';
private static $_3RD_LEVEL_KEY_ADDONS_BLACKLIST ='blacklist';
private static $_3RD_LEVEL_KEY_SERIALIZATION_ENC ='serializationEncoding';
private static $_3RD_LEVEL_KEY_URL_BASE ='base';
private static $_3RD_LEVEL_KEY_URL_AJAX ='ajax';
private static $_3RD_LEVEL_KEY_URL_USERCONTENT ='userContent';
private $_logger;
private $_hasInitialized = false;
private $_shouldLog = false;
private $_addonBlacklistArray = array();
private $_isClassLoaderEnabled;
private $_isCacheEnabled;
private $_systemCacheKillerKey;
private $_cacheDirectory;
private $_cachedUserContentDir;
private $_serializationEncoding;
private $_urlBase;
private $_urlAjax;
private $_urlUserContent;
private $_urlFactory;
public function __construct(tubepress_api_log_LoggerInterface $logger,
tubepress_api_url_UrlFactoryInterface $urlFactory)
{
$this->_logger = $logger;
$this->_shouldLog = $logger->isEnabled();
$this->_urlFactory = $urlFactory;
}
public function getSerializationEncoding()
{
$this->_init();
return $this->_serializationEncoding;
}
public function shouldClearCache()
{
$this->_init();
return isset($_GET[$this->_systemCacheKillerKey]) && $_GET[$this->_systemCacheKillerKey] ==='true';
}
public function getAddonBlacklistArray()
{
$this->_init();
return $this->_addonBlacklistArray;
}
public function isClassLoaderEnabled()
{
$this->_init();
return $this->_isClassLoaderEnabled;
}
public function isSystemCacheEnabled()
{
$this->_init();
return $this->_isCacheEnabled;
}
public function getPathToSystemCacheDirectory()
{
$this->_init();
return $this->_cacheDirectory;
}
public function getUserContentDirectory()
{
if (!isset($this->_cachedUserContentDir)) {
if (defined('TUBEPRESS_CONTENT_DIRECTORY')) {
$this->_cachedUserContentDir = rtrim(TUBEPRESS_CONTENT_DIRECTORY, DIRECTORY_SEPARATOR);
} else {
if ($this->_isWordPress()) {
if (!defined('WP_CONTENT_DIR')) {
define('WP_CONTENT_DIR', ABSPATH .'wp-content');
}
$this->_cachedUserContentDir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR .'tubepress-content';
} else {
$this->_cachedUserContentDir = TUBEPRESS_ROOT . DIRECTORY_SEPARATOR .'tubepress-content';
}
}
}
return $this->_cachedUserContentDir;
}
public function getUrlBase()
{
$this->_init();
return $this->_urlBase;
}
public function getUrlUserContent()
{
$this->_init();
return $this->_urlUserContent;
}
public function getUrlAjaxEndpoint()
{
$this->_init();
return $this->_urlAjax;
}
private function _init()
{
if ($this->_hasInitialized) {
return;
}
$this->_readConfig();
$this->_hasInitialized = true;
}
private function _readConfig()
{
$userContentDirectory = $this->getUserContentDirectory();
$userSettingsFilePath = $userContentDirectory .'/config/settings.php';
$configArray = array();
if (is_readable($userSettingsFilePath)) {
$configArray = $this->_readUserConfig($userSettingsFilePath);
} else {
if ($this->_shouldLog) {
$this->_log(sprintf('No readable settings file at <code>%s</code>', $userSettingsFilePath));
}
}
$this->_mergeConfig($configArray);
}
private function _readUserConfig($settingsFilePath)
{
if ($this->_shouldLog) {
$this->_log(sprintf('Detected candidate settings.php at <code>%s</code>', $settingsFilePath));
}
try {
ob_start();
$configArray = include $settingsFilePath;
ob_end_clean();
if (!is_array($configArray)) {
throw new RuntimeException('settings.php did not return an array of config values.');
}
if ($this->_shouldLog) {
$this->_log(sprintf('Successfully read config values from <code>%s</code>', $settingsFilePath));
}
return $configArray;
} catch (Exception $e) {
if ($this->_shouldLog) {
$this->_log(sprintf('Could not read settings.php from <code>%s</code>: <code>%s</code>',
$settingsFilePath, $e->getMessage()));
}
}
return array();
}
private function _mergeConfig(array $config)
{
$this->_addonBlacklistArray = $this->_getAddonBlacklistArray($config);
$this->_isClassLoaderEnabled = $this->_getClassLoaderEnablement($config);
$this->_systemCacheKillerKey = $this->_getCacheKillerKey($config);
$this->_cacheDirectory = rtrim($this->_getSystemCacheDirectory($config), DIRECTORY_SEPARATOR);
$this->_isCacheEnabled = $this->_getCacheEnablement($config);
$this->_serializationEncoding = $this->_getSerializationEncoding($config);
$this->_urlAjax = $this->_getUrl($config, self::$_3RD_LEVEL_KEY_URL_AJAX);
$this->_urlBase = $this->_getUrl($config, self::$_3RD_LEVEL_KEY_URL_BASE);
$this->_urlUserContent = $this->_getUrl($config, self::$_3RD_LEVEL_KEY_URL_USERCONTENT);
if ($this->_shouldLog) {
$this->_log('Final settings from settings.php below:');
$this->_log(sprintf('Add-on blacklist: <code>%s</code>', htmlspecialchars(json_encode($this->_addonBlacklistArray))));
$this->_log(sprintf('Class loader enabled? <code>%s</code>', $this->_isClassLoaderEnabled ?'yes':'no'));
$this->_log(sprintf('Cache directory: <code>%s</code>', $this->_cacheDirectory));
$this->_log(sprintf('Cache enabled? <code>%s</code>', $this->_isCacheEnabled ?'yes':'no'));
$this->_log(sprintf('Serialization encoding: <code>%s</code>', $this->_serializationEncoding));
$this->_log(sprintf('Ajax URL: <code>%s</code>', $this->_urlAjax));
$this->_log(sprintf('Base URL: <code>%s</code>', $this->_urlBase));
$this->_log(sprintf('User content URL: <code>%s</code>', $this->_urlUserContent));
}
}
private function _getUrl(array $config, $key)
{
if (!$this->_isAllSet($config, self::$_TOP_LEVEL_KEY_USER, self::$_2ND_LEVEL_KEY_URLS, $key)) {
return null;
}
$candidate = $config[self::$_TOP_LEVEL_KEY_USER][self::$_2ND_LEVEL_KEY_URLS][$key];
try {
$toReturn = $this->_urlFactory->fromString($candidate);
$toReturn->freeze();
return $toReturn;
} catch (InvalidArgumentException $e) {
if ($this->_shouldLog) {
$this->_logger->error("Unable to parse $key URL from settings.php");
}
return null;
}
}
private function _getSystemCacheDirectory(array $config)
{
if (!$this->_isAllSet($config, self::$_TOP_LEVEL_KEY_SYSTEM, self::$_2ND_LEVEL_KEY_CACHE,
self::$_3RD_LEVEL_KEY_CACHE_DIR)) {
return $this->_getFilesystemCacheDirectory();
}
$path = $config[self::$_TOP_LEVEL_KEY_SYSTEM][self::$_2ND_LEVEL_KEY_CACHE][self::$_3RD_LEVEL_KEY_CACHE_DIR];
$path = $this->_addVersionToPath($path);
if ($path === $this->_createDirectoryIfNecessary($path)) {
return $path;
}
if ($this->_shouldLog) {
$this->_log(sprintf('Unable to use <code>%s</code>, so we will instead use the system temp directory', $path
));
}
return $this->_getFilesystemCacheDirectory();
}
private function _getAddonBlacklistArray(array $config)
{
$default = array();
if (!$this->_isAllSet($config, self::$_TOP_LEVEL_KEY_SYSTEM, self::$_2ND_LEVEL_KEY_ADDONS,
self::$_3RD_LEVEL_KEY_ADDONS_BLACKLIST)) {
return $default;
}
$blackList = $config[self::$_TOP_LEVEL_KEY_SYSTEM][self::$_2ND_LEVEL_KEY_ADDONS]
[self::$_3RD_LEVEL_KEY_ADDONS_BLACKLIST];
if (!is_array($blackList)) {
return $default;
}
return array_values($blackList);
}
private function _getClassLoaderEnablement(array $config)
{
$default = true;
if (!$this->_isAllSet($config, self::$_TOP_LEVEL_KEY_SYSTEM, self::$_2ND_LEVEL_KEY_CLASSLOADER,
self::$_3RD_LEVEL_KEY_CLASSLOADER_ENABLED)) {
return $default;
}
$enabled = $config[self::$_TOP_LEVEL_KEY_SYSTEM][self::$_2ND_LEVEL_KEY_CLASSLOADER]
[self::$_3RD_LEVEL_KEY_CLASSLOADER_ENABLED];
if (!is_bool($enabled)) {
return $default;
}
return (boolean) $enabled;
}
private function _getCacheEnablement(array $config)
{
$default = true;
if (!$this->_isAllSet($config, self::$_TOP_LEVEL_KEY_SYSTEM, self::$_2ND_LEVEL_KEY_CACHE,
self::$_3RD_LEVEL_KEY_CACHE_ENABLED)) {
return $default;
}
$enabled = $config[self::$_TOP_LEVEL_KEY_SYSTEM][self::$_2ND_LEVEL_KEY_CACHE]
[self::$_3RD_LEVEL_KEY_CACHE_ENABLED];
if (!is_bool($enabled)) {
return $default;
}
return (boolean) $enabled;
}
private function _getCacheKillerKey(array $config)
{
$default ='tubepress_clear_system_cache';
if (!$this->_isAllSet($config, self::$_TOP_LEVEL_KEY_SYSTEM, self::$_2ND_LEVEL_KEY_CACHE,
self::$_3RD_LEVEL_KEY_CACHE_KILLERKEY)) {
return $default;
}
$key = $config[self::$_TOP_LEVEL_KEY_SYSTEM][self::$_2ND_LEVEL_KEY_CACHE]
[self::$_3RD_LEVEL_KEY_CACHE_KILLERKEY];
if (!is_string($key) || $key =='') {
return $default;
}
return $key;
}
private function _getSerializationEncoding(array $config)
{
$default ='base64';
if (!$this->_isAllSet($config, self::$_TOP_LEVEL_KEY_SYSTEM, self::$_2ND_LEVEL_KEY_CACHE,
self::$_3RD_LEVEL_KEY_SERIALIZATION_ENC)) {
return $default;
}
$encoding = $config[self::$_TOP_LEVEL_KEY_SYSTEM][self::$_2ND_LEVEL_KEY_CACHE]
[self::$_3RD_LEVEL_KEY_SERIALIZATION_ENC];
if (!in_array($encoding, array('base64','none','urlencode'))) {
return $default;
}
return $encoding;
}
private function _isAllSet(array $arr, $topLevel, $secondLevel, $thirdLevel)
{
if (!isset($arr[$topLevel])) {
return false;
}
if (!isset($arr[$topLevel][$secondLevel])) {
return false;
}
if (!isset($arr[$topLevel][$secondLevel][$thirdLevel])) {
return false;
}
return true;
}
private function _getFilesystemCacheDirectory()
{
if (function_exists('sys_get_temp_dir')) {
$tmp = rtrim(sys_get_temp_dir(),'/\\') . DIRECTORY_SEPARATOR;
} else {
$tmp ='/tmp/';
}
$baseDir = $tmp .'tubepress-system-cache-'. md5(__DIR__) . DIRECTORY_SEPARATOR;
$baseDir = $this->_addVersionToPath($baseDir);
if ($baseDir === $this->_createDirectoryIfNecessary($baseDir)) {
return $baseDir;
}
if (!$this->_isWordPress()) {
return null;
}
$userContentDirectory = $this->getUserContentDirectory();
$cacheDirectory = $userContentDirectory . DIRECTORY_SEPARATOR .'system-cache';
$cacheDirectory = $this->_addVersionToPath($cacheDirectory);
if ($this->_shouldLog) {
$this->_log(sprintf('Trying to use <code>%s</code> as the system cache directory instead.', $cacheDirectory));
}
if ($cacheDirectory === $this->_createDirectoryIfNecessary($cacheDirectory)) {
return $cacheDirectory;
}
return null;
}
private function _isWordPress()
{
return defined('DB_NAME') && defined('ABSPATH');
}
private function _log($msg)
{
$this->_logger->debug(sprintf('(Boot Settings) %s', $msg));
}
private function _createDirectoryIfNecessary($path)
{
if ($this->_shouldLog) {
$this->_log(sprintf('Seeing if we can use <code>%s</code> as our system cache directory', $path));
}
if (!is_dir($path)) {
if ($this->_shouldLog) {
$this->_log(sprintf('<code>%s</code> does not exist, so we will try to create it.', $path));
}
@mkdir($path, 0770, true);
if (!is_dir($path)) {
if ($this->_shouldLog) {
$this->_log(sprintf('Tried and failed to create <code>%s</code>.', $path));
}
return null;
}
}
if (!is_writable($path)) {
if ($this->_shouldLog) {
$this->_log(sprintf('<code>%s</code> is a directory but we cannot write to it.', $path));
}
return null;
}
if ($this->_shouldLog) {
$this->_log(sprintf('<code>%s</code> is a writable directory, so we should be able to use it.', $path));
}
return $path;
}
private function _addVersionToPath($path)
{
return rtrim($path, DIRECTORY_SEPARATOR) .
DIRECTORY_SEPARATOR .'tubepress-'.
TUBEPRESS_VERSION;
}
}
}
namespace
{
interface tubepress_api_ioc_ContainerInterface
{
function get($id);
function getParameter($name);
function has($id);
function hasParameter($name);
function set($id, $service);
function setParameter($name, $value);
}
}
namespace
{
class tubepress_internal_ioc_Container implements tubepress_api_ioc_ContainerInterface
{
private $_underlyingSymfonyContainer;
public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $delegate)
{
$this->_underlyingSymfonyContainer = $delegate;
}
public function get($id)
{
return $this->_underlyingSymfonyContainer->get($id, \Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE);
}
public function getParameter($name)
{
return $this->_underlyingSymfonyContainer->getParameter($name);
}
public function has($id)
{
return $this->_underlyingSymfonyContainer->has($id);
}
public function hasParameter($name)
{
return $this->_underlyingSymfonyContainer->hasParameter($name);
}
public function set($id, $service)
{
$this->_underlyingSymfonyContainer->set($id, $service);
}
public function setParameter($name, $value)
{
$this->_underlyingSymfonyContainer->setParameter($name, $value);
}
}
}
namespace
{
interface tubepress_api_log_LoggerInterface
{
const _ ='tubepress_api_log_LoggerInterface';
function isEnabled();
function onBootComplete();
function debug($message, array $context = array());
function error($message, array $context = array());
}
}
namespace
{
class tubepress_internal_logger_BootLogger implements tubepress_api_log_LoggerInterface
{
private $_isEnabled = false;
private $_buffer = array();
public function __construct($enabled)
{
$this->_isEnabled = (bool) $enabled;
}
public function isEnabled()
{
return $this->_isEnabled;
}
public function handleBootException(Exception $e)
{
$this->error('Caught exception while booting: '. $e->getMessage());
$traceData = $e->getTraceAsString();
$traceData = explode("\n", $traceData);
foreach ($traceData as $line) {
$this->error("<code>$line</code>");
}
foreach ($this->_buffer as $message => $context) {
$message = sprintf('%s [%s]', $message, print_r($context, true));
echo "$message<br />\n";
}
}
public function debug($message, array $context = array())
{
$this->_store($message, $context,'normal');
}
public function error($message, array $context = array())
{
$this->_store($message, $context,'error');
}
public function flushTo(tubepress_api_log_LoggerInterface $logger)
{
foreach ($this->_buffer as $message => $context) {
$error = false;
if (isset($context['__level'])) {
$error = $context['__level'] ==='error';
unset($context['__level']);
}
if ($error) {
$logger->error($message, $context);
} else {
$logger->debug($message, $context);
}
}
}
private function _store($message, $context, $level)
{
if (!$this->_isEnabled) {
return;
}
$message = sprintf('%s %s', $this->_udate(), $message);
$context['__level'] = $level;
$this->_buffer[$message] = $context;
}
private function _udate()
{
$utimestamp = microtime(true);
$timestamp = floor($utimestamp);
$milliseconds = round(($utimestamp - $timestamp) * 1000000);
return date(preg_replace('`(?<!\\\\)u`', $milliseconds,'H:i:s.u'), $timestamp);
}
public function onBootComplete()
{
$this->_isEnabled = false;
unset($this->_buffer);
}
}
}
namespace
{
class tubepress_options_impl_listeners_StringMagicListener
{
private $_eventDispatcher;
public function __construct(tubepress_api_event_EventDispatcherInterface $eventDispatcher)
{
$this->_eventDispatcher = $eventDispatcher;
}
public function onExternalInput(tubepress_api_event_EventInterface $event)
{
$value = $event->getSubject();
$this->_magic($value);
$event->setSubject($value);
}
public function onSet(tubepress_api_event_EventInterface $event)
{
$value = $event->getArgument('optionValue');
$this->_magic($value);
$event->setArgument('optionValue', $value);
}
private function _magic(&$value)
{
if (is_array($value)) {
foreach ($value as $key => $subValue) {
$this->_magic($subValue);
$value[$key] = $subValue;
}
}
if (!is_string($value)) {
return;
}
$value = trim($value);
$value = htmlspecialchars($value, ENT_NOQUOTES);
$value = $this->_booleanMagic($value);
}
private function _booleanMagic($value)
{
if (strcasecmp($value,'false') === 0) {
return false;
}
if (strcasecmp($value,'true') === 0) {
return true;
}
return $value;
}
}
}
namespace
{
interface tubepress_api_options_ReferenceInterface
{
const _ ='tubepress_api_options_ReferenceInterface';
function getAllOptionNames();
function getDefaultValue($optionName);
function getUntranslatedDescription($optionName);
function getUntranslatedLabel($optionName);
function optionExists($optionName);
function isAbleToBeSetViaShortcode($optionName);
function isBoolean($optionName);
function isMeantToBePersisted($optionName);
function isProOnly($optionName);
function getProperty($optionName, $propertyName);
function hasProperty($optionName, $propertyName);
function getPropertyAsBoolean($optionName, $propertyName);
}
}
namespace
{
class tubepress_api_options_Reference implements tubepress_api_options_ReferenceInterface
{
const PROPERTY_DEFAULT_VALUE ='defaultValue';
const PROPERTY_IS_BOOLEAN ='isBoolean';
const PROPERTY_NO_PERSIST ='isMeantToBePersisted';
const PROPERTY_NO_SHORTCODE ='isShortcodeSettable';
const PROPERTY_UNTRANSLATED_DESCRIPTION ='untranslatedDescription';
const PROPERTY_UNTRANSLATED_LABEL ='untranslatedLabel';
const PROPERTY_PRO_ONLY ='proOnly';
private $_valueMap;
private $_boolMap;
public function __construct(array $valueMap, array $booleanMap = array())
{
$this->_valueMap = $valueMap;
$this->_boolMap = $booleanMap;
}
public function getAllOptionNames()
{
return array_keys($this->_valueMap[self::PROPERTY_DEFAULT_VALUE]);
}
public function optionExists($optionName)
{
return array_key_exists($optionName, $this->_valueMap[self::PROPERTY_DEFAULT_VALUE]);
}
public function getProperty($optionName, $propertyName)
{
if (!$this->hasProperty($optionName, $propertyName)) {
throw new InvalidArgumentException("$propertyName is not defined for $optionName");
}
if (isset($this->_boolMap[$propertyName])) {
return in_array($optionName, $this->_boolMap[$propertyName]);
}
return $this->_valueMap[$propertyName][$optionName];
}
public function getPropertyAsBoolean($optionName, $propertyName)
{
return (bool) $this->getProperty($optionName, $propertyName);
}
public function hasProperty($optionName, $propertyName)
{
$this->_assertOptionExists($optionName);
if (isset($this->_valueMap[$propertyName]) && array_key_exists($optionName, $this->_valueMap[$propertyName])) {
return true;
}
return isset($this->_boolMap[$propertyName]);
}
public function getDefaultValue($optionName)
{
$this->_assertOptionExists($optionName);
return $this->_valueMap[self::PROPERTY_DEFAULT_VALUE][$optionName];
}
public function getUntranslatedDescription($optionName)
{
$this->_assertOptionExists($optionName);
return $this->_getOptionalProperty($optionName, self::PROPERTY_UNTRANSLATED_DESCRIPTION, null);
}
public function getUntranslatedLabel($optionName)
{
$this->_assertOptionExists($optionName);
return $this->_getOptionalProperty($optionName, self::PROPERTY_UNTRANSLATED_LABEL, null);
}
public function isAbleToBeSetViaShortcode($optionName)
{
$this->_assertOptionExists($optionName);
return !$this->_getOptionalProperty($optionName, self::PROPERTY_NO_SHORTCODE, false);
}
public function isBoolean($optionName)
{
$this->_assertOptionExists($optionName);
return is_bool($this->getProperty($optionName, self::PROPERTY_DEFAULT_VALUE));
}
public function isMeantToBePersisted($optionName)
{
$this->_assertOptionExists($optionName);
return !$this->_getOptionalProperty($optionName, self::PROPERTY_NO_PERSIST, false);
}
public function isProOnly($optionName)
{
$this->_assertOptionExists($optionName);
return $this->_getOptionalProperty($optionName, self::PROPERTY_PRO_ONLY, false);
}
private function _assertOptionExists($optionName)
{
if (!$this->optionExists($optionName)) {
throw new InvalidArgumentException("$optionName is not a know option");
}
}
private function _getOptionalProperty($optionName, $propertyName, $default)
{
if (!$this->hasProperty($optionName, $propertyName)) {
return $default;
}
return $this->getProperty($optionName, $propertyName);
}
}
}
namespace
{
class tubepress_app_api_options_Reference extends tubepress_api_options_Reference
{
}
}
namespace
{
abstract class puzzle_AbstractHasData
{
protected $data = array();
public function getIterator()
{
return new ArrayIterator($this->data);
}
public function offsetGet($offset)
{
return isset($this->data[$offset]) ? $this->data[$offset] : null;
}
public function offsetSet($offset, $value)
{
$this->data[$offset] = $value;
}
public function offsetExists($offset)
{
return isset($this->data[$offset]);
}
public function offsetUnset($offset)
{
unset($this->data[$offset]);
}
public function toArray()
{
return $this->data;
}
public function count()
{
return count($this->data);
}
public function getPath($path)
{
return puzzle_get_path($this->data, $path);
}
public function setPath($path, $value)
{
puzzle_set_path($this->data, $path, $value);
}
}
}
namespace
{
interface puzzle_ToArrayInterface
{
function toArray();
}
}
namespace
{
class puzzle_Collection extends puzzle_AbstractHasData implements
ArrayAccess,
IteratorAggregate,
Countable,
puzzle_ToArrayInterface
{
public function __construct(array $data = array())
{
$this->data = $data;
}
public static function fromConfig(
array $config = array(),
array $defaults = array(),
array $required = array()
) {
$data = $config + $defaults;
if ($missing = array_diff($required, array_keys($data))) {
throw new InvalidArgumentException('Config is missing the following keys: '.
implode(', ', $missing));
}
return new self($data);
}
public function clear()
{
$this->data = array();
return $this;
}
public function get($key)
{
return isset($this->data[$key]) ? $this->data[$key] : null;
}
public function set($key, $value)
{
$this->data[$key] = $value;
return $this;
}
public function add($key, $value)
{
if (!array_key_exists($key, $this->data)) {
$this->data[$key] = $value;
} elseif (is_array($this->data[$key])) {
$this->data[$key][] = $value;
} else {
$this->data[$key] = array($this->data[$key], $value);
}
return $this;
}
public function remove($key)
{
unset($this->data[$key]);
return $this;
}
public function getKeys()
{
return array_keys($this->data);
}
public function hasKey($key)
{
return array_key_exists($key, $this->data);
}
public function hasValue($value)
{
return array_search($value, $this->data, true);
}
public function replace(array $data)
{
$this->data = $data;
return $this;
}
public function merge($data)
{
foreach ($data as $key => $value) {
$this->add($key, $value);
}
return $this;
}
public function overwriteWith($data)
{
if (is_array($data)) {
$this->data = $data + $this->data;
} elseif ($data instanceof puzzle_Collection) {
$this->data = $data->toArray() + $this->data;
} else {
foreach ($data as $key => $value) {
$this->data[$key] = $value;
}
}
return $this;
}
public function map($closure, array $context = array())
{
$collection = new self();
foreach ($this as $key => $value) {
$collection[$key] = call_user_func_array($closure, array($key, $value, $context));
}
return $collection;
}
public function filter($closure)
{
$collection = new self();
foreach ($this->data as $key => $value) {
if (call_user_func_array($closure, array($key, $value))) {
$collection[$key] = $value;
}
}
return $collection;
}
}
}
namespace
{
class puzzle_Query extends puzzle_Collection
{
const RFC3986 ='RFC3986';
const RFC1738 ='RFC1738';
private $encoding = self::RFC3986;
private $aggregator;
public static function fromString($query, $urlEncoding = true)
{
static $qp;
if (!$qp) {
$qp = new puzzle_QueryParser();
}
$q = new self();
if ($urlEncoding !== true) {
$q->setEncodingType($urlEncoding);
}
$qp->parseInto($q, $query, $urlEncoding);
return $q;
}
public function __toString()
{
if (!$this->data) {
return'';
}
static $defaultAggregator;
if (!$this->aggregator) {
if (!$defaultAggregator) {
$defaultAggregator = self::phpAggregator();
}
$this->aggregator = $defaultAggregator;
}
$result ='';
$aggregator = $this->aggregator;
foreach (call_user_func($aggregator, $this->data) as $key => $values) {
foreach ($values as $value) {
if ($result) {
$result .='&';
}
if ($this->encoding == self::RFC1738) {
$result .= urlencode($key);
if ($value !== null) {
$result .='='. urlencode($value);
}
} elseif ($this->encoding == self::RFC3986) {
$result .= rawurlencode($key);
if ($value !== null) {
$result .='='. rawurlencode($value);
}
} else {
$result .= $key;
if ($value !== null) {
$result .='='. $value;
}
}
}
}
return $result;
}
public function setAggregator($aggregator)
{
$this->aggregator = $aggregator;
return $this;
}
public function setEncodingType($type)
{
if ($type === false || $type === self::RFC1738 || $type === self::RFC3986) {
$this->encoding = $type;
} else {
throw new InvalidArgumentException('Invalid URL encoding type');
}
return $this;
}
public static function duplicateAggregator()
{
return array('puzzle_Query','__callback_duplicateAggregator');
}
public static function __callback_duplicateAggregator(array $data)
{
return self::walkQuery($data,'', array('puzzle_Query','__callback_duplicateAggregator_1'));
}
public static function __callback_duplicateAggregator_1($key, $prefix)
{
return is_int($key) ? $prefix : "{$prefix}[{$key}]";
}
public static function phpAggregator($numericIndices = true)
{
$aggregator = new __puzzle_phpAggregator($numericIndices);
return array($aggregator,'aggregate');
}
public static function walkQuery(array $query, $keyPrefix, $prefixer)
{
$result = array();
foreach ($query as $key => $value) {
if ($keyPrefix) {
$key = call_user_func_array($prefixer, array($key, $keyPrefix));
}
if (is_array($value)) {
$result += self::walkQuery($value, $key, $prefixer);
} elseif (isset($result[$key])) {
$result[$key][] = $value;
} else {
$result[$key] = array($value);
}
}
return $result;
}
}
class __puzzle_phpAggregator
{
private $_useNumericIndices;
public function __construct($useNumericIndices)
{
$this->_useNumericIndices = $useNumericIndices;
}
public function aggregate(array $data)
{
return puzzle_Query::walkQuery($data,'', array($this,'_walker'));
}
public function _walker($key, $prefix)
{
return !$this->_useNumericIndices && is_int($key)
? "{$prefix}[]"
: "{$prefix}[{$key}]";
}
}
}
namespace
{
class puzzle_Url
{
private $scheme;
private $host;
private $port;
private $username;
private $password;
private $path ='';
private $fragment;
private static $defaultPorts = array('http'=> 80,'https'=> 443,'ftp'=> 21);
private $query;
public static function fromString($url)
{
static $defaults = array('scheme'=> null,'host'=> null,'path'=> null,'port'=> null,'query'=> null,'user'=> null,'pass'=> null,'fragment'=> null);
$phpLessThan547 = version_compare(PHP_VERSION,'5.4.7') < 0;
$phpLessThan547Hack = false;
if ($phpLessThan547 && substr($url, 0, 2) ==='//') {
$phpLessThan547Hack = true;
$url ='http:'. $url;
}
if (false === ($parts = @parse_url($url))) {
throw new InvalidArgumentException('Unable to parse malformed '.'url: '. $url);
}
if ($phpLessThan547Hack) {
$parts['scheme'] = null;
}
$parts += $defaults;
if ($parts['query'] || 0 !== strlen($parts['query'])) {
$parts['query'] = puzzle_Query::fromString($parts['query']);
}
return new self($parts['scheme'], $parts['host'], $parts['user'],
$parts['pass'], $parts['port'], $parts['path'], $parts['query'],
$parts['fragment']);
}
public static function buildUrl(array $parts)
{
$url = $scheme ='';
if (!empty($parts['scheme'])) {
$scheme = $parts['scheme'];
$url .= $scheme .':';
}
if (!empty($parts['host'])) {
$url .='//';
if (isset($parts['user'])) {
$url .= $parts['user'];
if (isset($parts['pass'])) {
$url .=':'. $parts['pass'];
}
$url .='@';
}
$url .= $parts['host'];
if (isset($parts['port']) &&
(!isset(self::$defaultPorts[$scheme]) ||
$parts['port'] != self::$defaultPorts[$scheme])
) {
$url .=':'. $parts['port'];
}
}
if (isset($parts['path']) && strlen($parts['path'])) {
if (!empty($parts['host']) && $parts['path'][0] !='/') {
$url .='/';
}
$url .= $parts['path'];
}
if (isset($parts['query'])) {
$queryStr = (string) $parts['query'];
if ($queryStr || $queryStr ==='0') {
$url .='?'. $queryStr;
}
}
if (isset($parts['fragment'])) {
$url .='#'. $parts['fragment'];
}
return $url;
}
public function __construct(
$scheme,
$host,
$username = null,
$password = null,
$port = null,
$path = null,
puzzle_Query $query = null,
$fragment = null
) {
$this->scheme = $scheme;
$this->host = $host;
$this->port = $port;
$this->username = $username;
$this->password = $password;
$this->fragment = $fragment;
if (!$query) {
$this->query = new puzzle_Query();
} else {
$this->setQuery($query);
}
$this->setPath($path);
}
public function __clone()
{
$this->query = clone $this->query;
}
public function __toString()
{
return self::buildUrl($this->getParts());
}
public function getParts()
{
return array('scheme'=> $this->scheme,'user'=> $this->username,'pass'=> $this->password,'host'=> $this->host,'port'=> $this->port,'path'=> $this->path,'query'=> $this->query,'fragment'=> $this->fragment,
);
}
public function setHost($host)
{
if (strpos($host,':') === false) {
$this->host = $host;
} else {
list($host, $port) = explode(':', $host);
$this->host = $host;
$this->setPort($port);
}
return $this;
}
public function getHost()
{
return $this->host;
}
public function setScheme($scheme)
{
if ($this->port && isset(self::$defaultPorts[$this->scheme]) &&
self::$defaultPorts[$this->scheme] == $this->port
) {
$this->port = null;
}
$this->scheme = $scheme;
return $this;
}
public function getScheme()
{
return $this->scheme;
}
public function setPort($port)
{
$this->port = $port;
return $this;
}
public function getPort()
{
if ($this->port) {
return $this->port;
} elseif (isset(self::$defaultPorts[$this->scheme])) {
return self::$defaultPorts[$this->scheme];
}
return null;
}
public function setPath($path)
{
static $search = array(' ','?');
static $replace = array('%20','%3F');
$this->path = str_replace($search, $replace, $path);
return $this;
}
public function removeDotSegments()
{
static $noopPaths = array(''=> true,'/'=> true,'*'=> true);
static $ignoreSegments = array('.'=> true,'..'=> true);
if (isset($noopPaths[$this->path])) {
return $this;
}
$results = array();
$segments = $this->getPathSegments();
foreach ($segments as $segment) {
if ($segment =='..') {
array_pop($results);
} elseif (!isset($ignoreSegments[$segment])) {
$results[] = $segment;
}
}
$newPath = implode('/', $results);
if (substr($this->path, 0, 1) ==='/'&&
substr($newPath, 0, 1) !=='/') {
$newPath ='/'. $newPath;
}
if ($newPath !='/'&& isset($ignoreSegments[end($segments)])) {
$newPath .='/';
}
$this->path = $newPath;
return $this;
}
public function addPath($relativePath)
{
if ($relativePath !='/'&&
is_string($relativePath) &&
strlen($relativePath) > 0
) {
if ($relativePath[0] !=='/'&&
substr($this->path, -1, 1) !=='/') {
$relativePath ='/'. $relativePath;
}
$this->setPath($this->path . $relativePath);
}
return $this;
}
public function getPath()
{
return $this->path;
}
public function getPathSegments()
{
return explode('/', $this->path);
}
public function setPassword($password)
{
$this->password = $password;
return $this;
}
public function getPassword()
{
return $this->password;
}
public function setUsername($username)
{
$this->username = $username;
return $this;
}
public function getUsername()
{
return $this->username;
}
public function getQuery()
{
return $this->query;
}
public function setQuery($query)
{
if ($query instanceof puzzle_Query) {
$this->query = $query;
} elseif (is_string($query)) {
$this->query = puzzle_Query::fromString($query);
} elseif (is_array($query)) {
$this->query = new puzzle_Query($query);
} else {
throw new InvalidArgumentException('Query must be a '.'QueryInterface, array, or string');
}
return $this;
}
public function getFragment()
{
return $this->fragment;
}
public function setFragment($fragment)
{
$this->fragment = $fragment;
return $this;
}
public function isAbsolute()
{
return $this->scheme && $this->host;
}
public function combine($url)
{
$url = self::fromString($url);
if (!$this->isAbsolute() && $url->isAbsolute()) {
$url = $url->combine($this);
}
$parts = $url->getParts();
if ($parts['scheme']) {
return new self(
$parts['scheme'],
$parts['host'],
$parts['user'],
$parts['pass'],
$parts['port'],
$parts['path'],
clone $parts['query'],
$parts['fragment']
);
}
if ($parts['host']) {
return new self(
$this->scheme,
$parts['host'],
$parts['user'],
$parts['pass'],
$parts['port'],
$parts['path'],
clone $parts['query'],
$parts['fragment']
);
}
if (!$parts['path'] && $parts['path'] !=='0') {
$path = $this->path ? $this->path :'';
$query = count($parts['query']) ? $parts['query'] : $this->query;
} else {
$query = $parts['query'];
if ($parts['path'][0] =='/'|| !$this->path) {
$path = $parts['path'];
} else {
$path = substr($this->path, 0, strrpos($this->path,'/') + 1) . $parts['path'];
}
}
$result = new self(
$this->scheme,
$this->host,
$this->username,
$this->password,
$this->port,
$path,
clone $query,
$parts['fragment']
);
if ($path) {
$result->removeDotSegments();
}
return $result;
}
}
}
namespace
{
interface tubepress_api_collection_MapInterface
{
function clear();
function containsKey($key);
function containsValue($value);
function count();
function get($key);
function getAsBoolean($key);
function isEmpty();
function keySet();
function put($name, $value);
function remove($key);
function values();
}
}
namespace
{
interface tubepress_api_contrib_ContributableInterface
{
function getName();
function getVersion();
function getTitle();
function getAuthors();
function getLicense();
function getDescription();
function getKeywords();
function getHomepageUrl();
function getDocumentationUrl();
function getDemoUrl();
function getDownloadUrl();
function getBugTrackerUrl();
function getSourceCodeUrl();
function getForumUrl();
function getScreenshots();
function getProperties();
}
}
namespace
{
interface tubepress_api_contrib_RegistryInterface
{
const _ ='tubepress_api_contrib_RegistryInterface';
function getAll();
function getInstanceByName($name);
}
}
namespace
{
interface tubepress_api_environment_EnvironmentInterface
{
const _ ='tubepress_api_environment_EnvironmentInterface';
function isPro();
function getVersion();
function getBaseUrl();
function getUserContentUrl();
function getAjaxEndpointUrl();
function getProperties();
function setUserContentUrl($url);
function setBaseUrl($url);
}
}
namespace
{
interface tubepress_api_event_EventDispatcherInterface
{
const _ ='tubepress_api_event_EventDispatcherInterface';
function addListener($eventName, $listener, $priority = 0);
function addListenerService($eventName, $callback, $priority = 0);
function dispatch($eventName, tubepress_api_event_EventInterface $event = null);
function getListeners($eventName = null);
function hasListeners($eventName = null);
function newEventInstance($subject = null, array $arguments = array());
function removeListener($eventName, $listener);
}
}
namespace
{
interface tubepress_lib_api_event_EventInterface
{
}
}
namespace
{
interface tubepress_api_event_EventInterface extends tubepress_lib_api_event_EventInterface
{
function getArgument($key);
function getArguments();
function getDispatcher();
function getName();
function getSubject();
function hasArgument($key);
function isPropagationStopped();
function setArgument($key, $value);
function setArguments(array $args = array());
function setSubject($subject);
function stopPropagation();
}
}
namespace
{
interface tubepress_api_event_Events
{
const GALLERY_INIT_JS ='tubepress.app.gallery.initJs';
const HTML_EXCEPTION_CAUGHT ='tubepress.app.html.exception.caught';
const HTML_GLOBAL_JS_CONFIG ='tubepress.app.html.globalJsConfig';
const HTML_GENERATION ='tubepress.app.html.generation';
const HTML_GENERATION_POST ='tubepress.app.html.generation.post';
const HTML_SCRIPTS ='tubepress.app.html.scripts';
const HTML_STYLESHEETS ='tubepress.app.html.stylesheets';
const HTML_SCRIPTS_ADMIN ='tubepress.app.html.scripts.admin';
const HTML_STYLESHEETS_ADMIN ='tubepress.app.html.stylesheets.admin';
const HTTP_AJAX ='tubepress.app.http.ajax';
const MEDIA_ITEM_HTTP_NEW ='tubepress.app.media.item.http.new';
const MEDIA_ITEM_HTTP_URL ='tubepress.app.media.item.http.url';
const MEDIA_ITEM_NEW ='tubepress.app.media.item.new';
const MEDIA_ITEM_REQUEST ='tubepress.app.media.item.request';
const MEDIA_PAGE_HTTP_NEW ='tubepress.app.media.page.http.new';
const MEDIA_PAGE_HTTP_URL ='tubepress.app.media.page.http.url';
const MEDIA_PAGE_NEW ='tubepress.app.media.page.new';
const MEDIA_PAGE_REQUEST ='tubepress.app.media.page.request';
const NVP_FROM_EXTERNAL_INPUT ='tubepress.app.nvp.fromExternalInput';
const OAUTH2_URL_REDIRECTION_ENDPOINT ='tubepress.oauth2.url.redirection';
const OAUTH2_URL_AUTHORIZATION ='tubepress.oauth2.url.authorization';
const OPTION_ACCEPTABLE_VALUES ='tubepress.app.option.acceptableValues';
const OPTION_DEFAULT_VALUE ='tubepress.app.option.defaultValue';
const OPTION_DESCRIPTION ='tubepress.app.option.description';
const OPTION_LABEL ='tubepress.app.option.label';
const OPTION_SET ='tubepress.app.option.set';
const TEMPLATE_SELECT ='tubepress.app.template.select';
const TEMPLATE_PRE_RENDER ='tubepress.app.template.pre';
const TEMPLATE_POST_RENDER ='tubepress.app.template.post';
}
}
namespace
{
interface tubepress_api_html_HtmlGeneratorInterface
{
const _ ='tubepress_api_html_HtmlGeneratorInterface';
function getHtml();
function getUrlsCSS();
function getUrlsJS();
function getCSS();
function getJS();
}
}
namespace
{
interface tubepress_api_http_AjaxInterface
{
const _ ='tubepress_api_http_AjaxInterface';
function handle();
}
}
namespace
{
interface tubepress_api_http_RequestParametersInterface
{
const _ ='tubepress_api_http_RequestParametersInterface';
function getParamValue($name);
function getParamValueAsInt($name, $default);
function hasParam($name);
function getAllParams();
}
}
namespace
{
interface tubepress_api_http_ResponseCodeInterface
{
const _ ='tubepress_api_http_ResponseCodeInterface';
function setResponseCode($code);
function getCurrentResponseCode();
}
}
namespace
{
interface tubepress_app_api_options_ContextInterface
{
}
}
namespace
{
interface tubepress_api_options_ContextInterface extends tubepress_app_api_options_ContextInterface
{
const _ ='tubepress_api_options_ContextInterface';
function get($optionName);
function getEphemeralOptions();
function setEphemeralOption($optionName, $optionValue);
function setEphemeralOptions(array $customOpts);
}
}
namespace
{
interface tubepress_api_options_Names
{
const CACHE_CLEANING_FACTOR ='cacheCleaningFactor';
const CACHE_DIRECTORY ='cacheDirectory';
const CACHE_ENABLED ='cacheEnabled';
const CACHE_LIFETIME_SECONDS ='cacheLifetimeSeconds';
const CACHE_HTML_CLEANING_FACTOR ='htmlCacheCleaningFactor';
const CACHE_HTML_CLEANING_KEY ='htmlCacheCleaningKey';
const CACHE_HTML_DIRECTORY ='htmlCacheDirectory';
const CACHE_HTML_ENABLED ='htmlCacheEnabled';
const CACHE_HTML_LIFETIME_SECONDS ='htmlCacheLifetimeSeconds';
const DEBUG_ON ='debugging_enabled';
const EMBEDDED_AUTOPLAY ='autoplay';
const EMBEDDED_HEIGHT ='embeddedHeight';
const EMBEDDED_WIDTH ='embeddedWidth';
const EMBEDDED_LAZYPLAY ='lazyPlay';
const EMBEDDED_LOOP ='loop';
const EMBEDDED_PLAYER_IMPL ='playerImplementation';
const EMBEDDED_SCROLL_DURATION ='embeddedScrollDuration';
const EMBEDDED_SCROLL_OFFSET ='embeddedScrollOffset';
const EMBEDDED_SCROLL_ON ='embeddedScrollOn';
const EMBEDDED_SHOW_INFO ='showInfo';
const FEED_ORDER_BY ='orderBy';
const FEED_PER_PAGE_SORT ='perPageSort';
const FEED_RESULT_COUNT_CAP ='resultCountCap';
const FEED_RESULTS_PER_PAGE ='resultsPerPage';
const FEED_ADJUSTED_RESULTS_PER_PAGE ='adjustedResultsPerPage';
const FEED_ITEM_ID_BLACKLIST ='videoBlacklist';
const GALLERY_AUTONEXT ='autoNext';
const GALLERY_SOURCE ='mode';
const GALLERY_AJAX_PAGINATION ='ajaxPagination';
const GALLERY_FLUID_THUMBS ='fluidThumbs';
const GALLERY_HQ_THUMBS ='hqThumbs';
const GALLERY_PAGINATE_ABOVE ='paginationAbove';
const GALLERY_PAGINATE_BELOW ='paginationBelow';
const GALLERY_RANDOM_THUMBS ='randomize_thumbnails';
const GALLERY_THUMB_HEIGHT ='thumbHeight';
const GALLERY_THUMB_WIDTH ='thumbWidth';
const HTML_OUTPUT ='output';
const HTTP_METHOD ='httpMethod';
const HTML_HTTPS ='https';
const HTML_GALLERY_ID ='galleryId';
const LANG ='lang';
const META_DISPLAY_AUTHOR ='author';
const META_DISPLAY_CATEGORY ='category';
const META_DISPLAY_DESCRIPTION ='description';
const META_DISPLAY_ID ='id';
const META_DISPLAY_KEYWORDS ='tags';
const META_DISPLAY_LENGTH ='length';
const META_DISPLAY_TITLE ='title';
const META_DISPLAY_UPLOADED ='uploaded';
const META_DISPLAY_URL ='url';
const META_DISPLAY_VIEWS ='views';
const META_DATEFORMAT ='dateFormat';
const META_DESC_LIMIT ='descriptionLimit';
const META_RELATIVE_DATES ='relativeDates';
const OAUTH2_TOKEN ='authorizeAs';
const OAUTH2_TOKENS ='oauth2Tokens';
const OAUTH2_CLIENT_DETAILS ='oauth2ClientDetails';
const OPTIONS_UI_DISABLED_FIELD_PROVIDERS ='disabledFieldProviderNames';
const PLAYER_LOCATION ='playerLocation';
const RESPONSIVE_EMBEDS ='responsiveEmbeds';
const SEARCH_ONLY_USER ='searchResultsRestrictedToUser';
const SEARCH_PROVIDER ='searchProvider';
const SEARCH_RESULTS_DOM_ID ='searchResultsDomId';
const SEARCH_RESULTS_ONLY ='searchResultsOnly';
const SEARCH_RESULTS_URL ='searchResultsUrl';
const SHORTCODE_KEYWORD ='keyword';
const SINGLE_MEDIA_ITEM_ID ='video';
const SOURCES ='sources';
const TEMPLATE_CACHE_AUTORELOAD ='templateCacheAutoreload';
const TEMPLATE_CACHE_ENABLED ='templateCacheEnabled';
const TEMPLATE_CACHE_DIR ='templateCacheDirectory';
const THEME ='theme';
const THEME_ADMIN ='adminTheme';
const TUBEPRESS_API_KEY ='tubepressApiKey';
}
}
namespace
{
interface tubepress_spi_options_PersistenceBackendInterface
{
const _ ='tubepress_spi_options_PersistenceBackendInterface';
function createEach(array $optionNamesToValuesMap);
function saveAll(array $optionNamesToValues);
function fetchAllCurrentlyKnownOptionNamesToValues();
}
}
namespace
{
interface tubepress_api_options_PersistenceInterface
{
const _ ='tubepress_api_options_PersistenceInterface';
function fetch($optionName);
function fetchAll();
function queueForSave($optionName, $optionValue);
function flushSaveQueue();
}
}
namespace
{
interface tubepress_api_shortcode_ParserInterface
{
const _ ='tubepress_api_shortcode_ParserInterface';
function parse($content);
function getLastShortcodeUsed();
function somethingToParse($content, $trigger ="tubepress");
}
}
namespace
{
interface tubepress_api_template_TemplatingInterface
{
const _ ='tubepress_api_template_TemplatingInterface';
function renderTemplate($name, array $templateVars = array());
}
}
namespace
{
interface tubepress_api_theme_ThemeInterface extends tubepress_api_contrib_ContributableInterface
{
const _ ='tubepress_api_theme_ThemeInterface';
function getUrlsJS(tubepress_api_url_UrlInterface $baseUrl,
tubepress_api_url_UrlInterface $userContentUrl);
function getUrlsCSS(tubepress_api_url_UrlInterface $baseUrl,
tubepress_api_url_UrlInterface $userContentUrl);
function getInlineCSS();
function getParentThemeName();
function hasTemplateSource($name);
function getTemplateSource($name);
function isTemplateSourceFresh($name, $time);
function getTemplateCacheKey($name);
}
}
namespace
{
interface tubepress_api_translation_TranslatorInterface
{
const _ ='tubepress_api_translation_TranslatorInterface';
function trans($id, array $parameters = array(), $domain = null, $locale = null);
function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null);
function setLocale($locale);
function getLocale();
}
}
namespace
{
interface tubepress_api_url_QueryInterface
{
const RFC3986_ENCODING ='RFC3986';
const RFC1738_ENCODING ='RFC1738';
function add($key, $value);
function clear();
function filter($closure);
function freeze();
function get($key);
function getKeys();
function hasKey($key);
function hasValue($value);
function isFrozen();
function map($closure, array $context = array());
function merge($data);
function overwriteWith($data);
function remove($key);
function replace(array $data);
function set($key, $value);
function setEncodingType($type);
function toArray();
function toString();
function __toString();
}
}
namespace
{
interface tubepress_platform_api_url_UrlFactoryInterface
{
}
}
namespace
{
interface tubepress_api_url_UrlFactoryInterface extends tubepress_platform_api_url_UrlFactoryInterface
{
const _ ='tubepress_api_url_UrlFactoryInterface';
function fromString($url);
function fromCurrent();
}
}
namespace
{
interface tubepress_api_url_UrlInterface
{
const _ ='tubepress_api_url_UrlInterface';
function addPath($relativePath);
function freeze();
function getClone();
function getAuthority();
function getFragment();
function getHost();
function getParts();
function getPassword();
function getPath();
function getPathSegments();
function getPort();
function getQuery();
function getScheme();
function getUsername();
function isAbsolute();
function isFrozen();
function removeDotSegments();
function removeSchemeAndAuthority();
function setFragment($fragment);
function setHost($host);
function setPassword($password);
function setPath($path);
function setPort($port);
function setQuery($query);
function setScheme($scheme);
function setUsername($username);
function toString();
function __toString();
}
}
namespace
{
interface tubepress_platform_api_util_StringUtilsInterface
{
}
}
namespace
{
interface tubepress_api_util_StringUtilsInterface extends tubepress_platform_api_util_StringUtilsInterface
{
const _ ='tubepress_api_util_StringUtilsInterface';
function replaceFirst($search, $replace, $str);
function removeNewLines($string);
function removeEmptyLines($string);
function startsWith($haystack, $needle);
function endsWith($haystack, $needle);
function stripslashes_deep($text, $times = 2);
function redactSecrets($string);
}
}
namespace
{
class tubepress_api_version_Version
{
private static $_SEPARATOR ='.';
private $_major = 0;
private $_minor = 0;
private $_micro = 0;
private $_qualifier = null;
private $_asString = null;
public function __construct($major, $minor = 0, $micro = 0, $qualifier ='')
{
$this->_major = intval($major);
$this->_minor = intval($minor);
$this->_micro = intval($micro);
if ($qualifier =='') {
$this->_qualifier = null;
} else {
$this->_qualifier = $qualifier;
}
$this->_validate();
$this->_asString = $this->_generateAsString();
}
public function compareTo($otherVersion)
{
if (!($otherVersion instanceof tubepress_api_version_Version)) {
return $this->compareTo(self::parse($otherVersion));
}
$result = $this->getMajor() - $otherVersion->getMajor();
if ($result !== 0) {
return $result;
}
$result = $this->getMinor() - $otherVersion->getMinor();
if ($result !== 0) {
return $result;
}
$result = $this->getMicro() - $otherVersion->getMicro();
if ($result !== 0) {
return $result;
}
return strcmp($this->getQualifier(), $otherVersion->getQualifier());
}
public static function parse($version)
{
if (! is_string($version)) {
throw new InvalidArgumentException('Can only parse strings to generate version');
}
$empty = new tubepress_api_version_Version(0, 0, 0);
if ($version ==''|| trim($version) =='') {
return $empty;
}
$pieces = explode(self::$_SEPARATOR, $version);
$pieceCount = count($pieces);
switch ($pieceCount) {
case 1:
return new tubepress_api_version_Version(self::_validateNumbersOnly($version));
case 2:
return new tubepress_api_version_Version(self::_validateNumbersOnly($pieces[0]), self::_validateNumbersOnly($pieces[1]));
case 3:
return new tubepress_api_version_Version(self::_validateNumbersOnly($pieces[0]), self::_validateNumbersOnly($pieces[1]), self::_validateNumbersOnly($pieces[2]));
case 4:
return new tubepress_api_version_Version(self::_validateNumbersOnly($pieces[0]), self::_validateNumbersOnly($pieces[1]), self::_validateNumbersOnly($pieces[2]), $pieces[3]);
default:
throw new InvalidArgumentException("Invalid version: $version");
}
}
public function __toString()
{
return $this->_asString;
}
public function getMajor()
{
return $this->_major;
}
public function getMinor()
{
return $this->_minor;
}
public function getMicro()
{
return $this->_micro;
}
public function getQualifier()
{
return $this->_qualifier;
}
private function _generateAsString()
{
$toReturn = $this->_major . self::$_SEPARATOR . $this->_minor . self::$_SEPARATOR . $this->_micro;
if ($this->_qualifier !== null) {
$toReturn = $toReturn . self::$_SEPARATOR . $this->_qualifier;
}
return $toReturn;
}
private function _validate()
{
self::_checkNonNegativeInteger($this->_major,'Major');
self::_checkNonNegativeInteger($this->_minor,'Minor');
self::_checkNonNegativeInteger($this->_micro,'Micro');
if ($this->_qualifier !== null && preg_match_all('/^(?:[0-9a-zA-Z_\-]+)$/', $this->_qualifier, $matches) !== 1) {
throw new InvalidArgumentException("Version qualifier must only consist of alphanumerics plus hyphen and underscore (". $this->_qualifier .")");
}
}
private static function _checkNonNegativeInteger($candidate, $name)
{
if ($candidate < 0) {
throw new InvalidArgumentException("$name version must be non-negative ($candidate)");
}
}
private static function _validateNumbersOnly($candidate)
{
if (preg_match_all('/^[0-9]+$/', $candidate, $matches) !== 1) {
throw new InvalidArgumentException("$candidate is not a number");
}
return $candidate;
}
}
}
namespace
{
class tubepress_environment_impl_Environment implements tubepress_api_environment_EnvironmentInterface
{
private static $_PROPERTY_URL_BASE ='urlBase';
private static $_PROPERTY_URL_USERCONTENT ='urlUserContent';
private static $_PROPERTY_URL_AJAX ='urlAjax';
private static $_PROPERTY_VERSION ='version';
private static $_PROPERTY_IS_PRO ='isPro';
private $_urlFactory;
private $_wpFunctionsInterface;
private $_bootSettings;
private $_properties;
public function __construct(tubepress_api_url_UrlFactoryInterface $urlFactory,
tubepress_api_boot_BootSettingsInterface $bootSettings)
{
$this->_urlFactory = $urlFactory;
$this->_bootSettings = $bootSettings;
$this->_properties = new tubepress_internal_collection_Map();
$this->_properties->put(self::$_PROPERTY_VERSION, tubepress_api_version_Version::parse('5.1.5'));
$this->_properties->put(self::$_PROPERTY_IS_PRO, false);
}
public function getBaseUrl()
{
if (!$this->_properties->containsKey(self::$_PROPERTY_URL_BASE)) {
$fromBootSettings = $this->_bootSettings->getUrlBase();
if ($fromBootSettings) {
$this->_properties->put(self::$_PROPERTY_URL_BASE, $fromBootSettings);
return $fromBootSettings;
}
if (!$this->_isWordPress()) {
throw new RuntimeException('Please specify TubePress base URL in tubepress-content/config/settings.php');
}
$baseName = basename(TUBEPRESS_ROOT);
$prefix = $this->_getWpContentUrl();
$url = rtrim($prefix,'/') . "/plugins/$baseName";
$url = $this->_toUrl($url);
$this->_properties->put(self::$_PROPERTY_URL_BASE, $url);
}
return $this->_properties->get(self::$_PROPERTY_URL_BASE);
}
public function setBaseUrl($url)
{
$asUrl = $this->_toUrl($url);
$this->_properties->put(self::$_PROPERTY_URL_BASE, $asUrl);
}
public function getUserContentUrl()
{
if (!$this->_properties->containsKey(self::$_PROPERTY_URL_USERCONTENT)) {
$fromBootSettings = $this->_bootSettings->getUrlUserContent();
if ($fromBootSettings) {
$this->_properties->put(self::$_PROPERTY_URL_USERCONTENT, $fromBootSettings);
return $fromBootSettings;
}
if ($this->_isWordPress()) {
$url = $this->_getWpContentUrl();
} else {
$url = $this->getBaseUrl()->toString();
}
$url = rtrim($url,'/') .'/tubepress-content';
$url = $this->_toUrl($url);
$this->_properties->put(self::$_PROPERTY_URL_USERCONTENT, $url);
}
return $this->_properties->get(self::$_PROPERTY_URL_USERCONTENT);
}
public function setUserContentUrl($url)
{
$asUrl = $this->_toUrl($url);
$this->_properties->put(self::$_PROPERTY_URL_USERCONTENT, $asUrl);
}
public function getAjaxEndpointUrl()
{
if (!$this->_properties->containsKey(self::$_PROPERTY_URL_AJAX)) {
$fromBootSettings = $this->_bootSettings->getUrlAjaxEndpoint();
if ($fromBootSettings) {
$this->_properties->put(self::$_PROPERTY_URL_AJAX, $fromBootSettings);
return $fromBootSettings;
}
if ($this->_isWordPress()) {
$url = $this->_wpFunctionsInterface->admin_url('admin-ajax.php');
} else {
$url = $this->getBaseUrl()->getClone()->setPath('/web/php/ajaxEndpoint.php');
}
$url = $this->_toUrl($url);
$this->_properties->put(self::$_PROPERTY_URL_AJAX, $url);
}
return $this->_properties->get(self::$_PROPERTY_URL_AJAX);
}
public function isPro()
{
return $this->_properties->getAsBoolean(self::$_PROPERTY_IS_PRO);
}
public function getVersion()
{
return $this->_properties->get(self::$_PROPERTY_VERSION);
}
public function getProperties()
{
return $this->_properties;
}
public function setWpFunctionsInterface($wpFunctionsInterface)
{
if (!is_a($wpFunctionsInterface,'tubepress_wordpress_impl_wp_WpFunctions')) {
throw new InvalidArgumentException('Invalid argument to tubepress_environment_impl_Environment::setWpFunctionsInterface');
}
$this->_wpFunctionsInterface = $wpFunctionsInterface;
}
public function markAsPro()
{
$this->_properties->put(self::$_PROPERTY_IS_PRO, true);
}
private function _toUrl($url)
{
if (!($url instanceof tubepress_api_url_UrlInterface)) {
$url = $this->_urlFactory->fromString($url);
}
$url->freeze();
return $url;
}
private function _isWordPress()
{
return defined('DB_USER') && defined('ABSPATH');
}
private function _getWpContentUrl()
{
$isWpMuDomainMapped = defined('DOMAIN_MAPPING') && constant('DOMAIN_MAPPING') && defined('COOKIE_DOMAIN');
if ($isWpMuDomainMapped) {
$scheme = $this->_wpFunctionsInterface->is_ssl() ?'https://':'http://';
return $scheme . constant('COOKIE_DOMAIN') .'/wp-content';
}
return $this->_wpFunctionsInterface->content_url();
}
}
}
namespace
{
class tubepress_html_impl_CssAndJsGenerationHelper
{
private $_eventDispatcher;
private $_themeRegistry;
private $_templating;
private $_currentThemeService;
private $_environment;
private $_cache;
private $_eventNameUrlsCss;
private $_eventNameUrlsJs;
private $_templateNameCss;
private $_templateNameJs;
public function __construct(tubepress_api_event_EventDispatcherInterface $eventDispatcher,
tubepress_api_contrib_RegistryInterface $themeRegistry,
tubepress_api_template_TemplatingInterface $templating,
tubepress_theme_impl_CurrentThemeService $currentThemeService,
tubepress_api_environment_EnvironmentInterface $environment,
$eventNameUrlsCss,
$eventNameUrlsJs,
$templateNameCss,
$templateNameJs)
{
$this->_eventDispatcher = $eventDispatcher;
$this->_themeRegistry = $themeRegistry;
$this->_templating = $templating;
$this->_currentThemeService = $currentThemeService;
$this->_environment = $environment;
$this->_eventNameUrlsCss = $eventNameUrlsCss;
$this->_eventNameUrlsJs = $eventNameUrlsJs;
$this->_templateNameCss = $templateNameCss;
$this->_templateNameJs = $templateNameJs;
$this->_cache = new tubepress_internal_collection_Map();
}
public function getUrlsCSS()
{
return $this->_getUrls('cached-urls-css','getUrlsCSS', $this->_eventNameUrlsCss);
}
public function getUrlsJS()
{
return $this->_getUrls('cached-urls-js','getUrlsJS', $this->_eventNameUrlsJs);
}
private function _getUrls($cacheKey, $themeGetter, $eventName)
{
if (!$this->_cache->containsKey($cacheKey)) {
$currentTheme = $this->_currentThemeService->getCurrentTheme();
$themeScripts = $this->_recursivelyGetFromTheme($currentTheme, $themeGetter);
$urls = $this->_fireEventAndReturnSubject($eventName, $themeScripts);
$this->_cache->put($cacheKey, $urls);
}
return $this->_cache->get($cacheKey);
}
public function getCSS()
{
$cssUrls = $this->getUrlsCSS();
$currentTheme = $this->_currentThemeService->getCurrentTheme();
$css = $this->_recursivelyGetFromTheme($currentTheme,'getInlineCSS');
return $this->_templating->renderTemplate($this->_templateNameCss, array('inlineCSS'=> $css,'urls'=> $cssUrls,
));
}
public function getJS()
{
$jsUrls = $this->getUrlsJS();
return $this->_templating->renderTemplate($this->_templateNameJs, array('urls'=> $jsUrls));
}
private function _fireEventAndReturnSubject($eventName, $raw)
{
if ($raw instanceof tubepress_api_event_EventInterface) {
$event = $raw;
} else {
$event = $this->_eventDispatcher->newEventInstance($raw);
}
$this->_eventDispatcher->dispatch($eventName, $event);
return $event->getSubject();
}
private function _recursivelyGetFromTheme(tubepress_api_theme_ThemeInterface $theme, $getter)
{
$toReturn = $theme->$getter(
$this->_environment->getBaseUrl(),
$this->_environment->getUserContentUrl()
);
$parentThemeName = $theme->getParentThemeName();
if (!$parentThemeName) {
return $toReturn;
}
$theme = $this->_themeRegistry->getInstanceByName($parentThemeName);
if (!$theme) {
return $toReturn;
}
$fromParent = $this->_recursivelyGetFromTheme($theme, $getter);
if (is_array($fromParent)) {
$toReturn = array_merge($fromParent, $toReturn);
} else {
$toReturn = $fromParent . $toReturn;
}
return $toReturn;
}
}
}
namespace
{
class tubepress_html_impl_HtmlGenerator implements tubepress_api_html_HtmlGeneratorInterface
{
private $_eventDispatcher;
private $_cssJsGenerationHelper;
private $_templating;
private $_cache;
private $_environment;
public function __construct(tubepress_api_event_EventDispatcherInterface $eventDispatcher,
tubepress_api_template_TemplatingInterface $templating,
tubepress_html_impl_CssAndJsGenerationHelper $cssAndJsGenerationHelper,
tubepress_api_environment_EnvironmentInterface $environment)
{
$this->_eventDispatcher = $eventDispatcher;
$this->_cssJsGenerationHelper = $cssAndJsGenerationHelper;
$this->_templating = $templating;
$this->_environment = $environment;
$this->_cache = new tubepress_internal_collection_Map();
}
public function getHtml()
{
try {
$htmlGenerationEventPre = $this->_eventDispatcher->newEventInstance('');
$this->_eventDispatcher->dispatch(tubepress_api_event_Events::HTML_GENERATION, $htmlGenerationEventPre);
$html = $htmlGenerationEventPre->getSubject();
if ($html === null) {
throw new RuntimeException('Unable to generate HTML.');
}
$htmlGenerationEventPost = $this->_eventDispatcher->newEventInstance($html);
$this->_eventDispatcher->dispatch(tubepress_api_event_Events::HTML_GENERATION_POST, $htmlGenerationEventPost);
$html = $htmlGenerationEventPost->getSubject();
return $html;
} catch (Exception $e) {
$event = $this->_eventDispatcher->newEventInstance($e);
$this->_eventDispatcher->dispatch(tubepress_api_event_Events::HTML_EXCEPTION_CAUGHT, $event);
$args = array('exception'=> $e);
$html = $this->_templating->renderTemplate('exception/static', $args);
return $html;
}
}
public function getUrlsCSS()
{
return $this->_cssJsGenerationHelper->getUrlsCSS();
}
public function getUrlsJS()
{
return $this->_cssJsGenerationHelper->getUrlsJs();
}
public function getCSS()
{
return $this->_cssJsGenerationHelper->getCSS();
}
public function getJS()
{
return $this->_cssJsGenerationHelper->getJS();
}
public function onScripts(tubepress_api_event_EventInterface $event)
{
$existingUrls = $event->getSubject();
$tubepressJsUrl = $this->_environment->getBaseUrl()->getClone();
$tubepressJsUrl->addPath('/web/js/tubepress.js');
array_unshift($existingUrls, $tubepressJsUrl);
$event->setSubject($existingUrls);
}
}
}
namespace
{
class tubepress_html_impl_listeners_HtmlListener
{
private $_environment;
private $_logger;
private $_eventDispatcher;
private $_requestParameters;
public function __construct(tubepress_api_log_LoggerInterface $logger,
tubepress_api_environment_EnvironmentInterface $environment,
tubepress_api_event_EventDispatcherInterface $eventDispatcher,
tubepress_api_http_RequestParametersInterface $requestParams)
{
$this->_logger = $logger;
$this->_environment = $environment;
$this->_eventDispatcher = $eventDispatcher;
$this->_requestParameters = $requestParams;
}
public function onGlobalJsConfig(tubepress_api_event_EventInterface $event)
{
$config = $event->getSubject();
if (!isset($config['urls'])) {
$config['urls'] = array();
}
$baseUrl = $this->_environment->getBaseUrl()->getClone();
$userContentUrl = $this->_environment->getUserContentUrl()->getClone();
$ajaxEndpointUrl = $this->_environment->getAjaxEndpointUrl()->getClone();
$baseUrl->removeSchemeAndAuthority();
$userContentUrl->removeSchemeAndAuthority();
$ajaxEndpointUrl->removeSchemeAndAuthority();
$config['urls']['base'] = "$baseUrl";
$config['urls']['usr'] = "$userContentUrl";
$config['urls']['ajax'] = "$ajaxEndpointUrl";
$event->setSubject($config);
}
public function onException(tubepress_api_event_EventInterface $event)
{
if (!$this->_logger->isEnabled()) {
return;
}
$exception = $event->getSubject();
$traceData = $exception->getTraceAsString();
$traceData = explode("\n", $traceData);
foreach ($traceData as $line) {
$line = htmlspecialchars($line);
$this->_logger->error("<code>$line</code><br />");
}
}
public function onPostScriptsTemplateRender(tubepress_api_event_EventInterface $event)
{
$jsEvent = $this->_eventDispatcher->newEventInstance(array());
$this->_eventDispatcher->dispatch(tubepress_api_event_Events::HTML_GLOBAL_JS_CONFIG, $jsEvent);
$args = $jsEvent->getSubject();
$asJson = json_encode($args);
$html = $event->getSubject();
$toPrepend =<<<EOT
<script type="text/javascript">var TubePressJsConfig = $asJson;</script>
EOT
;
$event->setSubject($toPrepend . $html);
}
public function onPostStylesTemplateRender(tubepress_api_event_EventInterface $event)
{
$html = $event->getSubject();
$page = $this->_requestParameters->getParamValueAsInt('tubepress_page', 1);
if ($page > 1) {
$html .="\n".'<meta name="robots" content="noindex, nofollow" />';
}
$event->setSubject($html);
}
}
}
namespace
{
class tubepress_http_impl_PrimaryAjaxHandler implements tubepress_api_http_AjaxInterface
{
private $_logger;
private $_isDebugEnabled;
private $_requestParameters;
private $_responseCode;
private $_eventDispatcher;
private $_templating;
public function __construct(tubepress_api_log_LoggerInterface $logger,
tubepress_api_http_RequestParametersInterface $requestParams,
tubepress_api_http_ResponseCodeInterface $responseCode,
tubepress_api_event_EventDispatcherInterface $eventDispatcher,
tubepress_api_template_TemplatingInterface $templating)
{
$this->_logger = $logger;
$this->_isDebugEnabled = $logger->isEnabled();
$this->_requestParameters = $requestParams;
$this->_responseCode = $responseCode;
$this->_eventDispatcher = $eventDispatcher;
$this->_templating = $templating;
}
public function handle()
{
if ($this->_isDebugEnabled) {
$this->_logger->debug('Handling incoming request');
}
if (!$this->_requestParameters->hasParam('tubepress_action')) {
$this->_errorOut(new RuntimeException('Missing "tubepress_action" parameter'), 400);
return;
}
$actionName = $this->_requestParameters->getParamValue('tubepress_action');
$ajaxEvent = $this->_eventDispatcher->newEventInstance();
try {
$this->_eventDispatcher->dispatch(tubepress_api_event_Events::HTTP_AJAX . ".$actionName", $ajaxEvent);
} catch (Exception $e) {
$this->_errorOut($e, 500);
return;
}
$resultingArgs = $ajaxEvent->getArguments();
if (!array_key_exists('handled', $resultingArgs) || !$resultingArgs['handled']) {
$this->_errorOut(new RuntimeException('Action not handled'), 400);
}
}
private function _errorOut(Exception $e, $code)
{
$this->_responseCode->setResponseCode($code);
$event = $this->_eventDispatcher->newEventInstance($e);
$this->_eventDispatcher->dispatch(tubepress_api_event_Events::HTML_EXCEPTION_CAUGHT, $event);
$args = array('exception'=> $e,
);
$response = $this->_templating->renderTemplate('exception/ajax', $args);
echo $response;
}
}
}
namespace
{
class tubepress_http_impl_RequestParameters implements tubepress_api_http_RequestParametersInterface
{
private $_cachedMergedGetAndPostArray;
private $_eventDispatcher;
public function __construct(tubepress_api_event_EventDispatcherInterface $eventDispatcher)
{
$this->_eventDispatcher = $eventDispatcher;
}
public function getParamValue($name)
{
if (!($this->hasParam($name))) {
return null;
}
$request = $this->_getGETandPOSTarray();
$rawValue = $request[$name];
$event = $this->_eventDispatcher->newEventInstance(
$rawValue,
array('optionName'=> $name)
);
$this->_eventDispatcher->dispatch(
tubepress_api_event_Events::NVP_FROM_EXTERNAL_INPUT,
$event
);
$event = $this->_eventDispatcher->newEventInstance($event->getSubject(), array('optionName'=> $name,
));
$this->_eventDispatcher->dispatch(
tubepress_api_event_Events::NVP_FROM_EXTERNAL_INPUT . ".$name",
$event
);
return $event->getSubject();
}
public function getParamValueAsInt($name, $default)
{
$raw = $this->getParamValue($name);
if (!is_numeric($raw) || ($raw < 1)) {
return $default;
}
return (int) $raw;
}
public function hasParam($name)
{
$request = $this->_getGETandPOSTarray();
return array_key_exists($name, $request);
}
public function getAllParams()
{
$toReturn = array();
$request = $this->_getGETandPOSTarray();
foreach ($request as $key => $value) {
$toReturn[$key] = $this->getParamValue($key);
}
return $toReturn;
}
private function _getGETandPOSTarray()
{
if (!isset($this->_cachedMergedGetAndPostArray)) {
$this->_cachedMergedGetAndPostArray = array_merge($_GET, $_POST);
}
return $this->_cachedMergedGetAndPostArray;
}
}
}
namespace
{
class tubepress_http_impl_ResponseCode implements tubepress_api_http_ResponseCodeInterface
{
public function setResponseCode($code)
{
if (function_exists('http_response_code')) {
return http_response_code($code);
} else {
return $this->__simulatedHttpResponseCode($code);
}
}
public function getCurrentResponseCode()
{
if (function_exists('http_response_code')) {
return http_response_code();
} else {
return $this->__simulatedHttpResponseCode();
}
}
public function __simulatedHttpResponseCode($code = null)
{
if ($code !== null) {
switch ($code) {
case 100: $text ='Continue'; break;
case 101: $text ='Switching Protocols'; break;
case 200: $text ='OK'; break;
case 201: $text ='Created'; break;
case 202: $text ='Accepted'; break;
case 203: $text ='Non-Authoritative Information'; break;
case 204: $text ='No Content'; break;
case 205: $text ='Reset Content'; break;
case 206: $text ='Partial Content'; break;
case 300: $text ='Multiple Choices'; break;
case 301: $text ='Moved Permanently'; break;
case 302: $text ='Moved Temporarily'; break;
case 303: $text ='See Other'; break;
case 304: $text ='Not Modified'; break;
case 305: $text ='Use Proxy'; break;
case 400: $text ='Bad Request'; break;
case 401: $text ='Unauthorized'; break;
case 402: $text ='Payment Required'; break;
case 403: $text ='Forbidden'; break;
case 404: $text ='Not Found'; break;
case 405: $text ='Method Not Allowed'; break;
case 406: $text ='Not Acceptable'; break;
case 407: $text ='Proxy Authentication Required'; break;
case 408: $text ='Request Time-out'; break;
case 409: $text ='Conflict'; break;
case 410: $text ='Gone'; break;
case 411: $text ='Length Required'; break;
case 412: $text ='Precondition Failed'; break;
case 413: $text ='Request Entity Too Large'; break;
case 414: $text ='Request-URI Too Large'; break;
case 415: $text ='Unsupported Media Type'; break;
case 500: $text ='Internal Server Error'; break;
case 501: $text ='Not Implemented'; break;
case 502: $text ='Bad Gateway'; break;
case 503: $text ='Service Unavailable'; break;
case 504: $text ='Gateway Time-out'; break;
case 505: $text ='HTTP Version not supported'; break;
default:
exit('Unknown http status code "'. htmlentities($code) .'"');
break;
}
$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] :'HTTP/1.0');
if (!headers_sent()) {
header($protocol .' '. $code .' '. $text);
}
$GLOBALS['http_response_code'] = $code;
} else {
$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
}
return $code;
}
}
}
namespace
{
class tubepress_internal_boot_helper_uncached_contrib_SerializedRegistry implements tubepress_api_contrib_RegistryInterface
{
private $_nameToInstanceMap;
private $_logger;
public function __construct(array $bootArtifacts, $key,
tubepress_api_log_LoggerInterface $logger,
tubepress_internal_boot_helper_uncached_Serializer $serializer)
{
$this->_logger = $logger;
$this->_nameToInstanceMap = array();
if (!isset($bootArtifacts[$key])) {
throw new InvalidArgumentException("$key not found in boot artifacts");
}
$contributables = $serializer->unserialize($bootArtifacts[$key]);
if (!is_array($contributables)) {
throw new InvalidArgumentException('Expected to deserialize an array');
}
foreach ($contributables as $contributable) {
if (!($contributable instanceof tubepress_api_contrib_ContributableInterface)) {
throw new InvalidArgumentException('Unserialized data contained a non contributable');
}
$name = $contributable->getName();
$this->_nameToInstanceMap[$name] = $contributable;
}
}
public function getAll()
{
return array_values($this->_nameToInstanceMap);
}
public function getInstanceByName($name)
{
if (!isset($this->_nameToInstanceMap[$name])) {
throw new InvalidArgumentException();
}
return $this->_nameToInstanceMap[$name];
}
}
}
namespace
{
class tubepress_internal_boot_helper_uncached_Serializer
{
private $_bootSettings;
public function __construct(tubepress_api_boot_BootSettingsInterface $bootSettings)
{
$this->_bootSettings = $bootSettings;
}
public function serialize($incomingData)
{
$serialized = @serialize($incomingData);
if ($serialized === false) {
throw new InvalidArgumentException('Failed to serialize data');
}
switch ($this->_bootSettings->getSerializationEncoding()) {
case'gzip-then-base64':
if (extension_loaded('zlib')) {
$toCompress = $serialized;
$compressed = gzcompress($toCompress);
if ($compressed !== false) {
$serialized = $compressed;
}
}
case'base64':
$encoded = @base64_encode($serialized);
if ($encoded === false) {
throw new InvalidArgumentException('Failed to base64_encode() serialized data');
}
return $encoded;
case'urlencode':
return urlencode($serialized);
default:
return $serialized;
}
}
public function unserialize($serializedString)
{
$decoded = $serializedString;
$encoding = $this->_bootSettings->getSerializationEncoding();
switch ($encoding) {
case'gzip-then-base64':
case'base64':
$decoded = @base64_decode($serializedString);
if ($decoded === false) {
throw new InvalidArgumentException('Failed to base64_decode() serialized data');
}
if ($encoding ==='gzip-then-base64') {
$decoded = gzuncompress($decoded);
if ($decoded === false) {
throw new InvalidArgumentException('Failed to gzuncompress() serialized data');
}
}
break;
case'urlencode':
$decoded = urldecode($serializedString);
break;
default:
break;
}
$unserialized = @unserialize($decoded);
if ($unserialized === false) {
throw new InvalidArgumentException('Failed to unserialize incoming data');
}
return $unserialized;
}
}
}
namespace
{
class tubepress_internal_collection_Map implements tubepress_api_collection_MapInterface
{
private $_props = array();
public function clear()
{
$this->_props = array();
}
public function containsKey($key)
{
return array_key_exists($key, $this->_props);
}
public function containsValue($value)
{
return in_array($value, array_values($this->_props));
}
public function count()
{
return count($this->_props);
}
public function get($key)
{
if (!$this->containsKey($key)) {
throw new InvalidArgumentException('No such key: '. $key);
}
return $this->_props[$key];
}
public function getAsBoolean($key)
{
return (bool) $this->get($key);
}
public function isEmpty()
{
return count($this->_props) === 0;
}
public function keySet()
{
return array_keys($this->_props);
}
public function put($name, $value)
{
$this->_props[$name] = $value;
}
public function remove($key)
{
if (!$this->containsKey($key)) {
throw new InvalidArgumentException('No such key: '. $key);
}
unset($this->_props[$key]);
}
public function values()
{
return array_values($this->_props);
}
}
}
namespace
{
abstract class tubepress_internal_contrib_AbstractContributable implements tubepress_api_contrib_ContributableInterface
{
private static $_PROPERTY_NAME ='name';
private static $_PROPERTY_VERSION ='version';
private static $_PROPERTY_TITLE ='title';
private static $_PROPERTY_AUTHORS ='authors';
private static $_PROPERTY_LICENSE ='license';
private static $_PROPERTY_DESCRIPTION ='description';
private static $_PROPERTY_KEYWORDS ='keywords';
private static $_PROPERTY_SCREENSHOTS ='screenshots';
private static $_PROPERTY_URL_HOMEPAGE ='urlHomepage';
private static $_PROPERTY_URL_DOCUMENTATION ='urlDocs';
private static $_PROPERTY_URL_DEMO ='urlDemo';
private static $_PROPERTY_URL_DOWNLOAD ='urlDownload';
private static $_PROPERTY_URL_BUGS ='urlBugs';
private static $_PROPERTY_URL_FORUM ='urlForum';
private static $_PROPERTY_URL_SOURCE ='urlSource';
private $_properties;
public function __construct($name, $version, $title, array $authors, array $license)
{
$this->_properties = new tubepress_internal_collection_Map();
$this->_setAuthors($authors);
$license = $this->_buildLicense($license);
$this->_properties->put(self::$_PROPERTY_NAME, $name);
$this->_properties->put(self::$_PROPERTY_VERSION, $version);
$this->_properties->put(self::$_PROPERTY_TITLE, $title);
$this->_properties->put(self::$_PROPERTY_AUTHORS, $authors);
$this->_properties->put(self::$_PROPERTY_LICENSE, $license);
$this->_properties->put(self::$_PROPERTY_KEYWORDS, array());
$this->_properties->put(self::$_PROPERTY_SCREENSHOTS, array());
}
public function getName()
{
return $this->_properties->get(self::$_PROPERTY_NAME);
}
public function getVersion()
{
return $this->_properties->get(self::$_PROPERTY_VERSION);
}
public function getTitle()
{
return $this->_properties->get(self::$_PROPERTY_TITLE);
}
public function getAuthors()
{
return $this->_properties->get(self::$_PROPERTY_AUTHORS);
}
public function getLicense()
{
return $this->_properties->get(self::$_PROPERTY_LICENSE);
}
public function getDescription()
{
return $this->getOptionalProperty(self::$_PROPERTY_DESCRIPTION, null);
}
public function getKeywords()
{
return $this->getOptionalProperty(self::$_PROPERTY_KEYWORDS, array());
}
public function getHomepageUrl()
{
return $this->getOptionalProperty(self::$_PROPERTY_URL_HOMEPAGE, null);
}
public function getDocumentationUrl()
{
return $this->getOptionalProperty(self::$_PROPERTY_URL_DOCUMENTATION, null);
}
public function getDemoUrl()
{
return $this->getOptionalProperty(self::$_PROPERTY_URL_DEMO, null);
}
public function getDownloadUrl()
{
return $this->getOptionalProperty(self::$_PROPERTY_URL_DOWNLOAD, null);
}
public function getBugTrackerUrl()
{
return $this->getOptionalProperty(self::$_PROPERTY_URL_BUGS, null);
}
public function getSourceCodeUrl()
{
return $this->getOptionalProperty(self::$_PROPERTY_URL_SOURCE, null);
}
public function getForumUrl()
{
return $this->getOptionalProperty(self::$_PROPERTY_URL_FORUM, null);
}
public function getScreenshots()
{
return $this->getOptionalProperty(self::$_PROPERTY_SCREENSHOTS, null);
}
public function getProperties()
{
return $this->_properties;
}
public function setDescription($description)
{
$this->_properties->put(self::$_PROPERTY_DESCRIPTION, $description);
}
public function setKeywords(array $keywords)
{
$this->_properties->put(self::$_PROPERTY_KEYWORDS, $keywords);
}
public function setScreenshots(array $screenshots)
{
$this->_properties->put(self::$_PROPERTY_SCREENSHOTS, $screenshots);
}
public function setBugTrackerUrl(tubepress_api_url_UrlInterface $url)
{
$this->_properties->put(self::$_PROPERTY_URL_BUGS, $url);
}
public function setDemoUrl(tubepress_api_url_UrlInterface $url)
{
$this->_properties->put(self::$_PROPERTY_URL_DEMO, $url);
}
public function setDownloadUrl(tubepress_api_url_UrlInterface $url)
{
$this->_properties->put(self::$_PROPERTY_URL_DOWNLOAD, $url);
}
public function setHomepageUrl(tubepress_api_url_UrlInterface $url)
{
$this->_properties->put(self::$_PROPERTY_URL_HOMEPAGE, $url);
}
public function setDocumentationUrl(tubepress_api_url_UrlInterface $url)
{
$this->_properties->put(self::$_PROPERTY_URL_DOCUMENTATION, $url);
}
public function setSourceUrl(tubepress_api_url_UrlInterface $url)
{
$this->_properties->put(self::$_PROPERTY_URL_SOURCE, $url);
}
public function setForumUrl(tubepress_api_url_UrlInterface $url)
{
$this->_properties->put(self::$_PROPERTY_URL_FORUM, $url);
}
protected function getOptionalProperty($key, $default)
{
if (!$this->_properties->containsKey($key)) {
return $default;
}
return $this->_properties->get($key);
}
private function _setAuthors(array &$incoming)
{
for ($x = 0; $x < count($incoming); $x++) {
$map = new tubepress_internal_collection_Map();
foreach ($incoming[$x] as $key => $value) {
$map->put($key, $value);
}
$incoming[$x] = $map;
}
}
private function _buildLicense(array $incoming)
{
$map = new tubepress_internal_collection_Map();
foreach ($incoming as $key => $value) {
$map->put($key, $value);
}
return $map;
}
}
}
namespace
{
abstract class tubepress_internal_theme_AbstractTheme extends tubepress_internal_contrib_AbstractContributable implements tubepress_api_theme_ThemeInterface
{
private static $_PROPERTY_SCRIPTS ='scripts';
private static $_PROPERTY_STYLES ='styles';
private static $_PROPERTY_PARENT ='parent';
public function getUrlsJS(tubepress_api_url_UrlInterface $baseUrl,
tubepress_api_url_UrlInterface $userContentUrl)
{
return $this->getOptionalProperty(self::$_PROPERTY_SCRIPTS, array());
}
public function getUrlsCSS(tubepress_api_url_UrlInterface $baseUrl,
tubepress_api_url_UrlInterface $userContentUrl)
{
return $this->getOptionalProperty(self::$_PROPERTY_STYLES, array());
}
public function getParentThemeName()
{
return $this->getOptionalProperty(self::$_PROPERTY_PARENT, null);
}
public function setScripts(array $scripts)
{
$this->getProperties()->put(self::$_PROPERTY_SCRIPTS, $scripts);
}
public function setStyles(array $styles)
{
$this->getProperties()->put(self::$_PROPERTY_STYLES, $styles);
}
public function setParentThemeName($name)
{
$this->getProperties()->put(self::$_PROPERTY_PARENT, $name);
}
}
}
namespace
{
class tubepress_internal_theme_FilesystemTheme extends tubepress_internal_theme_AbstractTheme
{
private static $_PROPERTY_TEMPLATE_NAMES_TO_PATHS ='templateNamesToAbsPaths';
private static $_PROPERTY_INLINE_CSS ='inlineCSS';
private static $_PROPERTY_MANIFEST_PATH ='manifestPath';
private static $_PROPERTY_IS_SYSTEM ='isSystem';
private static $_PROPERTY_IS_ADMIN ='isAdmin';
public function getInlineCSS()
{
return $this->getOptionalProperty(self::$_PROPERTY_INLINE_CSS, null);
}
public function getTemplateSource($name)
{
$map = $this->getProperties()->get(self::$_PROPERTY_TEMPLATE_NAMES_TO_PATHS);
return file_get_contents($map[$name]);
}
public function isTemplateSourceFresh($name, $time)
{
$path = $this->getTemplateCacheKey($name);
return filemtime($path) < $time;
}
public function getTemplateCacheKey($name)
{
$map = $this->getProperties()->get(self::$_PROPERTY_TEMPLATE_NAMES_TO_PATHS);
return $map[$name];
}
public function hasTemplateSource($name)
{
$map = $this->getProperties()->get(self::$_PROPERTY_TEMPLATE_NAMES_TO_PATHS);
return isset($map[$name]);
}
public function setInlineCss($css)
{
$this->getProperties()->put(self::$_PROPERTY_INLINE_CSS, $css);
}
public function setTemplateNamesToAbsPathsMap(array $map)
{
$this->getProperties()->put(self::$_PROPERTY_TEMPLATE_NAMES_TO_PATHS, $map);
}
public function setManifestPath($path)
{
$this->getProperties()->put(self::$_PROPERTY_MANIFEST_PATH, $path);
$themeAbsPath = dirname($path);
$publicPathElements = array(TUBEPRESS_ROOT,'web','themes');
$publicNeedle = implode(DIRECTORY_SEPARATOR, $publicPathElements);
$adminPathElements = array(TUBEPRESS_ROOT,'web','admin-themes');
$adminNeedle = implode(DIRECTORY_SEPARATOR, $adminPathElements);
$isSystem = strpos($themeAbsPath, $publicNeedle) !== false
|| strpos($themeAbsPath, $adminNeedle) !== false;
$this->getProperties()->put(self::$_PROPERTY_IS_SYSTEM, $isSystem);
$isAdmin = strpos($themeAbsPath, DIRECTORY_SEPARATOR .'admin-themes'. DIRECTORY_SEPARATOR) !== false;
$this->getProperties()->put(self::$_PROPERTY_IS_ADMIN, $isAdmin);
}
public function getUrlsJS(tubepress_api_url_UrlInterface $baseUrl,
tubepress_api_url_UrlInterface $userContentUrl)
{
return $this->_getStylesOrScripts($baseUrl, $userContentUrl,'getUrlsJS');
}
public function getUrlsCSS(tubepress_api_url_UrlInterface $baseUrl,
tubepress_api_url_UrlInterface $userContentUrl)
{
return $this->_getStylesOrScripts($baseUrl, $userContentUrl,'getUrlsCSS');
}
public function getThemePath()
{
return dirname($this->getProperties()->get(self::$_PROPERTY_MANIFEST_PATH));
}
public function getTemplatePath($name)
{
$map = $this->getProperties()->get(self::$_PROPERTY_TEMPLATE_NAMES_TO_PATHS);
return $map[$name];
}
private function _getStylesOrScripts(tubepress_api_url_UrlInterface $baseUrl,
tubepress_api_url_UrlInterface $userContentUrl,
$getter)
{
$toReturn = parent::$getter($baseUrl, $userContentUrl);
for ($x = 0; $x < count($toReturn); $x++) {
$url = $toReturn[$x];
if ($url->isAbsolute()) {
continue;
}
if (strpos("$url",'/') === 0) {
continue;
}
$toReturn[$x] = $this->_convertRelativeUrlToAbsolute($baseUrl, $userContentUrl, $url);
}
return $toReturn;
}
private function _convertRelativeUrlToAbsolute(tubepress_api_url_UrlInterface $baseUrl,
tubepress_api_url_UrlInterface $userContentUrl,
tubepress_api_url_UrlInterface $candidate)
{
$toReturn = null;
$properties = $this->getProperties();
$manifestPath = $properties->get(self::$_PROPERTY_MANIFEST_PATH);
$themeBase = basename(dirname($manifestPath));
if ($properties->getAsBoolean(self::$_PROPERTY_IS_SYSTEM)) {
$toReturn = $baseUrl->getClone();
if ($properties->getAsBoolean(self::$_PROPERTY_IS_ADMIN)) {
$toReturn->addPath("/web/admin-themes/$themeBase/$candidate");
} else {
$toReturn->addPath("/web/themes/$themeBase/$candidate");
}
} else {
$toReturn = $userContentUrl->getClone();
if ($properties->getAsBoolean(self::$_PROPERTY_IS_ADMIN)) {
$toReturn->addPath("/admin-themes/$themeBase/$candidate");
} else {
$toReturn->addPath("/themes/$themeBase/$candidate");
}
}
return $toReturn;
}
}
}
namespace
{
class tubepress_logger_impl_HtmlLogger implements tubepress_api_log_LoggerInterface
{
private $_enabled;
private $_bootMessageBuffer;
private $_shouldBuffer;
private $_timezone;
public function __construct(tubepress_api_options_ContextInterface $context,
tubepress_api_http_RequestParametersInterface $requestParams)
{
$loggingRequested = $requestParams->hasParam('tubepress_debug') && $requestParams->getParamValue('tubepress_debug') === true;
$loggingEnabled = $context->get(tubepress_api_options_Names::DEBUG_ON);
$this->_enabled = $loggingRequested && $loggingEnabled;
$this->_bootMessageBuffer = array();
$this->_shouldBuffer = true;
$this->_timezone = new DateTimeZone(@date_default_timezone_get() ? @date_default_timezone_get() :'UTC');
}
public function onBootComplete()
{
if (!$this->_enabled) {
unset($this->_bootMessageBuffer);
return;
}
$this->_shouldBuffer = false;
foreach ($this->_bootMessageBuffer as $message) {
echo $message;
}
}
public function isEnabled()
{
return $this->_enabled;
}
public function debug($message, array $context = array())
{
$this->_write($message, $context, false);
}
public function error($message, array $context = array())
{
$this->_write($message, $context, true);
}
private function _write($message, array $context, $error)
{
if (!$this->_enabled) {
return;
}
$finalMessage = $this->_getFormattedMessage($message, $context, $error);
if ($this->_shouldBuffer) {
$this->_bootMessageBuffer[] = $finalMessage;
} else {
echo $finalMessage;
}
}
private function _getFormattedMessage($message, array $context, $error)
{
$dateTime = $this->_createDateTimeFromFormat();
$formattedTime = $dateTime->format('H:i:s.u');
$level = $error ?'ERROR':'INFO';
$color = $error ?'red':'inherit';
if (!empty($context)) {
$message .=' '. json_encode($context);
}
return "<span style=\"color: $color\">[$formattedTime - $level] $message</span><br />\n";
}
public function ___write($message, array $context, $error)
{
$this->_write($message, $context, $error);
}
private function _createDateTimeFromFormat()
{
$toReturn = DateTime::createFromFormat('U.u',
sprintf('%.6F', microtime(true)),
$this->_timezone
);
$toReturn->setTimezone($this->_timezone);
return $toReturn;
}
}
}
namespace
{
class tubepress_options_impl_Context implements tubepress_api_options_ContextInterface
{
private $_ephemeralOptions = array();
private $_persistence;
private $_optionReference;
private $_eventDispatcher;
public function __construct(tubepress_api_options_PersistenceInterface $persistence,
tubepress_api_event_EventDispatcherInterface $eventDispatcher,
tubepress_api_options_ReferenceInterface $reference)
{
$this->_eventDispatcher = $eventDispatcher;
$this->_persistence = $persistence;
$this->_optionReference = $reference;
}
public function get($optionName)
{
if (array_key_exists($optionName, $this->_ephemeralOptions)) {
return $this->_ephemeralOptions[$optionName];
}
try {
return $this->_persistence->fetch($optionName);
} catch (InvalidArgumentException $e) {
if ($this->_optionReference->optionExists($optionName) &&
!$this->_optionReference->isMeantToBePersisted($optionName)) {
return null;
}
throw $e;
}
}
public function getEphemeralOptions()
{
return $this->_ephemeralOptions;
}
public function setEphemeralOption($optionName, $optionValue)
{
$errors = $this->getErrors($optionName, $optionValue);
if (count($errors) === 0) {
$this->_ephemeralOptions[$optionName] = $optionValue;
return null;
}
return $errors[0];
}
public function setEphemeralOptions(array $customOpts)
{
$toReturn = array();
$this->_ephemeralOptions = array();
foreach ($customOpts as $name => $value) {
$error = $this->setEphemeralOption($name, $value);
if ($error !== null) {
$toReturn[] = $error;
}
}
return $toReturn;
}
protected function getErrors($optionName, &$optionValue)
{
$externallyCleanedValue = $this->_dispatchForExternalInput($optionName, $optionValue);
$event = $this->_dispatchForOptionSet(
$optionName,
$externallyCleanedValue,
array(),
tubepress_api_event_Events::OPTION_SET .'.'. $optionName
);
$event = $this->_dispatchForOptionSet(
$optionName,
$event->getArgument('optionValue'),
$event->getSubject(),
tubepress_api_event_Events::OPTION_SET
);
$optionValue = $event->getArgument('optionValue');
return $event->getSubject();
}
private function _dispatchForExternalInput($optionName, $optionValue)
{
$event = $this->_eventDispatcher->newEventInstance($optionValue, array('optionName'=> $optionName,
));
$this->_eventDispatcher->dispatch(tubepress_api_event_Events::NVP_FROM_EXTERNAL_INPUT, $event);
return $event->getSubject();
}
private function _dispatchForOptionSet($optionName, $optionValue, array $errors, $eventName)
{
$event = $this->_eventDispatcher->newEventInstance($errors, array('optionName'=> $optionName,'optionValue'=> $optionValue,
));
$this->_eventDispatcher->dispatch($eventName, $event);
return $event;
}
}
}
namespace
{
class tubepress_options_impl_DispatchingReference implements tubepress_api_options_ReferenceInterface
{
private $_eventDispatcher;
private $_delegateReferences = array();
private $_nameToReferenceMap;
public function __construct(tubepress_api_event_EventDispatcherInterface $eventDispatcher)
{
$this->_eventDispatcher = $eventDispatcher;
}
public function getAllOptionNames()
{
$this->_initCache();
return array_keys($this->_nameToReferenceMap);
}
public function optionExists($optionName)
{
$this->_initCache();
return array_key_exists($optionName, $this->_nameToReferenceMap);
}
public function getProperty($optionName, $propertyName)
{
$this->_assertExists($optionName);
return $this->_nameToReferenceMap[$optionName]->getProperty($optionName, $propertyName);
}
public function hasProperty($optionName, $propertyName)
{
$this->_assertExists($optionName);
return $this->_nameToReferenceMap[$optionName]->hasProperty($optionName, $propertyName);
}
public function getPropertyAsBoolean($optionName, $propertyName)
{
return (bool) $this->getProperty($optionName, $propertyName);
}
public function getDefaultValue($optionName)
{
$this->_assertExists($optionName);
$raw = $this->_nameToReferenceMap[$optionName]->getDefaultValue($optionName);
return $this->_dispatchEventAndReturnSubject($optionName, $raw,
tubepress_api_event_Events::OPTION_DEFAULT_VALUE);
}
public function getUntranslatedDescription($optionName)
{
$this->_assertExists($optionName);
$raw = $this->_nameToReferenceMap[$optionName]->getUntranslatedDescription($optionName);
return $this->_dispatchEventAndReturnSubject($optionName, $raw,
tubepress_api_event_Events::OPTION_DESCRIPTION);
}
public function getUntranslatedLabel($optionName)
{
$this->_assertExists($optionName);
$raw = $this->_nameToReferenceMap[$optionName]->getUntranslatedLabel($optionName);
return $this->_dispatchEventAndReturnSubject($optionName, $raw,
tubepress_api_event_Events::OPTION_LABEL);
}
public function isAbleToBeSetViaShortcode($optionName)
{
$this->_assertExists($optionName);
return $this->_nameToReferenceMap[$optionName]->isAbleToBeSetViaShortcode($optionName);
}
public function isBoolean($optionName)
{
$this->_assertExists($optionName);
return $this->_nameToReferenceMap[$optionName]->isBoolean($optionName);
}
public function isMeantToBePersisted($optionName)
{
$this->_assertExists($optionName);
return $this->_nameToReferenceMap[$optionName]->isMeantToBePersisted($optionName);
}
public function isProOnly($optionName)
{
$this->_assertExists($optionName);
return $this->_nameToReferenceMap[$optionName]->isProOnly($optionName);
}
public function setReferences(array $references)
{
$this->_delegateReferences = $references;
}
private function _initCache()
{
if (isset($this->_nameToReferenceMap)) {
return;
}
$this->_nameToReferenceMap = array();
foreach ($this->_delegateReferences as $delegateReference) {
$allOptions = $delegateReference->getAllOptionNames();
foreach ($allOptions as $optionName) {
$this->_nameToReferenceMap[$optionName] = $delegateReference;
}
}
}
private function _assertExists($optionName)
{
if (!$this->optionExists($optionName)) {
throw new InvalidArgumentException("$optionName is not a known option");
}
}
private function _dispatchEventAndReturnSubject($optionName, $value, $eventName)
{
$event = $this->_eventDispatcher->newEventInstance($value, array('optionName'=> $optionName,
));
$this->_eventDispatcher->dispatch("$eventName.$optionName", $event);
return $event->getSubject();
}
}
}
namespace
{
class tubepress_options_impl_Persistence implements tubepress_api_options_PersistenceInterface
{
private $_saveQueue;
private $_cachedOptions;
private $_flagCheckedForMissingOptions = false;
private $_optionsReference;
private $_eventDispatcher;
private $_backend;
public function __construct(tubepress_api_options_ReferenceInterface $reference,
tubepress_api_event_EventDispatcherInterface $eventDispatcher,
tubepress_spi_options_PersistenceBackendInterface $backend)
{
$this->_eventDispatcher = $eventDispatcher;
$this->_optionsReference = $reference;
$this->_backend = $backend;
}
public function getCloneWithCustomBackend(tubepress_spi_options_PersistenceBackendInterface $persistenceBackend)
{
return new self($this->_optionsReference, $this->_eventDispatcher, $persistenceBackend);
}
public function fetch($optionName)
{
$allOptions = $this->fetchAll();
if (array_key_exists($optionName, $allOptions)) {
return $allOptions[$optionName];
}
throw new InvalidArgumentException('No such option: '. $optionName);
}
public function fetchAll()
{
if (!isset($this->_cachedOptions)) {
$this->_cachedOptions = $this->_backend->fetchAllCurrentlyKnownOptionNamesToValues();
$this->_addAnyMissingOptions($this->_cachedOptions);
}
return $this->_cachedOptions;
}
public function queueForSave($optionName, $optionValue)
{
if (!isset($this->_saveQueue)) {
$this->_saveQueue = array();
}
if (!$this->_optionsReference->isMeantToBePersisted($optionName)) {
return null;
}
$errors = $this->_getErrors($optionName, $optionValue);
if (count($errors) > 0) {
return $errors[0];
}
if ($this->_noChangeBetweenIncomingAndCurrent($optionName, $optionValue)) {
return null;
}
$this->_saveQueue[$optionName] = $optionValue;
return null;
}
public function flushSaveQueue()
{
if (!isset($this->_saveQueue) || count($this->_saveQueue) === 0) {
return null;
}
$result = $this->_backend->saveAll($this->_saveQueue);
unset($this->_saveQueue);
$this->_forceReloadOfOptionsCache();
return $result;
}
private function _forceReloadOfOptionsCache()
{
unset($this->_cachedOptions);
$this->fetchAll();
}
private function _addAnyMissingOptions(array $optionsInThisStorageManager)
{
if ($this->_flagCheckedForMissingOptions) {
return;
}
$optionNamesFromProvider = $this->_optionsReference->getAllOptionNames();
$toPersist = array();
$missingOptions = array_diff($optionNamesFromProvider, array_keys($optionsInThisStorageManager));
foreach ($missingOptions as $optionName) {
if ($this->_optionsReference->isMeantToBePersisted($optionName)) {
$toPersist[$optionName] = $this->_optionsReference->getDefaultValue($optionName);
}
}
if (!empty($toPersist)) {
$this->_backend->createEach($toPersist);
$this->_forceReloadOfOptionsCache();
}
$this->_flagCheckedForMissingOptions = true;
}
private function _noChangeBetweenIncomingAndCurrent($optionName, $filteredValue)
{
$boolean = $this->_optionsReference->isBoolean($optionName);
$currentValue = $this->fetch($optionName);
if ($boolean) {
return ((boolean) $filteredValue) === ((boolean) $currentValue);
}
return $currentValue == $filteredValue;
}
private function _getErrors($optionName, &$optionValue)
{
$externallyCleanedValue = $this->_dispatchForExternalInput($optionName, $optionValue);
$event = $this->_dispatch(
$optionName,
$externallyCleanedValue,
array(),
tubepress_api_event_Events::OPTION_SET .'.'. $optionName
);
$event = $this->_dispatch($optionName,
$event->getArgument('optionValue'),
$event->getSubject(), tubepress_api_event_Events::OPTION_SET
);
$optionValue = $event->getArgument('optionValue');
return $event->getSubject();
}
private function _dispatch($optionName, $optionValue, array $errors, $eventName)
{
$event = $this->_eventDispatcher->newEventInstance($errors, array('optionName'=> $optionName,'optionValue'=> $optionValue,
));
$this->_eventDispatcher->dispatch($eventName, $event);
return $event;
}
private function _dispatchForExternalInput($optionName, $optionValue)
{
$event = $this->_eventDispatcher->newEventInstance($optionValue, array('optionName'=> $optionName,
));
$this->_eventDispatcher->dispatch(tubepress_api_event_Events::NVP_FROM_EXTERNAL_INPUT, $event);
return $event->getSubject();
}
}
}
namespace
{
class tubepress_shortcode_impl_Parser implements tubepress_api_shortcode_ParserInterface
{
private $_logger;
private $_shouldLog;
private $_context;
private $_eventDispatcher;
private $_stringUtils;
private $_lastShortcodeUsed = null;
public function __construct(tubepress_api_log_LoggerInterface $logger,
tubepress_api_options_ContextInterface $context,
tubepress_api_event_EventDispatcherInterface $eventDispatcher,
tubepress_api_util_StringUtilsInterface $stringUtils)
{
$this->_logger = $logger;
$this->_shouldLog = $logger->isEnabled();
$this->_context = $context;
$this->_eventDispatcher = $eventDispatcher;
$this->_stringUtils = $stringUtils;
}
public function parse($content)
{
try {
$this->_wrappedParse($content);
} catch (Exception $e) {
if ($this->_shouldLog) {
$this->_logger->error('Caught exception when parsing shortcode: '. $e->getMessage());
}
}
}
private function _wrappedParse($content)
{
$keyword = $this->_context->get(tubepress_api_options_Names::SHORTCODE_KEYWORD);
if (!$this->somethingToParse($content, $keyword)) {
return;
}
preg_match("/\[$keyword\b(.*)\]/", $content, $matches);
if ($this->_shouldLog) {
$this->_logger->debug(sprintf('Found a shortcode: %s', $this->_stringUtils->redactSecrets($matches[0])));
}
$this->_lastShortcodeUsed = $matches[0];
if (isset($matches[1]) && $matches[1] !='') {
$text = preg_replace('/[\x{00a0}\x{200b}]+/u',' ', $matches[1]);
$text = self::_convertQuotes($text);
$pattern ='/(\w+)\s*=\s*"([^"]*)"(?:\s*,)?(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s*,)?(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s*,)?(?:\s|$)/';
if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
if ($this->_shouldLog) {
$this->_logger->debug(sprintf('Candidate options detected in shortcode: %s', $this->_stringUtils->redactSecrets($matches[0])));
}
$toReturn = $this->_buildNameValuePairArray($match);
$this->_context->setEphemeralOptions($toReturn);
}
} else {
if ($this->_shouldLog) {
$this->_logger->debug(sprintf('No custom options detected in shortcode: %s', $this->_stringUtils->redactSecrets($matches[0])));
}
}
}
public function somethingToParse($content, $trigger ='tubepress')
{
return preg_match("/\[$trigger\b(.*)\]/", $content) === 1;
}
public function getLastShortcodeUsed()
{
return $this->_lastShortcodeUsed;
}
private function _buildNameValuePairArray($match)
{
$toReturn = array();
$value = null;
foreach ($match as $m) {
if (!empty($m[1])) {
$name = $m[1];
$value = $m[2];
} elseif (!empty($m[3])) {
$name = $m[3];
$value = $m[4];
} elseif (!empty($m[5])) {
$name = $m[5];
$value = $m[6];
}
if (!isset($name) || !isset($value)) {
continue;
}
if ($this->_shouldLog) {
$this->_logger->debug(sprintf('Name-value pair detected: %s = "%s" (unfiltered)', $name, $this->_stringUtils->redactSecrets($value)));
}
$event = $this->_eventDispatcher->newEventInstance(
$value,
array('optionName'=> $name)
);
$this->_eventDispatcher->dispatch(
tubepress_api_event_Events::NVP_FROM_EXTERNAL_INPUT,
$event
);
$filtered = $event->getSubject();
$event = $this->_eventDispatcher->newEventInstance($filtered, array('optionName'=> $name,
));
$this->_eventDispatcher->dispatch(
tubepress_api_event_Events::NVP_FROM_EXTERNAL_INPUT . ".$name",
$event
);
$filtered = $event->getSubject();
if ($this->_shouldLog) {
$this->_logger->debug(sprintf('Name-value pair detected: %s = "%s" (filtered)', $name, $this->_stringUtils->redactSecrets($filtered)));
}
$toReturn[$name] = $filtered;
}
return $toReturn;
}
private static function _convertQuotes($text)
{
$converted = str_replace(array('&#8216;','&#8217;','&#8242;'),'\'', $text);
return str_replace(array('&#34;','&#8220;','&#8221;','&#8243;'),'"', $converted);
}
}
}
namespace
{
class tubepress_theme_impl_CurrentThemeService
{
private $_themeRegistry;
private $_context;
private $_themeMap;
private $_defaultThemeName;
private $_optionName;
public function __construct(tubepress_api_options_ContextInterface $context,
tubepress_api_contrib_RegistryInterface $themeRegistry,
$defaultThemeName,
$optionName)
{
$this->_themeRegistry = $themeRegistry;
$this->_context = $context;
$this->_defaultThemeName = $defaultThemeName;
$this->_optionName = $optionName;
}
public function getCurrentTheme()
{
$currentTheme = $this->_context->get($this->_optionName);
$this->_initCache();
if ($currentTheme =='') {
$currentTheme = $this->_defaultThemeName;
}
if (array_key_exists($currentTheme, $this->_themeMap)) {
return $this->_themeMap[$currentTheme];
}
if (array_key_exists("tubepress/legacy-$currentTheme", $this->_themeMap)) {
return $this->_themeMap["tubepress/legacy-$currentTheme"];
}
if (array_key_exists("unknown/legacy-$currentTheme", $this->_themeMap)) {
return $this->_themeMap["unknown/legacy-$currentTheme"];
}
return $this->_themeMap[$this->_defaultThemeName];
}
private function _initCache()
{
if (isset($this->_themeMap)) {
return;
}
$this->_themeMap = array();
foreach ($this->_themeRegistry->getAll() as $theme) {
$this->_themeMap[$theme->getName()] = $theme;
}
}
}
}
namespace
{
class tubepress_url_impl_puzzle_PuzzleBasedQuery implements tubepress_api_url_QueryInterface
{
private $_delegate;
private $_isFrozen = false;
public function __construct(puzzle_Query $delegate)
{
$this->_delegate = $delegate;
}
public function add($key, $value)
{
$this->_assertNotFrozen();
$this->_delegate->add($key, $value);
return $this;
}
public function clear()
{
$this->_assertNotFrozen();
$this->_delegate->clear();
return $this;
}
public function filter($closure)
{
return $this->_delegate->filter($closure);
}
public function get($key)
{
return $this->_delegate->get($key);
}
public function getKeys()
{
return $this->_delegate->getKeys();
}
public function hasKey($key)
{
return $this->_delegate->hasKey($key);
}
public function hasValue($value)
{
return $this->_delegate->hasValue($value);
}
public function map($closure, array $context = array())
{
return $this->_delegate->map($closure, $context);
}
public function merge($data)
{
$this->_assertNotFrozen();
if ($data instanceof tubepress_api_url_QueryInterface) {
$data = $data->toArray();
}
$this->_delegate->merge($data);
return $this;
}
public function overwriteWith($data)
{
$this->_assertNotFrozen();
$this->_delegate->overwriteWith($data);
return $this;
}
public function remove($key)
{
$this->_assertNotFrozen();
$this->_delegate->remove($key);
return $this;
}
public function replace(array $data)
{
$this->_assertNotFrozen();
$this->_delegate->replace($data);
return $this;
}
public function set($key, $value)
{
$this->_assertNotFrozen();
$this->_delegate->set($key, $value);
return $this;
}
public function setEncodingType($type)
{
$this->_assertNotFrozen();
$this->_delegate->setEncodingType($type);
return $this;
}
public function toArray()
{
return $this->_delegate->toArray();
}
public function toString()
{
return $this->__toString();
}
public function __toString()
{
return $this->_delegate->__toString();
}
public function freeze()
{
$this->_isFrozen = true;
}
public function isFrozen()
{
return $this->_isFrozen;
}
private function _assertNotFrozen()
{
if ($this->_isFrozen) {
throw new BadMethodCallException('Query is frozen');
}
}
}
}
namespace
{
class tubepress_url_impl_puzzle_PuzzleBasedUrl implements tubepress_api_url_UrlInterface
{
private $_query = null;
private $_delegateUrl;
private $_isFrozen = false;
private static $_DEFAULT_PORTS = array('http'=> 80,'https'=> 443,'ftp'=> 21,
);
public function __construct(puzzle_Url $delegate)
{
$this->_delegateUrl = $delegate;
if ($this->_delegateUrl->getQuery()) {
$this->_query = new tubepress_url_impl_puzzle_PuzzleBasedQuery($this->_delegateUrl->getQuery());
}
}
public function addPath($relativePath)
{
$this->_assertNotFrozen();
if ($this->getPath() ==='/') {
$this->_delegateUrl->setPath('');
}
$this->_delegateUrl->addPath($relativePath);
return $this;
}
public function getAuthority()
{
$userName = $this->getUsername();
$password = $this->getPassword();
$host = $this->getHost();
$port = $this->getPort();
$scheme = $this->getScheme();
if ($port && isset(self::$_DEFAULT_PORTS[$scheme]) && intval($port) === self::$_DEFAULT_PORTS[$scheme]) {
$port ='';
}
$authority ='';
if ($userName) {
$authority .= $userName;
}
if ($password) {
$authority .= ":$password";
}
if ($userName || $password) {
$authority .='@';
}
$authority .= "$host";
if ($port) {
$authority .= ":$port";
}
return $authority;
}
public function getFragment()
{
return $this->_delegateUrl->getFragment();
}
public function getHost()
{
return $this->_delegateUrl->getHost();
}
public function getParts()
{
return $this->_delegateUrl->getParts();
}
public function getPassword()
{
return $this->_delegateUrl->getPassword();
}
public function getPath()
{
return $this->_delegateUrl->getPath();
}
public function getPathSegments()
{
return $this->_delegateUrl->getPathSegments();
}
public function getPort()
{
return $this->_delegateUrl->getPort();
}
public function getQuery()
{
return $this->_query;
}
public function getScheme()
{
return $this->_delegateUrl->getScheme();
}
public function getUsername()
{
return $this->_delegateUrl->getUsername();
}
public function isAbsolute()
{
return $this->_delegateUrl->isAbsolute();
}
public function removeDotSegments()
{
$this->_assertNotFrozen();
$this->_delegateUrl->removeDotSegments();
return $this;
}
public function setFragment($fragment)
{
$this->_assertNotFrozen();
$this->_delegateUrl->setFragment($fragment);
return $this;
}
public function setHost($host)
{
$this->_assertNotFrozen();
$this->_delegateUrl->setHost($host);
return $this;
}
public function setPassword($password)
{
$this->_assertNotFrozen();
$this->_delegateUrl->setPassword($password);
return $this;
}
public function setPath($path)
{
$this->_assertNotFrozen();
$this->_delegateUrl->setPath($path);
return $this;
}
public function setPort($port)
{
$this->_assertNotFrozen();
$this->_delegateUrl->setPort($port);
return $this;
}
public function setQuery($query)
{
$this->_assertNotFrozen();
if ($query === null) {
$this->_query = null;
return $this;
}
if ($query instanceof tubepress_api_url_QueryInterface) {
$this->_query = $query;
return $this;
}
if (is_string($query)) {
$puzzleQuery = puzzle_Query::fromString($query);
} else {
$puzzleQuery = new puzzle_Query($query);
}
$this->_query = new tubepress_url_impl_puzzle_PuzzleBasedQuery($puzzleQuery);
return $this;
}
public function setScheme($scheme)
{
$this->_assertNotFrozen();
$this->_delegateUrl->setScheme($scheme);
return $this;
}
public function setUsername($username)
{
$this->_assertNotFrozen();
$this->_delegateUrl->setUsername($username);
return $this;
}
public function __toString()
{
$parts = $this->_delegateUrl->getParts();
if ($this->_query) {
$parts['query'] = $this->_query;
} else {
unset($parts['query']);
}
return puzzle_Url::buildUrl($parts);
}
public function toString()
{
return $this->__toString();
}
public function getClone()
{
return new self(puzzle_Url::fromString($this->toString()));
}
public function removeSchemeAndAuthority()
{
$this->_assertNotFrozen();
$this->setScheme(null);
$this->setHost(null);
$this->setUsername(null);
$this->setPort(null);
$this->setPassword(null);
}
public function freeze()
{
$this->_query->freeze();
$this->_isFrozen = true;
}
public function isFrozen()
{
return $this->_isFrozen;
}
private function _assertNotFrozen()
{
if ($this->_isFrozen) {
throw new BadMethodCallException('URL is frozen');
}
}
}
}
namespace
{
class tubepress_url_impl_puzzle_UrlFactory implements tubepress_api_url_UrlFactoryInterface
{
private static $_KEY_HTTPS ='HTTPS';
private static $_KEY_NAME ='SERVER_NAME';
private static $_KEY_PORT ='SERVER_PORT';
private static $_KEY_URI ='REQUEST_URI';
private $_cachedCurrentUrl;
private $_serverVars;
public function __construct(array $serverVars = array())
{
if (count($serverVars) === 0) {
$serverVars = $_SERVER;
}
$this->_serverVars = $serverVars;
}
public function fromString($url)
{
if (!is_string($url)) {
throw new InvalidArgumentException('tubepress_url_impl_puzzle_UrlFactory::fromString() can only accept strings.');
}
return new tubepress_url_impl_puzzle_PuzzleBasedUrl(puzzle_Url::fromString($url));
}
public function fromCurrent()
{
if (!isset($this->_cachedCurrentUrl)) {
$this->_cacheUrl();
}
return $this->_cachedCurrentUrl->getClone();
}
private function _cacheUrl()
{
$toReturn ='http';
$requiredServerVars = array(
self::$_KEY_PORT,
self::$_KEY_NAME,
self::$_KEY_URI,
);
foreach ($requiredServerVars as $requiredServerVar) {
if (!isset($this->_serverVars[$requiredServerVar])) {
throw new RuntimeException(sprintf('Missing $_SERVER variable: %s', $requiredServerVar));
}
}
if (isset($this->_serverVars[self::$_KEY_HTTPS]) && $this->_serverVars[self::$_KEY_HTTPS] =='on') {
$toReturn .='s';
}
$toReturn .='://';
if ($this->_serverVars[self::$_KEY_PORT] !='80') {
$toReturn .= sprintf('%s:%s%s',
$this->_serverVars[self::$_KEY_NAME],
$this->_serverVars[self::$_KEY_PORT],
$this->_serverVars[self::$_KEY_URI]
);
} else {
$toReturn .= $this->_serverVars[self::$_KEY_NAME] . $this->_serverVars[self::$_KEY_URI];
}
try {
$this->_cachedCurrentUrl = $this->fromString($toReturn);
} catch (InvalidArgumentException $e) {
throw new RuntimeException($e->getMessage());
}
}
}
}
namespace
{
class tubepress_util_impl_StringUtils implements tubepress_api_util_StringUtilsInterface
{
public function replaceFirst($search, $replace, $str)
{
$l = strlen($str);
$a = strpos($str, $search);
$b = $a + strlen($search);
$temp = substr($str, 0, $a) . $replace . substr($str, $b, ($l - $b));
return $temp;
}
public function removeNewLines($string)
{
return str_replace(array("\r\n","\r","\n"),'', $string);
}
public function removeEmptyLines($string)
{
return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/","\n", $string);
}
public function startsWith($haystack, $needle)
{
if (!is_string($haystack) || !is_string($needle)) {
return false;
}
$length = strlen($needle);
return substr($haystack, 0, $length) === $needle;
}
public function endsWith($haystack, $needle)
{
if (!is_string($haystack) || !is_string($needle)) {
return false;
}
$length = strlen($needle);
$start = $length * -1;
return substr($haystack, $start) === $needle;
}
public function stripslashes_deep($text, $times = 2) {
$i = 0;
while (strstr($text,'\\') && $i != $times) {
$text = stripslashes($text);
++$i;
}
return $text;
}
public function redactSecrets($string)
{
if (is_scalar($string)) {
$string = "$string";
} else {
if (is_array($string)) {
$string = var_export($string, true);
} else {
$string ='resource/object';
}
}
return preg_replace('/[0-9a-fA-F]{12,}/','XXXXXX', $string);
}
}
}
namespace
{
class tubepress_wordpress_impl_EntryPoint
{
private $_wpFunctions;
private $_persistence;
private $_eventDispatcher;
private $_logger;
private $_loggingEnabled;
private $_baseName;
private $_actions;
private $_filters;
private $_testMode = false;
public function __construct(tubepress_wordpress_impl_wp_WpFunctions $wpFunctions,
tubepress_api_options_PersistenceInterface $persistence,
tubepress_api_log_LoggerInterface $logger,
tubepress_api_event_EventDispatcherInterface $eventDispatcher,
array $actions, array $filters)
{
$this->_baseName = basename(TUBEPRESS_ROOT);
$this->_wpFunctions = $wpFunctions;
$this->_persistence = $persistence;
$this->_actions = $actions;
$this->_filters = $filters;
$this->_logger = $logger;
$this->_eventDispatcher = $eventDispatcher;
$this->_loggingEnabled = $logger->isEnabled();
}
public function start()
{
if ($this->_loggingEnabled) {
$this->_logDebug('Hooking into WordPress');
}
$this->_loadPluginTextDomain();
$this->_addFilterListener();
$this->_addActionListener();
$this->_addActivationListener();
$this->_addShortcodeListener();
$this->_addUpdateChecker();
if ($this->_loggingEnabled) {
$this->_logDebug('Done hooking into WordPress');
}
}
public function callback_onShortcode()
{
try {
$event = $this->_dispatch(
tubepress_wordpress_api_Constants::EVENT_SHORTCODE_FOUND,
func_get_args()
);
if (!$event->hasArgument('result') || !is_string($event->getArgument('result'))) {
throw new \RuntimeException(sprintf('<code>%s</code> event did not return a string',
tubepress_wordpress_api_Constants::EVENT_SHORTCODE_FOUND
));
}
return $event->getArgument('result');
} catch (Exception $e) {
$this->_logger->error($e->getMessage());
return'';
}
}
public function callback_onFilter()
{
try {
$currentFilter = $this->_wpFunctions->current_filter();
$funcArgs = func_get_args();
$funcArgCount = count($funcArgs);
$eventName = "tubepress.wordpress.filter.$currentFilter";
if ($this->_loggingEnabled) {
$this->_logDebug(sprintf('WordPress filter <code>%s</code> invoked with <code>%d</code> argument(s). We will re-dispatch as <code>%s</code>.',
$currentFilter, $funcArgCount, $eventName
));
}
$subject = $funcArgs[0];
$args = $funcArgCount > 1 ? array_slice($funcArgs, 1) : array();
$event = $this->_dispatch($eventName, $subject, array('args'=> $args));
return $event->getSubject();
} catch (Exception $e) {
$this->_logger->error($e->getMessage());
return func_get_arg(0);
}
}
public function callback_onAction()
{
try {
$currentAction = $this->_wpFunctions->current_filter();
$args = func_get_args();
$eventName = "tubepress.wordpress.action.$currentAction";
if ($this->_loggingEnabled) {
$this->_logDebug(sprintf('WordPress action <code>%s</code> invoked with <code>%d</code> argument(s). We will re-dispatch as <code>%s</code>.',
$currentAction, count($args), $eventName
));
}
$this->_dispatch($eventName, $args);
} catch (Exception $e) {
$this->_logger->error($e->getMessage());
}
}
public function callback_onActivation()
{
try {
$this->_dispatch(
tubepress_wordpress_api_Constants::EVENT_PLUGIN_ACTIVATION,
func_get_args()
);
} catch (\Exception $e) {
$this->_logger->error($e->getMessage());
}
}
public function __enableTestMode()
{
$this->_testMode = true;
}
private function _loadPluginTextDomain()
{
$this->_wpFunctions->load_plugin_textdomain('tubepress',
false,
$this->_baseName .'/src/translations');
}
private function _addFilterListener()
{
$filterCallback = array($this,'callback_onFilter');
foreach ($this->_filters as $filterData) {
if (!is_array($filterData)) {
throw new \InvalidArgumentException('Filter data must be an array');
}
$this->_addFilterOrActionToWordPress($filterData, $filterCallback,'add_filter');
}
}
private function _addActionListener()
{
$actionCallback = array($this,'callback_onAction');
foreach ($this->_actions as $actionData) {
$this->_addFilterOrActionToWordPress($actionData, $actionCallback,'add_action');
}
}
private function _addFilterOrActionToWordPress($incoming, $callback, $method)
{
$priority = 10;
$argCount = 1;
if (is_array($incoming)) {
$dataCount = count($incoming);
if ($dataCount < 1 || $dataCount > 3) {
throw new InvalidArgumentException('Filter or action data must be an array of size 1 to 3');
}
if (!is_string($incoming[0])) {
throw new \InvalidArgumentException('One of your requested filters or actions has a non-string name');
}
$name = $incoming[0];
switch ($dataCount) {
case 3:
$priority = intval($incoming[1]);
$argCount = intval($incoming[2]);
break;
case 2:
$priority = intval($incoming[1]);
$argCount = 1;
break;
default:
break;
}
$filterOrActionName = $name;
} else {
$filterOrActionName = "$incoming";
}
if ($this->_loggingEnabled) {
$this->_logDebug(sprintf('
                <code>%s()</code> for <code>%s</code> with priority <code>%d</code> and <code>%d</code> argument(s)',
$method, $filterOrActionName, $priority, $argCount
));
}
$this->_wpFunctions->$method($filterOrActionName, $callback, $priority, $argCount);
}
private function _addActivationListener()
{
$this->_wpFunctions->register_activation_hook(
$this->_baseName .'/tubepress.php',
array($this,'callback_onActivation')
);
}
private function _addShortcodeListener()
{
$keyword = $this->_persistence->fetch(tubepress_api_options_Names::SHORTCODE_KEYWORD);
$this->_wpFunctions->add_shortcode(
$keyword,
array($this,'callback_onShortcode')
);
}
private function _addUpdateChecker()
{
require TUBEPRESS_ROOT .'/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
if (!$this->_testMode) {
PucFactory::buildUpdateChecker('http://snippets.wp.tubepress.com/update.php',
TUBEPRESS_ROOT .'/tubepress.php','tubepress');
}
}
private function _dispatch($eventName, $subject, $args = null)
{
$event = $this->_eventDispatcher->newEventInstance($subject);
if ($args) {
$event->setArguments($args);
}
if ($this->_loggingEnabled) {
$this->_logDebug(sprintf('Start dispatch of event <code>%s</code>', $eventName));
}
$this->_eventDispatcher->dispatch($eventName, $event);
if ($this->_loggingEnabled) {
$this->_logDebug(sprintf('End dispatch of event <code>%s</code>', $eventName));
}
return $event;
}
private function _logDebug($msg)
{
$this->_logger->debug(sprintf('(WordPress Entry Point) %s', $msg));
}
}
}
namespace
{
class tubepress_wordpress_impl_listeners_html_WpHtmlListener
{
public function onScriptsStylesTemplatePreRender(tubepress_api_event_EventInterface $event)
{
$templateVars = $event->getSubject();
if (is_array($templateVars)) {
$templateVars['urls'] = array();
$event->setSubject($templateVars);
}
}
}
}
namespace
{
class tubepress_wordpress_impl_options_WpPersistence implements tubepress_spi_options_PersistenceBackendInterface
{
private static $_optionPrefix ='tubepress-';
private $_wpFunctions;
public function __construct(tubepress_wordpress_impl_wp_WpFunctions $wpFunctions)
{
$this->_wpFunctions = $wpFunctions;
}
public function createEach(array $optionNamesToValuesMap)
{
$existingOptions = array_keys($this->fetchAllCurrentlyKnownOptionNamesToValues());
$incomingOptions = array_keys($optionNamesToValuesMap);
$newOptionNames = array_diff($incomingOptions, $existingOptions);
$toCreate = array();
foreach ($newOptionNames as $newOptionName) {
$toCreate[$newOptionName] = $optionNamesToValuesMap[$newOptionName];
}
foreach ($toCreate as $missingOptionName => $defaultValue) {
$this->_wpFunctions->add_option(self::$_optionPrefix . $missingOptionName, $defaultValue);
}
}
public function fetchAllCurrentlyKnownOptionNamesToValues()
{
$allOptions = $this->_wpFunctions->wp_load_alloptions();
$allOptionNames = array_keys($allOptions);
$tubePressOptionNames = array_filter($allOptionNames, array($this,'__onlyPrefixedWithTubePress'));
$toReturn = array_intersect_key($allOptions, array_flip($tubePressOptionNames));
foreach ($toReturn as $prefixedName => $value) {
$unprefixedName = str_replace(self::$_optionPrefix,'', $prefixedName);
$toReturn[$unprefixedName] = $toReturn[$prefixedName];
unset($toReturn[$prefixedName]);
}
return $toReturn;
}
public function __onlyPrefixedWithTubePress($key)
{
return strpos("$key", self::$_optionPrefix) === 0;
}
public function saveAll(array $optionNamesToValues)
{
foreach ($optionNamesToValues as $optionName => $optionValue) {
$this->_wpFunctions->update_option(self::$_optionPrefix . $optionName, $optionValue);
}
return null;
}
}
}
namespace
{
abstract class tubepress_internal_translation_AbstractTranslator implements tubepress_api_translation_TranslatorInterface
{
private $_pluralRules;
public function trans($id, array $parameters = array(), $domain = null, $locale = null)
{
$translated = $this->_translateSimple($id, $domain, $locale);
return strtr($translated, $parameters);
}
public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
{
$translated = $this->_translateSimple($id, $domain, $locale);
$locale = $locale ? $locale : $this->getLocale();
$translated = $this->_choose($translated, $number, $locale);
return strtr($translated, $parameters);
}
protected abstract function translate($id, $domain = null, $locale = null);
private function _translateSimple($id, $domain, $locale)
{
$domain = $domain ? $domain :'tubepress';
return $this->translate($id, $domain, $locale);
}
private function _choose($message, $number, $locale)
{
$parts = explode('|', $message);
$explicitRules = array();
$standardRules = array();
foreach ($parts as $part) {
$part = trim($part);
if (preg_match('/^(?P<interval>'.$this->_getIntervalRegexp().')\s*(?P<message>.*?)$/x', $part, $matches)) {
$explicitRules[$matches['interval']] = $matches['message'];
} elseif (preg_match('/^\w+\:\s*(.*?)$/', $part, $matches)) {
$standardRules[] = $matches[1];
} else {
$standardRules[] = $part;
}
}
foreach ($explicitRules as $interval => $m) {
if ($this->_test($number, $interval)) {
return $m;
}
}
$position = $this->_getPluralPosition($number, $locale);
if (!isset($standardRules[$position])) {
if (1 === count($parts) && isset($standardRules[0])) {
return $standardRules[0];
}
throw new InvalidArgumentException(sprintf('Unable to choose a translation for "%s" with locale "%s". Double check that this translation has the correct plural options (e.g. "There is one apple|There are %%count%% apples").', $message, $locale));
}
return $standardRules[$position];
}
private function _getIntervalRegexp()
{
return<<<EOF
        ({\s*
            (\-?\d+(\.\d+)?[\s*,\s*\-?\d+(\.\d+)?]*)
        \s*})

            |

        (?P<left_delimiter>[\[\]])
            \s*
            (?P<left>-Inf|\-?\d+(\.\d+)?)
            \s*,\s*
            (?P<right>\+?Inf|\-?\d+(\.\d+)?)
            \s*
        (?P<right_delimiter>[\[\]])
EOF
;
}
private function _test($number, $interval)
{
$interval = trim($interval);
if (!preg_match('/^'.$this->_getIntervalRegexp().'$/x', $interval, $matches)) {
throw new InvalidArgumentException(sprintf('"%s" is not a valid interval.', $interval));
}
if ($matches[1]) {
foreach (explode(',', $matches[2]) as $n) {
if ($number == $n) {
return true;
}
}
} else {
$leftNumber = $this->_convertNumber($matches['left']);
$rightNumber = $this->_convertNumber($matches['right']);
return
('['=== $matches['left_delimiter'] ? $number >= $leftNumber : $number > $leftNumber)
&& (']'=== $matches['right_delimiter'] ? $number <= $rightNumber : $number < $rightNumber)
;
}
return false;
}
private function _convertNumber($number)
{
if ('-Inf'=== $number) {
return log(0);
} elseif ('+Inf'=== $number ||'Inf'=== $number) {
return -log(0);
}
return (float) $number;
}
private function _getPluralPosition($number, $locale)
{
if ('pt_BR'=== $locale) {
$locale ='xbr';
}
if (strlen($locale) > 3) {
$locale = substr($locale, 0, -strlen(strrchr($locale,'_')));
}
if (isset($this->_pluralRules[$locale])) {
$return = call_user_func($this->_pluralRules[$locale], $number);
if (!is_int($return) || $return < 0) {
return 0;
}
return $return;
}
switch ($locale) {
case'bo':
case'dz':
case'id':
case'ja':
case'jv':
case'ka':
case'km':
case'kn':
case'ko':
case'ms':
case'th':
case'tr':
case'vi':
case'zh':
return 0;
break;
case'af':
case'az':
case'bn':
case'bg':
case'ca':
case'da':
case'de':
case'el':
case'en':
case'eo':
case'es':
case'et':
case'eu':
case'fa':
case'fi':
case'fo':
case'fur':
case'fy':
case'gl':
case'gu':
case'ha':
case'he':
case'hu':
case'is':
case'it':
case'ku':
case'lb':
case'ml':
case'mn':
case'mr':
case'nah':
case'nb':
case'ne':
case'nl':
case'nn':
case'no':
case'om':
case'or':
case'pa':
case'pap':
case'ps':
case'pt':
case'so':
case'sq':
case'sv':
case'sw':
case'ta':
case'te':
case'tk':
case'ur':
case'zu':
return ($number == 1) ? 0 : 1;
case'am':
case'bh':
case'fil':
case'fr':
case'gun':
case'hi':
case'ln':
case'mg':
case'nso':
case'xbr':
case'ti':
case'wa':
return (($number == 0) || ($number == 1)) ? 0 : 1;
case'be':
case'bs':
case'hr':
case'ru':
case'sr':
case'uk':
return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
case'cs':
case'sk':
return ($number == 1) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);
case'ga':
return ($number == 1) ? 0 : (($number == 2) ? 1 : 2);
case'lt':
return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
case'sl':
return ($number % 100 == 1) ? 0 : (($number % 100 == 2) ? 1 : ((($number % 100 == 3) || ($number % 100 == 4)) ? 2 : 3));
case'mk':
return ($number % 10 == 1) ? 0 : 1;
case'mt':
return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3));
case'lv':
return ($number == 0) ? 0 : ((($number % 10 == 1) && ($number % 100 != 11)) ? 1 : 2);
case'pl':
return ($number == 1) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2);
case'cy':
return ($number == 1) ? 0 : (($number == 2) ? 1 : ((($number == 8) || ($number == 11)) ? 2 : 3));
case'ro':
return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);
case'ar':
return ($number == 0) ? 0 : (($number == 1) ? 1 : (($number == 2) ? 2 : ((($number >= 3) && ($number <= 10)) ? 3 : ((($number >= 11) && ($number <= 99)) ? 4 : 5))));
default:
return 0;
}
}
}
}
namespace
{
class tubepress_wordpress_impl_translation_WpTranslator extends tubepress_internal_translation_AbstractTranslator
{
private $_wpFunctions;
public function __construct(tubepress_wordpress_impl_wp_WpFunctions $wpFunctions)
{
$this->_wpFunctions = $wpFunctions;
}
protected function translate($id, $domain = null, $locale = null)
{
$domain = $domain ? $domain :'tubepress';
return $id ==''?'': $this->_wpFunctions->__($id, $domain);
}
public function setLocale($locale)
{
throw new LogicException('Use WPLANG to set WordPress locale');
}
public function getLocale()
{
return $this->_wpFunctions->get_locale();
}
}
}
namespace
{
class tubepress_wordpress_impl_listeners_wp_ShortcodeListener
{
private $_eventDispatcher;
private $_htmlGenerator;
private $_context;
private $_optionsReference;
private $_optionMapCache;
private $_logger;
private $_loggingEnabled;
public function __construct(tubepress_api_event_EventDispatcherInterface $eventDispatcher,
tubepress_api_options_ContextInterface $context,
tubepress_api_html_HtmlGeneratorInterface $htmlGenerator,
tubepress_api_options_ReferenceInterface $optionsReference,
tubepress_api_log_LoggerInterface $logger)
{
$this->_eventDispatcher = $eventDispatcher;
$this->_context = $context;
$this->_htmlGenerator = $htmlGenerator;
$this->_optionsReference = $optionsReference;
$this->_logger = $logger;
$this->_loggingEnabled = $logger->isEnabled();
}
public function onShortcode(tubepress_api_event_EventInterface $incomingEvent)
{
$subject = $incomingEvent->getSubject();
$rawShortcodeAttributes = $subject[0];
$rawShortcodeContent = isset($subject[1]) ? $subject[1] :'';
if (!is_array($rawShortcodeAttributes)) {
$rawShortcodeAttributes = array();
}
if ($this->_loggingEnabled) {
$this->_logRawShortcode($rawShortcodeAttributes, $rawShortcodeContent);
}
$normalizedOptions = $this->_normalizeIncomingShortcodeOptionMap($rawShortcodeAttributes);
$this->_context->setEphemeralOptions($normalizedOptions);
$event = $this->_buildShortcodeEvent($normalizedOptions, $rawShortcodeContent);
$this->_eventDispatcher->dispatch(tubepress_wordpress_api_Constants::SHORTCODE_PARSED, $event);
$toReturn = $this->_htmlGenerator->getHtml();
$this->_context->setEphemeralOptions(array());
$incomingEvent->setArgument('result', $toReturn);
}
private function _buildShortcodeEvent(array $normalizedOptions, $innerContent)
{
if (!$innerContent) {
$innerContent = null;
}
$name = $this->_context->get(tubepress_api_options_Names::SHORTCODE_KEYWORD);
$shortcode = new tubepress_shortcode_impl_Shortcode($name, $normalizedOptions, $innerContent);
return $this->_eventDispatcher->newEventInstance($shortcode);
}
private function _normalizeIncomingShortcodeOptionMap(array $optionMap)
{
if (!isset($this->_optionMapCache)) {
$this->_optionMapCache = array();
$allKnownOptionNames = $this->_optionsReference->getAllOptionNames();
foreach ($allKnownOptionNames as $camelCaseOptionName) {
$asLowerCase = strtolower($camelCaseOptionName);
$this->_optionMapCache[$asLowerCase] = $camelCaseOptionName;
}
}
$toReturn = array();
foreach ($optionMap as $lowerCaseCandidate => $value) {
if (isset($this->_optionMapCache[$lowerCaseCandidate])) {
$camelCaseOptionName = $this->_optionMapCache[$lowerCaseCandidate];
$toReturn[$camelCaseOptionName] = $value;
}
}
return $toReturn;
}
private function _logRawShortcode(array $rawShortcodeAttributes, $rawShortcodeContent)
{
$this->_logDebug(sprintf('WordPress sent us a shortcode to parse with <code>%d</code> attribute(s).',
count($rawShortcodeAttributes)
));
if (count($rawShortcodeAttributes) > 0) {
$this->_logDebug('Attributes follow...');
}
foreach ($rawShortcodeAttributes as $key => $value) {
$printKey = is_scalar($key) ? (string) $key : json_encode($key);
$printValue = is_scalar($value) ? (string) $value : json_encode($value);
$this->_logDebug(sprintf('<code>%s</code> : <code>%s</code>',
htmlspecialchars($printKey), htmlspecialchars($printValue)
));
}
$printContent = is_scalar($rawShortcodeContent) ? (string) $rawShortcodeContent : json_encode($rawShortcodeContent);
$this->_logDebug(sprintf('Shortcode content is: <code>%s</code>', htmlspecialchars($printContent)));
}
private function _logDebug($msg)
{
$this->_logger->debug(sprintf('(Shortcode Listener) %s', $msg));
}
}
}
namespace
{
class tubepress_wordpress_impl_wp_WpFunctions
{
const _ ='tubepress_wordpress_impl_wp_WpFunctions';
public function __($message, $domain)
{
return $message ==''?'': __($message, $domain);
}
public function add_shortcode($tag, $function)
{
add_shortcode($tag, $function);
}
public function current_filter()
{
return current_filter();
}
public function update_option($name, $value)
{
return update_option($name, $value);
}
public function get_categories(array $args = array())
{
return get_categories($args);
}
public function get_locale()
{
return get_locale();
}
public function get_option($name)
{
return get_option($name);
}
public function get_post_stati(array $args = array(), $output ='names', $operator ='and')
{
return get_post_stati($args, $output, $operator);
}
public function get_permalink($post, $leavename = false)
{
return get_permalink($post, $leavename);
}
public function get_posts($args)
{
return get_posts($args);
}
public function get_post_types(array $args = array(), $output ='names', $operator ='and')
{
return get_post_types($args, $output, $operator);
}
public function get_tags(array $args = array())
{
return get_tags($args);
}
public function get_user_by($field, $value)
{
return get_user_by($field, $value);
}
public function get_users(array $args = array())
{
return get_users($args);
}
public function wp_dequeue_script($handle)
{
wp_dequeue_script($handle);
}
public function wp_dequeue_style($handle)
{
wp_dequeue_style($handle);
}
public function wp_deregister_script($handle)
{
wp_deregister_script($handle);
}
public function add_option($name, $value)
{
add_option($name, $value);
}
public function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function)
{
return add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
}
public function add_options_page($pageTitle, $menuTitle, $capability, $menu_slug, $callback)
{
return add_options_page($pageTitle, $menuTitle, $capability, $menu_slug, $callback);
}
public function check_admin_referer($action, $queryArg)
{
return check_admin_referer($action, $queryArg);
}
public function is_admin()
{
return is_admin();
}
public function plugins_url($path, $plugin)
{
return plugins_url($path, $plugin);
}
public function plugin_basename($file)
{
return plugin_basename($file);
}
public function wp_enqueue_script($handle, $src, $deps, $ver, $in_footer)
{
wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
}
public function wp_enqueue_style($handle)
{
wp_enqueue_style($handle);
}
public function wp_register_script($handle, $src, $deps = array(), $version = null, $inFooter = false)
{
wp_register_script($handle, $src, $deps, $version, $inFooter);
}
public function wp_register_sidebar_widget($id, $name, $callback, $options)
{
wp_register_sidebar_widget($id, $name, $callback, $options);
}
public function wp_register_style($handle, $src, $deps = array(), $version = null)
{
wp_register_style($handle, $src, $deps, $version);
}
public function wp_register_widget_control($id, $name, $callback)
{
wp_register_widget_control($id, $name, $callback);
}
public function add_action($tag, $function, $priority, $acceptedArgs)
{
add_action($tag, $function, $priority, $acceptedArgs);
}
public function add_filter($tag, $function, $priority, $acceptedArgs)
{
add_filter($tag, $function, $priority, $acceptedArgs);
}
public function is_ssl()
{
return is_ssl();
}
public function load_plugin_textdomain($domain, $absPath, $relPath)
{
load_plugin_textdomain($domain, $absPath, $relPath);
}
public function site_url()
{
return site_url();
}
public function content_url($path ='')
{
return content_url($path);
}
public function wp_version()
{
global $wp_version;
return $wp_version;
}
public function register_activation_hook($file, $function)
{
return register_activation_hook($file, $function);
}
public function wp_nonce_field($action, $name, $referrer, $echo)
{
return wp_nonce_field($action, $name, $referrer, $echo);
}
public function wp_verify_nonce($nonce, $action)
{
return wp_verify_nonce($nonce, $action);
}
public function admin_url($path = null, $scheme ='admin')
{
return admin_url($path, $scheme);
}
public function current_user_can($capability, $args = null)
{
return current_user_can($capability, $args);
}
public function wp_create_nonce($action = null)
{
return wp_create_nonce($action);
}
public function wp_insert_post(array $postArray, $wpError = false)
{
return wp_insert_post($postArray, $wpError);
}
public function wp_load_alloptions()
{
return wp_load_alloptions();
}
public function register_widget($class)
{
register_widget($class);
}
public function wp_scripts() {
global $wp_scripts;
if (!($wp_scripts instanceof WP_Scripts)) {
$wp_scripts = new WP_Scripts();
}
return $wp_scripts;
}
public function wp_get_theme($stylesheet = null, $theme_root = null)
{
return wp_get_theme($stylesheet, $theme_root);
}
}
}
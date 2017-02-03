<?php
/**
 * Copyright 2006 - 2016 TubePress LLC (http://tubepress.com/)
 *
 * This file is part of TubePress Pro.
 *
 * License summary
 *   - Can be used on 1 site, 1 server
 *   - Cannot be resold or distributed
 *   - Commercial use allowed
 *   - Can modify source-code but cannot distribute modifications (derivative works)
 *
 * Please see http://tubepress.com/license for details.
 */

class tubepress_pro_cache_html_impl_listeners_HtmlCacheListener
{
    private static $_REQUEST_PARAM_NAMES_TO_INCLUDE_IN_CACHE_KEY = array(
        'tubepress_page',
        'tubepress_item',
        'tubepress_search',
    );

    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var \Stash\Interfaces\PoolInterface
     */
    private $_cache;

    /**
     * @var tubepress_api_log_LoggerInterface
     */
    private $_logger;

    /**
     * @var tubepress_api_options_PersistenceInterface
     */
    private $_persistence;

    /**
     * @var tubepress_api_http_RequestParametersInterface
     */
    private $_requestParams;

    /**
     * @var boolean
     */
    private $_cacheKey;

    /**
     * @var boolean
     */
    private $_shouldLog;

    public function __construct(tubepress_api_log_LoggerInterface             $logger,
                                tubepress_api_options_ContextInterface        $context,
                                tubepress_api_options_PersistenceInterface    $persistence,
                                \Stash\Interfaces\PoolInterface               $cache,
                                tubepress_api_http_RequestParametersInterface $requestParams)
    {
        $this->_logger        = $logger;
        $this->_context       = $context;
        $this->_cache         = $cache;
        $this->_persistence   = $persistence;
        $this->_shouldLog     = $logger->isEnabled();
        $this->_requestParams = $requestParams;
    }

    public function onHtmlGenerationPre(tubepress_api_event_EventInterface $event)
    {
        if (!$this->_shouldExecute()) {

            return;
        }

        $this->_flushEntireCacheIfRequested();

        $this->_cacheKey = $this->_getCacheKey();

        /**
         * @var $item ehough_stash_interfaces_ItemInterface
         */
        $item = $this->_cache->getItem($this->_cacheKey);

        if ($item->isMiss()) {

            if ($this->_shouldLog) {

                $this->_logDebug(sprintf('Miss HTML cache for <code>%s</code>', $this->_cacheKey));
            }

            //allow processing to continue
            return;
        }

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('HTML cache hit for <code>%s</code>', $this->_cacheKey));
        }

        unset($this->_cacheKey);

        $storedHtml = $item->get();
        $event->setSubject($storedHtml);
        $event->stopPropagation();
    }

    public function onHtmlGenerationPost(tubepress_api_event_EventInterface $event)
    {
        /**
         * Not enabled or we served it from the cache already.
         */
        if (!isset($this->_cacheKey) || !$this->_shouldExecute()) {

            return;
        }

        $this->_possiblyClearCache();

        $newCacheKey = $this->_getCacheKey();
        $html        = $event->getSubject();

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('Saving newly-generated HTML to cache. Previously-saved cache key is <code>%s</code>. Post HTML generation cache key is <code>%s</code>',
                $this->_cacheKey,
                $newCacheKey
            ));
        }

        $this->_storeWithLogging($this->_cacheKey, $html);

        if ($this->_cacheKey !== $newCacheKey) {

            if ($this->_shouldLog) {

                $this->_logDebug('Since cache key changed during HTML generation, we will save to the new key as well.');
            }

            $this->_storeWithLogging($newCacheKey, $html);
        }

        unset($this->_cacheKey);
    }

    private function _storeWithLogging($cacheKey, $data)
    {
        /**
         * @var $item ehough_stash_interfaces_ItemInterface
         */
        $item = $this->_cache->getItem($cacheKey);

        $storedSuccessfully = $item->set($data, intval($this->_context->get(tubepress_api_options_Names::CACHE_HTML_LIFETIME_SECONDS)));

        if (!$storedSuccessfully) {

            if ($this->_shouldLog) {

                $this->_logger->error(sprintf('Unable to store data to cache under key <code>%s</code>', $this->_cacheKey));
            }

        } else {

            if ($this->_shouldLog) {

                $this->_logDebug(sprintf('Stored HTML into cache under key <code>%s</code>', $this->_cacheKey));
            }
        }
    }

    private function _possiblyClearCache()
    {
        $cleaningFactor = $this->_context->get(tubepress_api_options_Names::CACHE_HTML_CLEANING_FACTOR);
        $cleaningFactor = intval($cleaningFactor);

        /**
         * Handle cleaning factor.
         */
        if ($cleaningFactor > 0 && rand(1, $cleaningFactor) === 1) {

            $this->_cache->flush();
        }
    }

    private function _getCacheKey()
    {
        if ($this->_shouldLog) {

            $this->_logDebug('Starting to generate HTML cache key.');
        }

        $ephemeralOptions = $this->_context->getEphemeralOptions();
        $persistedOptions = $this->_persistence->fetchAll();

        //TODO: come up with a more elegant solution for this type of situation
        if (isset($ephemeralOptions[tubepress_api_options_Names::HTML_GALLERY_ID])) {

            $ephemeralOptions[tubepress_api_options_Names::HTML_GALLERY_ID] = (string) $ephemeralOptions[tubepress_api_options_Names::HTML_GALLERY_ID];
        }

        ksort($ephemeralOptions);
        ksort($persistedOptions);

        $encodedEphemeralOptions = json_encode($ephemeralOptions);
        $encodedPersistedOptions = json_encode($persistedOptions);
        $requestParams           = array();

        foreach (self::$_REQUEST_PARAM_NAMES_TO_INCLUDE_IN_CACHE_KEY as $key) {

            if ($this->_requestParams->hasParam($key)) {

                $requestParams[$key] = $this->_requestParams->getParamValue($key);

            } else {

                if ($key === 'tubepress_page') {

                    $requestParams[$key] = '1';
                }
            }
        }

        $encodedRequestParams = json_encode($requestParams);
        $toReturn             = md5($encodedRequestParams . $encodedEphemeralOptions . $encodedPersistedOptions);

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('HTML cache key is <code>%s</code>', $this->_cacheKey));
        }

        return $toReturn;
    }

    private function _shouldExecute()
    {
        if ($this->_shouldLog || !$this->_context->get(tubepress_api_options_Names::CACHE_HTML_ENABLED)) {

            if ($this->_shouldLog) {

                $this->_logDebug('HTML cache is not enabled or disabled for debugging.');
            }

            unset($this->_cacheKey);

            return false;
        }

        if ($this->_shouldLog) {

            $this->_logDebug('HTML cache is enabled.');
        }

        return true;
    }

    private function _flushEntireCacheIfRequested()
    {
        $clearingKey        = $this->_context->get(tubepress_api_options_Names::CACHE_HTML_CLEANING_KEY);
        $clearingKeyPresent = $this->_requestParams->hasParam($clearingKey);

        if (!$clearingKeyPresent) {

            return;
        }

        $clearingValue = $this->_requestParams->getParamValue($clearingKey);

        if (!$clearingValue) {

            return;
        }

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('Remote cache clear requested. Flushing entire HTML cache.'));
        }

        $this->_cache->flush();
    }

    /**
     * THIS IS HERE FOR TESTING ONLY.
     */
    public function __setCacheKey($key)
    {
        $this->_cacheKey = $key;
    }

    private function _logDebug($msg)
    {
        $this->_logger->debug(sprintf('(HTML Cache Listener) %s', $msg));
    }
}
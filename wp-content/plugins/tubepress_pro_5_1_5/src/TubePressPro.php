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

/**
 * This is a thin wrapper around an instance of tubepress_api_pro_TubePressProInterface which is convenient
 * to use in standalone PHP.
 */
class TubePressPro
{
    /**
     * @var tubepress_api_pro_TubePressProInterface
     */
    private static $_backend;

    /**
     * @return string
     *
     * @api
     * @since 4.0.0
     */
    public static function getCSS()
    {
        return self::_getBackend()->getCSS();
    }

    /**
     * @param bool $includeJQuery
     *
     * @return string
     *
     * @api
     * @since 4.0.0
     */
    public static function getJS($includeJQuery = false)
    {
        return self::_getBackend()->getJS($includeJQuery);
    }

    /**
     *
     * @param mixed $options One of the following:
     *                       1. a TubePress shortcode, e.g. [tubepress mode="tag"]
     *                       2. an NVP string, e.g. mode="tag"
     *                       3. An associative array of TubePress options, e.g. array('mode' => 'tag')
     *                       4. a falsey value, like null or ""
     *
     * @return string
     *
     * @api
     * @since 4.2.0
     */
    public static function getHTML($options = null)
    {
        return self::_getBackend()->getHTML($options);
    }







    /***************************************************************************************
     ** DEPRECATED FUNCTIONS BELOW                                                        **
     ***************************************************************************************/

    /**
     * Set the base TubePress URL.
     *
     * @param string|tubepress_api_url_UrlInterface $url The base TubePress URL.
     *
     * @throws InvalidArgumentException If invalid URL supplied.
     *
     * @return void
     *
     * @api
     * @since 4.0.0
     *
     * @deprecated
     */
    public static function setBaseUrl($url)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        self::_getBackend()->setBaseUrl($url);
    }

    /**
     * Returns the HTML necessary for the HTML <head>.
     *
     * @param bool $includeJQuery True to include the jQuery <script> include, false otherwise.
     *
     * @return string The HTML that will go into the HTML <head>.
     *
     * @deprecated
     */
    public static function getHtmlForHead($includeJQuery = false)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return self::_getBackend()->getHtmlForHead($includeJQuery);
    }

    /**
     * @param string $raw_shortcode
     *
     * @return string
     *
     * @api
     * @since 4.0.0
     *
     * @deprecated
     */
    public static function getHtmlForShortcode($raw_shortcode = '')
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return self::_getBackend()->getHtmlForShortcode($raw_shortcode);
    }




    /**
     * @return tubepress_api_pro_TubePressProInterface
     */
    private static function _getBackend()
    {
        if (!isset(self::$_backend)) {

            /** @noinspection PhpIncludeInspection */
            $container = require __DIR__ . '/php/scripts/boot.php';

            self::$_backend = $container->get(tubepress_api_pro_TubePressProInterface::_);
        }

        return self::$_backend;
    }
}

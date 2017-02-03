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

class tubepress_pro_core_impl_TubePressProBackend implements tubepress_api_pro_TubePressProInterface
{
    /**
     * @var tubepress_api_environment_EnvironmentInterface
     */
    private $_environment;

    /**
     * @var tubepress_api_html_HtmlGeneratorInterface
     */
    private $_htmlGenerator;

    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_options_PersistenceInterface
     */
    private $_persistence;

    /**
     * @var tubepress_api_shortcode_ParserInterface
     */
    private $_shortcodeParser;

    public function __construct(tubepress_api_environment_EnvironmentInterface $environment,
                                tubepress_api_html_HtmlGeneratorInterface      $htmlGenerator,
                                tubepress_api_options_ContextInterface         $context,
                                tubepress_api_options_PersistenceInterface     $persistence,
                                tubepress_api_shortcode_ParserInterface        $shortcodeParser)
    {
        $this->_environment     = $environment;
        $this->_htmlGenerator   = $htmlGenerator;
        $this->_context         = $context;
        $this->_persistence     = $persistence;
        $this->_shortcodeParser = $shortcodeParser;
    }

    /**
     * @return string The CSS tags and inline CSS for the head of an HTML document that contains
     *                Tubepress.
     *
     * @api
     * @since 4.1.8
     */
    public function getCSS()
    {
        $this->_ensureBaseUrlSet();

        return $this->_htmlGenerator->getCSS();
    }

    /**
     * @param bool $includeJQuery True to load a copy of jQuery, false if it's already included elsewhere
     *                            in the document.
     *
     * @return string The script tags and inline JavaScript for an HTML document that contains TubePress. This
     *                can be placed anywhere in the document.
     *
     * @api
     * @since 4.1.8
     */
    public function getJS($includeJQuery = false)
    {
        $this->_ensureBaseUrlSet();

        $toReturn = $this->_htmlGenerator->getJS();

        if ($includeJQuery) {

            $jqueryUrl = $this->_environment->getBaseUrl()->getClone();
            $jqueryUrl->addPath('/web/vendor/jquery/jquery.min.js');
            $jqueryTag = sprintf('<script type="text/javascript" src="%s"></script>'  . "\n", $jqueryUrl);
            $toReturn  = $jqueryTag . $toReturn;
        }

        return $toReturn;
    }

    /**
     *
     * @param mixed $options One of the following:
     *                       1. a TubePress shortcode, e.g. [tubepress mode="tag"]
     *                       2. an NVP string, e.g. mode="tag" tagValue="something"
     *                       3. An associative array of TubePress options, e.g. array('mode' => 'tag')
     *                       4. a falsey value, like null or ""
     *
     * @return string The primary TubePress HTML.
     *
     * @api
     * @since 4.1.8
     */
    public function getHTML($options = null)
    {
        if (is_string($options)) {

            return $this->getHtmlForShortcode($options);
        }

        if (!is_array($options)) {

            throw new InvalidArgumentException('TubePressPro::getHTML() can only accept a string or an array');
        }

        $this->_ensureBaseUrlSet();

        $this->_context->setEphemeralOptions($options);

        return $this->_htmlGenerator->getHtml();
    }





    /***************************************************************************************
     ** DEPRECATED FUNCTIONS BELOW                                                        **
     ***************************************************************************************/

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
    public function getHtmlForShortcode($raw_shortcode = '')
    {
        $this->_ensureBaseUrlSet();

        /* pad the shortcode if it doesn't start and end with the right stuff */
        $shortcode = $this->_conditionalPadShortcode($raw_shortcode);

        $this->_shortcodeParser->parse($shortcode);

        return $this->_htmlGenerator->getHtml();
    }

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
    public function setBaseUrl($url)
    {
        $this->_environment->setBaseUrl($url);
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
    public function getHtmlForHead($includeJQuery = false)
    {
        return $this->getCSS() . "\n" . $this->getJS($includeJQuery);
    }

    private function _conditionalPadShortcode($shortcode)
    {
        $trigger = $this->_persistence->fetch(tubepress_api_options_Names::SHORTCODE_KEYWORD);
        $lookFor = "[$trigger ";
        $len     = strlen($lookFor);

        /* make sure it starts with [tubepress */
        if (substr($shortcode, 0, $len) != "[$trigger ") {

            $shortcode = $lookFor . $shortcode;
        }

        /* make sure it ends with a bracket */
        if (substr($shortcode, strlen($shortcode) - 1) != ']') {

            $shortcode = "$shortcode]";
        }

        return $shortcode;
    }

    private function _ensureBaseUrlSet()
    {
        $baseUrl = $this->_environment->getBaseUrl();

        if (!$baseUrl) {

            throw new RuntimeException('No base URL set for TubePress. Be sure to call TubePressPro::setBaseUrl()');
        }
    }
}

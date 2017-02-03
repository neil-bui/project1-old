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
 */
class tubepress_pro_wordpress_impl_listeners_shortcode_ShortcodeListener
{
    /**
     * @var tubepress_api_shortcode_ShortcodeExtractorInterface
     */
    private $_shortcodeExtractor;

    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_log_LoggerInterface
     */
    private $_logger;

    /**
     * @var bool
     */
    private $_shouldLog;

    public function __construct(tubepress_api_shortcode_ShortcodeExtractorInterface $shortcodeExtractor,
                                tubepress_api_options_ContextInterface              $context,
                                tubepress_api_log_LoggerInterface                   $logger)
    {
        $this->_shortcodeExtractor = $shortcodeExtractor;
        $this->_context            = $context;
        $this->_logger             = $logger;
        $this->_shouldLog          = $logger->isEnabled();
    }

    public function onShortcode(tubepress_api_event_EventInterface $event)
    {
        /**
         * @var $shortcode tubepress_api_shortcode_ShortcodeInterface
         */
        $shortcode     = $event->getSubject();
        $content       = $shortcode->getInnerContent();
        $shortcodeName = $shortcode->getName();

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('Shortcode listener invoked for <code>%s</code> shortcode', $shortcodeName));
        }

        if (!$content) {

            if ($this->_shouldLog) {

                $this->_logDebug('No inner content in shortcode. Nothing to do.');
            }

            return;
        }

        $innerShortcodes = $this->_shortcodeExtractor->getShortcodes($shortcodeName, $content);

        if (count($innerShortcodes) === 0) {

            if ($this->_shouldLog) {

                $this->_logDebug('No shortcodes found in inner content. Nothing to do.');
            }

            return;
        }

        $sources = $this->_buildSourcesArray($innerShortcodes);

        $this->_context->setEphemeralOption(tubepress_api_options_Names::SOURCES, json_encode($sources));
    }

    /**
     * @param tubepress_api_shortcode_ShortcodeInterface[] $innerShortcodes
     *
     * @return array
     */
    private function _buildSourcesArray(array $innerShortcodes)
    {
        $sources    = array();
        $totalCount = count($innerShortcodes);

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('%d inner shortcode(s) found.', $totalCount));
        }

        foreach ($innerShortcodes as $shortcode) {

            $sources[] = $shortcode->getAttributes();
        }

        return $sources;
    }

    private function _logDebug($msg)
    {
        $this->_logger->debug(sprintf('(WP Shortcode Listener) %s', $msg));
    }
}
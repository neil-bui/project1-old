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
 * Pro mods for video construction.
 */
abstract class tubepress_pro_media_impl_listeners_AbstractHttpItemListener
{
    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_util_StringUtilsInterface
     */
    private $_stringUtils;

    /**
     * @var tubepress_api_array_ArrayReaderInterface
     */
    private $_arrayReader;

    public function __construct(tubepress_api_options_ContextInterface   $context,
                                tubepress_api_util_StringUtilsInterface  $stringUtils,
                                tubepress_api_array_ArrayReaderInterface $arrayReader)
    {
        $this->_context     = $context;
        $this->_stringUtils = $stringUtils;
        $this->_arrayReader = $arrayReader;
    }

    public function onVideoConstruction(tubepress_api_event_EventInterface $event)
    {
        $mediaItem = $event->getSubject();

        if ($this->_context->get(tubepress_api_options_Names::GALLERY_HQ_THUMBS)) {

            $this->convertThumbToHQ($mediaItem, $event);
        }

        if ($this->_context->get(tubepress_api_options_Names::HTML_HTTPS)) {

            $this->_convertThumbToHttps($mediaItem);
        }
    }

    protected function getArrayReader()
    {
        return $this->_arrayReader;
    }

    protected abstract function convertThumbToHQ(tubepress_api_media_MediaItem      $mediaItem,
                                                 tubepress_api_event_EventInterface $event);

    private function _convertThumbToHttps(tubepress_api_media_MediaItem $mediaItem)
    {
        $url = $mediaItem->getAttribute(tubepress_api_media_MediaItem::ATTRIBUTE_THUMBNAIL_URL);

        /**
         * If, on the off chance, the URL doesn't begin with "http://" then just bail.
         */
        if (substr($url, 0, 7) !== 'http://') {

            return;
        }

        $url = $this->_stringUtils->replaceFirst('http://', 'https://', $url);

        $mediaItem->setAttribute(tubepress_api_media_MediaItem::ATTRIBUTE_THUMBNAIL_URL, $url);
    }
}
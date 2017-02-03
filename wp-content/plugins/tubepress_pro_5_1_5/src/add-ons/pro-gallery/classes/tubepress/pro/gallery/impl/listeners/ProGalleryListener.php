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
 * Writes the video sequence to JavaScript.
 */
class tubepress_pro_gallery_impl_listeners_ProGalleryListener
{
    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    public function __construct(tubepress_api_options_ContextInterface $context)
    {
        $this->_context = $context;
    }

    public function onPostGalleryTemplateRender(tubepress_api_event_EventInterface $event)
    {
        $html = $event->getSubject();

        $toReturn = $html . <<<EOT
<script type="text/javascript">
   var tubePressDomInjector = tubePressDomInjector || [];
       tubePressDomInjector.push(['loadJs', 'web/js/pro-gallery.js']);
</script>
EOT;

        $event->setSubject($toReturn);
    }

    public function onGalleryInitJs(tubepress_api_event_EventInterface $event)
    {
        $args = $event->getSubject();

        /**
         * Grab the existing maps, if they exist, or create fresh ones.
         */
        $ephemeralOptions = $args['ephemeral'];
        $options          = $args['options'];

        if ($event->hasArgument('mediaPage')) {

            /**
             * @var $mediaPage tubepress_api_media_MediaPage
             */
            $mediaPage = $event->getArgument('mediaPage');
            $items     = $mediaPage->getItems();

            /**
             * Write the sequence to JavaScript.
             */
            $options['sequence'] = $this->_getItemIds($items);
        }

        $autoNext               = $this->_context->get(tubepress_api_options_Names::GALLERY_AUTONEXT);
        $embeddedScrollOn       = $this->_context->get(tubepress_api_options_Names::EMBEDDED_SCROLL_ON);
        $embeddedScrollDuration = $this->_context->get(tubepress_api_options_Names::EMBEDDED_SCROLL_DURATION);
        $embeddedScrollOffset   = $this->_context->get(tubepress_api_options_Names::EMBEDDED_SCROLL_OFFSET);

        $options[tubepress_api_options_Names::GALLERY_AUTONEXT]            = $autoNext ? true : false;
        $options[tubepress_api_options_Names::EMBEDDED_SCROLL_ON]          = $embeddedScrollOn ? true : false;
        $options[tubepress_api_options_Names::EMBEDDED_SCROLL_DURATION]    = $embeddedScrollDuration;
        $options[tubepress_api_options_Names::EMBEDDED_SCROLL_OFFSET]      = $embeddedScrollOffset;

        /**
         * Reset the maps and restore them into the event.
         */
        $args['ephemeral'] = $ephemeralOptions;
        $args['options']   = $options;

        $event->setSubject($args);
    }

    private function _getItemIds($items)
    {
        $toReturn = array();

        foreach ($items as $item) {

            /**
             * @var $item tubepress_api_media_MediaItem
             */
            $toReturn[] = $item->getId();
        }

        return $toReturn;
    }
}
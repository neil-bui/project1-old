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
 * Registers videos with the JS player API.
 */
class tubepress_pro_playerapi_impl_listeners_PlayerApiJsListener
{
    /**
     * @var string
     */
    private $_asyncJsObjectName;

    /**
     * @var string
     */
    private $_jsPath;

    public function __construct($asyncJsObjectName, $jsPath)
    {
        $this->_asyncJsObjectName = $asyncJsObjectName;
        $this->_jsPath            = $jsPath;
    }

    public function onEmbeddedHtml(tubepress_api_event_EventInterface $event)
    {
        $existingTemplateVars = $event->getArguments();

        if (!isset($existingTemplateVars[tubepress_api_template_VariableNames::MEDIA_ITEM])) {

            return;
        }

        /**
         * @var $mediaItem tubepress_api_media_MediaItem
         */
        $mediaItem = $existingTemplateVars[tubepress_api_template_VariableNames::MEDIA_ITEM];

        /**
         * @var $html string
         */
        $html         = $event->getSubject();
        $asyncObjName = $this->_asyncJsObjectName;
        $jsPath       = $this->_jsPath;
        $domId        = 'tubepress-player-' . mt_rand();
        $html         = $this->_addDomIdToHtml($html, $domId);
        $itemId       = $mediaItem->getId();
        $final        = $html . <<<EOT
<script type="text/javascript">
   var tubePressDomInjector = tubePressDomInjector || [], $asyncObjName = $asyncObjName || [];
       tubePressDomInjector.push(['loadPlayerApiJs']);
       tubePressDomInjector.push(['loadJs', '$jsPath', true ]);
       $asyncObjName.push(['register', '$itemId', '$domId' ]);
</script>
EOT;

        $event->setSubject($final);
    }

    private function _addDomIdToHtml($html, $domId)
    {
        return preg_replace('~(<iframe\s+[^>]+)>~', "\${1} id=\"$domId\" name=\"$domId\">", $html);
    }
}

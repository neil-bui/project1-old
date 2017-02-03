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

class tubepress_pro_player_impl_listeners_DetachedPlayerListener implements tubepress_spi_player_PlayerLocationInterface
{
    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_template_TemplatingInterface
     */
    private $_templating;

    /**
     * @var tubepress_api_media_CollectorInterface
     */
    private $_collector;

    /**
     * @var tubepress_api_http_RequestParametersInterface
     */
    private $_requestParams;

    public function __construct(tubepress_api_options_ContextInterface        $context,
                                tubepress_api_template_TemplatingInterface    $templating,
                                tubepress_api_media_CollectorInterface        $collector,
                                tubepress_api_http_RequestParametersInterface $requestParams)
    {
        $this->_context       = $context;
        $this->_templating    = $templating;
        $this->_collector     = $collector;
        $this->_requestParams = $requestParams;
    }

    public function onHtmlGeneration(tubepress_api_event_EventInterface $event)
    {
        if ($this->_context->get(tubepress_api_options_Names::HTML_OUTPUT) !== tubepress_api_options_AcceptableValues::OUTPUT_PLAYER) {

            return;
        }

        if ($this->_context->get(tubepress_api_options_Names::PLAYER_LOCATION) !== 'detached') {

            return;
        }

        $playerHtml = $this->_getPlayerHtml();

        $event->setSubject($playerHtml);
        $event->stopPropagation();
    }

    public function onGalleryTemplatePreRender(tubepress_api_event_EventInterface $event)
    {
        if ($this->_context->get(tubepress_api_options_Names::PLAYER_LOCATION) !== 'detached') {

            return;
        }

        $templateVars = $event->getSubject();
        $templateVars[tubepress_api_template_VariableNames::PLAYER_HTML] = '';
        $event->setSubject($templateVars);
    }

    /**
     * @return string The HTML for this shortcode handler.
     */
    private function _getPlayerHtml()
    {
        $pageNumber = $this->_requestParams->getParamValueAsInt('tubepress_page', 1);
        $mediaPage  = $this->_collector->collectPage($pageNumber);
        $widgetId   = $this->_context->get(tubepress_api_options_Names::HTML_GALLERY_ID);
        $mediaItems = $mediaPage->getItems();

        if (count($mediaItems) === 0) {

            return '';
        }

        $mediaItem = $mediaItems[0];

        $playerTemplateVars = array(
            tubepress_api_template_VariableNames::MEDIA_ITEM     => $mediaItem,
            tubepress_api_template_VariableNames::HTML_WIDGET_ID => $widgetId,
        );

        return $this->_templating->renderTemplate('gallery/player/static', $playerTemplateVars);
    }

    /**
     * @return string The name of this player location.
     *
     * @api
     * @since 4.0.0
     */
    public function getName()
    {
        return 'detached';
    }

    /**
     * @return string The display name of this player location.
     *
     * @api
     * @since 4.0.0
     */
    public function getUntranslatedDisplayName()
    {
        return 'in a "detached" location (see the documentation)';   //>(translatable)<
    }

    /**
     * @return string The template name that this player location uses when it is loaded
     *                statically on a gallery page, or null if not required on static page load.
     *
     * @api
     * @since 4.0.0
     */
    public function getStaticTemplateName()
    {
        return 'gallery/players/detached/static';
    }

    /**
     * @return string The template name that this player location uses when it is loaded
     *                dynamically via Ajax, or null if not used via Ajax.
     *
     * @api
     * @since 4.0.0
     */
    public function getAjaxTemplateName()
    {
        return 'gallery/players/detached/ajax';
    }

    /**
     * Get the data required to populate the invoking HTML anchor.
     *
     * @param tubepress_api_media_MediaItem $mediaItem
     *
     * @return array An associative array where the keys are HTML <a> attribute names and the values are
     *               the corresponding attribute values. May be empty nut never null.
     *
     * @api
     * @since 4.0.0
     */
    public function getAttributesForInvocationAnchor(tubepress_api_media_MediaItem $mediaItem)
    {
        return array();
    }
}
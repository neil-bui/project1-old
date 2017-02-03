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
class tubepress_pro_dailymotion_impl_listeners_ProPlayerListener
{
    private static $_URL_PARAM_ENABLE_API = 'api';
    private static $_URL_PARAM_ORIGIN     = 'origin';

    private static $_URL_PARAM_CONTROLS         = 'controls';
    private static $_URL_PARAM_ENDSCREEN_ENABLE = 'endscreen-enable';
    private static $_URL_PARAM_QUALITY          = 'quality';
    private static $_URL_PARAM_SHARING_ENABLE   = 'sharing-enable';
    private static $_URL_PARAM_UI_HIGHLIGHT     = 'ui-highlight';
    private static $_URL_PARAM_UI_LOGO          = 'ui-logo';
    private static $_URL_PARAM_UI_THEME         = 'ui-theme';

    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_util_LangUtilsInterface
     */
    private $_langUtils;

    /**
     * @var tubepress_api_url_UrlFactoryInterface
     */
    private $_urlFactory;

    public function __construct(tubepress_api_options_ContextInterface $context,
                                tubepress_api_util_LangUtilsInterface  $langUtils,
                                tubepress_api_url_UrlFactoryInterface  $urlFactory)
    {
        $this->_context    = $context;
        $this->_langUtils  = $langUtils;
        $this->_urlFactory = $urlFactory;
    }

    public function onEmbeddedTemplate(tubepress_api_event_EventInterface $event)
    {
        $templateVars = $event->getSubject();

        /**
         * @var $dataUrl tubepress_api_url_UrlInterface
         */
        $dataUrl = $templateVars[tubepress_api_template_VariableNames::EMBEDDED_DATA_URL];
        $query   = $dataUrl->getQuery();

        $showControls   = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_PLAYER_SHOW_CONTROLS);
        $showEndScreen  = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_PLAYER_SHOW_ENDSCREEN);
        $quality        = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_PLAYER_QUALITY);
        $showSharing    = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_PLAYER_SHOW_SHARING);
        $colorHighlight = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_PLAYER_COLOR);
        $showLogo       = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_PLAYER_SHOW_LOGO);
        $theme          = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_PLAYER_THEME);
        $origin         = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_PLAYER_ORIGIN_DOMAIN);

        $query->set(self::$_URL_PARAM_CONTROLS,         $this->_langUtils->booleanToStringOneOrZero($showControls));
        $query->set(self::$_URL_PARAM_ENDSCREEN_ENABLE, $this->_langUtils->booleanToStringOneOrZero($showEndScreen));
        $query->set(self::$_URL_PARAM_QUALITY,          $quality);
        $query->set(self::$_URL_PARAM_SHARING_ENABLE,   $this->_langUtils->booleanToStringOneOrZero($showSharing));
        $query->set(self::$_URL_PARAM_UI_LOGO,          $this->_langUtils->booleanToStringOneOrZero($showLogo));
        $query->set(self::$_URL_PARAM_UI_THEME,         $theme);

        if (!$origin) {

            $currentUrl = $this->_urlFactory->fromCurrent();
            $origin     = $currentUrl->getScheme() . '://' . $currentUrl->getHost();
        }

        if (strcasecmp($colorHighlight, 'ffcc33') !== 0) {

            $query->set(self::$_URL_PARAM_UI_HIGHLIGHT, $colorHighlight);
        }

        $query->set(self::$_URL_PARAM_ORIGIN,     $origin);
        $query->set(self::$_URL_PARAM_ENABLE_API, '1');

        $templateVars[tubepress_api_template_VariableNames::EMBEDDED_DATA_URL] = $dataUrl;

        $event->setSubject($templateVars);
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
        $html   = $event->getSubject();
        $itemId = $mediaItem->getId();
        $domId  = $this->_getIdFromHtml($html);
        $final  = $html . <<<EOT
<script type="text/javascript">
   var tubePressDomInjector = tubePressDomInjector || [], tubePressDailymotionPlayerApi = tubePressDailymotionPlayerApi || [];
       tubePressDomInjector.push(['loadPlayerApiJs']);
       tubePressDomInjector.push(['loadJs', 'web/js/dailymotion-player-api.js', true ]);
       tubePressDailymotionPlayerApi.push(['register', '$itemId', '$domId' ]);
</script>
EOT;

        $event->setSubject($final);
    }

    private function _getIdFromHtml($html)
    {
        preg_match_all('~\s+id="([^"]+)"\s~', $html, $matches);

        return $matches[1][0];
    }
}
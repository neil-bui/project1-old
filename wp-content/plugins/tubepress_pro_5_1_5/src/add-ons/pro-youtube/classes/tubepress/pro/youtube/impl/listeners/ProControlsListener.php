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
class tubepress_pro_youtube_impl_listeners_ProControlsListener
{
    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_util_LangUtilsInterface
     */
    private $_langUtils;

    public function __construct(tubepress_api_options_ContextInterface $context,
                                tubepress_api_util_LangUtilsInterface  $langUtils)
    {
        $this->_context   = $context;
        $this->_langUtils = $langUtils;
    }

    public function onEmbeddedTemplate(tubepress_api_event_EventInterface $event)
    {
        $templateVars = $event->getSubject();

        /**
         * @var $dataUrl tubepress_api_url_UrlInterface
         */
        $dataUrl = $templateVars[tubepress_api_template_VariableNames::EMBEDDED_DATA_URL];
        $query   = $dataUrl->getQuery();

        $theme        = $this->_context->get(tubepress_youtube3_api_Constants::OPTION_THEME);
        $annotations  = $this->_context->get(tubepress_youtube3_api_Constants::OPTION_SHOW_ANNOTATIONS);
        $cc           = $this->_context->get(tubepress_youtube3_api_Constants::OPTION_CLOSED_CAPTIONS);
        $controls     = $this->_context->get(tubepress_youtube3_api_Constants::OPTION_SHOW_CONTROLS);
        $disableKeys  = $this->_context->get(tubepress_youtube3_api_Constants::OPTION_DISABLE_KEYBOARD);

        if ($cc) {

            $query->set('cc_load_policy', '1');
        }

        $query->set('controls',       self::_getControlsValue($controls));
        $query->set('disablekb',      $this->_langUtils->booleanToStringOneOrZero($disableKeys));
        $query->set('theme',          self::_getThemeValue($theme));
        $query->set('iv_load_policy', self::_getAnnotationsValue($annotations));

        $templateVars[tubepress_api_template_VariableNames::EMBEDDED_DATA_URL] = $dataUrl;

        $event->setSubject($templateVars);
     }

    private static function _getAnnotationsValue($raw)
    {
        return $raw ? 1 : 3;
    }

    private static function _getThemeValue($raw)
    {
        if ($raw === tubepress_youtube3_api_Constants::PLAYER_THEME_LIGHT) {

            return 'light';
        }

        return 'dark';
    }

    private static function _getControlsValue($raw)
    {
        switch ($raw) {

            case tubepress_youtube3_api_Constants::CONTROLS_HIDE:

                return 0;

            case tubepress_youtube3_api_Constants::CONTROLS_SHOW_DELAYED_FLASH:

                return 2;

            default:

                return 1;
        }
    }
}
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

class tubepress_pro_player_ioc_ProPlayerExtension implements tubepress_spi_ioc_ContainerExtensionInterface
{
    /**
     * Allows extensions to load services into the TubePress IOC container.
     *
     * @param tubepress_api_ioc_ContainerBuilderInterface $containerBuilder A tubepress_api_ioc_ContainerInterface instance.
     *
     * @return void
     *
     * @api
     * @since 3.1.0
     */
    public function load(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $this->_registerPlayerLocations($containerBuilder);
        $this->_registerTemplatePathProvider($containerBuilder);
        $this->_registerListeners($containerBuilder);
    }

    private function _registerListeners(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $containerBuilder->register(
            'tubepress_pro_player_impl_listeners_DetachedPlayerListener',
            'tubepress_pro_player_impl_listeners_DetachedPlayerListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_template_TemplatingInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_media_CollectorInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_http_RequestParametersInterface::_))
         ->addTag('tubepress_spi_player_PlayerLocationInterface')
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::HTML_GENERATION,
            'method'   => 'onHtmlGeneration',
            'priority' => 94000,
        ))->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::TEMPLATE_PRE_RENDER . '.gallery/main',
            'method'   => 'onGalleryTemplatePreRender',
            'priority' => 93000,
        ));
    }

    private function _registerPlayerLocations(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $containerBuilder->register(
            'tubepress_player_impl_JsPlayerLocation__fancybox',
            'tubepress_player_impl_JsPlayerLocation'
        )->addArgument(tubepress_api_options_AcceptableValues::PLAYER_LOC_FANCYBOX)
            ->addArgument('with Fancybox')                    //>(translatable)<
            ->addArgument('gallery/players/fancybox/static')
            ->addArgument('gallery/players/fancybox/ajax')
            ->addTag('tubepress_spi_player_PlayerLocationInterface');

        $containerBuilder->register(
            'tubepress_player_impl_JsPlayerLocation__fancybox2',
            'tubepress_player_impl_JsPlayerLocation'
        )->addArgument(tubepress_api_options_AcceptableValues::PLAYER_LOC_FANCYBOX2)
            ->addArgument('with Fancybox v2')                    //>(translatable)<
            ->addArgument('gallery/players/fancybox2/static')
            ->addArgument('gallery/players/fancybox2/ajax')
            ->addTag('tubepress_spi_player_PlayerLocationInterface');

        $containerBuilder->register(
            'tubepress_player_impl_JsPlayerLocation__tinybox',
            'tubepress_player_impl_JsPlayerLocation'
        )->addArgument(tubepress_api_options_AcceptableValues::PLAYER_LOC_TINYBOX)
            ->addArgument('with TinyBox')                    //>(translatable)<
            ->addArgument('gallery/players/tinybox/static')
            ->addArgument('gallery/players/tinybox/ajax')
            ->addTag('tubepress_spi_player_PlayerLocationInterface');
    }

    private function _registerTemplatePathProvider(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $containerBuilder->register(
            'tubepress_api_template_BasePathProvider__pro_players',
            'tubepress_api_template_BasePathProvider'
        )->addArgument(array(
            TUBEPRESS_ROOT . '/src/add-ons/pro-player/templates'
        ))->addTag('tubepress_spi_template_PathProviderInterface');
    }
}
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

class tubepress_pro_vimeo_ioc_ProVimeoExtension implements tubepress_spi_ioc_ContainerExtensionInterface
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
        $containerBuilder->register(

            'tubepress_pro_playerapi_impl_listeners_PlayerApiJsListener__vimeo',
            'tubepress_pro_playerapi_impl_listeners_PlayerApiJsListener'
        )->addArgument('tubePressVimeoPlayerApi')
         ->addArgument('web/js/vimeo-player-api.js')
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::TEMPLATE_POST_RENDER . '.single/embedded/vimeo_iframe',
            'method'   => 'onEmbeddedHtml',
            'priority' => 100000
        ));

        $containerBuilder->register(
            'tubepress_pro_vimeo_impl_listeners_ProHttpItemListener',
            'tubepress_pro_vimeo_impl_listeners_ProHttpItemListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_util_StringUtilsInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_array_ArrayReaderInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
             'event'    => tubepress_api_event_Events::MEDIA_ITEM_HTTP_NEW . '.vimeo_v3',
             'method'   => 'onVideoConstruction',
             'priority' => 98000
        ));
    }
}
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
 *
 */
class tubepress_pro_dailymotion_ioc_ProDailymotionExtension implements tubepress_spi_ioc_ContainerExtensionInterface
{
    /**
     * Called during construction of the TubePress service container. If an add-on intends to add
     * services to the container, it should do so here. The incoming `tubepress_api_ioc_ContainerBuilderInterface`
     * will be completely empty, and after this method is executed will be merged into the primary service container.
     *
     * @param tubepress_api_ioc_ContainerBuilderInterface $containerBuilder An empty `tubepress_api_ioc_ContainerBuilderInterface` instance.
     *
     * @return void
     *
     * @api
     * @since 4.0.0
     */
    public function load(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $containerBuilder->register(
            'tubepress_pro_dailymotion_impl_listeners_ProPlayerListener',
            'tubepress_pro_dailymotion_impl_listeners_ProPlayerListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_util_LangUtilsInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_url_UrlFactoryInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::TEMPLATE_PRE_RENDER . '.single/embedded/dailymotion_iframe',
            'method'   => 'onEmbeddedTemplate',
            'priority' => 100000
        ))->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::TEMPLATE_POST_RENDER . '.single/embedded/dailymotion_iframe',
            'method'   => 'onEmbeddedHtml',
            'priority' => 100000
        ));

        $containerBuilder->register(
            'tubepress_pro_dailymotion_impl_listeners_ProUrlListener',
            'tubepress_pro_dailymotion_impl_listeners_ProUrlListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_util_LangUtilsInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_url_UrlFactoryInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::MEDIA_PAGE_HTTP_URL . '.dailymotion',
            'method'   => 'onPageUrl',
            'priority' => 100000))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
             'event'    => tubepress_api_event_Events::MEDIA_ITEM_HTTP_URL . '.dailymotion',
             'method'   => 'onItemUrl',
             'priority' => 100000
         ));
    }
}
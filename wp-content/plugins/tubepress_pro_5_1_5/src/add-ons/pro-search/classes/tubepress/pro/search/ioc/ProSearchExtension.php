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
 * Registers all the Pro Core services.
 */
class tubepress_pro_search_ioc_ProSearchExtension implements tubepress_spi_ioc_ContainerExtensionInterface
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
            'tubepress_pro_search_impl_listeners_AjaxSearchListener',
            'tubepress_pro_search_impl_listeners_AjaxSearchListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_template_TemplatingInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_event_EventDispatcherInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ReferenceInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'     => tubepress_api_event_Events::HTML_GENERATION,
            'method'   => 'onHtmlGeneration',
            'priority' => 100000,
        ));

        $containerBuilder->register(
            'tubepress_api_template_BasePathProvider__pro_search',
            'tubepress_api_template_BasePathProvider'
        )->addArgument(array(
            TUBEPRESS_ROOT . '/src/add-ons/pro-search/templates',
        ))->addTag('tubepress_spi_template_PathProviderInterface');
    }
}
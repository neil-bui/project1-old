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

class tubepress_pro_cache_html_ioc_ProHtmlCacheExtension implements tubepress_spi_ioc_ContainerExtensionInterface
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
            'tubepress_pro_cache_html_impl_listeners_HtmlCacheListener',
            'tubepress_pro_cache_html_impl_listeners_HtmlCacheListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_PersistenceInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference('html_cache_pool'))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_http_RequestParametersInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::HTML_GENERATION,
            'method'   => 'onHtmlGenerationPre',
            'priority' => 200000
        ))->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::HTML_GENERATION_POST,
            'method'   => 'onHtmlGenerationPost',
            'priority' => 5000
        ));

        $containerBuilder->register(
            'tubepress_pro_cache_html_impl_FilesystemCacheBuilder',
            'tubepress_pro_cache_html_impl_FilesystemCacheBuilder'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_boot_BootSettingsInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_));

        $containerBuilder->register(
            'html_cache_pool',
            'Stash\Pool'
        )->addMethodCall('setDriver', array(new tubepress_api_ioc_Reference('html_cache_driver')));

        $containerBuilder->register(
            'html_cache_driver',
            'Stash\Interfaces\DriverInterface'
        )->setFactoryService('tubepress_pro_cache_html_impl_FilesystemCacheBuilder')
         ->setFactoryMethod('buildFilesystemDriver');
    }
}
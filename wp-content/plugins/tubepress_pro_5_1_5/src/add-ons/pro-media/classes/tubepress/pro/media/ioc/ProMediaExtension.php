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

class tubepress_pro_media_ioc_ProMediaExtension implements tubepress_spi_ioc_ContainerExtensionInterface
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
            'tubepress_pro_media_impl_listeners_LegacyMultipleSourcesCollectionListener',
            'tubepress_pro_media_impl_listeners_LegacyMultipleSourcesCollectionListener'
        )->addArgument(new tubepress_api_ioc_Reference('tubepress_pro_media_impl_listeners_LegacyMultipleSourcesCollectionListener.inner'))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ReferenceInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_event_EventDispatcherInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::TAGGED_SERVICES_CONSUMER, array(
            'tag' => tubepress_spi_media_MediaProviderInterface::__,
            'method' => 'setMediaProviders',
        ))->setDecoratedService('tubepress_media_impl_listeners_CollectionListener');

        $containerBuilder->register(
            'tubepress_pro_media_impl_listeners_ModernMultipleSourcesCollectionListener',
            'tubepress_pro_media_impl_listeners_ModernMultipleSourcesCollectionListener'
        )->addArgument(new tubepress_api_ioc_Reference('tubepress_pro_media_impl_listeners_ModernMultipleSourcesCollectionListener.inner'))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_event_EventDispatcherInterface::_))
         ->setDecoratedService('tubepress_pro_media_impl_listeners_LegacyMultipleSourcesCollectionListener');
    }
}
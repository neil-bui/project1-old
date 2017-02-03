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

class tubepress_pro_gallery_ioc_ProGalleryExtension implements tubepress_spi_ioc_ContainerExtensionInterface
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
            'tubepress_pro_gallery_impl_listeners_ProGalleryListener',
            'tubepress_pro_gallery_impl_listeners_ProGalleryListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::TEMPLATE_POST_RENDER . '.gallery/main',
            'method'   => 'onPostGalleryTemplateRender',
            'priority' => 99000,
        ))->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::GALLERY_INIT_JS,
            'method'   => 'onGalleryInitJs',
            'priority' => 92000,
        ));
    }
}
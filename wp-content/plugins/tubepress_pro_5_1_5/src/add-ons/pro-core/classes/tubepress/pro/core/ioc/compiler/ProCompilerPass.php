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
class tubepress_pro_core_ioc_compiler_ProCompilerPass implements tubepress_spi_ioc_CompilerPassInterface
{
    /**
     * Provides add-ons with the ability to modify the TubePress IOC container
     * before it is put into production.
     *
     * @param tubepress_api_ioc_ContainerBuilderInterface $containerBuilder The core IOC container.
     *
     * @api
     * @since 3.1.0
     */
    public function process(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $this->_invoke($containerBuilder);
        $this->_markAsPro($containerBuilder);
    }

    private function _invoke(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $services = array(
            'tubepress_dailymotion_impl_media_FeedHandler',
            'tubepress_youtube3_impl_media_FeedHandler',
            'tubepress_vimeo3_impl_media_FeedHandler',
            'tubepress_media_impl_listeners_PageListener',
        );

        foreach ($services as $serviceId) {

            if ($containerBuilder->hasDefinition($serviceId)) {

                $def = $containerBuilder->getDefinition($serviceId);
                $def->addMethodCall('__invoke');
            }
        }
    }

    private function _markAsPro(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        if ($containerBuilder->hasDefinition(tubepress_api_environment_EnvironmentInterface::_)) {

            $def = $containerBuilder->getDefinition(tubepress_api_environment_EnvironmentInterface::_);
            $def->addMethodCall('markAsPro');
        }
    }
}
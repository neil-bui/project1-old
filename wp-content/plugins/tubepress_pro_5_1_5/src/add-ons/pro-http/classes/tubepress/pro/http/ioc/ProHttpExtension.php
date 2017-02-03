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

class tubepress_pro_http_ioc_ProHttpExtension implements tubepress_spi_ioc_ContainerExtensionInterface
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
            'tubepress_pro_http_impl_listeners_ShortcodeAjaxListener',
            'tubepress_pro_http_impl_listeners_ShortcodeAjaxListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_http_RequestParametersInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_html_HtmlGeneratorInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_http_ResponseCodeInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::HTTP_AJAX . '.shortcode',
            'method'   => 'onAjax',
            'priority' => 100000,
        ));
    }
}
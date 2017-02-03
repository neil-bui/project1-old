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
class tubepress_pro_wordpress_ioc_ProWordPressExtension implements tubepress_spi_ioc_ContainerExtensionInterface
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
        if (!defined('ABSPATH')) {

            /*
             * Skip all this if we're not in WP.
             */
            return;
        }

        $containerBuilder->register(
            'tubepress_pro_wordpress_impl_listeners_shortcode_ShortcodeListener',
            'tubepress_pro_wordpress_impl_listeners_shortcode_ShortcodeListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_shortcode_ShortcodeExtractorInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(

             'event'    => tubepress_wordpress_api_Constants::SHORTCODE_PARSED,
             'method'   => 'onShortcode',
             'priority' => 100000,
        ));

        $containerBuilder->register(
            'tubepress_pro_wordpress_impl_listeners_media_AutoPostMediaListener',
            'tubepress_pro_wordpress_impl_listeners_media_AutoPostMediaListener'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_wordpress_impl_wp_WpFunctions::_))
         ->addArgument(new tubepress_api_ioc_Reference('tubepress_wordpress_impl_wp_ResourceRepository'))
         ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
            'event'    => tubepress_api_event_Events::MEDIA_PAGE_NEW,
            'method'   => 'onNewMediaPage',
            'priority' => 92500,
        ));

        $containerBuilder->register(
            'tubepress_pro_wordpress_impl_player_AutoPostPlayerLocation',
            'tubepress_pro_wordpress_impl_player_AutoPostPlayerLocation'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_wordpress_impl_wp_WpFunctions::_))
         ->addTag('tubepress_spi_player_PlayerLocationInterface');
    }
}
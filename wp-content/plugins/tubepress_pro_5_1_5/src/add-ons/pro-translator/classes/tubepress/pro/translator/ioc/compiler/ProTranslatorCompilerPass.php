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
class tubepress_pro_translator_ioc_compiler_ProTranslatorCompilerPass implements tubepress_spi_ioc_CompilerPassInterface
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
        if (!$containerBuilder->has(tubepress_api_translation_TranslatorInterface::_)) {

            $containerBuilder->register(
                tubepress_api_translation_TranslatorInterface::_,
                'tubepress_pro_translator_impl_GettextTranslator'
            )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
             ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ContextInterface::_));
        }
    }
}
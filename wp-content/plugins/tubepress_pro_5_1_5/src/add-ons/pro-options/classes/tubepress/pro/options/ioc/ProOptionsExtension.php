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

class tubepress_pro_options_ioc_ProOptionsExtension implements tubepress_spi_ioc_ContainerExtensionInterface
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
        $this->_registerOptions($containerBuilder);
        $this->_registerListeners($containerBuilder);
    }

    private function _registerOptions(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $containerBuilder->register(
            'tubepress_api_options_Reference__pro_options',
            'tubepress_api_options_Reference'
        )->addTag(tubepress_api_options_ReferenceInterface::_)
         ->addArgument(array(

            tubepress_api_options_Reference::PROPERTY_DEFAULT_VALUE => array(
                tubepress_api_options_Names::LANG                  => 'en_US',
                tubepress_api_options_Names::SEARCH_RESULTS_DOM_ID => null,
            ),

            tubepress_api_options_Reference::PROPERTY_UNTRANSLATED_LABEL => array(
                tubepress_api_options_Names::LANG => 'Preferred language',
            ),

        ))->addArgument(array(

            tubepress_api_options_Reference::PROPERTY_PRO_ONLY => array(
                tubepress_api_options_Names::LANG,
                tubepress_api_options_Names::SEARCH_RESULTS_DOM_ID,
            ),
        ));
    }

    private function _registerListeners(tubepress_api_ioc_ContainerBuilderInterface $containerBuilder)
    {
        $containerBuilder->register(
            'tubepress_pro_options_impl_listeners_ProOptionValidity',
            'tubepress_pro_options_impl_listeners_ProOptionValidity'
        )->addArgument(new tubepress_api_ioc_Reference(tubepress_api_log_LoggerInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_options_ReferenceInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_event_EventDispatcherInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference(tubepress_api_translation_TranslatorInterface::_))
         ->addArgument(new tubepress_api_ioc_Reference('tubepress_pro_options_impl_listeners_ProOptionValidity.inner'))
         ->addTag(tubepress_api_ioc_ServiceTags::TAGGED_SERVICES_CONSUMER, array(
            'tag' => tubepress_spi_media_MediaProviderInterface::__,
            'method' => 'setMediaProviders',
        ))->setDecoratedService('tubepress_options_impl_listeners_BasicOptionValidity');

        $fixedValuesMap = array(
            tubepress_api_options_Names::LANG => array(
                'en_US'    => 'English (US)',
                'ar'       => 'Arabic',
                'zh_CN'    => 'Chinese (Simplified)',
                'zh_TW'    => 'Chinese (Traditional)',
                'nl_NL'    => 'Dutch',
                'fi'       => 'Finnish',
                'fr_FR'    => 'French',
                'de_DE'    => 'German',
                'el'       => 'Greek',
                'he_IL'    => 'Hebrew',
                'hi_IN'    => 'Hindi',
                'it_IT'    => 'Italian',
                'ja'       => 'Japanese',
                'ko_KR'    => 'Korean',
                'nb_NO'    => 'Norwegian Bokmål',
                'fa_IR'    => 'Persian',
                'pl_PL'    => 'Polish',
                'pt_BR'    => 'Portuguese (Brazil)',
                'ru_RU'    => 'Russian',
                'sk_SK'    => 'Slovak',
                'es_MX'    => 'Spanish (Mexico)',
                'es_ES'    => 'Spanish (Spain)',
                'sv_SE'    => 'Swedish',
            ),
        );

        foreach ($fixedValuesMap as $optionName => $valuesMap) {
            $containerBuilder->register(
                'fixed_values.' . $optionName,
                'tubepress_api_options_listeners_FixedValuesListener'
            )->addArgument($valuesMap)
             ->addTag(tubepress_api_ioc_ServiceTags::EVENT_LISTENER, array(
                'priority' => 100000,
                'event'    => tubepress_api_event_Events::OPTION_ACCEPTABLE_VALUES . ".$optionName",
                'method'   => 'onAcceptableValues'
            ));
        }
    }
}
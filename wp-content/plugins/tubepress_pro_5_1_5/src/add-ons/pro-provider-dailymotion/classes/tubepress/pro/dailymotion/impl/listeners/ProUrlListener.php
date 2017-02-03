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
 */
class tubepress_pro_dailymotion_impl_listeners_ProUrlListener
{
    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_util_LangUtilsInterface
     */
    private $_langUtils;

    /**
     * @var tubepress_api_url_UrlFactoryInterface
     */
    private $_urlFactory;

    public function __construct(tubepress_api_options_ContextInterface $context,
                                tubepress_api_util_LangUtilsInterface  $langUtils,
                                tubepress_api_url_UrlFactoryInterface  $urlFactory)
    {
        $this->_context    = $context;
        $this->_langUtils  = $langUtils;
        $this->_urlFactory = $urlFactory;
    }

    public function onPageUrl(tubepress_api_event_EventInterface $event)
    {
        /**
         * @var $url tubepress_api_url_UrlInterface
         */
        $url   = $event->getSubject();
        $query = $url->getQuery();

        $this->_addGlobalParams($query);
        $this->_addFilters($query);

        $event->setSubject($url);
    }

    public function onItemUrl(tubepress_api_event_EventInterface $event)
    {
        /**
         * @var $url tubepress_api_url_UrlInterface
         */
        $url   = $event->getSubject();
        $query = $url->getQuery();

        $this->_addGlobalParams($query);

        $event->setSubject($url);
    }

    private function _addFilters(tubepress_api_url_QueryInterface $query)
    {
        $country           = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_COUNTRY);
        $detectedLanguage  = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_LANGUAGE_DETECTED);
        $declaredLanguages = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_LANGUAGES_DECLARED);
        $blacklist         = $this->_context->get(tubepress_api_options_Names::FEED_ITEM_ID_BLACKLIST);
        $featuredOnly      = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_FEATURED_ONLY);
        $onlyGenre         = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_GENRE);
        $notGenre          = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_NO_GENRE);
        $hdOnly            = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_HD_ONLY);
        $liveFilter        = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_LIVE_FILTER);
        $longerThan        = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_LONGER_THAN);
        $shorterThan       = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_SHORTER_THAN);
        $premiumFilter     = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_PREMIUM_FILTER);
        $ownersFilter      = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_OWNERS_FILTER);
        $search            = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_SEARCH);
        $strongTags        = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_TAGS_STRONG);
        $tags              = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_TAGS);
        $partnerFilter     = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_PARTNER_FILTER);
        $flags             = array();

        if ($country) {

            $query->set('country', $country);
        }

        if ($detectedLanguage && $detectedLanguage !== 'none') {

            $query->set('detected_language', $detectedLanguage);
        }

        if ($declaredLanguages) {

            $query->set('languages', $this->_implodeCsv($declaredLanguages));
        }

        if ($blacklist) {

            $query->set('exclude_ids', $this->_implodeCsv($blacklist));
        }

        if ($featuredOnly) {

            $flags[] = 'featured';
        }

        if ($onlyGenre) {

            $query->set('genre', $onlyGenre);
        }

        if ($notGenre) {

            $query->set('nogenre', $notGenre);
        }

        if ($hdOnly) {

            $flags[] = 'hd';
        }

        switch ($liveFilter) {

            case tubepress_dailymotion_api_Constants::FILTER_LIVE_LIVE_OFF:

                $flags[] = 'live_offair';
                break;

            case tubepress_dailymotion_api_Constants::FILTER_LIVE_LIVE_ON:

                $flags[] = 'live_onair';
                break;

            case tubepress_dailymotion_api_Constants::FILTER_LIVE_LIVE_ONLY:

                $flags[] = 'live';
                break;

            case tubepress_dailymotion_api_Constants::FILTER_LIVE_LIVE_UPCOMING:

                $flags[] = 'live_upcoming';
                break;

            case tubepress_dailymotion_api_Constants::FILTER_LIVE_NON_LIVE:

                $flags[] = 'no_live';
                break;
        }

        if ($longerThan > 0) {

            $query->set('longer_than', $longerThan);
        }

        if ($shorterThan) {

            $query->set('shorter_than', $shorterThan);
        }

        switch ($premiumFilter) {

            case tubepress_dailymotion_api_Constants::FILTER_PREMIUM_NON_PREMIUM_ONLY:

                $flags[] = 'no_premium';
                break;

            case tubepress_dailymotion_api_Constants::FILTER_PREMIUM_PREMIUM_ONLY:

                $flags[] = 'premium';
                break;
        }

        if ($ownersFilter) {

            $query->set('owners', $this->_implodeCsv($ownersFilter));
        }

        if ($search && $this->_context->get(tubepress_api_options_Names::GALLERY_SOURCE) !== tubepress_dailymotion_api_Constants::GALLERY_SOURCE_SEARCH) {

            $query->set('search', $search);
        }

        if ($strongTags && $this->_context->get(tubepress_api_options_Names::GALLERY_SOURCE) !== tubepress_dailymotion_api_Constants::GALLERY_SOURCE_TAG) {

            $query->set('strongtags', $this->_implodeCsv($strongTags));
        }

        if ($tags) {

            $query->set('tags', $this->_implodeCsv($tags));
        }

        switch ($partnerFilter) {

            case tubepress_dailymotion_api_Constants::FILTER_PARTNER_NON_PARTNER_ONLY:

                $flags[] = 'ugc';
                break;

            case tubepress_dailymotion_api_Constants::FILTER_PARTNER_PARTNER_ONLY:

                $flags[] = 'partner';
                break;
        }

        if (count($flags) > 0) {

            $query->set('flags', implode(',', $flags));
        }
    }

    private function _addGlobalParams(tubepress_api_url_QueryInterface $query)
    {
        $familyFilter = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_FAMILY_FILTER);
        $ssl          = $this->_context->get(tubepress_api_options_Names::HTML_HTTPS);
        $locale       = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_FEED_LOCALE);
        $thumbRatio   = $this->_context->get(tubepress_dailymotion_api_Constants::OPTION_THUMBS_RATIO);

        $query->set('family_filter',   $familyFilter ? 'true' : 'false')
              ->set('ssl_assets',      $ssl ? 'true' : 'false')
              ->set('thumbnail_ratio', $thumbRatio);

        if ($locale && $locale !== 'none') {

            $query->set('localization', $locale);
        }
    }

    private function _implodeCsv($incoming)
    {
        $incoming = preg_split('/\s*,\s*/', $incoming);
        $incoming = implode(',', $incoming);

        return $incoming;
    }
}
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
 * Video provider that can handle multiple sources.
 */
class tubepress_pro_media_impl_listeners_ModernMultipleSourcesCollectionListener
{
    /**
     * @var tubepress_pro_media_impl_listeners_LegacyMultipleSourcesCollectionListener
     */
    private $_delegateCollectionListener;

    /**
     * @var tubepress_api_log_LoggerInterface
     */
    private $_logger;

    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_event_EventDispatcherInterface
     */
    private $_eventDispatcher;

    /**
     * @var bool
     */
    private $_shouldLog;

    /**
     * @var int
     */
    private $_largestPageCountSeen;

    public function __construct(tubepress_pro_media_impl_listeners_LegacyMultipleSourcesCollectionListener $delegateListener,
                                tubepress_api_log_LoggerInterface                                          $logger,
                                tubepress_api_options_ContextInterface                                     $context,
                                tubepress_api_event_EventDispatcherInterface                               $eventDispatcher)
    {
        $this->_delegateCollectionListener = $delegateListener;
        $this->_logger                     = $logger;
        $this->_context                    = $context;
        $this->_eventDispatcher            = $eventDispatcher;
        $this->_shouldLog                  = $logger->isEnabled();
    }

    public function onMediaPageRequest(tubepress_api_event_EventInterface $event)
    {
        $ephemeralOptions = $this->_context->getEphemeralOptions();

        if (isset($ephemeralOptions[tubepress_api_options_Names::GALLERY_SOURCE])) {

            if ($this->_shouldLog) {

                $this->_logDebug('Explicit mode set in ephemeral options. Delegating.');
            }

            $this->_delegateCollectionListener->onMediaPageRequest($event);

            return;
        }

        //either mode is not in the context, or mode is set to multi

        $jsonEncodedSources = $this->_context->get(tubepress_api_options_Names::SOURCES);

        if (!$jsonEncodedSources) {

            if ($this->_shouldLog) {

                $this->_logDebug('Encoded sources are falsy. Delegating.');
            }

            $this->_delegateCollectionListener->onMediaPageRequest($event);
            return;
        }

        $decodedSources = json_decode($jsonEncodedSources, true);

        if (!$decodedSources || !is_array($decodedSources)) {

            if ($this->_shouldLog) {

                $this->_logDebug('Encoded sources are empty. Delegating.');
            }

            $this->_delegateCollectionListener->onMediaPageRequest($event);
            return;
        }

        if ($this->_shouldLog) {

            $this->_logDebug('Modern multi source activated.');
        }

        $mediaPage = $this->_collectMediaPage($decodedSources, $event);

        $event->setArgument('mediaPage', $mediaPage);
    }

    public function onMediaItemRequest(tubepress_api_event_EventInterface $event)
    {
        $this->_delegateCollectionListener->onMediaItemRequest($event);
    }

    private function _collectMediaPage(array $decodedSources, tubepress_api_event_EventInterface $topLevelEvent)
    {
    	/** Save a copy of the original options. */
        $originalCustomOptions = $this->_context->getEphemeralOptions();

        /** Build the result. */
        $result = $this->__collectMediaPage($decodedSources, $topLevelEvent);

        /** Restore the original options. */
        $this->_context->setEphemeralOptions($originalCustomOptions);

        $this->_setResultsPerPageAppropriately($result);

        return $result;
    }

    private function __collectMediaPage(array $decodedSources, tubepress_api_event_EventInterface $topLevelEvent)
    {
    	$sourcesCount                = count($decodedSources);
    	$index                       = 1;
    	$toReturn                    = new tubepress_api_media_MediaPage();
        $this->_largestPageCountSeen = 0;

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('We will try to collect from %d sources', $sourcesCount));
        }

    	/** Iterate over each mode and collect the videos */
    	foreach ($decodedSources as $innerSourceArray) {

            if ($this->_shouldLog) {

                $this->_logDebug(sprintf('Starting to collect videos for source %d of %d', $index, $sourcesCount));
            }

    		try {

    			$innerMediaPage = $this->_collectSingleInnerMediaPage($innerSourceArray, $topLevelEvent);

                if ($this->_shouldLog) {

                    $this->_logDebug(sprintf('Done collecting videos for source %d of %d. For that page we got %d videos from a reported total of %d.',
                        $index++, $sourcesCount, count($innerMediaPage->getItems()), $innerMediaPage->getTotalResultCount()));
                }

                $this->_appendToMediaPage($toReturn, $innerMediaPage);

    		} catch (Exception $e) {

                $this->_logger->error('Caught exception getting videos: ' . $e->getMessage(). '. We will continue with the next mode');
    		}
    	}

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('After full collection, we now have %d videos and a reported total result count of %d',
                count($toReturn->getItems()), $toReturn->getTotalResultCount()));
        }

    	return $toReturn;
    }

    /**
     * @param array $innerSourceArray
     *
     * @return tubepress_api_media_MediaPage
     */
    private function _collectSingleInnerMediaPage(array $innerSourceArray, tubepress_api_event_EventInterface $topLevelEvent)
    {
        $originalEphemeralOptions = $this->_context->getEphemeralOptions();
        $originalEventArgs        = $topLevelEvent->getArguments();
        $toReturn                 = new tubepress_api_media_MediaPage();
        $temporaryOptions         = array_merge($originalEphemeralOptions, $innerSourceArray);

        $this->_context->setEphemeralOptions($temporaryOptions);

        $innerMode = $this->_context->get(tubepress_api_options_Names::GALLERY_SOURCE);

        try {

            $subEvent = $this->_eventDispatcher->newEventInstance($innerMode, $originalEventArgs);

            $this->_delegateCollectionListener->onMediaPageRequest($subEvent);

            $toReturn = $subEvent->getArgument('mediaPage');

        } catch (Exception $e) {

            if ($this->_shouldLog) {

                $this->_logger->error(sprintf('Caught error when collecting videos for a single source: %s', $e->getMessage()));
            }
        }

        $iterationPerPage            = $this->_context->get(tubepress_api_options_Names::FEED_RESULTS_PER_PAGE);
        $iterationTotalResultCount   = $toReturn->getTotalResultCount();
        $iterationPageCount          = intval(ceil($iterationTotalResultCount / $iterationPerPage));
        $this->_largestPageCountSeen = max($this->_largestPageCountSeen, $iterationPageCount);

        $this->_context->setEphemeralOptions($originalEphemeralOptions);

        return $toReturn;
    }

    private function _appendToMediaPage(tubepress_api_media_MediaPage $toAppendTo, tubepress_api_media_MediaPage $toBeAppended)
    {
        $firstItems  = $toAppendTo->getItems();
        $firstTotal  = $toAppendTo->getTotalResultCount();
        $secondItems = $toBeAppended->getItems();
        $secondTotal = $toBeAppended->getTotalResultCount();
        $mergedItems = array_merge($firstItems, $secondItems);
        $newTotal    = $firstTotal + $secondTotal;

        $toAppendTo->setItems($mergedItems);
        $toAppendTo->setTotalResultCount($newTotal);
    }

    private function _setResultsPerPageAppropriately(tubepress_api_media_MediaPage $page)
    {
        $totalResultCount = $page->getTotalResultCount();
        $desiredPageCount = $this->_largestPageCountSeen;

        if ($desiredPageCount < 1) {

            return;
        }

        $newResultsPerPage = ($totalResultCount / $desiredPageCount);

        $this->_context->setEphemeralOption(tubepress_api_options_Names::FEED_ADJUSTED_RESULTS_PER_PAGE, $newResultsPerPage);
    }

    private function _logDebug($msg)
    {
        $this->_logger->debug(sprintf('(Modern Multisource Listener) %s', $msg));
    }
}
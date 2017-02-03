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
class tubepress_pro_media_impl_listeners_LegacyMultipleSourcesCollectionListener
{
    /**
     * @var tubepress_media_impl_listeners_CollectionListener
     */
    private $_singleSourceCollectionListener;

    /**
     * @var tubepress_api_log_LoggerInterface
     */
    private $_logger;

    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var tubepress_api_options_ReferenceInterface
     */
    private $_optionsReference;

    /**
     * @var tubepress_api_event_EventDispatcherInterface
     */
    private $_eventDispatcher;

    /**
     * @var int
     */
    private $_largestBatchOfItemsSeenInSingleIteration;

    public function __construct(tubepress_media_impl_listeners_CollectionListener $singleSourceListener,
                                tubepress_api_log_LoggerInterface                 $logger,
                                tubepress_api_options_ContextInterface            $context,
                                tubepress_api_options_ReferenceInterface          $optionsReference,
                                tubepress_api_event_EventDispatcherInterface      $eventDispatcher)
    {
        $this->_singleSourceCollectionListener = $singleSourceListener;
        $this->_logger                         = $logger;
        $this->_context                        = $context;
        $this->_optionsReference               = $optionsReference;
        $this->_eventDispatcher                = $eventDispatcher;
    }

    public function setMediaProviders(array $mediaProviders)
    {
        $this->_singleSourceCollectionListener->setMediaProviders($mediaProviders);
    }

    public function onMediaPageRequest(tubepress_api_event_EventInterface $event)
    {
        $isDebugging = $this->_logger->isEnabled();

        /** See if we're using multiple modes */
        if (!$this->_usingMultipleModes()) {

            if ($isDebugging) {

                $this->_logDebug('Multiple sources not detected.');
            }

            $this->_singleSourceCollectionListener->onMediaPageRequest($event);
            return;
        }

        if ($isDebugging) {

            $this->_logDebug('Multiple sources detected.');
        }

        $this->_largestBatchOfItemsSeenInSingleIteration = 0;

        $result = $this->_collectVideosFromMultipleSources($isDebugging, $event);

        $this->_setResultsPerPageAppropriately($result);

        $event->setArgument('mediaPage', $result);
    }

    public function onMediaItemRequest(tubepress_api_event_EventInterface $event)
    {
        $this->_singleSourceCollectionListener->onMediaItemRequest($event);
    }

    private function _collectVideosFromMultipleSources($isDebuggingEnabled, tubepress_api_event_EventInterface $originalEvent)
    {
    	/** Save a copy of the original options. */
        $originalCustomOptions = $this->_context->getEphemeralOptions();

        /** Build the result. */
        $result = $this->__collectVideosFromMultipleSources($isDebuggingEnabled, $originalEvent);

        /** Restore the original options. */
        $this->_context->setEphemeralOptions($originalCustomOptions);

        return $result;
    }

    private function __collectVideosFromMultipleSources($isDebuggingEnabled, tubepress_api_event_EventInterface $originalEvent)
    {
    	/** Figure out which modes we're gonna run. */
    	$suppliedModeValue = $this->_context->get(tubepress_api_options_Names::GALLERY_SOURCE);
    	$modesToRun        = $this->_splitByPlusSurroundedBySpaces($suppliedModeValue);
    	$modeCount         = count($modesToRun);
    	$index             = 1;

    	$result = new tubepress_api_media_MediaPage();

        if ($isDebuggingEnabled) {

            $this->_logDebug(sprintf('Detected %d modes (%s)', $modeCount, implode(', ', $modesToRun)));
        }

    	/** Iterate over each mode and collect the videos */
    	foreach ($modesToRun as $mode) {

            if ($isDebuggingEnabled) {

                $this->_logDebug(sprintf('Start collecting videos for mode %s (%d of %d modes)', $mode, $index, $modeCount));
            }

    		try {

    			$result = $this->_createCombinedResult($mode, $originalEvent, $result, $isDebuggingEnabled);

    		} catch (Exception $e) {

                $this->_logger->error('Caught exception getting videos: ' . $e->getMessage(). '. We will continue with the next mode');
    		}

            if ($isDebuggingEnabled) {

                $this->_logDebug(sprintf('Done collecting videos for mode %s (%d of %d modes)', $mode, $index++, $modeCount));
            }
    	}

        if ($isDebuggingEnabled) {

            $this->_logDebug(sprintf('After full collection, we now have %d videos', count($result->getItems())));
        }

    	return $result;
    }

    private function _createCombinedResult($mode, tubepress_api_event_EventInterface $originalEvent, tubepress_api_media_MediaPage $resultToAppendTo, $isDebuggingEnabled)
    {
        $this->_context->setEphemeralOption(tubepress_api_options_Names::GALLERY_SOURCE, $mode);

        /** Some modes don't take a parameter */
        if (!$this->_optionsReference->optionExists($mode . 'Value')) {

        	$modeResult = $this->_collectedValuelessModeResult($mode, $originalEvent, $isDebuggingEnabled);

            $this->_recordBatchSize($modeResult);

        } else {

        	$modeResult = $this->_collectedValuefulModeResult($mode, $originalEvent, $isDebuggingEnabled);
        }

        $newCombinedResult = $this->_combineMediaPages($resultToAppendTo, $modeResult);

        return $newCombinedResult;
    }

    private function _collectedValuelessModeResult($mode, tubepress_api_event_EventInterface $originalEvent, $isDebuggingEnabled)
    {
        if ($isDebuggingEnabled) {

            $this->_logDebug(sprintf('Now collecting videos for value-less "%s" mode', $mode));
        }

        return $this->_getPageFromDelegate($mode, $originalEvent);
    }

    private function _collectedValuefulModeResult($mode, tubepress_api_event_EventInterface $originalEvent, $isDebuggingEnabled)
    {
    	$rawModeValue   = $this->_context->get($mode . 'Value');
    	$modeValueArray = $this->_splitByPlusSurroundedBySpaces($rawModeValue);
    	$modeValueCount = count($modeValueArray);
    	$index          = 1;

    	$resultToReturn = new tubepress_api_media_MediaPage();

        foreach ($modeValueArray as $modeValue) {

            if ($isDebuggingEnabled) {

                $this->_logDebug(sprintf('Start collecting videos for mode %s with value %s (%d of %d values for mode %s)', $mode, $modeValue, $index, $modeValueCount, $mode));
            }

            $this->_context->setEphemeralOption($mode . 'Value', $modeValue);

            try {

                $modeResult = $this->_getPageFromDelegate($mode, $originalEvent);

                $this->_recordBatchSize($modeResult);

                $resultToReturn = $this->_combineMediaPages($resultToReturn, $modeResult);

            } catch (Exception $e) {

                $this->_logger->error(sprintf('Problem collecting videos for mode "%s" and value "%s": %s', $mode, $modeValue, $e->getMessage()));
            }

            if ($isDebuggingEnabled) {

                $this->_logDebug(sprintf('Done collecting videos for mode %s with value %s (%d of %d values for mode %s)', $mode, $modeValue, $index++, $modeValueCount, $mode));
            }
        }

        return $resultToReturn;
    }

    private function _combineMediaPages(tubepress_api_media_MediaPage $first, tubepress_api_media_MediaPage $second)
    {
        $result = new tubepress_api_media_MediaPage();

        /** Merge the two video arrays into a single one */
        $result->setItems(array_merge($first->getItems(), $second->getItems()));

        /** The total result count is the max of the two total result counts */
        $result->setTotalResultCount($first->getTotalResultCount() + $second->getTotalResultCount());

        return $result;
    }

    private function _usingMultipleModes()
    {
        $mode = $this->_context->get(tubepress_api_options_Names::GALLERY_SOURCE);

        if (count($this->_splitByPlusSurroundedBySpaces($mode)) > 1) {

            return true;
        }

        if ($this->_optionsReference->optionExists($mode . 'Value')) {

            $modeValue = $this->_context->get($mode . 'Value');

            return strpos($modeValue, '+') !== false;
        }

        return false;
    }

    private function _splitByPlusSurroundedBySpaces($string)
    {
    	return preg_split('/\s*\+\s*/', $string);
    }

    private function _recordBatchSize(tubepress_api_media_MediaPage $page)
    {
        $this->_largestBatchOfItemsSeenInSingleIteration = max(

            $this->_largestBatchOfItemsSeenInSingleIteration,
            $page->getTotalResultCount()
        );
    }

    /**
     * OK, follow closely because this can be complicated. We need to adjust the resultsPerPage setting, otherwise
     * pagination will be completely screwed up. Here's an example
     *
     * Source 1 produces 100 videos
     * Source 2 produces 200 videos
     * Source 3 produces 300 videos
     * Source 4 produces 400 videos
     *
     * resultsPerPage was set by the user to 25
     *
     * Since there are 1000 videos, TubePress will generate pagination for 40 pages (25 * 40 = 1000). But in reality
     * there will be no more videos after the 8th page (8 * 25 = 400).
     *
     * So the solution is to record the maximum results that any provider returns. We divide that by the user's setting
     * for resultsPerPage and get a number: desired page count.
     *
     * Now we have to set resultsPerPage to a number that tricks TubePress's pagination into producing the desired page
     * count. That's easy, just take the total videos and divide it by the desired page count. In our example,
     * 1000 / 8 = 125. So resultsPerPage="125"
     */
    private function _setResultsPerPageAppropriately(tubepress_api_media_MediaPage $page)
    {
        $currentResultsPerPage = $this->_context->get(tubepress_api_options_Names::FEED_RESULTS_PER_PAGE);
        $currentResultsPerPage = intval($currentResultsPerPage);
        $maxResultsSeen        = intval($this->_largestBatchOfItemsSeenInSingleIteration);
        $totalResultCount      = intval($page->getTotalResultCount());
        $isDebugging           = $this->_logger->isEnabled();

        /**
         * Prevent a divide-by-zero.
         */
        if ($currentResultsPerPage < 1) {

            $this->_logger->error("resultsPerPage was somehow set to a non-positive integer. This should never happen!");
            return;
        }

        if ($isDebugging) {

            $this->_logDebug(sprintf('After full collection, we have %d video(s). The largest batch was %d video(s). resultsPerPage is currently set to %d.',
                $totalResultCount, $maxResultsSeen, $currentResultsPerPage));
        }

        $desiredPageCount  = ceil($maxResultsSeen / $currentResultsPerPage);
        $newResultsPerPage = intval(ceil($totalResultCount / $desiredPageCount));

        if ($isDebugging) {

            $this->_logDebug(sprintf('Now setting resultsPerPage to %d so that we have %d pages for pagination',
                $newResultsPerPage, $desiredPageCount));
        }

        $this->_context->setEphemeralOption(tubepress_api_options_Names::FEED_ADJUSTED_RESULTS_PER_PAGE, $newResultsPerPage);
    }

    private function _getPageFromDelegate($mode, tubepress_api_event_EventInterface $originalEvent)
    {
        $newEvent = $this->_eventDispatcher->newEventInstance($mode, $originalEvent->getArguments());

        $this->_singleSourceCollectionListener->onMediaPageRequest($newEvent);

        if ($newEvent->hasArgument('mediaPage')) {

            return $newEvent->getArgument('mediaPage');
        }

        return new tubepress_api_media_MediaPage();
    }

    private function _logDebug($msg)
    {
        $this->_logger->debug(sprintf('(Legacy Multisource Listener) %s', $msg));
    }
}

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
 * Pro mods for video construction.
 */
class tubepress_pro_vimeo_impl_listeners_ProHttpItemListener extends tubepress_pro_media_impl_listeners_AbstractHttpItemListener
{
    protected function convertThumbToHQ(tubepress_api_media_MediaItem $mediaItem, tubepress_api_event_EventInterface $event)
    {
        $video          = $event->getSubject();
        $videoArray     = $event->getArgument('videoArray');
        $index          = $event->getArgument('zeroBasedIndex');
        $node           = $videoArray[$index];
        $thumbnailArray = $node['pictures']['sizes'];
        $size           = count($thumbnailArray);

        do {

            $size--;
            $thumb = $thumbnailArray[$size]['link'];
            $width = $thumbnailArray[$size]['width'];

        } while ($size > 0 && intval($width) > 640);

        $video->setAttribute(tubepress_api_media_MediaItem::ATTRIBUTE_THUMBNAIL_URL, $thumb);
    }
}
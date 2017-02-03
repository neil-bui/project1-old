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
class tubepress_pro_wordpress_impl_player_AutoPostPlayerLocation implements tubepress_spi_player_PlayerLocationInterface
{
    const NAME = 'wpAutoPost';

    /**
     * @var tubepress_wordpress_impl_wp_WpFunctions
     */
    private $_wpFunctions;

    /**
     * @var tubepress_api_log_LoggerInterface
     */
    private $_logger;

    /**
     * @var bool
     */
    private $_shouldLog;

    public function __construct(tubepress_api_log_LoggerInterface       $logger,
                                tubepress_wordpress_impl_wp_WpFunctions $wpFunctions)
    {
        $this->_wpFunctions = $wpFunctions;
        $this->_logger      = $logger;
        $this->_shouldLog   = $logger->isEnabled();
    }

    /**
     * @return string The name of this player location.
     *
     * @api
     * @since 4.0.0
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return string The display name of this player location.
     *
     * @api
     * @since 4.0.0
     */
    public function getUntranslatedDisplayName()
    {
        return 'in its own WordPress page (via TubePress Auto Post)';
    }

    /**
     * @return string The template name that this player location uses when it is loaded
     *                statically on a gallery page, or null if not required on static page load.
     *
     * @api
     * @since 4.0.0
     */
    public function getStaticTemplateName()
    {
        return null;
    }

    /**
     * @return string The template name that this player location uses when it is loaded
     *                dynamically via Ajax, or null if not used via Ajax.
     *
     * @api
     * @since 4.0.0
     */
    public function getAjaxTemplateName()
    {
        return null;
    }

    /**
     * Get the data required to populate the invoking HTML anchor.
     *
     * @param tubepress_api_media_MediaItem $mediaItem
     *
     * @return array An associative array where the keys are HTML <a> attribute names and the values are
     *               the corresponding attribute values. May be empty nut never null.
     *
     * @api
     * @since 4.0.0
     */
    public function getAttributesForInvocationAnchor(tubepress_api_media_MediaItem $mediaItem)
    {
        if (!$mediaItem->hasAttribute('wordpress_post_id')) {

            if ($this->_shouldLog) {

                $this->_logError(sprintf('Item <code>%s</code> does not have a post ID in its attributes.',
                    $mediaItem->getId()
                ));
            }

            return array();
        }

        $postId    = $mediaItem->getAttribute('wordpress_post_id');
        $permalink = $this->_wpFunctions->get_permalink($postId);

        if ($permalink === false) {

            if ($this->_shouldLog) {

                $this->_logError(sprintf('Item <code>%s</code> has post ID <code>%s</code>, but that post was not found in WP.',
                    $mediaItem->getId(), $postId
                ));
            }

            return array();
        }

        return array(
            'href' => $permalink
        );
    }

    /**
     * @param $msg
     *
     * @return void
     */
    private function _logError($msg)
    {
        $this->_logger->error(sprintf('(Auto Post Player Location) %s', $msg));
    }
}
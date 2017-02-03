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
class tubepress_pro_wordpress_impl_listeners_media_AutoPostMediaListener
{
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

    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    /**
     * @var Twig_Environment
     */
    private $_twig;

    /**
     * @var tubepress_wordpress_impl_wp_ResourceRepository
     */
    private $_resourceRepo;

    /**
     * @var tubepress_api_collection_MapInterface
     */
    private $_cache;

    private static $_CACHE_KEY_POST_STATUSES     = 'post-statuses';
    private static $_CACHE_KEY_POST_STATUS_NAMES = 'post-status-names';
    private static $_CACHE_KEY_POST_TYPES        = 'post-types';
    private static $_CACHE_KEY_POST_TYPE_NAMES   = 'post-type-names';

    public function __construct(tubepress_api_log_LoggerInterface              $logger,
        tubepress_api_options_ContextInterface         $context,
        tubepress_wordpress_impl_wp_WpFunctions        $wpFunctions,
        tubepress_wordpress_impl_wp_ResourceRepository $resourceRepo)
    {
        $this->_wpFunctions  = $wpFunctions;
        $this->_logger       = $logger;
        $this->_shouldLog    = $logger->isEnabled();
        $this->_context      = $context;
        $this->_resourceRepo = $resourceRepo;
        $this->_cache        = new tubepress_internal_collection_Map();
    }

    public function onNewMediaPage(tubepress_api_event_EventInterface $event)
    {
        if (!$this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_ENABLE)) {

            if ($this->_shouldLog) {

                $this->_logDebug('Auto post is disabled.');
            }

            return;
        }

        /**
         * @var $mediaPage tubepress_api_media_MediaPage
         */
        $mediaPage = $event->getSubject();
        $items     = $mediaPage->getItems();

        if (count($items) === 0) {

            if ($this->_shouldLog) {

                $this->_logDebug('Empty media page. Nothing to do.');
            }

            return;
        }

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('Examining <code>%d</code> item(s) in page for auto post.', count($items)));
        }

        $this->_addMissingPosts($mediaPage);
        $this->_removeNonPublicPosts($mediaPage);

        $this->_context->setEphemeralOption(
            tubepress_api_options_Names::PLAYER_LOCATION,
            tubepress_pro_wordpress_impl_player_AutoPostPlayerLocation::NAME
        );
    }

    private function _addMissingPosts(tubepress_api_media_MediaPage $page)
    {
        $items = $page->getItems();

        foreach ($items as $item) {

            $existingPosts     = $this->_findExistingPosts($item);
            $existingPostCount = count($existingPosts);

            if ($existingPostCount === 0) {

                $postCreated = $this->_createPost($item);

                if ($postCreated === false) {

                    $this->_removeItemFromPage($page, $item);

                } else {

                    $item->setAttribute('wordpress_post_id', $postCreated);
                }
            }
        }
    }

    private function _removeNonPublicPosts(tubepress_api_media_MediaPage $page)
    {
        $items = $page->getItems();

        foreach ($items as $item) {

            $existingPosts     = $this->_findExistingPosts($item);
            $existingPostCount = count($existingPosts);

            if ($existingPostCount === 1) {

                $post = $existingPosts[0];

                $this->_handleSinglePost($post, $item, $page);

                continue;
            }

            if ($existingPostCount > 1) {

                if ($this->_shouldLog) {

                    $this->_logDebug(
                        sprintf('Since there are somehow more than 1 posts for item <code>%s</code>, we will not display it.',
                            $item->getId()
                        ));
                }

                $this->_removeItemFromPage($page, $item);

                continue;
            }
        }
    }

    private function _handleSinglePost($post, tubepress_api_media_MediaItem $item, tubepress_api_media_MediaPage $page)
    {
        if ($this->_isPostPublic($post)) {

            if ($this->_shouldLog) {

                $this->_logDebug(
                    sprintf('Post for item <code>%s</code> is publicly-viewable. Nothing to do. Moving on.',
                        $item->getId()
                    ));
            }

            /** @noinspection PhpUndefinedFieldInspection */
            $item->setAttribute('wordpress_post_id', $post->ID);

            return;
        }

        if ($this->_shouldLog) {

            $this->_logDebug(
                sprintf('Post for item <code>%s</code> is not publicly-viewable.',
                    $item->getId()
                ));
        }

        $this->_removeItemFromPage($page, $item);
    }

    private function _isPostPublic($post)
    {
        $type   = $post->post_type;
        $status = $post->post_status;

        if (!in_array($type, $this->_cache->get(self::$_CACHE_KEY_POST_TYPE_NAMES))) {

            return false;
        }

        if (!in_array($status, $this->_cache->get(self::$_CACHE_KEY_POST_STATUS_NAMES))) {

            return false;
        }

        foreach ($this->_cache->get(self::$_CACHE_KEY_POST_STATUSES) as $registeredPostStatus) {

            if ($registeredPostStatus->name === $status) {

                return (bool) $registeredPostStatus->public;
            }
        }

        return false;
    }

    private function _createPost(tubepress_api_media_MediaItem $item)
    {
        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('No existing posts found for item <code>%s</code>. We will create one.', $item->getId()));
        }

        try {

            $newPostId = $this->_wrappedCreatePost($item);

            if ($this->_shouldLog) {

                $this->_logDebug(sprintf('Successfully created post <code>%s</code> for item <code>%s</code>.',
                    $newPostId, $item->getId()
                ));
            }

            $cacheKey = sprintf('posts-for-item-%s', $item->getId());

            if ($this->_cache->containsKey($cacheKey)) {

                $this->_cache->remove($cacheKey);
            }

            return $newPostId;

        } catch (\Exception $e) {

            $this->_logError(sprintf('Caught error when trying to add post for item <code>%s</code>: %s',

                $item->getId(),
                $e->getMessage()
            ));

            return false;
        }
    }

    private function _wrappedCreatePost(tubepress_api_media_MediaItem $item)
    {
        $author          = $this->_calculateAuthor();
        $postDateTime    = $this->_calculatePostDateTime($item);
        $postType        = $this->_calculatePostType();
        $commentStatus   = $this->_calculateCommentStatus();
        $pingStatus      = $this->_calculatePingStatus();
        $postStatus      = $this->_calculatePostStatus();
        $postPassword    = $this->_calculatePostPassword();
        $categories      = $this->_calculateCategories();
        $tags            = $this->_calculateTags();
        $template        = $this->_calculatePageTemplate($postType);
        $templateVars    = array(
            'post' => array(
                'allow_comments'  => $commentStatus,
                'allow_pings'     => $pingStatus,
                'author'          => $author,
                'categories'      => $categories,
                'password'        => $postPassword,
                'status'          => $postStatus,
                'tags'            => $tags,
                'template'        => $template,
                'time'            => $postDateTime,
                'type'            => $postType,
            ),
            'item' => $item,
        );

        $metadata    = $this->_calculateMetadata($templateVars);
        $postTitle   = $this->_calculatePostTitle($templateVars);
        $postContent = $this->_calculatePostContent($templateVars);
        $termMapper  = function ($term) {

            return $term->term_id;
        };
        $categories  = array_map($termMapper, $categories);
        $tags        = array_map($termMapper, $tags);

        /** @noinspection PhpUndefinedFieldInspection */
        $insertPostArgs = array(
            'post_author'    => $author->ID,
            'post_content'   => $postContent,
            'post_title'     => $postTitle,
            'post_status'    => $postStatus->name,
            'post_type'      => $postType->name,
            'comment_status' => $commentStatus,
            'ping_status'    => $pingStatus,
            'post_password'  => $postPassword,
            'meta_input'     => $metadata,
            'post_category'  => $categories,
            'tags_input'     => $tags,
            'page_template'  => $template,
        );

        if ($postDateTime) {

            $insertPostArgs['post_date_gmt'] = $postDateTime->format('Y-m-d H:i:s');
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $result = $this->_wpFunctions->wp_insert_post($insertPostArgs, true);

        if (!is_int($result)) {

            if ($this->_shouldLog) {

                $this->_logError(sprintf('Failed to create post for item <code>%s</code>.'));

                /** @noinspection PhpUndefinedMethodInspection */
                foreach ($result->get_error_codes() as $errorCode) {

                    /** @noinspection PhpUndefinedMethodInspection */
                    foreach ($result->get_error_messages($errorCode) as $errorMessage) {

                        $this->_logError($errorMessage);
                    }
                }
            }

            return false;
        }

        return $result;
    }

    private function _calculateTags()
    {
        $registeredTags = $this->_resourceRepo->getAllTags();

        return $this->_filterTerms($registeredTags, tubepress_wordpress_api_Constants::OPTION_AUTOPOST_TAGS);
    }

    private function _calculateCategories()
    {
        $registeredCategories = $this->_resourceRepo->getAllCategories();

        return $this->_filterTerms($registeredCategories, tubepress_wordpress_api_Constants::OPTION_AUTOPOST_CATEGORIES);
    }

    private function _filterTerms(array $registeredTerms, $optionName)
    {
        $requestedTermSlugs = $this->_context->get($optionName);
        $requestedTermSlugs = explode(',', $requestedTermSlugs);
        $toReturn          = array();

        foreach ($requestedTermSlugs as $requestedSlug) {

            foreach ($registeredTerms as $registeredTerm) {

                if ($registeredTerm->slug === $requestedSlug) {

                    $toReturn[] = $registeredTerm;
                }
            }
        }

        return $toReturn;
    }

    private function _calculatePageTemplate($postType)
    {
        if ($postType->name !== 'page') {

            return null;
        }

        $requestedTemplate = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_PAGE_TEMPLATE);
        $actualTemplates   = $this->_resourceRepo->getPageTemplates();

        if ($requestedTemplate === 'index.php') {

            return 'default';
        }

        if (array_key_exists($requestedTemplate, $actualTemplates)) {

            return $requestedTemplate;
        }

        return 'default';
    }

    private function _calculatePostContent(array &$templateVars)
    {
        $format   = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_CONTENT_TEMPLATE);
        $contents = $this->_twig->createTemplate($format)->render($templateVars);

        $templateVars['post']['contents'] = $contents;

        return $contents;
    }

    private function _calculatePostTitle(array &$templateVars)
    {
        $format = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_TITLE_FORMAT);
        $title  = $this->_twig->createTemplate($format)->render($templateVars);

        $templateVars['post']['title'] = $title;

        return $title;
    }

    private function _calculateMetadata(array &$templateVars)
    {
        if (!isset($this->_twig)) {

            $this->_twig = new Twig_Environment(new Twig_Loader_Array(array()));
        }

        $metaDataTemplate = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_META_MAP);
        $asString         = $this->_twig->createTemplate($metaDataTemplate)->render($templateVars);
        $finalMetaAsArray = json_decode($asString, true, 2);

        if (!is_array($finalMetaAsArray)) {

            throw new \RuntimeException('Unable to decode JSON for metadata insertion. Please check your template.');
        }

        foreach ($finalMetaAsArray as $key => $value) {

            if (!is_string($key)) {

                throw new \RuntimeException('Non-string key in decoded metadata.');
            }

            if (!is_scalar($value)) {

                throw new \RuntimeException('Non-scalar value in decoded metadata.');
            }

            $finalMetaAsArray[$key] = "$value";
        }

        /**
         * @var $item tubepress_api_media_MediaItem
         */
        $item                                   = $templateVars['item'];
        $encodedItem                            = array();
        $finalMetaAsArray['_tubepress-item-id'] = $item->getId();
        $attributeNames                         = $item->getAttributeNames();

        foreach ($attributeNames as $attributeName) {

            if (is_scalar($attributeName) && $item->hasAttribute($attributeName)) {

                $value = $item->getAttribute($attributeName);

                if (is_scalar($value)) {

                    $encodedItem[$attributeName] = $value;
                }
            }
        }

        $encodedItem                                        = base64_encode(serialize($encodedItem));
        $finalMetaAsArray['_tubepress-item-encoded']        = $encodedItem;
        $finalMetaAsArray['_tubepress-item-discovery-time'] = time();
        $templateVars['post']['metadata']                   = $finalMetaAsArray;

        return $finalMetaAsArray;
    }

    /**
     * @return string
     */
    private function _calculatePostPassword()
    {
        $password = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_PASSWORD);
        $password = trim($password);

        return $password ? $password : '';
    }

    /**
     * @return stdClass
     */
    private function _calculatePostStatus()
    {
        $requestedStatus = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_POST_STATUS);

        if (!in_array($requestedStatus, $this->_cache->get(self::$_CACHE_KEY_POST_STATUS_NAMES))) {

            throw new \RuntimeException('You have requested an invalid post status.');
        }

        foreach ($this->_cache->get(self::$_CACHE_KEY_POST_STATUSES) as $registeredStatus) {

            if ($registeredStatus->name === $requestedStatus) {

                return $registeredStatus;
            }
        }

        throw new \RuntimeException('Could not find requested post status. This should never happen.');
    }

    /**
     * @return string
     */
    private function _calculateCommentStatus()
    {
        $allowed = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_ALLOW_COMMENTS);

        return $allowed ? 'open' : 'closed';
    }

    /**
     * @return string
     */
    private function _calculatePingStatus()
    {
        $allowed = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_ALLOW_PING);

        return $allowed ? 'open' : 'closed';
    }

    /**
     * @return stdClass
     */
    private function _calculatePostType()
    {
        $requestedType = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_TYPE);

        if (!in_array($requestedType, $this->_cache->get(self::$_CACHE_KEY_POST_TYPE_NAMES))) {

            throw new \RuntimeException('You have requested an invalid post type.');
        }

        foreach ($this->_cache->get(self::$_CACHE_KEY_POST_TYPES) as $registeredType) {

            if ($registeredType->name === $requestedType) {

                return $registeredType;
            }
        }

        throw new \RuntimeException('Could not find requested post type. This should never happen.');
    }

    /**
     * @param tubepress_api_media_MediaItem $item
     *
     * @return DateTime|null
     */
    private function _calculatePostDateTime(tubepress_api_media_MediaItem $item)
    {
        $requestedDateSource = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_DATE_SOURCE);

        if ($requestedDateSource === tubepress_wordpress_api_Constants::AUTOPOST_DATA_SOURCE_DISCOVERY) {

            // let WP assign the date
            return null;
        }

        if (!$item->hasAttribute(tubepress_api_media_MediaItem::ATTRIBUTE_TIME_PUBLISHED_UNIXTIME)) {

            throw new \RuntimeException(sprintf('Item <code>%s</code> is missing a publication time. This should never happen.',
                    $item->getId())
            );
        }

        $unixTime = intval($item->getAttribute(tubepress_api_media_MediaItem::ATTRIBUTE_TIME_PUBLISHED_UNIXTIME));

        if ($unixTime === 0) {

            throw new \RuntimeException(sprintf('Item <code>%s</code> has an invalid publication time.', $item->getId()));
        }

        $dt = new \DateTime('now', new \DateTimeZone('GMT'));

        $dt->setTimestamp($unixTime);

        return $dt;
    }

    /** @noinspection PhpUndefinedClassInspection */
    /**
     * @return WP_User
     */
    private function _calculateAuthor()
    {
        $requestedAuthorLogin = $this->_context->get(tubepress_wordpress_api_Constants::OPTION_AUTOPOST_AUTHOR);

        if (!$requestedAuthorLogin) {

            throw new \RuntimeException('Missing post author');
        }

        $actualUser = $this->_wpFunctions->get_user_by('login', $requestedAuthorLogin);

        if ($actualUser === false) {

            throw new \RuntimeException(sprintf('Requested author <code>%s</code> does not exist.', $requestedAuthorLogin));
        }

        return $actualUser;
    }

    /** @noinspection PhpUndefinedClassInspection */
    /**
     * @param tubepress_api_media_MediaItem $item
     *
     * @return WP_Post[]
     */
    private function _findExistingPosts(tubepress_api_media_MediaItem $item)
    {
        $cacheKey = sprintf('posts-for-item-%s', $item->getId());

        if ($this->_cache->containsKey($cacheKey)) {

            return $this->_cache->get($cacheKey);
        }

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('Looking for existing posts for item <code>%s</code>.', $item->getId()));
        }

        if (!$this->_cache->containsKey(self::$_CACHE_KEY_POST_STATUSES)) {

            $statuses = $this->_resourceRepo->getAllUsablePostStatuses();
            $names    = array();

            foreach ($statuses as $postStatus) {

                $names[] = $postStatus->name;
            }

            $this->_cache->put(self::$_CACHE_KEY_POST_STATUSES, $statuses);
            $this->_cache->put(self::$_CACHE_KEY_POST_STATUS_NAMES, $names);
        }

        if (!$this->_cache->containsKey(self::$_CACHE_KEY_POST_TYPES)) {

            $types = $this->_resourceRepo->getAllUsablePostTypes();
            $names = array();

            foreach ($types as $type) {

                $names[] = $type->name;
            }

            $this->_cache->put(self::$_CACHE_KEY_POST_TYPES, $types);
            $this->_cache->put(self::$_CACHE_KEY_POST_TYPE_NAMES, $names);
        }

        $getPostsArgs = array(
            'meta_key'    => '_tubepress-item-id',
            'meta_value'  => $item->getId(),
            'numberposts' => -1,
            'post_type'   => $this->_cache->get(self::$_CACHE_KEY_POST_TYPE_NAMES),
            'post_status' => $this->_cache->get(self::$_CACHE_KEY_POST_STATUS_NAMES),
        );

        $toReturn = $this->_wpFunctions->get_posts($getPostsArgs);

        if ($this->_shouldLog) {

            $this->_logPosts($toReturn, count($toReturn), $item);
        }

        $this->_cache->put($cacheKey, $toReturn);

        return $toReturn;
    }

    private function _logPosts(array $posts, $existingPostCount, tubepress_api_media_MediaItem $item)
    {
        $this->_logDebug(sprintf('Found <code>%d</code> existing post(s) for item <code>%s</code>',

            $existingPostCount,
            $item->getId()
        ));

        foreach ($posts as $post) {

            $this->_logDebug(
                sprintf('Post for item <code>%s</code> has ID <code>%s</code>, type <code>%s</code>, and status <code>%s</code>',
                    $item->getId(), $post->ID, $post->post_type, $post->post_status
                ));
        }
    }

    private function _removeItemFromPage(tubepress_api_media_MediaPage $page, tubepress_api_media_MediaItem $needle)
    {
        $items = $page->getItems();
        $found = false;

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf('Attempting to remove item <code>%s</code> from page.',
                $needle->getId()
            ));
        }

        for ($x = 0; $x < count($items); $x++) {

            /**
             * @var $item tubepress_api_media_MediaItem
             */
            $item = $items[$x];

            if ($item->getId() === $needle->getId()) {

                unset($items[$x]);

                $items = array_values($items);
                $found = true;
                break;
            }
        }

        if (!$found) {

            if ($this->_shouldLog) {

                $this->_logDebug(sprintf('Could not find item <code>%s</code> in the page. This should not happen.',
                    $needle->getId()
                ));
            }

            return;
        }

        $oldTotalResultCount = $page->getTotalResultCount();
        $newTotalResultCount = max(0, ($oldTotalResultCount - 1));

        if ($this->_shouldLog) {

            $this->_logDebug(sprintf(
                'Removed item <code>%s</code> from the page. Old result count was <code>%d</code>, new total result ' .
                'count is <code>%d</code>. New page size is <code>%d</code>.',
                $needle->getId(), $oldTotalResultCount, $newTotalResultCount, count($items)
            ));
        }

        $page->setTotalResultCount($newTotalResultCount);
        $page->setItems($items);
    }

    /**
     * @param $msg
     *
     * @return void
     */
    private function _logError($msg)
    {
        $this->_logger->error(sprintf('(Auto Post Listener) %s', $msg));
    }

    /**
     * @param $msg
     *
     * @return void
     */
    private function _logDebug($msg)
    {
        $this->_logger->debug(sprintf('(Auto Post Listener) %s', $msg));
    }
}
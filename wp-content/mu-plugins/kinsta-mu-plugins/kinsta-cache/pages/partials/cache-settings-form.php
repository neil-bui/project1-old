<?php namespace Kinsta ?>
<form method="post">

    <div class='kinsta-box'>

        <fieldset class='mb22'>
            <legend class='kinsta-box-title-bar kinsta-box-title-bar__small mb22'><h3><?php _e( 'General Options', 'kinsta-cache' ) ?></h3></legend>


            <?php

                KinstaTools::kinsta_number_field(
                    $this->KinstaCache->config['option_name'],
                    'options[page_depth_blog]',
                    $this->KinstaCache->settings['options']['page_depth_blog'],
                    __( 'Clear blog page depth', 'kinsta-cache'),
                    true,
                    sprintf( __( 'Set the number of blog pages to clear. If set to 3 it will clear the cache of %s and %s as well as the main blog. For more information <a href="'.KINSTA_CACHE_DOCS_URL.'">read the docs</a>', 'kinsta-cache' ), site_url() . '/page/2', site_url() . '/page/3' )
                );
                KinstaTools::kinsta_number_field(
                    $this->KinstaCache->config['option_name'],
                    'options[page_depth_archives]',
                    $this->KinstaCache->settings['options']['page_depth_archives'],
                    __( 'Clear archive page depth', 'kinsta-cache'),
                    true,
                    __( 'Set the number of archive pages to clear. Archive pages are generally not visited as frequently, setting this value between 2-4 is recommended. For more information <a href="'.KINSTA_CACHE_DOCS_URL.'">read the docs</a>', 'kinsta-cache' )
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'options[purge_blog_feeds]',
                    $this->KinstaCache->settings['options']['purge_blog_feeds'],
                    __( 'Clear blog feeds', 'kinsta-cache'),
                    true,
                    __( 'When this option is checked the plugin will clear the blog feed cache whenever the regular blog page is cleared. For more information <a href="'.KINSTA_CACHE_DOCS_URL.'">read the docs</a>', 'kinsta-cache' )
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'options[purge_static_home]',
                    $this->KinstaCache->settings['options']['purge_static_home'],
                    __( 'Clear static home page', 'kinsta-cache'),
                    true,
                    __( 'This option is useful if you have a static home page that actually uses dynamic content. Set this option to true to clear the static home page whenever the blog is cleared. For more information <a href="'.KINSTA_CACHE_DOCS_URL.'">read the docs</a>', 'kinsta-cache' )
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'options[purge_archive_feeds]',
                    $this->KinstaCache->settings['options']['purge_archive_feeds'],
                    __( 'Clear archive feeds', 'kinsta-cache'),
                    true,
                    __( 'Archive feeds are used infrequently. If your viewers use them you can check this option to clear them. For more information <a href="'.KINSTA_CACHE_DOCS_URL.'">read the docs</a>', 'kinsta-cache' )
                );

                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'options[purge_date_archives]',
                    $this->KinstaCache->settings['options']['purge_date_archives'],
                    __( 'Clear date archives', 'kinsta-cache'),
                    true,
                    __( 'Date archives are scarcely used so it is best to leave this option off. If you rely heavily on date archives set this option to yes to clear all date archives. For more information <a href="'.KINSTA_CACHE_DOCS_URL.'">read the docs</a>', 'kinsta-cache' )
                );

                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'options[has_mobile_plugin]',
                    $this->KinstaCache->settings['options']['has_mobile_plugin'],
                    __( 'Do you use a dedicated plugin (such as WP Touch) to make your site\'s mobile version?', 'kinsta-cache'),
                    true,
                    __( 'If you use a dedicated plugin for your site\'s mobile version we need to clear additional caches. Set this option to yes <strong>only if you use such a plugin</strong> because it increases your site\'s resource usage. For more information <a href="'.KINSTA_CACHE_DOCS_URL.'">read the docs</a>', 'kinsta-cache' )
                );
            ?>

        </fieldset>


        <fieldset class='mb22'>
            <legend class='kinsta-box-title-bar kinsta-box-title-bar__small mb22'><h3><?php _e( 'Clear Blog Cache', 'kinsta-cache' ) ?></h3></legend>

            <?php
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[blog][post_added]',
                    $this->KinstaCache->settings['rules']['blog']['post_added'],
                    __( 'when a post (or page/custom post) is <strong>published</strong>', 'kinsta-cache')
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[blog][post_modified]',
                    $this->KinstaCache->settings['rules']['blog']['post_modified'],
                    __( 'when a <strong>published post</strong> (or page/custom post) is <strong>updated</strong>', 'kinsta-cache')
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[blog][post_unpublished]',
                    $this->KinstaCache->settings['rules']['blog']['post_unpublished'],
                    __( 'when a post is <strong>unpublished</strong> (trashed, drafted, etc.)', 'kinsta-cache')
                );
            ?>

        </fieldset>

        <fieldset class='mb22'>
            <legend class='kinsta-box-title-bar kinsta-box-title-bar__small mb22'><h3><?php _e( 'Clear Singular Page Cache', 'kinsta-cache' ) ?></h3></legend>

            <?php
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[post][post_added]',
                    $this->KinstaCache->settings['rules']['post']['post_added'],
                    __( 'when a post (or page/custom post) is <strong>published</strong>', 'kinsta-cache')
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[post][post_modified]',
                    $this->KinstaCache->settings['rules']['post']['post_modified'],
                    __( 'when a <strong>published post</strong> (or page/custom post) is <strong>updated</strong>', 'kinsta-cache')
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[post][post_unpublished]',
                    $this->KinstaCache->settings['rules']['post']['post_unpublished'],
                    __( 'when a post is <strong>unpublished</strong> (trashed, drafted, etc.)', 'kinsta-cache' )
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[post][comment_added]',
                    $this->KinstaCache->settings['rules']['post']['comment_added'],
                    __( 'when a comment is <strong>published</strong>', 'kinsta-cache' )
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[post][comment_modified]',
                    $this->KinstaCache->settings['rules']['post']['comment_modified'],
                    __( 'when a comment is <strong>updated</strong>', 'kinsta-cache' )
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[post][comment_unpublished]',
                    $this->KinstaCache->settings['rules']['post']['comment_unpublished'],
                    __( 'when a comment is <strong>unpublished</strong> (marked as spam, deleted, etc.)', 'kinsta-cache' )
                );

            ?>

        </fieldset>

        <fieldset class='mb22'>
            <legend class='kinsta-box-title-bar kinsta-box-title-bar__small mb22'><h3><?php _e( 'Clear Archive Caches', 'kinsta-cache' ) ?></h3></legend>

            <?php
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[archive][post_added]',
                    $this->KinstaCache->settings['rules']['archive']['post_added'],
                    __( 'when a post (or page/custom post) is <strong>published</strong>', 'kinsta-cache')
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[archive][post_modified]',
                    $this->KinstaCache->settings['rules']['archive']['post_modified'],
                    __( 'when a <strong>published post</strong> (or page/custom post) is <strong>updated</strong>', 'kinsta-cache')
                );
                KinstaTools::kinsta_switch(
                    $this->KinstaCache->config['option_name'],
                    'rules[archive][post_unpublished]',
                    $this->KinstaCache->settings['rules']['archive']['post_unpublished'],
                    __( 'when a post is <strong>unpublished</strong> (trashed, drafted, etc.)', 'kinsta-cache')
                );
            ?>
        </fieldset>

    </div>

    <noscript>
        <?php wp_nonce_field( 'save_plugin_options', 'kinsta_nonce' ); ?>
        <input type="hidden" name="action" value='save_plugin_options'>
        <button type="submit" class='kinsta-button kinsta-loader' value='<?php _e( 'Save Changes', 'kinsta-cache' ) ?>' data-progressText='<?php _e( 'Saving...', 'kinsta-cache' ) ?>' data-completedText='<?php _e( 'Saved', 'kinsta-cache' ) ?>' data-type='reload'><?php _e( 'Save Changes', 'kinsta-cache' ) ?></button>
    </noscript>

</form>

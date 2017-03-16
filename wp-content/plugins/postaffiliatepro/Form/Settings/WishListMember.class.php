<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_WishListMember extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::WLM_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/WLMConfig.xtpl';
    }

    protected function initForm() {
        $this->addTextBox(postaffiliatepro::WLM_TRACK_REGISTRATION, 30);
        $this->addCheckbox(postaffiliatepro::WLM_TRACK_RECURRING);

        $this->addSubmit();
    }
}
<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_MemberPress extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::MEMBERPRESS_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/MemberPressConfig.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(postaffiliatepro::MEMBERPRESS_ENABLE_LIFETIME);
        $this->addCheckbox(postaffiliatepro::MEMBERPRESS_TRACK_RECURRING);

        $this->addSubmit();
    }
}
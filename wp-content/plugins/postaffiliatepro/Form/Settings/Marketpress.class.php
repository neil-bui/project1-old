<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_Marketpress extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::MARKETPRESS_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/MarketpressConfig.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(postaffiliatepro::MARKETPRESS_PERPRODUCT);
        $this->addCheckbox(postaffiliatepro::MARKETPRESS_STATUS_UPDATE);
        $this->addCheckbox(postaffiliatepro::MARKETPRESS_TRACK_DATA1);

        $this->addSubmit();
    }
}
<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_JotForm extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::JOTFORM_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/JotFormConfig.xtpl';
    }

    protected function initForm() {
        $this->addTextBox(postaffiliatepro::JOTFORM_TOTAL_COST);
        $this->addTextBox(postaffiliatepro::JOTFORM_COMMISSION_CAMPAIGN);
        $this->addTextBox(postaffiliatepro::JOTFORM_PRODUCTID);
        $this->addTextBox(postaffiliatepro::JOTFORM_DATA1);
        $this->addTextBox(postaffiliatepro::JOTFORM_DATA2);
        $this->addTextBox(postaffiliatepro::JOTFORM_DATA3);
        $this->addTextBox(postaffiliatepro::JOTFORM_DATA4);
        $this->addTextBox(postaffiliatepro::JOTFORM_DATA5);
        $this->addSubmit();
    }
}
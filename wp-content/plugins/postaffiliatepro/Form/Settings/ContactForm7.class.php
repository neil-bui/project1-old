<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_ContactForm7 extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/ContactForm7Config.xtpl';
    }

    private function getCampaignSelectData() {
        $campaigns = $this->getCampaignHelper()->getCampaignsList();
        foreach($campaigns as $rec) {
            $data[$rec->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID)] = $rec->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_NAME);
        }
        return $data;
    }

    private function getFormsSelectData() {
        $forms = postaffiliatepro_Util_ContactForm7Helper::getFormList();
        $data[0] = 'All';
        foreach($forms as $form) {
            $data[$form->cf7_unit_id] = $form->title;
        }
        return $data;
    }

    protected function getOption($name) {
        if ($name == postaffiliatepro::CONTACT7_CONTACT_COMMISSION_AMOUNT && get_option($name) == '') {
            return 0;
        }
        if ($name == postaffiliatepro::CONTACT7_CONTACT_COMMISSION_FORM && get_option($name) == '') {
            return 0;
        }
        return parent::getOption($name);
    }



    protected function initForm() {
        $this->addTextBox(postaffiliatepro::CONTACT7_CONTACT_COMMISSION_AMOUNT, 10);
        $this->addSelect(postaffiliatepro::CONTACT7_CONTACT_COMMISSION_CAMPAIGN, $this->getCampaignSelectData());
        $this->addCheckbox(postaffiliatepro::CONTACT7_CONTACT_COMMISSION_STORE_FORM);
        $this->addSelect(postaffiliatepro::CONTACT7_CONTACT_COMMISSION_FORM, $this->getFormsSelectData());
        $this->addSubmit();
    }
}

?>
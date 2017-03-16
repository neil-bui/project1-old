<?php
class postaffiliatepro_Form_Settings_Integrations extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::INTEGRATIONS_SETTINGS_PAGE_NAME, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/IntegrationsConfig.xtpl';
    }

    protected function initForm() {
        if (!postaffiliatepro_Util_ContactForm7Helper::formsExists()) {
            $this->addCheckbox(postaffiliatepro::CONTACT7_SIGNUP_COMMISSION_ENABLED, null, ' disabled');
            $this->addHtml('contact7-signup-note', '<tr><td colspan="2" style="padding-top:0px;padding-bottom:15px;color:#750808;">No forms exist!</td></tr>');
        } else {
            $this->addCheckbox(postaffiliatepro::CONTACT7_SIGNUP_COMMISSION_ENABLED);
        }
        // JotForm
        $this->addCheckbox(postaffiliatepro::JOTFORM_COMMISSION_ENABLED);
        // Marketpress
        $this->addCheckbox(postaffiliatepro::MARKETPRESS_COMMISSION_ENABLED);
        // MemberPress
        $this->addCheckbox(postaffiliatepro::MEMBERPRESS_COMMISSION_ENABLED);
        // Simple Pay Pro
        $this->addCheckbox(postaffiliatepro::SIMPLEPAYPRO_COMMISSION_ENABLED);
        // WishList Member
        $this->addCheckbox(postaffiliatepro::WLM_COMMISSION_ENABLED);
        // WooComm
        $this->addCheckbox(postaffiliatepro::WOOCOMM_COMMISSION_ENABLED);

        $this->addSubmit();
    }
}
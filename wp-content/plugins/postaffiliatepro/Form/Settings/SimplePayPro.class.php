<?php
/**
 *   @copyright Copyright (c) 2017 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_SimplePayPro extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::SIMPLEPAYPRO_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/SimplePayProConfig.xtpl';
    }

    protected function initForm() {
        $campaignHelper = new postaffiliatepro_Util_CampaignHelper();
        $campaignList = $campaignHelper->getCampaignsList();

        $campaigns = array('0' => ' ');
        foreach ($campaignList as $row) {
        	$campaigns[$row->get('campaignid')] = $row->get('name');
        }
        $this->addSelect(postaffiliatepro::SIMPLEPAYPRO_CAMPAIGN, $campaigns);

        $this->addSubmit();
    }
}
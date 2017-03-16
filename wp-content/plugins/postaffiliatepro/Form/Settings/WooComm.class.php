<?php
/**
 *   @copyright Copyright (c) 2016 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_WooComm extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::WOOCOMM_CONFIG_PAGE, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/WooCommConfig.xtpl';
    }

    protected function initForm() {
        $this->addCheckbox(postaffiliatepro::WOOCOMM_PERPRODUCT);
        $this->addSelect(postaffiliatepro::WOOCOMM_PRODUCT_ID, array('0' => ' ', 'id' => 'product ID', 'sku' => 'SKU', 'categ' => 'product category', 'role' => 'user role'));
        $this->addCheckbox(postaffiliatepro::WOOCOMM_STATUS_UPDATE);
        $this->addSelect(postaffiliatepro::WOOCOMM_DATA1, array('0' => ' ', 'id' => 'customer ID', 'email' => 'customer email'));

        $campaignHelper = new postaffiliatepro_Util_CampaignHelper();
        $campaignList = $campaignHelper->getCampaignsList();

        $campaigns = array('0' => ' ');
        foreach ($campaignList as $row) {
        	$campaigns[$row->get('campaignid')] = $row->get('name');
        }
        $this->addSelect(postaffiliatepro::WOOCOMM_CAMPAIGN, $campaigns);

        $this->addSubmit();
    }
}
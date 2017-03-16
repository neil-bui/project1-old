<?php
/*
 Plugin Name: Post Affiliate Pro
 Plugin URI: http://www.postaffiliatepro.com/
 Description: Easily integrate your WordPress site with your Post Affiliate Pro
 Author: QualityUnit
 Version: 1.8.2
 Author URI: http://www.qualityunit.com/
 License: GPL2
 */
if (!defined('PAP_PLUGIN_VERSION')) {
	define('PAP_PLUGIN_VERSION', '1.8.2');
}
if (!defined('PAP_PLUGIN_NAME')) {
	define('PAP_PLUGIN_NAME', 'postaffiliatepro');
}
include WP_PLUGIN_DIR . '/' . PAP_PLUGIN_NAME . '/Base.class.php';

if (!class_exists('postaffiliatepro')) {
    class postaffiliatepro extends postaffiliatepro_Base {
        const API_FILE = '/postaffiliatepro/PapApi.class.php';

        //configuration pages and settings
        //general page
        const GENERAL_SETTINGS_PAGE_NAME = 'pap_config_general_page';

        const PAP_URL_SETTING_NAME = 'pap-url';
        const PAP_MERCHANT_NAME_SETTING_NAME = 'pap-merchant-name';
        const PAP_MERCHANT_PASSWORD_SETTING_NAME = 'pap-merchant-password';

        //signup options
        const SIGNUP_SETTINGS_PAGE_NAME = 'pap_config_signup_page';

        const SIGNUP_INTEGRATION_ENABLED_SETTING_NAME = 'pap-sugnup-integration-enabled';
        const SIGNUP_DEFAULT_PARENT_SETTING_NAME = 'pap-sugnup-default-parent';
        const SIGNUP_DEFAULT_STATUS_SETTING_NAME = 'pap-sugnup-default-status';
        const SIGNUP_SEND_CONFIRMATION_EMAIL_SETTING_NAME = 'pap-sugnup-sendconfiramtionemail';
        const SIGNUP_CAMPAIGNS_SETTINGS_SETTING_NAME = 'pap-sugnup-campaigns-settings';
        const SIGNUP_INTEGRATION_USE_PHOTO = 'pap-sugnup-use-photo';

        //click tracking integration page
        const CLICK_TRACKING_SETTINGS_PAGE_NAME = 'pap_config_click_tracking_page';

        const CLICK_TRACKING_ENABLED_SETTING_NAME = 'pap-click-tracking-enabled';
        const CLICK_TRACKING_ACCOUNT_SETTING_NAME = 'pap-click-tracking-account';
        const CLICK_TRACKING_CAMPAIGN = 'pap-click-tracking-capaign';

        const DEFAULT_ACCOUNT_NAME = 'default1';

        //top affiliates widget options
        const TOP_AFFILAITES_WIDGET_SETTINGS_PAGE_NAME = 'pap-top-affiliates-widget-settings-page';
        const TOP_AFFILAITES_REFRESHTIME = 'pap-top-affiliates-refresh-time';
        const TOP_AFFILAITES_REFRESHINTERVAL = 'pap-top-affiliates-refresh-interval';
        const TOP_AFFILAITES_CACHE = 'pap-top-affiliates-cache';
        const TOP_AFFILAITES_ORDER_BY = 'pap-top-affiliates-order-by';
        const TOP_AFFILAITES_ORDER_ASC = 'pap-top-affiliates-order-asc';
        const TOP_AFFILAITES_LIMIT = 'pap-top-affiliates-limit';
        const TOP_AFFILAITES_ROW_TEMPLATE = 'pap-top-affiliates-row-template';

        const SHORTCODES_SETTINGS_PAGE_NAME = 'shortcodes-settings-page';
        const AFFILAITE_SHORTCODE_CACHE = 'affiliate-shortcode_cache';

        //specail integrations page
        const INTEGRATIONS_SETTINGS_PAGE_NAME = 'pap-integrations-config-page';

        //contact form 7 integration page
        const CONTACT7_SIGNUP_COMMISSION_ENABLED = 'contact7-signup-commission-enabled';
        const CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE = 'contact7-signup-commission-config-page';
        const CONTACT7_CONTACT_COMMISSION_AMOUNT = 'contact7-contact-commission-amount';
        const CONTACT7_CONTACT_COMMISSION_CAMPAIGN = 'contact7-contact-commission-campaign';
        const CONTACT7_CONTACT_COMMISSION_FORM = 'contact7-contact-commission-form';
        const CONTACT7_CONTACT_COMMISSION_STORE_FORM = 'contact7-contact-commission-store-form';

        // jotform integration settings
        const JOTFORM_COMMISSION_ENABLED = 'jotform-commission-enabled';
        const JOTFORM_CONFIG_PAGE = 'jotform-config-page';
        const JOTFORM_TOTAL_COST = 'jotform-total-cost';
        const JOTFORM_COMMISSION_CAMPAIGN = 'jotform-commission-campaign';
        const JOTFORM_PRODUCTID = 'jotform-product-id';
        const JOTFORM_DATA1 = 'jotform-data-1';
        const JOTFORM_DATA2 = 'jotform-data-2';
        const JOTFORM_DATA3 = 'jotform-data-3';
        const JOTFORM_DATA4 = 'jotform-data-4';
        const JOTFORM_DATA5 = 'jotform-data-5';

        // WooCommerce integration settings
        const WOOCOMM_COMMISSION_ENABLED = 'woocomm-commission-enabled';
        const WOOCOMM_CONFIG_PAGE = 'woocomm-config-page';
        const WOOCOMM_PERPRODUCT = 'woocomm-per-product';
        const WOOCOMM_PRODUCT_ID = 'woocomm-product-id';
        const WOOCOMM_DATA1 = 'woocomm-data1';
        const WOOCOMM_CAMPAIGN = 'woocomm-campaign';
        const WOOCOMM_STATUS_UPDATE = 'woocomm-status-update';

        // MemberPress
        const MEMBERPRESS_COMMISSION_ENABLED = 'memberpress-commission-enabled';
        const MEMBERPRESS_CONFIG_PAGE = 'memberpress-config-page';
        const MEMBERPRESS_ENABLE_LIFETIME = 'memberpress-enable-lifetime';
        const MEMBERPRESS_TRACK_RECURRING = 'memberpress-track-refurring';

        // Marketpress
        const MARKETPRESS_COMMISSION_ENABLED = 'marketpress-commission-enabled';
        const MARKETPRESS_CONFIG_PAGE = 'marketpress-config-page';
        const MARKETPRESS_PERPRODUCT = 'marketpress-per-product';
        const MARKETPRESS_TRACK_DATA1 = 'marketpress-track-data1';
        const MARKETPRESS_STATUS_UPDATE = 'marketpress-status-update';

        // WishList Member
        const WLM_COMMISSION_ENABLED = 'wlm-commission-enabled';
        const WLM_CONFIG_PAGE = 'wlm-config-page';
        const WLM_TRACK_RECURRING = 'wlm-track-recurring';
        const WLM_TRACK_REGISTRATION = 'wlm-track-registration';

        // Simple Pay Pro
        const SIMPLEPAYPRO_COMMISSION_ENABLED = 'simplepaypro-commission-enabled';
        const SIMPLEPAYPRO_CONFIG_PAGE = 'simplepaypro-config-page';
        const SIMPLEPAYPRO_CAMPAIGN = 'simplepaypro-campaign';

        private $Contact7PostedData = array();

        public function __construct() {
            if (!$this->apiFileExists()) {
                $this->_log(__('Error during loading PAP API file: ' . WP_PLUGIN_DIR . self::API_FILE));
                return;
            }
            $this->includePapApiFile();
            $this->initUtils();
            $this->initForms();
            $this->initWidgets();
            $this->initPlugin();
            $this->initShortcodes();
        }

        private function initUtils() {
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Util/CampaignHelper.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Util/TopAffiliatesHelper.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Util/ContactForm7Helper.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Util/JotFormHelper.class.php';
        }

        private function initForms() {
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Base.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/General.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/Signup.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/Campaigns.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/CampaignInfo.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/ClickTracking.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/Integrations.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/ContactForm7.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/JotForm.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/WooComm.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/Marketpress.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/MemberPress.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/SimplePayPro.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Form/Settings/WishListMember.class.php';
        }

        private function initWidgets() {
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Widget/TopAffiliates.class.php';
        }

        private function initShortcodes() {
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Shortcode/Cache.class.php';
            require_once WP_PLUGIN_DIR . '/postaffiliatepro/Shortcode/Affiliate.class.php';

            add_shortcode('affiliate', array($this, 'getAffiliateShortCode'));
            add_shortcode('parent', array($this, 'getParentAffiliateShortCode'));
        }

        private function initPlugin() {
            add_action('admin_init', array($this, 'initSettings'));
            add_filter('admin_head', array($this, 'initAdminHeader'), 99);
            add_action('admin_menu', array($this, 'addPrimaryConfigMenu'));
            add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'addSettingsLinkIntoPlugin'));
            add_action('user_register', array($this, 'onNewUserRegistration'), 99);
            add_action('register_form', array($this, 'addHiddenFieldToRegistrationForm'));
            //fix to work with magic members
            add_action('mgm_user_register', array($this, 'onNewUserRegistration'), 99);
            //fix end
            add_action('profile_update', array($this, 'onUpdateExistingUser'));
            //contact7
            add_action('wpcf7_mail_sent', array($this, 'addContactForm7ContactCommission'));
            add_filter('wpcf7_posted_data', array($this, 'saveContactForm7FormData'));

            // WooCommerce
            add_action('woocommerce_thankyou', array($this, 'wooAddThankYouPageTrackSale'));
            add_action('woocommerce_checkout_after_order_review', array($this, 'addHiddenFieldToPaymentForm'));
            add_action('woocommerce_order_status_changed', array($this, 'wooOrderStatusChanged'), 99, 3);
            add_action('woocommerce_subscription_status_changed', array($this, 'wooSubscriptionStatusChanged'), 99, 3);
            add_filter('wcs_renewal_order_created', array($this, 'wooRecurringCommission'), 99, 2);

            // WooCommerce PayPal
            add_filter('woocommerce_paypal_args', array($this, 'wooModifyPaypalArgs'), 99);
            add_action('valid-paypal-standard-ipn-request', array($this, 'wooProcessPaypalIPN'));

            // Marketpress
            add_filter('mp_order/confirmation_text', array($this, 'MarketpressThankYouPage'), 99, 2);
            add_action('mp_order_order_paid', array($this, 'MarketpressChangeOrderStatusPaid'));
            add_action('mp_order_order_closed', array($this, 'MarketpressChangeOrderStatusDecline'));
            add_action('mp_order_trash', array($this, 'MarketpressChangeOrderStatusDecline'));

            // MemberPress
            add_action('mepr-signup', array($this, 'MemberPressTrackSale'));
            add_action('mepr-checkout-before-submit', array($this, 'addHiddenFieldToPaymentForm'));
            add_action('mepr-event-recurring-transaction-completed', 'MemberPressRecurringSale', 99, 1);

            // WishList Member
            add_filter('wishlistmember_after_registration_page', array($this, 'WLMnewUserRegistration'), 99, 2);
            add_action('wlm_shoppingcart_rebill', array($this, 'WLMRecurringCommission'));

            // Simple Pay Pro
            add_filter('sc_before_payment_button', array($this, 'SimplePayProAddCodeToPaymentButton'), 99);
            add_action('simpay_charge_created', array($this, 'SimplePayProHandleCharge'));

            add_filter('wp_footer', array($this, 'insertIntegrationCodeToFooter'), 99);
            add_action('widgets_init', create_function('', 'return register_widget("postaffiliatepro_Widget_TopAffiliates");'));
        }

        private function includePapApiFile() {
            require_once WP_PLUGIN_DIR . self::API_FILE;
        }

        private function apiFileExists() {
            return @file_exists(WP_PLUGIN_DIR . self::API_FILE);
        }

        private function getPapIconURL() {
            return $this->getImgUrl() . '/menu-icon.png';
        }

        public function initSettings() {
        	register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::PAP_URL_SETTING_NAME);
        	register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::PAP_MERCHANT_NAME_SETTING_NAME);
        	register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::PAP_MERCHANT_PASSWORD_SETTING_NAME);
        	register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::CLICK_TRACKING_ACCOUNT_SETTING_NAME);

        	register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_INTEGRATION_ENABLED_SETTING_NAME);
        	register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_DEFAULT_PARENT_SETTING_NAME);
        	register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_DEFAULT_STATUS_SETTING_NAME);
        	register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_SEND_CONFIRMATION_EMAIL_SETTING_NAME);
        	register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_CAMPAIGNS_SETTINGS_SETTING_NAME);
        	register_setting(self::SIGNUP_SETTINGS_PAGE_NAME, self::SIGNUP_INTEGRATION_USE_PHOTO);

        	register_setting(self::CLICK_TRACKING_SETTINGS_PAGE_NAME, self::CLICK_TRACKING_ENABLED_SETTING_NAME);
        	register_setting(self::CLICK_TRACKING_SETTINGS_PAGE_NAME, self::CLICK_TRACKING_CAMPAIGN);

        	register_setting(self::TOP_AFFILAITES_WIDGET_SETTINGS_PAGE_NAME, self::TOP_AFFILAITES_REFRESHTIME);
        	register_setting(self::TOP_AFFILAITES_WIDGET_SETTINGS_PAGE_NAME, self::TOP_AFFILAITES_REFRESHINTERVAL);
        	register_setting(self::TOP_AFFILAITES_WIDGET_SETTINGS_PAGE_NAME, self::TOP_AFFILAITES_CACHE);
        	register_setting(self::TOP_AFFILAITES_WIDGET_SETTINGS_PAGE_NAME, self::TOP_AFFILAITES_ORDER_BY);
        	register_setting(self::TOP_AFFILAITES_WIDGET_SETTINGS_PAGE_NAME, self::TOP_AFFILAITES_ORDER_ASC);
        	register_setting(self::TOP_AFFILAITES_WIDGET_SETTINGS_PAGE_NAME, self::TOP_AFFILAITES_LIMIT);
        	register_setting(self::TOP_AFFILAITES_WIDGET_SETTINGS_PAGE_NAME, self::TOP_AFFILAITES_ROW_TEMPLATE);

        	register_setting(self::SHORTCODES_SETTINGS_PAGE_NAME, self::AFFILAITE_SHORTCODE_CACHE);

        	register_setting(self::INTEGRATIONS_SETTINGS_PAGE_NAME, self::CONTACT7_SIGNUP_COMMISSION_ENABLED);
        	register_setting(self::INTEGRATIONS_SETTINGS_PAGE_NAME, self::JOTFORM_COMMISSION_ENABLED);
        	register_setting(self::INTEGRATIONS_SETTINGS_PAGE_NAME, self::WOOCOMM_COMMISSION_ENABLED);
        	register_setting(self::INTEGRATIONS_SETTINGS_PAGE_NAME, self::MARKETPRESS_COMMISSION_ENABLED);
        	register_setting(self::INTEGRATIONS_SETTINGS_PAGE_NAME, self::MEMBERPRESS_COMMISSION_ENABLED);
        	register_setting(self::INTEGRATIONS_SETTINGS_PAGE_NAME, self::SIMPLEPAYPRO_COMMISSION_ENABLED);
        	register_setting(self::INTEGRATIONS_SETTINGS_PAGE_NAME, self::WLM_COMMISSION_ENABLED);

        	register_setting(self::JOTFORM_CONFIG_PAGE, self::JOTFORM_TOTAL_COST);
        	register_setting(self::JOTFORM_CONFIG_PAGE, self::JOTFORM_COMMISSION_CAMPAIGN);
        	register_setting(self::JOTFORM_CONFIG_PAGE, self::JOTFORM_PRODUCTID);
        	register_setting(self::JOTFORM_CONFIG_PAGE, self::JOTFORM_DATA1);
        	register_setting(self::JOTFORM_CONFIG_PAGE, self::JOTFORM_DATA2);
        	register_setting(self::JOTFORM_CONFIG_PAGE, self::JOTFORM_DATA3);
        	register_setting(self::JOTFORM_CONFIG_PAGE, self::JOTFORM_DATA4);
        	register_setting(self::JOTFORM_CONFIG_PAGE, self::JOTFORM_DATA5);

        	register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_PERPRODUCT);
        	register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_PRODUCT_ID);
        	register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_STATUS_UPDATE);
        	register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_DATA1);
        	register_setting(self::WOOCOMM_CONFIG_PAGE, self::WOOCOMM_CAMPAIGN);

        	register_setting(self::MARKETPRESS_CONFIG_PAGE, self::MARKETPRESS_PERPRODUCT);
        	register_setting(self::MARKETPRESS_CONFIG_PAGE, self::MARKETPRESS_STATUS_UPDATE);
        	register_setting(self::MARKETPRESS_CONFIG_PAGE, self::MARKETPRESS_TRACK_DATA1);

        	register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_ENABLE_LIFETIME);
        	register_setting(self::MEMBERPRESS_CONFIG_PAGE, self::MEMBERPRESS_TRACK_RECURRING);

        	register_setting(self::SIMPLEPAYPRO_CONFIG_PAGE, self::SIMPLEPAYPRO_CAMPAIGN);

        	register_setting(self::WLM_CONFIG_PAGE, self::WLM_TRACK_REGISTRATION);
        	register_setting(self::WLM_CONFIG_PAGE, self::WLM_TRACK_RECURRING);

        	register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_AMOUNT);
        	register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_CAMPAIGN);
        	register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_FORM);
        	register_setting(self::CONTACT7_SIGNUP_COMMISSION_CONFIG_PAGE, self::CONTACT7_CONTACT_COMMISSION_STORE_FORM);
        }

        private function getFormData() {
            if (count($this->Contact7PostedData) == 0) {
                return '';
            }
            $output = '';
            foreach ($this->Contact7PostedData as $key => $value) {
                $output .= $key . ': ' . $value . ', ';
            }
            return substr($output,0,-2);
        }

        private function commissionEnabledForForm($form) {
            if (get_option(self::CONTACT7_CONTACT_COMMISSION_FORM) == '0') {
                return true;
            }
            return get_option(self::CONTACT7_CONTACT_COMMISSION_FORM) == $form->id;
        }

        public function addContactForm7ContactCommission($form) {
            if (!$this->contactForm7ContactCommissionEnabled()) {
                $this->_log(__('Contact form 7 contact commission disabled. Skipping action.'));
                return $form;
            }
            if (!$this->commissionEnabledForForm($form)) {
                $this->_log(__('Contact form 7 contact commission not enabled for form ' . $form->unit_tag . '. Skipping action.'));
                return $form;
            }
            $saleTracker = new Pap_Api_SaleTracker($this->getApiSessionUrl());
            $sale = $saleTracker->createSale();
            $sale->setTotalCost(get_option(self::CONTACT7_CONTACT_COMMISSION_AMOUNT));
            $sale->setProductID($form->title);
            if ($this->contactForm7ContactCommissionStoreForm()) {
                $sale->setData1($this->getFormData());
            }
            if (get_option(self::CONTACT7_CONTACT_COMMISSION_CAMPAIGN) != '') {
                $sale->setCampaignId(get_option(self::CONTACT7_CONTACT_COMMISSION_CAMPAIGN));
            }
            try {
                $saleTracker->register();
            } catch (Exception $e) {
                $this->_log(__('Error during registering contact commission: ' . $e->getMessage()));
            }
        }

        public function saveContactForm7FormData($posted_data) {
            $this->Contact7PostedData = $posted_data;
            return $posted_data;
        }

        // WooCommerce
        public function wooAddThankYouPageTrackSale($order_id) {
        	$order = wc_get_order($order_id);
        	if (get_option(postaffiliatepro::WOOCOMM_COMMISSION_ENABLED) != 'true') {
        		echo "<!-- Post Affiliate Pro sale tracking error - tracking not enabled -->\n";
        		return $order_id;
        	}
        	if (empty($order)) {
        		echo '<!-- Post Affiliate Pro sale tracking error - no order loaded for order ID '.$order_id." -->\n";
        		return $order_id;
        	}
        	if (isset($_GET['customGateway'])) {
        		echo "<!-- Post Affiliate Pro sale tracking - no sale tracker needed -->\n";
        		return $order_id;
        	}
        	$this->trackWooOrder($order);

        	return $order_id;
        }

		private function trackWooOrder($order) {
			$orderId = $order->id;
			echo "<!-- Post Affiliate Pro sale tracking -->\n";
			echo $this->getPAPTrackJSDynamicCode();
			echo '<script type="text/javascript">';
			echo 'PostAffTracker.setAccountId(\''.$this->getAccountName().'\');';

			if (function_exists('wcs_get_subscriptions_for_order')) {
				$subscriptions = wcs_get_subscriptions_for_order($orderId);
				if (!empty($subscriptions)) {
					foreach($subscriptions as $key => $value) { // take the first and leave
						$orderId = $key;
						break;
					}
				}
			}

			if (get_option(postaffiliatepro::WOOCOMM_PERPRODUCT) === 'true') {
        		$i = 1;
        		foreach ($order->get_items() as $item) {
        			$itemprice = $item['line_total'];
        			$couponCode = '';

        			try { //if coupon has been used, set the last one in the setCoupon() parameter
        				$coupon = $order->get_used_coupons();
        				$couponToBeUsed = (count($coupon)>1 ? count($coupon)-1 : 0);

        				if (isset($coupon[$couponToBeUsed])) {
        					$itemcount = $order->get_item_count($type = '');
        					$orderdiscount = $order->get_order_discount();

        					if ($itemcount > 0) {
        						$discountperitem = $orderdiscount / $itemcount;
        						$itemprice = $item['line_total'] - $discountperitem;
        					}
        					$couponCode = $coupon[$couponToBeUsed];
        				}
        			}
        			catch (Exception $e) {
        				//echo "<!--Error: ".$e->getMessage()."-->";
        			}

        			echo "var sale$i = PostAffTracker.createSale();\n";
        			echo "sale$i.setTotalCost('".$itemprice."');\n";
        			echo "sale$i.setOrderID('$orderId($i)');\n";
        			echo "sale$i.setProductID('".$this->getTrackingProductID($order, $item)."');\n";
        			echo "sale$i.setCurrency('".$order->get_order_currency()."');\n";
        			echo "sale$i.setCoupon('".$couponCode."');\n";
        			echo "sale$i.setData1('".$this->getTrackingData1($order)."');\n";
        			if (get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== '' &&
        					get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== null &&
        					get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== 0 &&
        					get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== '0') {
        				echo "sale$i.setCampaignID('".get_option(postaffiliatepro::WOOCOMM_CAMPAIGN)."');\n";
        			}
        			echo "PostAffTracker.register();\n";
        			$i++;
        		}
        	}
        	else {
        		echo "var sale = PostAffTracker.createSale();\n";
        		echo "sale.setTotalCost('".($order->order_total - $order->order_shipping)."');\n";
        		echo "sale.setOrderID('$orderId(1)');\n";
        		echo "sale.setCurrency('".$order->get_order_currency()."');\n";
        		echo "sale.setProductID('".$this->getTrackingProductIDsLine($order)."');\n";
        		echo "sale.setData1('".$this->getTrackingData1($order)."');\n";

        		if (get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== '' &&
        				get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== null &&
        				get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== 0 &&
        				get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== '0') {
        			echo "sale.setCampaignID('".get_option(postaffiliatepro::WOOCOMM_CAMPAIGN)."');\n";
        		}
        		try {
        			$coupon = $order->get_used_coupons();
        			echo "sale.setCoupon('".$coupon[0]."');\n";
        		} catch (Exception $e) {
        			//
        		}
        		echo 'PostAffTracker.register();';
        	}
        	echo '</script>';
        	return true;
        }

        private function getTrackingProductID($order, $item) {
            $product = $order->get_product_from_item($item);

            switch (get_option(postaffiliatepro::WOOCOMM_PRODUCT_ID)) {
                case 'id':
                    return $product->id;
                case 'sku':
                    if (!empty($product->sku)) {
                        return $product->sku;
                    } else {
                        return $product->id;
                    }
                case 'categ':
                    $categories = explode(',',$product->get_categories(','));
                    return $categories[0];
                case 'role':
                    try {
                        $users = new WP_User($order->user_id);
                        if (isset($user->roles[0])) {
                            return $user->roles[0];
                        } else {
                            break;
                        }
                    } catch (Exception $e){
                        break;
                    }
            }
        	return '';
        }

        private function getTrackingProductIDsLine($order) {
        	$productSelection = get_option(postaffiliatepro::WOOCOMM_PRODUCT_ID);
        	if (empty($productSelection)) {
        		return '';
        	}

        	$line = '';
        	foreach ($order->get_items() as $item) {
                $line .= $this->getTrackingProductID($order, $item).', ';
        	}
        	if (!empty($line)) {
            	$line = substr($line,0,-2);
        	}
        	return $line;
        }

        private function getTrackingData1($order) {
        	if (get_option(postaffiliatepro::WOOCOMM_DATA1) === 'id') {
        		return $order->user_id;
        	}
        	if (get_option(postaffiliatepro::WOOCOMM_DATA1) === 'email') {
        		return $order->billing_email;
        	}
        	return '';
        }

        public function wooModifyPaypalArgs($array) {
        	if (strpos($array['notify_url'], '?')) {
        		$array['notify_url'] .= '&';
        	} else {
        		$array['notify_url'] .= '?';
        	}
        	$array['notify_url'] .= 'pap_custom='.$_REQUEST['pap_custom'];
        	if (isset($_REQUEST['pap_IP'])) {
                $array['notify_url'] .= '&pap_IP='.$_REQUEST['pap_IP'];
        	}
        	if (strpos($array['return'], '?')) {
        		$array['return'] .= '&';
        	} else {
        		$array['return'] .= '?';
        	}
        	$array['return'] .= 'customGateway=paypal';
        	return $array;
        }

        public function wooRecurringCommission($renewal_order, $subscription) { // wcs_get_subscriptions_for_order($order_id)
          if (!is_object($subscription)) {
      			$subscription = wcs_get_subscription($subscription);
      		}

      		if (!is_object($renewal_order)) {
      			$renewal_order = wc_get_order($renewal_order);
      		}

        	// try to recurr a commission with order ID $subscription->id
        	$session = $this->getApiSession();
        	if ($session === null) {
        		$this->_log(__('We have no session to PAP installation! Recurring commission failed.'));
        		return $renewal_order;
        	}

        	if (!$this->fireRecurringCommissions($subscription->id.'(1)',$session)) {
        	    // creating recurring commissions failed, create a new commission instead
        	    $this->_log(__('Creating new commissions with order ID ').$renewal_order->id.'(1)');
        	    $this->trackWooOrder($renewal_order.'(1)');
        	}

        	return $renewal_order;
        }

        private function fireRecurringCommissions($orderId,$session) {
            $recurringCommission = new Pap_Api_RecurringCommission($session);
            $recurringCommission->setOrderId($orderId);
            try {
                $recurringCommission->createCommissions();
            } catch (Exception $e) {
                $this->_log(__('Can not process recurring commission: ' . $e->getMessage()));
                return false;
            }
            return true;
        }

        public function wooProcessPaypalIPN($post_data) {
        	$post_data['payment_status'] = strtolower($post_data['payment_status']);
        	if (empty($post_data['custom'])) {
        		return false;
        	}
        	if (!$order = $this->get_paypal_order($post_data['custom'])) {
        		return false;
        	}

        	if ($post_data['payment_status'] == 'completed') {
    			if (get_option(postaffiliatepro::WOOCOMM_PERPRODUCT) === 'true') {
    				foreach ($order->get_items() as $item) {
    					$itemprice = $item['line_total'];
    					$couponCode = '';

    					try { //if coupon has been used, set the last one in the setCoupon() parameter
    						$coupon = $order->get_used_coupons();
    						$couponToBeUsed = (count($coupon)>1 ? count($coupon)-1 : 0);

    						if (isset($coupon[$couponToBeUsed])) {
    							$itemcount = $order->get_item_count($type = '');
    							$orderdiscount = $order->get_order_discount();

    							if ($itemcount > 0) {
    								$discountperitem = $orderdiscount / $itemcount;
    								$itemprice = $item['line_total'] - $discountperitem;
    							}
    							$couponCode = $coupon[$couponToBeUsed];
    						}
    					}
    					catch (Exception $e) {
    						//echo "<!--Error: ".$e->getMessage()."-->";
    					}

    					$query = 'AccountId='.substr($_GET['pap_custom'],0,8). '&visitorId='.substr($_GET['pap_custom'],-32);
    					if (isset($_GET['pap_IP'])) {
        					$query .= '&ip='.$_GET['pap_IP'];
    					}
    					$query .= "&TotalCost=$itemprice&OrderID=".$order->id."($i)";
    					$query .= '&ProductID='.urlencode($this->getTrackingProductID($order, $item));
    					$query .= '&Currency='.$order->get_order_currency()."&Coupon=$couponCode";
    					$query .= '&Data1='.urlencode($this->getTrackingData1($order));

    					if (get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== '' &&
    							get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== null &&
    							get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== 0 &&
    							get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== '0') {
    						$query .= '&CampaignID='.get_option(postaffiliatepro::WOOCOMM_CAMPAIGN);
    					}

    					$this->sendRequest($this->parseSaleScriptPath(), $query);
    					$i++;
    				}
    			}
    			else {
    				$coupon = $order->get_used_coupons();
    				$query = 'AccountId='.substr($_GET['pap_custom'],0,8). '&visitorId='.substr($_GET['pap_custom'],-32);
    				if (isset($_GET['pap_IP'])) {
        				$query .= '&ip='.$_GET['pap_IP'];
    				}
    				$query .= '&TotalCost='.($order->order_total - $order->order_shipping).'&OrderID='.$order->id.'(1)';
    				$query .= '&ProductID='.urlencode($this->getTrackingProductIDsLine($order));
    				$query .= '&Currency='.$order->get_order_currency().'&Coupon='.$coupon[0];
    				$query .= '&Data1='.urlencode($this->getTrackingData1($order));

    				if (get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== '' &&
    						get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== null &&
    						get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== 0 &&
    						get_option(postaffiliatepro::WOOCOMM_CAMPAIGN) !== '0') {
    					$query .= '&CampaignID='.get_option(postaffiliatepro::WOOCOMM_CAMPAIGN);
    				}

    				$this->sendRequest($this->parseSaleScriptPath(), $query);
    			}

    			return true;
    		}
        	return false;
        }

        public function wooOrderStatusChanged($orderId, $old_status, $new_status) {
        	if (get_option(postaffiliatepro::WOOCOMM_STATUS_UPDATE) !== 'true') {
        		return false;
        	}

        	$this->_log(__('Received status '.$new_status));

	        switch ($new_status) {
	            case 'completed':
	                $status = 'A';
	                break;
	            case 'processing':
	            case 'on-hold':
	                $status = 'P';
	                break;
	            case 'cancelled':
	            case 'failed':
	                $status = 'D';
	                break;
	            case 'refunded':
	                return $this->refundTransaction($orderId);
	                break;
	            default:
	            	$status = '';
	        }

            if ($status == '') {
            	$this->_log('Unsupported status '.$new_status);
            	return false;
            }

        	return $this->changeOrderStatus($orderId, $status);
        }

        public function wooSubscriptionStatusChanged($orderId, $old_status, $new_status) {
            if ($new_status != 'cancelled') {
                return false;
            }
            $session = $this->getApiSession();
            if ($session === null) {
                $this->_log(__('We have no session to PAP installation! Transaction status change failed.'));
                return;
            }
            // load recurring order ID
            $request = new Gpf_Rpc_GridRequest('Pap_Features_RecurringCommissions_RecurringCommissionsGrid', 'getRows', $session);
            $request->addFilter('orderid', 'L', $orderId.'(%');
            $recurringId = array();
            try {
                $request->sendNow();
                $grid = $request->getGrid();
                $recordset = $grid->getRecordset();
                foreach($recordset as $rec) {
                    $recurringId[] = $rec->get('orderid');
                }
            } catch (Exception $e) {
                $this->_log(__('A problem occurred while transaction status change with API: '.$e->getMessage()));
                return false;
            }

            if ($recurringId == '') {
                $this->_log(__('Nothing to change, the commission does not exist in PAP'));
                return false;
            }

            $request = new Gpf_Rpc_FormRequest('Pap_Features_RecurringCommissions_RecurringCommissionsForm', 'changeStatus', $session);
            $request->addParam('ids', new Gpf_Rpc_Array($ids));
            $request->addParam('status', 'D');
            try {
                $request->sendNow();
            } catch (Exception $e) {
                $this->_log(__('A problem occurred while transaction status change with API: '.$e->getMessage()));
                return false;
            }

            return true;
        }

        public function WLMnewUserRegistration($message, WishListMemberPluginMethods $functions) {
            if (get_option(postaffiliatepro::WLM_COMMISSION_ENABLED) !== 'true') {
                return $message;
            }
            $levels = new WLMAPIMethods();
            $levels = $levels->get_level($_POST['wpm_id']);

            $members = wlmapi_get_level_member_data($_POST['wpm_id'],$_POST['mergewith']);

            $message = str_replace('(wlmredirect,3000)', '(wlmredirect,5000)', $message);
            $message = str_replace('<meta http-equiv="refresh" content="3', '<meta http-equiv="refresh" content="5', $message);
            $message .= "<!-- Post Affiliate Pro sale tracking -->\n".
                $this->getPAPTrackJSDynamicCode().'<script type="text/javascript">'.
                "PostAffTracker.setAccountId('".$this->getAccountName()."');\n
                var sale = PostAffTracker.createSale();\n
                sale.setProductID('".$levels['level']['name']."');\n
                sale.setOrderID('".$members['member']['level']->TxnID."');\n
                sale.setData1('".$_POST['email']."');\n";

            if (get_option(postaffiliatepro::WLM_TRACK_REGISTRATION) != '') { // action code is set
                $message .= "var action = PostAffTracker.createAction('".get_option(postaffiliatepro::WLM_TRACK_REGISTRATION)."');\n
                action.setOrderID('".$_POST['firstname'].' '.$_POST['lastname']."');\n
                action.setData1('".$_POST['email']."');\n";
            }

            return $message."PostAffTracker.register();\n</script><!-- /Post Affiliate Pro sale tracking -->\n";
        }

        public function WLMRecurringCommission() {
            if (get_option(postaffiliatepro::WLM_COMMISSION_ENABLED) !== 'true') {
                return $message;
            }
            $orderId = $_POST['sctxnid'];

            $session = $this->getApiSession();
            if ($session === null) {
                $this->_log(__('We have no session to PAP installation! Recurring commission failed.'));
                return false;
            }

            if ($orderId == '' || $orderId == null) {
                $this->_log(__('No order ID found! Recurring commission failed.'));
                return false;
            }

            if (!$this->fireRecurringCommissions($orderId,$session)) {
                return false;
            }
            return true;
        }

        public function MemberPressTrackSale($txn) {
            // $txn->amount, $txn->product_id, $txn->id, $txn->usr->user_email ($txn->user_id), $txn->subscription_id (if subscription)
            if (get_option(postaffiliatepro::MEMBERPRESS_COMMISSION_ENABLED) !== 'true') {
                return false;
            }

            $accountID = '';
            $visitorID = '';
            if (isset($_REQUEST['pap_custom']) && ($_REQUEST['pap_custom'] != '')) {
                $visitorID = substr($_REQUEST['pap_custom'],-32);
            }
            if (isset($_REQUEST['pap_custom']) && ($_REQUEST['pap_custom'] != '')) {
                $accountID = substr($_REQUEST['pap_custom'],0,8);
            }

            $query = 'AccountId='.$accountID. '&visitorId='.$visitorID.
                '&TotalCost='.$txn->amount.'&ProductID='.$txn->product_id.'&OrderID=';

            if (isset($txn->subscription_id)) {
                $query .= $txn->subscription_id;
            } else {
                $query .= $txn->id;
            }
            if (get_option(postaffiliatepro::MEMBERPRESS_ENABLE_LIFETIME) === 'true') {
                $query .= '&Data1='.$txn->user_id;
        	}
        	if (isset($_REQUEST['pap_IP'])) {
        	    $query .= '&ip='.$_REQUEST['pap_IP'];
        	}

            $this->sendRequest($this->parseSaleScriptPath(), $query);
            return $txn;
        }

        public function MemberPressRecurringSale(MeprEvent $event) {
            $txn = new MeprTransaction($event->evt_id);
            if (get_option(postaffiliatepro::MEMBERPRESS_TRACK_RECURRING) !== 'true') {
                $this->_log(__('Recurring commissions are not enabled, ending'));
                return false;
            }

            // try to recurr a commission with order ID $txn->subscription_id
            $session = $this->getApiSession();
            if ($session === null) {
                $this->_log(__('We have no session to PAP installation! Recurring commission failed.'));
                return $renewal_order;
            }

            if (!$this->fireRecurringCommissions($txn->subscription_id,$session)) {
                // creating recurring commissions failed, create a new commission instead
                $this->_log(__('Creating a new commissions with order ID ').$txn->subscription_id);
                $this->MemberPressTrackSale($txn);
            }
        }

        public function MarketpressThankYouPage($text, MP_Order $order) {
            if (get_option(postaffiliatepro::MARKETPRESS_COMMISSION_ENABLED) !== 'true') {
                return false;
            }

            $text .= "<!-- Post Affiliate Pro sale tracking -->\n".
$this->getPAPTrackJSDynamicCode().'<script type="text/javascript">'.
"PostAffTracker.setAccountId('".$this->getAccountName()."');";

            if (get_option(postaffiliatepro::MARKETPRESS_PERPRODUCT) !== 'true') {
                $text .= "var sale = PostAffTracker.createSale();
sale.setTotalCost('".$order->get_meta('mp_order_total', '')."');
sale.setOrderID('".$order->get_id()."(1)');
sale.setCurrency('".$order->get_meta('mp_payment_info->currency', '')."');";
                if (get_option(postaffiliatepro::MARKETPRESS_TRACK_DATA1) !== 'true') {
                    $text .= "sale.setData1('".$order->get_meta('mp_billing_info->email', '')."');";
                }
                $text .= 'PostAffTracker.register();';
            } else {
                $cart = $order->get_meta('mp_cart_items');
                if (!$cart) {
                    $cart = $order->get_cart();
                }
                if (is_array($cart)) {
                    $i = 1;
                    foreach ($cart as $product_id => $items)
                    	foreach ($items as $item) {
                    	    if($item['quantity'] >= MP_BULK_AMOUNT_LEX) {
                    	        $item['price'] = $item['price'] * MP_BULK_PERCENT_LEX;
                    	    }

                            $text .= "var sale$i = PostAffTracker.createSale();
sale$i.setTotalCost('".($item['price']*$item['quantity'])."');
sale$i.setOrderID('".$order->get_id()."($i)');
sale$i.setProductID('".(($item['SKU'] == '')?$item['name']:$item['SKU'])."');
sale$i.setCurrency('".$order->get_meta('mp_payment_info->currency', '')."');";
                            if (get_option(postaffiliatepro::MARKETPRESS_TRACK_DATA1) !== 'true') {
                                $text .= "sale$i.setData1('".$order->get_meta('mp_billing_info->email', '')."');";
                            }
                            $text .= 'PostAffTracker.register();';
                            $i++;
                    	}
                } else {
                    $text .= "var sale = PostAffTracker.createSale();
sale.setTotalCost('".$order->get_meta('mp_order_total', '')."');
sale.setOrderID('".$order->get_id()."(1)');
sale.setCurrency('".$order->get_meta('mp_payment_info->currency', '')."');";
                    if (get_option(postaffiliatepro::MARKETPRESS_TRACK_DATA1) !== 'true') {
                        $text .= "sale.setData1('".$order->get_meta('mp_billing_info->email', '')."');";
                    }
                    $text .= 'PostAffTracker.register();';
                }
            }
            $text .= '</script>';

            return $text."<!-- /Post Affiliate Pro sale tracking -->\n";
        }

        public function MarketpressChangeOrderStatusPaid(MP_Order $order) {
            return $this->changeOrderStatus($order->get_id(), 'A');
        }

        public function MarketpressChangeOrderStatusDeclined(MP_Order $order) {
            return $this->changeOrderStatus($order->get_id(), 'D');
        }

        public function SimplePayProAddCodeToPaymentButton($string = '') {
            $formCode = $this->addHiddenFieldToPaymentForm(true);
            return $string.$formCode;
        }

        public function SimplePayProHandleCharge($charge) {
            $query = 'AccountId='.substr($_POST['pap_custom'],0,8). '&visitorId='.substr($_POST['pap_custom'],-32);
            if (isset($_POST['pap_IP'])) {
                $query .= '&ip='.$_POST['pap_IP'];
            }
            $query .= '&TotalCost='.($_POST['sc-amount']/100).'&OrderID='.$charge->id;
            $query .= '&ProductID='.urlencode($_POST['sc-description']).'&Currency='.$_POST['sc-currency'];
            $query .= '&Data1='.urlencode($_POST['stripeEmail']);

            if (get_option(postaffiliatepro::SIMPLEPAYPRO_CAMPAIGN) !== '' &&
                get_option(postaffiliatepro::SIMPLEPAYPRO_CAMPAIGN) !== null &&
                get_option(postaffiliatepro::SIMPLEPAYPRO_CAMPAIGN) !== 0 &&
                get_option(postaffiliatepro::SIMPLEPAYPRO_CAMPAIGN) !== '0') {
                $query .= '&CampaignID='.get_option(postaffiliatepro::SIMPLEPAYPRO_CAMPAIGN);
            }

            $this->sendRequest($this->parseSaleScriptPath(), $query);
        }

        private function getTransactionIDsByOrderID($orderId, $session, $limit = 100) {
            $ids = array();
            if (($orderId == '') ||$orderId == null) {
                return $ids;
            }
            $request = new Pap_Api_TransactionsGrid($session);
            $request->addFilter('orderid', Gpf_Data_Filter::LIKE, $orderId.'(%');
            $request->setLimit(0, $limit);
            if ($limit == 1) {
                $request->addParam('sort_col', 'dateinserted');
                $request->addParam('sort_asc', 'false');
                $request->addFilter('rstatus','IN','A,P');
            }

            try {
                $request->sendNow();
                $grid = $request->getGrid();
                $recordset = $grid->getRecordset();
                foreach($recordset as $rec) {
                    $ids[] = $rec->get('id');
                }
            } catch (Exception $e) {
                $this->_log(__('A problem occurred while loading transactions with API: '.$e->getMessage()));
            }
            return $ids;
        }

        private function changeOrderStatus($orderId, $status) {
            $session = $this->getApiSession();
            if ($session === null) {
                $this->_log(__('We have no session to PAP installation! Transaction status change failed.'));
                return;
            }
            $ids = $this->getTransactionIDsByOrderID($orderId, $session);
            if (empty($ids)) {
                $this->_log(__('Nothing to change, the commission does not exist in PAP'));
                return true;
            }

            $request = new Gpf_Rpc_FormRequest('Pap_Merchants_Transaction_TransactionsForm', 'changeStatus', $session);
            $request->addParam('ids',new Gpf_Rpc_Array($ids));
            $request->addParam('status',$status);
            try {
                $request->sendNow();
            } catch (Exception $e) {
                $this->_log(__('A problem occurred while transaction status change with API: '.$e->getMessage()));
                return false;
            }

            return true;
        }

        private function refundTransaction($orderId) {
            $limit = 100;
            if (function_exists('wcs_get_subscriptions_for_order')) { // we will have to refund one of the recurring commissions
                $subscriptions = wcs_get_subscriptions_for_order($orderId);
                if (!empty($subscriptions)) {
                    foreach($subscriptions as $key => $value) { // take the first and leave
                        $orderId = $key;
                        $limit = 1;
                        break;
                    }
                }
            }

            $session = $this->getApiSession();
            if ($session === null) {
                $this->_log(__('We have no session to PAP installation! Transaction status change failed.'));
                return;
            }
            $ids = $this->getTransactionIDsByOrderID($orderId, $session, $limit);
            if (empty($ids)) {
                $this->_log(__('Nothing to change, the commission does not exist in PAP'));
                return true;
            }

            $request = new Gpf_Rpc_FormRequest('Pap_Merchants_Transaction_TransactionsForm', 'makeRefundChargeback', $session);
            $request->addParam('ids',new Gpf_Rpc_Array($ids));
            $request->addParam('status','R');
            $request->addParam('merchant_note','Refunded automatically from WooCommerce');
            try {
                $request->sendNow();
            } catch (Exception $e) {
                $this->_log(__('A problem occurred while transaction status change with API: '.$e->getMessage()));
                return false;
            }

            return true;
        }

        private function get_paypal_order($raw_custom) {
        	if (($custom = json_decode($raw_custom)) && is_object($custom)) {
        		$order_id  = $custom->order_id;
        		$order_key = $custom->order_key;
        	}
        	elseif (preg_match('/^a:2:{/', $raw_custom) && !preg_match('/[CO]:\+?[0-9]+:"/', $raw_custom) &&
        			($custom = maybe_unserialize($raw_custom))) {
        		$order_id = $custom[0];
        		$order_key = $custom[1];
        	}
        	else {
        		$this->_log('PayPal IPN handling: Order ID and key were not found in "custom".');
        		return false;
        	}

        	if (!$order = wc_get_order($order_id)) {
        		// We have an invalid $order_id, probably because invoice_prefix has changed.
        		$order_id = wc_get_order_id_by_order_key($order_key);
        		$order = wc_get_order($order_id);
        	}

        	if (!$order || $order->order_key !== $order_key) {
        		$this->_log('PayPal IPN handling: Order keys do not match.');
        		return false;
        	}

        	return $order;
        }

        private function sendRequest($url, $query = null, $method = 'GET') {
        	$curl = curl_init();
        	if ($method == 'POST') {
        		curl_setopt($curl, CURLOPT_POST, 1);
        		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
        		curl_setopt($curl, CURLOPT_URL, str_replace('https://','http://',$url));
        	}
        	else {
        		if (is_array($query)) {
        			$query = http_build_query($query);
        		}
        		curl_setopt($curl, CURLOPT_URL, $url.((strpos($url, '?') === false)?'?':'&').$query);
        	}
        	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        	$response = curl_exec($curl);
        	$error = curl_error($curl);
        	curl_close($curl);
        	if (!$response) {
        		$this->_log($error);
        		return false;
        	} else {
        		return true;
        	}
        }

        public function getAffiliateShortCode($attr, $content = null) {
            $affiliate = new Shortcode_Affiliate();
            return $affiliate->getCode($attr, $content);
        }

        public function getParentAffiliateShortCode($attr, $content = null) {
            $parent = new Shortcode_Affiliate();
            return $parent->getCode($attr, $content, true);
        }

        public function widgetTopAffiliates($args) {
            $widget = new postaffiliatepro_Widget_TopAffiliates($args);
            $widget->render();
        }

        private function getPAPTrackJSDynamicCode() {
          return '<script type="text/javascript">
document.write(decodeURI("%3Cscript id=\'pap_x2s6df8d\' src=\'" + (("https:" == document.location.protocol) ? "https://" : "http://") +
"'.$this->parseServerPathForClickTrackingCode().'scripts/trackjs.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>';
        }

        private function parseServerPathForClickTrackingCode() {
            $url = str_replace ('https://', '', get_option(self::PAP_URL_SETTING_NAME));
            $url = str_replace ('http://', '', $url);
            if (substr($url,-1) != '/') {
                $url .= '/';
            }
            return $url;
        }

        private function parseSaleScriptPath() {
        	$url = str_replace ('https://', 'http://', get_option(self::PAP_URL_SETTING_NAME));
        	if (substr($url,-1) != '/') {
        		$url .= '/';
        	}
        	return $url.'scripts/sale.php';
        }

        public function insertIntegrationCodeToFooter($content) {
        	// exit if feed
        	if (is_feed()) {
        		return false;
        	}

        	// JotForm
        	if (get_option(self::JOTFORM_COMMISSION_ENABLED) === 'true') {
        		$jotform = new postaffiliatepro_Util_JotFormHelper();
        		$jotform->trackSubmission($this->parseServerPathForClickTrackingCode(), $this->getAccountName());
        	}

            if (get_option(self::CLICK_TRACKING_ENABLED_SETTING_NAME) != 'true') {
                return $content;
            }

            $result = $this->getPAPTrackJSDynamicCode().'<script type="text/javascript">
PostAffTracker.setAccountId(\''.$this->getAccountName().'\');
try {';
            if (get_option(self::CLICK_TRACKING_CAMPAIGN) != '' && get_option(self::CLICK_TRACKING_CAMPAIGN) != '0' &&
            		get_option(self::CLICK_TRACKING_CAMPAIGN) != null & get_option(self::CLICK_TRACKING_CAMPAIGN) != 0) {
            	$result .= "var CampaignID = '".get_option(self::CLICK_TRACKING_CAMPAIGN)."';\n";
            }
  			$result .= 'PostAffTracker.track();
} catch (err) { }
</script>';
			echo $result.$content;
        }

        private function resolveParentAffiliateFromCookie(Gpf_Api_Session $session, Pap_Api_Affiliate $affiliate) {
        	if (!empty($_REQUEST['pap_parent'])) {
        		$affiliate->setParentUserId($_REQUEST['pap_parent']);
        		$this->_log(__('Parent affiliate resolved from cookies: '.$_REQUEST['pap_parent']));
        		return true;
        	}

            $clickTracker = new Pap_Api_ClickTracker($session);
            try {
                $clickTracker->track();
            } catch (Exception $e) {
                $this->_log(__('Error running track:' . $e->getMessage()));
            }
            if ($clickTracker->getAffiliate() != null) {
                $affiliate->setParentUserId($clickTracker->getAffiliate()->getValue('userid'));
            } else {
                $this->_log(__('Parent affiliate not found from cookie'));
            }
        }

        private function resolveFirstAndLastName(WP_User $user, Pap_Api_Affiliate $affiliate) {
            if ($user->first_name=='' && $user->last_name=='') {
                $affiliate->setFirstname($user->nickname);
                $affiliate->setLastname(' ');
            } else {
                $affiliate->setFirstname(($user->first_name=='')?' ':$user->first_name);
                $affiliate->setLastname(($user->last_name=='')?' ':$user->last_name);
            }
        }

        /**
         * @return Pap_Api_Affiliate
         */
        private function initAffiliate(WP_User $user, Gpf_Api_Session $session) {
            $affiliate = new Pap_Api_Affiliate($session);
            $affiliate->setUsername($user->user_email);
            $affiliate->setRefid(preg_replace('([^a-zA-Z0-9_-])','_',$user->user_login));
            $this->resolveFirstAndLastName($user, $affiliate);
            $affiliate->setNotificationEmail($user->user_email);
            $affiliate->setData(1, __('User level: ') . $user->user_level);
            if (get_option(self::SIGNUP_INTEGRATION_USE_PHOTO) == 'true') {
                $affiliate->setPhoto(site_url('/avatar/user-'.$user->ID.'-96.png'));
                if (is_multisite()) {
                    $affiliate->setPhoto(network_site_url('/avatar/user-'.$user->ID.'-96.png'));
                }
            }
            return $affiliate;
        }

        private function setParentToAffiliate(Pap_Api_Affiliate $affiliate, Gpf_Api_Session $session) {
        	$parentSignup = get_option(self::SIGNUP_DEFAULT_PARENT_SETTING_NAME);
            if (!empty($parentSignup) && $parentSignup != 'from_cookie') {
                $affiliate->setParentUserId($parentSignup);
            }
            if ($parentSignup == 'from_cookie') {
                $this->resolveParentAffiliateFromCookie($session, $affiliate);
            }
        }

        private function setStatusToAffiliate(Pap_Api_Affiliate $affiliate) {
        	$status = get_option(self::SIGNUP_DEFAULT_STATUS_SETTING_NAME);
            if (!empty($status)) {
                $affiliate->setStatus($status);
            }
        }

        private function signupIntegrationEnabled() {
            return get_option(self::SIGNUP_INTEGRATION_ENABLED_SETTING_NAME) == 'true';
        }

        private function contactForm7ContactCommissionEnabled() {
            return postaffiliatepro_Util_ContactForm7Helper::formsExists() && get_option(self::CONTACT7_SIGNUP_COMMISSION_ENABLED) == 'true';
        }

        private function contactForm7ContactCommissionStoreForm() {
            return get_option(self::CONTACT7_CONTACT_COMMISSION_STORE_FORM) == 'true';
        }

        public function onNewUserRegistration($user_id) {
            if (!$this->signupIntegrationEnabled()) {
                $this->_log(__("Signup integration disabled - skipping new affiliate creation"));
                return;
            }
            $session = $this->getApiSession();
            if ($session===null) {
                $this->_log(__("We have no session to PAP installation! Registration of PAP user cancelled."));
                return;
            }
            $affiliate = $this->initAffiliate(new WP_User($user_id), $session);

            $this->setParentToAffiliate($affiliate, $session);

            $this->setStatusToAffiliate($affiliate);

            try {
                $affiliate->add();
            } catch (Exception $e) {
                $this->_log(__("Error adding affiliate" . $e->getMessage()));
                return;
            }

            if (get_option(self::SIGNUP_SEND_CONFIRMATION_EMAIL_SETTING_NAME) == 'true') {
                if (get_option(self::SIGNUP_INTEGRATION_ENABLED_SETTING_NAME) == 'false' || get_option('aff_notification_signup_approved_declined') == 'N') {
                    try {
                        $affiliate->sendConfirmationEmail();
                    } catch (Exception $e) {
                        $this->_log(__("Error on sending confirmation email"));
                        return;
                    }
                }
            }
            $this->processCampaigns($affiliate);
        }

        public function addHiddenFieldToRegistrationForm() {
        	if (get_option(self::PAP_URL_SETTING_NAME) == '') {
        		return false;
        	}
        	echo '<input type="hidden" name="pap_parent" value="" id="pap_xa77cb50a">'.
        		$this->getPAPTrackJSDynamicCode().'
<script type="text/javascript">
	PostAffTracker.writeAffiliateToCustomField(\'pap_xa77cb50a\');
</script>';
        }

        public function addHiddenFieldToPaymentForm($return = false) {
            $result = '<!-- Post Affiliate Pro integration snippet -->';
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $result .= '<input type="hidden" name="pap_IP" value="'.$_SERVER['REMOTE_ADDR'].'" />';
            }
            $result .= '<input type="hidden" name="pap_custom" value="" id="pap_dx8vc2s5" />'.
                    $this->getPAPTrackJSDynamicCode().'
      			<script type="text/javascript">
	      		  PostAffTracker.setAccountId(\''.$this->getAccountName().'\');
	              PostAffTracker.notifySale();
      			</script>
      			<!-- /Post Affiliate Pro integration snippet -->';
            if ($return) {
                return $result;
            } else {
                echo $result;
                return;
            }
        }

        private function getCampaignOption($campaignId, $name) {
            $value = get_option(self::SIGNUP_CAMPAIGNS_SETTINGS_SETTING_NAME);
            if (!is_array($value)) {
                return '';
            }
            if (!array_key_exists($name . '-' . $campaignId, $value)) {
                return '';
            }
            return $value[$name . '-' . $campaignId];
        }

        private function assignToCampaign(Pap_Api_Affiliate $affiliate, $campaignId, $sendNotification) {
            try {
                $affiliate->assignToPrivateCampaign($campaignId, ($sendNotification=='true')?true:false);
            } catch (Exception $e) {
                $this->_log('Unable to assign user to private camapign ' . $campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID) . ', problem: ' . $e->getMessage());
            }
        }

        private function processCampaigns(Pap_Api_Affiliate $affiliate) {
            $campaigns = $this->getCampaignHelper()->getCampaignsList();
            if ($campaigns === null) {
                return;
            }
            foreach ($campaigns as $campaign) {
                if ($campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_TYPE) != postaffiliatepro_Util_CampaignHelper::CAMPAIGN_TYPE_PUBLIC) {
                    if ($this->getCampaignOption($campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID), postaffiliatepro_Form_Settings_CampaignInfo::ADD_TO_CAMPAIGN) == 'true') {
                        $this->assignToCampaign($affiliate, $campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID),
                        $this->getCampaignOption($campaign->get(postaffiliatepro_Util_CampaignHelper::CAMPAIGN_ID), postaffiliatepro_Form_Settings_CampaignInfo::SEND_NOTIFICATION_EMAIL));
                    }
                }
            }
        }

        public function onUpdateExistingUser($user_id) {
            if (!$this->signupIntegrationEnabled()) {
                $this->_log(__("Signup integratoin disabled - skipping upating existing affiliate"));
                return;
            }
            $session = $this->getApiSession();
            if ($session === null) {
                $this->_log(__("We have no session to PAP installation! Updating of PAP user cancelled."));
                return;
            }
            $user = new WP_User($user_id);
            $affiliate = new Pap_Api_Affiliate($session);
            $affiliate->setUsername($user->user_email);
            try {
                $affiliate->load();
            } catch (Exception $e) {
                $this->_log(__("Unable to load affiliate from Post Affiliate Pro. Update of user " . $user->nickname . " cancelled"));
                return;
            }
            $this->resolveFirstAndLastName($user, $affiliate);
            $affiliate->setNotificationEmail($user->user_email);
            $affiliate->setData(1, $user->user_level);
            $affiliate->save();
        }

        public function initAdminHeader($content) {
        	if (!is_feed()) {
        		echo $this->getStylesheetHeaderLink('style.css');
        	}
        	echo $content;
        }

        public function addSettingsLinkIntoPlugin($links) {
            return array_merge($links, array('<a href="'.admin_url('page.php?page=pap-top-level-options-handle').'">Settings</a>'));
        }

        public function addPrimaryConfigMenu() {
            add_menu_page(__('Post Affiliate Pro','pap-menu'), __('Post Affiliate Pro','pap-menu'), 'manage_options', 'pap-top-level-options-handle', array($this, 'printGeneralConfigPage'), $this->getPapIconURL());
            add_submenu_page('pap-top-level-options-handle', __('General','pap-menu'), __('General','pap-menu'), 'manage_options', 'pap-top-level-options-handle', array($this, 'printGeneralConfigPage'));
            add_submenu_page('pap-top-level-options-handle', __('Click tracking','pap-menu'), __('Click tracking','pap-menu'), 'manage_options', 'click-tracking-config-page', array($this, 'printClickTrackingConfigPage'));
            add_submenu_page('pap-top-level-options-handle', __('Signup','pap-menu'), __('Signup options','pap-menu'), 'manage_options', 'signup-config-page', array($this, 'printSignupConfigPage'));


            add_menu_page(__('Integrations','pap-integrations'), __('Integrations','pap-integrations'), 'manage_options', 'integrations-config-page-handle', array($this, 'printSpecialIntegrationsConfigPage'), $this->getPapIconURL());
            add_submenu_page('integrations-config-page-handle', __('General', 'pap-integrations'), __('General', 'pap-integrations'), 'manage_options', 'integrations-config-page-handle', array($this, 'printSpecialIntegrationsConfigPage'));
            // Contact form 7
            if (postaffiliatepro_Util_ContactForm7Helper::formsExists()) {
                add_submenu_page(
                    'integrations-config-page-handle',
                    __('Contact form 7','pap-integrations'),
                    __('Contact form 7','pap-integrations'),
                    'manage_options',
                    'contact-form-7-settings-page',
                    array($this, 'printContactForm7ConfigPage')
                );
            }
			// JotForm
            if (get_option(self::JOTFORM_COMMISSION_ENABLED) == 'true') {
                add_submenu_page(
                    'integrations-config-page-handle',
                    __('JotForm','pap-integrations'),
                    __('JotForm','pap-integrations'),
                    'manage_options',
                    'jotform-settings-page',
                    array($this, 'printJotFormConfigPage')
                );
            }
            // Marketpress
            if (get_option(self::MARKETPRESS_COMMISSION_ENABLED) == 'true') {
                add_submenu_page(
                    'integrations-config-page-handle',
                    __('Marketpress','pap-integrations'),
                    __('Marketpress','pap-integrations'),
                    'manage_options',
                    'marketpressintegration-settings-page',
                    array($this, 'printMarketpressConfigPage')
                    );
            }
            // MemberPress
            if (get_option(self::MEMBERPRESS_COMMISSION_ENABLED) == 'true') {
                add_submenu_page(
                    'integrations-config-page-handle',
                    __('MemberPress','pap-integrations'),
                    __('MemberPress','pap-integrations'),
                    'manage_options',
                    'memberpressintegration-settings-page',
                    array($this, 'printMemberPressConfigPage')
                    );
            }
            // Simple Pay Pro
            if (get_option(self::SIMPLEPAYPRO_COMMISSION_ENABLED) == 'true') {
                add_submenu_page(
                    'integrations-config-page-handle',
                    __('Simple Pay Pro','pap-integrations'),
                    __('Simple Pay Pro','pap-integrations'),
                    'manage_options',
                    'simplepayprointegration-settings-page',
                    array($this, 'printSimplePayProConfigPage')
                    );
            }
            // WishList Member
            if (get_option(self::WLM_COMMISSION_ENABLED) == 'true') {
                add_submenu_page(
                    'integrations-config-page-handle',
                    __('WishList Member','pap-integrations'),
                    __('WishList Member','pap-integrations'),
                    'manage_options',
                    'wlm-settings-page',
                    array($this, 'printWLMConfigPage')
                    );
            }
            // WooComm
            if (get_option(self::WOOCOMM_COMMISSION_ENABLED) == 'true') {
                add_submenu_page(
                        'integrations-config-page-handle',
                        __('WooCommerce','pap-integrations'),
                        __('WooCommerce','pap-integrations'),
                        'manage_options',
                        'woocommintegration-settings-page',
                        array($this, 'printWooCommConfigPage')
                        );
            }
        }

        public function printGeneralConfigPage() {
            $form = new postaffiliatepro_Form_Settings_General();
            $form->render();
        }
        public function printSignupConfigPage() {
            $form = new postaffiliatepro_Form_Settings_Signup();
            $form->render();
            return;
        }
        public function printClickTrackingConfigPage() {
            $form = new postaffiliatepro_Form_Settings_ClickTracking();
            $form->render();
            return;
        }
        public function printSpecialIntegrationsConfigPage() {
            $form = new postaffiliatepro_Form_Settings_Integrations();
            $form->render();
            return;
        }
        public function printContactForm7ConfigPage() {
            $form = new postaffiliatepro_Form_Settings_ContactForm7();
            $form->render();
            return;
        }
        public function printJotFormConfigPage() {
            $form = new postaffiliatepro_Form_Settings_JotForm();
            $form->render();
            return;
        }
        public function printWooCommConfigPage() {
        	$form = new postaffiliatepro_Form_Settings_WooComm();
        	$form->render();
        	return;
        }
        public function printMarketpressConfigPage() {
            $form = new postaffiliatepro_Form_Settings_Marketpress();
            $form->render();
            return;
        }
        public function printMemberPressConfigPage() {
            $form = new postaffiliatepro_Form_Settings_MemberPress();
            $form->render();
            return;
        }
        public function printSimplePayProConfigPage() {
            $form = new postaffiliatepro_Form_Settings_SimplePayPro();
            $form->render();
            return;
        }
        public function printWLMConfigPage() {
            $form = new postaffiliatepro_Form_Settings_WishListMember();
            $form->render();
            return;
        }
    }
}

$postaffiliatepro = new postaffiliatepro();
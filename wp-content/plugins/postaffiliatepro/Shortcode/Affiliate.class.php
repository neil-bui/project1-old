<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class Shortcode_Affiliate extends postaffiliatepro_Base {
    /**
     *
     * @var Shortcode_Cache
     */
    private static $cache = null;

    public function __construct() {
        if (self::$cache === null) {
            self::$cache = new Shortcode_Cache();
        }
    }

    /**
     * @return Pap_Api_Affiliate
     */
    private function loadAffiliate(Gpf_Api_Session $session, $parent = false) {
        global $current_user;
        $affiliate = new Pap_Api_Affiliate($session);
        $affiliate->setUsername($current_user->user_email);
        try {
            $affiliate->load();
        } catch (Exception $e) {
            $this->_log('Error getting affiliate by email, let\'s try it by login.');
            $affiliate->setUsername($current_user->user_login);
            try {
                $affiliate->load();
            } catch (Exception $e) {
                $this->_log('Error getting affiliate: ' . $e->getMessage());
                return null;
            }
        }
        if ($parent) {
            $parentId = $affiliate->getParentUserId();
            if ($parentId) {
                $parentAffiliate = new Pap_Api_Affiliate($session);
                $parentAffiliate->setUserid($parentId);
                $parentAffiliate->load();
                return $parentAffiliate;
            }
            return null;
        }
        return $affiliate;
    }

    public function getCode($atts, $content = null, $parent = false) {
        global $current_user;
        if ($current_user->ID == 0) {
            return;
        }
        $session = $this->getApiSession();
        if ($session == null) {
            $this->_log('Error getting session for login to PAP. Check WP logs for details.');
            return;
        }
        $affiliate = $this->loadAffiliate($session, $parent);
        if ($affiliate == null) {
            $this->_log('Error getting affiliate');
            return;
        }
        if (array_key_exists('item', $atts)) {
            if ($atts['item'] == 'name') {
                return $affiliate->getFirstname() . ' ' . $affiliate->getLastname();
            }
            if ($atts['item'] == 'loginurl') {
                if (array_key_exists('caption', $atts)) {
                    return '<a href="'.$this->getLoginUrl($affiliate->getField('authtoken')).'" target="_blank">' . $atts['caption'] . '</a>';
                } else {
                    return '<a href="'.$this->getLoginUrl($affiliate->getField('authtoken')).'" target="_blank">Affiliate panel</a>';
                }
            }
			if ($atts['item'] == 'loginurl_raw') {
                return $this->getLoginUrl($affiliate->getField('authtoken'));
            }
            return $affiliate->getField($atts['item']);
        }
    }

    private function getLoginUrl($authToken) {
        return get_option(postaffiliatepro::PAP_URL_SETTING_NAME) . 'affiliates/login.php?authToken=' . $authToken;
    }
}
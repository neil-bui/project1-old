<?php
if (!class_exists('postaffiliatepro_Base')) {
    class postaffiliatepro_Base {
        const IMG_PATH = 'resources/img/';
        const URL_SEPARATOR = '/';
        const CSS_PATH = 'resources/css/';

        private static $session = null;
        private static $campaignHelper = null;
        private $error = '';

        protected function _log($message) {
            if( WP_DEBUG === true ){
                if( is_array( $message ) || is_object( $message ) ){
                    $message = print_r( $message, true );
                }
                $message = 'PostAffiliatPro Wordpress plugin log: ' . $message;
                error_log($message);
                echo $message;
            }
        }

        /**
         * @return postaffiliatepro_Util_CampaignHelper
         */
        protected function getCampaignHelper() {
            if (self::$campaignHelper) {
                return self::$campaignHelper;
            }
            self::$campaignHelper = new postaffiliatepro_Util_CampaignHelper();
            return self::$campaignHelper;
        }

        public function getAccountName() {
            if (get_option(postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME) == '') {
                return postaffiliatepro::DEFAULT_ACCOUNT_NAME;
            }
            return get_option(postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME);
        }

        public function getError() {
            return $this->error;
        }

        protected function getPapVersion () {
            $url = get_option(postaffiliatepro::PAP_URL_SETTING_NAME);
            if (substr($url, -1) != '/') {
                $url .= '/';
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . 'api/version.php');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($result);
            if (!$xml) {
                $msg = "";
                foreach(libxml_get_errors() as $error) {
                    $msg .= $error->message;
                }
                $this->_log('Unable to parse application version number: ' . $msg);
                return _('unknown (possible less than 4.5.48.1)').$msg;
            }
            return (string) $xml->applications->pap->versionNumber;
        }

        protected function getApiSessionUrl() {
            $url = get_option(postaffiliatepro::PAP_URL_SETTING_NAME);
            if (substr($url, -1) != '/') {
                $url .= '/';
            }
            return $url . 'scripts/server.php';
        }

        /**
         * @return Gpf_Api_Session
         */
        protected function getApiSession() {
            if (self::$session !== null) {
                return self::$session;
            }
            $session = new Gpf_Api_Session($this->getApiSessionUrl());
            try {
                $login = $session->login(get_option(postaffiliatepro::PAP_MERCHANT_NAME_SETTING_NAME), get_option(postaffiliatepro::PAP_MERCHANT_PASSWORD_SETTING_NAME));
            } catch (Gpf_Api_IncompatibleVersionException $e) {
                $this->error = 'Unable to login into PAP installation because of icompatible versions (probably your API file here in WP installation is older than your PAP installation)';
                $this->_log(__("Unable to login into PAP installation because of icompatible versions (probably your API file here in WP installation is older than your PAP installation)"));
                return null;
            }
            if($login == false) {
                $this->error = $session->getMessage();
                $this->_log(__("Unable to login into PAP installation with given credentails: " . $session->getMessage()));
                return null;
            }
            self::$session = $session;
            return $session;
        }

        protected function getImgUrl() {
            return WP_PLUGIN_URL . self::URL_SEPARATOR . PAP_PLUGIN_NAME . self::URL_SEPARATOR . self::IMG_PATH;
        }

        protected function getCssUrl() {
            return WP_PLUGIN_URL . self::URL_SEPARATOR . PAP_PLUGIN_NAME . self::URL_SEPARATOR . self::CSS_PATH;
        }

        protected function getStylesheetHeaderLink($filename) {
            return '<link type="text/css" rel="stylesheet" href="' . $this->getCssUrl() . $filename . '?ver=' . PAP_PLUGIN_VERSION . '" />' . "\n";
        }
    }
}
<?php
/**
 *   @copyright Copyright (c) 2011 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

class postaffiliatepro_Form_Settings_General extends postaffiliatepro_Form_Base {
    public function __construct() {
        parent::__construct(postaffiliatepro::GENERAL_SETTINGS_PAGE_NAME, 'options.php');
    }

    protected function getTemplateFile() {
        return WP_PLUGIN_DIR . '/postaffiliatepro/Template/GeneralSettings.xtpl';
    }

    protected function initForm() {
        $url = get_option(postaffiliatepro::PAP_URL_SETTING_NAME);
        if (empty($url)) {
            $this->addTextBox(postaffiliatepro::PAP_URL_SETTING_NAME, 50);
        } else {
            if (substr($url, -1) != '/') {
                $url .= '/';
            }
            $this->addTextBox(postaffiliatepro::PAP_URL_SETTING_NAME, 50, $url, true);
        }

        $this->addTextBox(postaffiliatepro::PAP_MERCHANT_NAME_SETTING_NAME, 30);
        $this->addPassword(postaffiliatepro::PAP_MERCHANT_PASSWORD_SETTING_NAME, 30);
        $this->addTextBox(postaffiliatepro::CLICK_TRACKING_ACCOUNT_SETTING_NAME, 30, 'default1');
        $this->checkCredentails();
        $this->addSubmit();
    }

    private function checkCredentails() {
        $session = $this->getApiSession();
        $this->addHtml('login_check_ok', '');
        $this->addHtml('installation_info', '');
        $this->addHtml('error', '');
        $this->addHtml('notice', '');
        $notice = false;
        if ($session !== null) {
            $this->addHtml('login_check_ok', $this->getSectionCode('Successfully connected to your Post Affiliate Pro installation','style="color:#2B7508;"'));
            $this->addHtml('installation_info', $this->getSectionCode('Application version: <span style="font-style:italic;">'.$this->getPapVersion().'</span>'));
            $versionArray = explode('.',$this->getPapVersion());

            if ($versionArray[0] < 4) {
                $notice = true;
            } elseif ($versionArray[0] == 4) {
                if ($versionArray[1] < 5) {
                    $notice = true;
                } elseif ($versionArray[1] == 5) {
                    if ($versionArray[2] < 67) {
                        $notice = true;
                    } elseif ($versionArray[2] == 67) {
                        if ($versionArray[3] < 3) {
                            $notice = true;
                        }
                    }
                }
            }
        } else {
            $this->addHtml('error', $this->getSectionCode($this->getError(), 'style="color:#ff0000"'));
        }

        if ($notice) {
            $this->addHtml('notice', $this->getSectionCode('Your Post Affilate Pro version should be <strong>4.5.67.3 or higher<strong> to enjoy full functionality. Lower versions will not work properly.'));
        }
    }

    private function getSectionCode($content, $style = '') {
        return '<table class="form-table"><tr><td '.$style.'>'.$content."</td></tr></table>\n";
    }
}

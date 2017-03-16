<?php
class postaffiliatepro_Util_JotFormHelper extends postaffiliatepro_Base {

	public function trackSubmission($url, $account) {
		// check if this is a submission
		if (!isset($_REQUEST['enteryour']) && !isset($_REQUEST['email']) && !isset($_REQUEST['lastname'])) {
			return false;
		}

		// print sale tracker code
		$result = '<script type="text/javascript">
document.write(decodeURI("%3Cscript id=\'pap_x2s6df8d\' src=\'" + (("https:" == document.location.protocol) ? "https://" : "http://") +
"'.$url.'scripts/trackjs.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">'."
PostAffTracker.setAccountId('$account');

var sale = PostAffTracker.createSale();
sale.setOrderID('".$_REQUEST['email']."');\n";

    $total = get_option(postaffiliatepro::JOTFORM_TOTAL_COST);
    if (is_numeric($total)) {
      $result .= "sale.setTotalCost('$total');\n";
    }
    else {
      if (isset($_REQUEST[$total])) {
        $result .= "sale.setTotalCost('".$_REQUEST[$total]."');\n";
      }
    }

		$line = $this->checkRequestData('setProductID', get_option(postaffiliatepro::JOTFORM_PRODUCTID));
		if ($line == '') {
			$line = "sale.setProductID('".$_REQUEST['name']." ".$_REQUEST['lastname']."');\n";
		}
    $result .= $line;

		$line = $this->checkRequestData('setData1', get_option(postaffiliatepro::JOTFORM_DATA1));
		if ($line == '') {
			$line = "sale.setData1('".$_REQUEST['email']."');\n";
		}
    $result .= $line;

		$result .= $this->checkRequestData('setData2', get_option(postaffiliatepro::JOTFORM_DATA2));
		$result .= $this->checkRequestData('setData3', get_option(postaffiliatepro::JOTFORM_DATA3));
		$result .= $this->checkRequestData('setData4', get_option(postaffiliatepro::JOTFORM_DATA4));
		$result .= $this->checkRequestData('setData5', get_option(postaffiliatepro::JOTFORM_DATA5));
		$result .= $this->checkRequestData('setCampaignID', get_option(postaffiliatepro::JOTFORM_COMMISSION_CAMPAIGN));

		echo $result."PostAffTracker.register();</script>";
	}

	private function checkRequestData($function, $data) {
		if (empty($data)) {
			return '';
		}
		if (isset($_REQUEST[$data])) {
			return "sale.$function('".$_REQUEST[$data]."');\n";
		}
		else {
			return "sale.$function('".$data."');\n";
		}
	}

}

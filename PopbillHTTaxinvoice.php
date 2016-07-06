<?php
/**
* =====================================================================================
* Class for base module for Popbill API SDK. It include base functionality for
* RESTful web service request and parse json result. It uses Linkhub module
* to accomplish authentication APIs.
*
* This module uses curl and openssl for HTTPS Request. So related modules must
* be installed and enabled.
*
* http://www.linkhub.co.kr
* Author : Jeong Yohan (code@linkhub.co.kr)
* Written : 2015-07-10
* Contributor : Jeong YoHan (code@linkhub.co.kr)
* Updated : 2016-07-06
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

class HTTaxinvoiceService extends PopbillBase {

	public function __construct($LinkID,$SecretKey) {
    	parent::__construct($LinkID,$SecretKey);
    	$this->AddScope('111');
    }

  public function GetChargeInfo ( $CorpNum, $UserID = null) {
    $uri = '/HomeTax/Taxinvoice/ChargeInfo';

    $response = $this->executeCURL($uri, $CorpNum, $UserID);
    $ChargeInfo = new ChargeInfo();
    $ChargeInfo->fromJsonInfo($response);

    return $ChargeInfo;
  }

}
?>

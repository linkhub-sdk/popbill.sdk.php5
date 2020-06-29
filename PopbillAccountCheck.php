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
* Written : 2020-06-29
* Updated : 2020-06-29
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

class AccountCheckService extends PopbillBase {

	public function __construct($LinkID,$SecretKey) {
    	parent::__construct($LinkID,$SecretKey);
    	$this->AddScope('182');
    }

  // 예금주성명 조회
  public function CheckAccountInfo($MemberCorpNum, $BankCode, $AccountNumber) {
  	if(is_null($MemberCorpNum) || empty($MemberCorpNum)) {
  		throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
  	}

	  if(is_null($BankCode) || empty($BankCode)) {
  		throw new PopbillException('기관코드가 입력되지 않았습니다.');
  	}

    if(is_null($AccountNumber) || empty($AccountNumber)) {
  		throw new PopbillException('계좌번호가 입력되지 않았습니다.');
  	}

    $uri = "/EasyFin/AccountCheck";
    $uri .= "?c=" . $BankCode;
    $uri .= "&n=" . $AccountNumber;

  	$result = $this->executeCURL($uri, $MemberCorpNum, null, true, null, null);

		$AccountInfo = new AccountInfo();
		$AccountInfo->fromJsonInfo($result);
		return $AccountInfo;

  }

  // 조회단가 확인
  public function GetUnitCost($CorpNum) {
	  return $this->executeCURL('/EasyFin/AccountCheck/UnitCost', $CorpNum)->unitCost;
  }

  // 과금정보 확인
  public function GetChargeInfo ( $CorpNum, $UserID = null) {
    $uri = '/EasyFin/AccountCheck/ChargeInfo';

    $response = $this->executeCURL($uri, $CorpNum, $UserID);
    $ChargeInfo = new ChargeInfo();
    $ChargeInfo->fromJsonInfo($response);

    return $ChargeInfo;
  }
}

class AccountInfo
{
    public $resultCode;
    public $resultMessage;
    public $bankCode;
    public $accountNumber;
    public $accountName;
    public $checkDate;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->resultCode) ? $this->resultCode = $jsonInfo->resultCode : null;
        isset($jsonInfo->resultMessage) ? $this->resultMessage = $jsonInfo->resultMessage : null;
        isset($jsonInfo->bankCode) ? $this->bankCode = $jsonInfo->bankCode : null;
        isset($jsonInfo->accountNumber) ? $this->accountNumber = $jsonInfo->accountNumber : null;
        isset($jsonInfo->accountName) ? $this->accountName = $jsonInfo->accountName : null;
        isset($jsonInfo->checkDate) ? $this->checkDate = $jsonInfo->checkDate : null;
    }
}

?>

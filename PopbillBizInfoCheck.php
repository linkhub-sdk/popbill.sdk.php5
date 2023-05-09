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
* Author : choi sh (code@linkhubcorp.com)
* Written : 2022-09-30
* Updated : 2023-05-09
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

class BizInfoCheckService extends PopbillBase {

    public function __construct($LinkID,$SecretKey) {
        parent::__construct($LinkID,$SecretKey);
        $this->AddScope('171');
    }

    // 기업정보조회 - 단건
    public function CheckBizInfo($MemberCorpNum, $CheckCorpNum, $UserId = null) {
        if(is_null($MemberCorpNum) || empty($MemberCorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        if(is_null($CheckCorpNum) || empty($CheckCorpNum)) {
            throw new PopbillException('조회할 사업자번호가 입력되지 않았습니다.');
        }

        $result = $this->executeCURL('/BizInfo/Check?CN='.$CheckCorpNum, $MemberCorpNum, $UserId);

        $BizCheckInfo = new BizCheckInfo();
        $BizCheckInfo->fromJsonInfo($result);
        return $BizCheckInfo;

    }

    // 조회 단가 확인
    public function GetUnitCost($CorpNum) {
        return $this->executeCURL('/BizInfo/UnitCost', $CorpNum)->unitCost;
    }

    public function GetChargeInfo ( $CorpNum, $UserID = null) {
        $uri = '/BizInfo/ChargeInfo';

        $response = $this->executeCURL($uri, $CorpNum, $UserID);
        $ChargeInfo = new ChargeInfo();
        $ChargeInfo->fromJsonInfo($response);

        return $ChargeInfo;
    }
}

class BizCheckInfo
{
    public $corpNum;
    public $companyRegNum;
    public $checkDT ;
    public $corpName;
    public $corpCode;
    public $corpScaleCode;
    public $personCorpCode;
    public $headOfficeCode;
    public $industryCode ;
    public $establishCode ;
    public $establishDate;
    public $ceoname;
    public $workPlaceCode;
    public $addrCode;
    public $zipCode;
    public $addr;
    public $addrDetail;
    public $enAddr;
    public $bizClass;
    public $bizType;
    public $result;
    public $resultMessage ;
    public $closeDownTaxType;
    public $closeDownTaxTypeDate;
    public $closeDownState;
    public $closeDownStateDate;

    function fromJsonInfo($jsonInfo)
    {
        isset( $jsonInfo->corpNum) ? $this->corpNum = $jsonInfo->corpNum : null;
        isset( $jsonInfo->companyRegNum) ? $this->companyRegNum = $jsonInfo->companyRegNum : null;
        isset( $jsonInfo->checkDT) ? $this->checkDT = $jsonInfo->checkDT : null;
        isset( $jsonInfo->corpName) ? $this->corpName = $jsonInfo->corpName : null;
        isset( $jsonInfo->corpCode) ? $this->corpCode = $jsonInfo->corpCode : null;
        isset( $jsonInfo->corpScaleCode) ? $this->corpScaleCode = $jsonInfo->corpScaleCode : null;
        isset( $jsonInfo->personCorpCode) ? $this->personCorpCode = $jsonInfo->personCorpCode : null;
        isset( $jsonInfo->headOfficeCode) ? $this->headOfficeCode = $jsonInfo->headOfficeCode : null;
        isset( $jsonInfo->industryCode) ? $this->industryCode = $jsonInfo->industryCode : null;
        isset( $jsonInfo->establishCode) ? $this->establishCode = $jsonInfo->establishCode : null;
        isset( $jsonInfo->establishDate) ? $this->establishDate = $jsonInfo->establishDate : null;
        isset( $jsonInfo->ceoname) ? $this->ceoname = $jsonInfo->ceoname : null;
        isset( $jsonInfo->workPlaceCode) ? $this->workPlaceCode = $jsonInfo->workPlaceCode : null;
        isset( $jsonInfo->addrCode) ? $this->addrCode = $jsonInfo->addrCode : null;
        isset( $jsonInfo->zipCode) ? $this->zipCode = $jsonInfo->zipCode : null;
        isset( $jsonInfo->addr) ? $this->addr = $jsonInfo->addr : null;
        isset( $jsonInfo->addrDetail) ? $this->addrDetail = $jsonInfo->addrDetail : null;
        isset( $jsonInfo->enAddr) ? $this->enAddr = $jsonInfo->enAddr : null;
        isset( $jsonInfo->bizClass) ? $this->bizClass = $jsonInfo->bizClass : null;
        isset( $jsonInfo->bizType) ? $this->bizType = $jsonInfo->bizType : null;
        isset( $jsonInfo->result) ? $this->result = $jsonInfo->result : null;
        isset( $jsonInfo->resultMessage) ? $this->resultMessage = $jsonInfo->resultMessage : null;
        isset( $jsonInfo->closeDownTaxType) ? $this->closeDownTaxType = $jsonInfo->closeDownTaxType : null;
        isset( $jsonInfo->closeDownTaxTypeDate) ? $this->closeDownTaxTypeDate = $jsonInfo->closeDownTaxTypeDate : null;
        isset( $jsonInfo->closeDownState) ? $this->closeDownState = $jsonInfo->closeDownState : null;
        isset( $jsonInfo->closeDownStateDate) ? $this->closeDownStateDate = $jsonInfo->closeDownStateDate : null;
    }
}

?>

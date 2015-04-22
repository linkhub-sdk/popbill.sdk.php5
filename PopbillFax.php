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
* Author : Kim Seongjun (pallet027@gmail.com)
* Written : 2014-04-15
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

class FaxService extends PopbillBase {
	
	public function __construct($LinkID,$SecretKey) {
    	parent::__construct($LinkID,$SecretKey);
    	$this->AddScope('160');
    }
    
    //발행단가 확인
    public function GetUnitCost($CorpNum) {
    	return $this->executeCURL('/FAX/UnitCost', $CorpNum)->unitCost;
    }

	/* 팩스 전송 요청
    *	$CorpNum => 발송사업자번호
    *	$Sender	=> 발신번호
    *	$Receviers => 수신처 목록
    *		'rcv'	=> 수신번호
    *		'rcvnm'	=> 수신자 명칭
    *	$FilePaths	=> 전송할 파일경로 문자열 목록, 최대 5개.
    *	$ReserveDT	=> 예약전송을 할경우 전송예약시간 yyyyMMddHHmmss 형식
    *	$UserID	=> 팝빌 회원아이디
    */
	public function SendFAX($CorpNum,$Sender,$Receivers = array(),$FilePaths = array(),$ReserveDT = null,$UserID = null) {
		if(empty($Receivers)) {
			throw new PopbillException('수신처 목록이 입력되지 않았습니다.');
		}
		
		if(empty($FilePaths)) {
			throw new PopbillException('발신파일 목록이 입력되지 않았습니다.');
		}
		
		$RequestForm = array();
		
		$RequestForm['snd'] = $Sender;
		if(!empty($ReserveDT)) $RequestForm['sndDT'] = $ReserveDT;
		$RequestForm['fCnt'] = count($FilePaths);
		
		$RequestForm['rcvs'] = $Receivers;
	
    	$postdata = array();
    	$postdata['form'] = json_encode($RequestForm);
    	
    	$i = 0;
    	
    	foreach($FilePaths as $FilePath) {
    		$postdata['file['.$i++.']'] = '@'.$FilePath;
    	}
    	
    	return $this->executeCURL('/FAX', $CorpNum, $UserID, true,null,$postdata,true)->receiptNum;
 		
	}
	
	/* 팩스 전송 내역 확인
    *	$CorpNum => 발송사업자번호
    *	$ReceiptNum	=> 접수번호
    *	$UserID	=> 팝빌 회원아이디
    */
	public function GetFaxDetail($CorpNum,$ReceiptNum,$UserID) {
		if(empty($ReceiptNum)) {
    		throw new PopbillException('확인할 접수번호를 입력하지 않았습니다.'); 
    	}
		$result = $this->executeCURL('/FAX/'.$ReceiptNum, $CorpNum,$UserID);	
		$FaxState = new FaxState();
		
		$FaxInfoList = array();
		
		for($i=0; $i<Count($result); $i++){
			$FaxInfo = new FaxState();
			$FaxInfo->fromJsonInfo($result[$i]);
			$FaxInfoList[$i] = $FaxInfo;

		}
		return $FaxInfoList;
	}
	
    /* 예약전송 취소
    *	$CorpNum => 발송사업자번호
    *	$ReceiptNum	=> 접수번호
    *	$UserID	=> 팝빌 회원아이디
    */
    public function CancelReserve($CorpNum,$ReceiptNum,$UserID) {
    	if(empty($ReceiptNum)) {
    		throw new PopbillException('취소할 접수번호를 입력하지 않았습니다.'); 
    	}
    	return $this->executeCURL('/FAX/'.$ReceiptNum.'/Cancel', $CorpNum,$UserID);
    }
    
   /* 팩스 관련 기능 URL 확인
    *	$CorpNum => 발송사업자번호
    *	$UserID	=> 팝빌 회원아이디
    *	$TOGO => URL 위치 아이디
    */
    public function GetURL($CorpNum ,$UserID, $TOGO) {
    	$response = $this->executeCURL('/FAX/?TG='.$TOGO,$CorpNum,$UserID);
    	return $response->url;
    }
}


class FaxState {
	public $sendState;
	public $convState;
	public $sendNum;
	public $receiveNum;
	public $receiveName;
	public $sendPageCnt;
	public $successPageCnt;
	public $failPageCnt;
	public $refundPageCnt;
	public $cancelPageCnt;
	public $reserveDT;
	public $sendDT;
	public $resultDT;
	public $sendResult;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->sendState) ? $this->sendState = $jsonInfo->sendState : null;
		isset($jsonInfo->convState) ? $this->convState = $jsonInfo->convState : null;
		isset($jsonInfo->sendNum) ? $this->sendNum = $jsonInfo->sendNum : null;
		isset($jsonInfo->receiveNum) ? $this->receiveNum = $jsonInfo->receiveNum : null;
		isset($jsonInfo->receiveName) ? $this->receiveName = $jsonInfo->receiveName : null;
		isset($jsonInfo->sendPageCnt) ? $this->sendPageCnt = $jsonInfo->sendPageCnt : null;
		isset($jsonInfo->successPageCnt) ? $this->successPageCnt = $jsonInfo->successPageCnt : null;
		isset($jsonInfo->failPageCnt) ? $this->failPageCnt = $jsonInfo->failPageCnt : null;
		isset($jsonInfo->refundPageCnt) ? $this->refundPageCnt = $jsonInfo->refundPageCnt : null;
		isset($jsonInfo->cancelPageCnt) ? $this->cancelPageCnt = $jsonInfo->cancelPageCnt : null;
		isset($jsonInfo->reserveDT) ? $this->reserveDT = $jsonInfo->reserveDT : null;
		isset($jsonInfo->sendDT) ? $this->sendDT = $jsonInfo->sendDT : null;
		isset($jsonInfo->resultDT) ? $this->resultDT = $jsonInfo->resultDT : null;
		isset($jsonInfo->sendResult) ? $this->sendResult = $jsonInfo->sendResult : null;
	}
}
?>

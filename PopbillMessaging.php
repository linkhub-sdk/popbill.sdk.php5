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

class MessagingService extends PopbillBase {
	
	public function __construct($LinkID,$SecretKey) {
    	parent::__construct($LinkID,$SecretKey);
    	$this->AddScope('150');
    	$this->AddScope('151');
    	$this->AddScope('152');
    }
    
    //발행단가 확인
    public function GetUnitCost($CorpNum,$MessageType) {
    	return $this->executeCURL('/Message/UnitCost?Type='.$MessageType, $CorpNum)->unitCost;
    }
    
    
    /* 단문메시지 전송
    *	$CorpNum => 발송사업자번호
    *	$Sender	=> 동보전송용 발신번호 미기재시 개별메시지 발신번호로 전송. 발신번호가 없는 개별메시지에만 동보처리함.
    *	$Content => 동보전송용 발신내용 미기재시 개별메시지 내용으로 전송, 발신내용이 없는 개별메시지에만 동보처리함.
    *	$Messages => 발신메시지 최대 1000건, 배열
    *		'snd' => 개별발신번호
    *		'rcv' => 수신번호, 필수
    *		'rcvnm' => 수신자 성명
    *		'msg' => 메시지 내용, 미기재시 동보메시지로 전송함.
	*	$ReserveDT	=> 예약전송시 예약시간 yyyyMMddHHmmss 형식으로 기재
	*	$UserID		=> 발신자 팝빌 회원아이디
    */
    public function SendSMS($CorpNum,$Sender,$Content,$Messages = array(),$ReserveDT = null , $UserID = null) {
    	return $this->SendMessage(ENumMessageType::SMS,$CorpNum,$Sender,null,$Content,$Messages,$ReserveDT,$UserID);
    }
    
    /* 장문메시지 전송
    *	$CorpNum => 발송사업자번호
    *	$Sender	=> 동보전송용 발신번호 미기재시 개별메시지 발신번호로 전송. 발신번호가 없는 개별메시지에만 동보처리함.
    *	$Subject => 동보전송용 제목 미기재시 개별메시지 제목으로 전송, 제목이 없는 개별메시지에만 동보처리함.
    *	$Content => 동보전송용 발신내용 미기재시 개별베시지 내용으로 전송, 발신내용이 없는 개별메시지에만 동보처리함.
    *	$Messages => 발신메시지 최대 1000건, 배열
    *		'snd' => 개별발신번호
    *		'rcv' => 수신번호, 필수
    *		'rcvnm' => 수신자 성명
    *		'msg' => 메시지 내용, 미기재시 동보메시지로 전송함.
    *		'sjt' => 제목, 미기재시 동보 제목으로 전송함.
	*	$ReserveDT	=> 예약전송시 예약시간 yyyyMMddHHmmss 형식으로 기재
	*	$UserID		=> 발신자 팝빌 회원아이디
    */
    public function SendLMS($CorpNum,$Sender,$Subject,$Content,$Messages = array(),$ReserveDT = null , $UserID = null) {
    	return $this->SendMessage(ENumMessageType::LMS,$CorpNum,$Sender,$Subject, $Content,$Messages,$ReserveDT,$UserID);
    }
    
    /* 장/단문메시지 전송 - 메지시 길이에 따라 단문과 장문을 선택하여 전송합니다.
    *	$CorpNum => 발송사업자번호
    *	$Sender	=> 동보전송용 발신번호 미기재시 개별메시지 발신번호로 전송. 발신번호가 없는 개별메시지에만 동보처리함.
    *	$Subject => 동보전송용 제목 미기재시 개별메시지 제목으로 전송, 제목이 없는 개별메시지에만 동보처리함.
    *	$Content => 동보전송용 발신내용 미기재시 개별베시지 내용으로 전송, 발신내용이 없는 개별메시지에만 동보처리함.
    *	$Messages => 발신메시지 최대 1000건, 배열
    *		'snd' => 개별발신번호
    *		'rcv' => 수신번호, 필수
    *		'rcvnm' => 수신자 성명
    *		'msg' => 메시지 내용, 미기재시 동보메시지로 전송함.
    *		'sjt' => 제목, 미기재시 동보 제목으로 전송함.
	*	$ReserveDT	=> 예약전송시 예약시간 yyyyMMddHHmmss 형식으로 기재
	*	$UserID		=> 발신자 팝빌 회원아이디
    */
    public function SendXMS($CorpNum,$Sender,$Subject,$Content,$Messages = array(),$ReserveDT = null , $UserID = null) {
    	return $this->SendMessage(ENumMessageType::XMS,$CorpNum,$Sender,$Subject, $Content,$Messages,$ReserveDT,$UserID);
    }
    
    /* 전송메시지 내역 및 전송상태 확인
    *	$CorpNum => 발송사업자번호
    *	$ReceiptNum	=> 접수번호
    *	$UserID	=> 팝빌 회원아이디
    */
    public function GetMessages($CorpNum,$ReceiptNum,$UserID) {
    	if(empty($ReceiptNum)) {
    		throw new PopbillException('확인할 접수번호를 입력하지 않았습니다.'); 
    	}
    	$result = $this->executeCURL('/Message/'.$ReceiptNum, $CorpNum,$UserID);
		
		$MessageInfoList = array();

		for($i=0; $i<Count($result); $i++){
			$MsgInfo = new MessageInfo();
			$MsgInfo->fromJsonInfo($result[$i]);
			$MessageInfoList[$i] = $MsgInfo;
		}
		return $MessageInfoList;
    }
    /* 예약전송 취소
    *	$CorpNum => 발송사업자번호
    *	$ReceiptNum	=> 접수번호
    *	$UserID	=> 팝빌 회원아이디
    */
    public function CancelReserve($CorpNum,$ReceiptNum,$UserID) {
    	if(empty($ReceiptNum)) {
    		throw new PopbillException('확인할 접수번호를 입력하지 않았습니다.'); 
    	}
    	return $this->executeCURL('/Message/'.$ReceiptNum.'/Cancel', $CorpNum,$UserID);
    }
    
    private function SendMessage($MessageType, $CorpNum, $Sender,$Subject,$Content, $Messages = array(), $ReserveDT = null , $UserID = null) {
    	if(empty($Messages)) {
    		throw new PopbillException('전송할 메시지가 입력되지 않았습니다.'); 
    	}
    	
    	$Request = array();
    	
    	if(empty($Sender) == false)		$Request['snd'] = $Sender;
    	if(empty($Content) == false)	$Request['content'] = $Content;
    	if(empty($Subject) == false)	$Request['subject'] = $Subject;
    	if(empty($ReserveDT) == false)	$Request['sndDT'] = $ReserveDT;
    	
    	$Request['msgs'] = $Messages;
    	
    	$postdata = json_encode($Request);
    	return $this->executeCURL('/'.$MessageType,$CorpNum,$UserID,true,null,$postdata)->receiptNum;
    	
    }
    
    //문자 관련 URL함수
    public function GetURL($CorpNum ,$UserID, $TOGO) {
    	$response = $this->executeCURL('/Message/?TG='.$TOGO,$CorpNum,$UserID);
    	return $response->url;
    }
    
}
class ENumMessageType {
	const SMS = 'SMS';
	const LMS = 'LMS';
	const XMS = 'XMS';
	const MMS = 'MMS';
}


class MessageInfo{
	public $state;
	public $subject;
	public $type;
	public $content;
	public $sendNum;
	public $receiveNum;
	public $receiveName;
	public $reserveDT;
	public $sendDT;
	public $resultDT;
	public $sendResult;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->state) ? $this->state = $jsonInfo->state : null;
		isset($jsonInfo->subject) ? $this->subject = $jsonInfo->subject : null;
		isset($jsonInfo->type) ? $this->type = $jsonInfo->type : null;
		isset($jsonInfo->content) ? $this->content = $jsonInfo->content : null;
		isset($jsonInfo->sendNum) ? $this->sendNum = $jsonInfo->sendNum : null;
		isset($jsonInfo->receiveNum) ? $this->receiveNum = $jsonInfo->receiveNum : null;
		isset($jsonInfo->receiveName) ? $this->receiveName = $jsonInfo->receiveName : null;
		isset($jsonInfo->reserveDT) ? $this->reserveDT = $jsonInfo->reserveDT : null;
		isset($jsonInfo->sendDT) ? $this->sendDT = $jsonInfo->sendDT : null;
		isset($jsonInfo->resultDT) ? $this->resultDT = $jsonInfo->resultDT : null;
		isset($jsonInfo->sendResult) ? $this->sendResult = $jsonInfo->sendResult : null;
	}
}
?>
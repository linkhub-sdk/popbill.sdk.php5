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
* Contributor : Jeong YoHan (code@linkhub.co.kr)
* Updated : 2016-07-06
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
    *	$SenderName	=> 동보전송용 발신자명 미기재시 개별메시지 발신자명으로 전송
    *	$Content => 동보전송용 발신내용 미기재시 개별메시지 내용으로 전송, 발신내용이 없는 개별메시지에만 동보처리함.
    *	$Messages => 발신메시지 최대 1000건, 배열
    *		'snd' => 개별발신번호
    *		'rcv' => 수신번호, 필수
    *		'rcvnm' => 수신자 성명
    *		'msg' => 메시지 내용, 미기재시 동보메시지로 전송함.
	*	$ReserveDT	=> 예약전송시 예약시간 yyyyMMddHHmmss 형식으로 기재
	*	$UserID		=> 발신자 팝빌 회원아이디
    */
    public function SendSMS($CorpNum,$Sender,$SenderName,$Content,$Messages = array(),$ReserveDT = null ,$adsYN = false, $UserID = null) {
    	return $this->SendMessage(ENumMessageType::SMS,$CorpNum,$Sender,$SenderName,null,$Content,$Messages,$ReserveDT,$adsYN,$UserID);
    }

    /* 장문메시지 전송
    *	$CorpNum => 발송사업자번호
    *	$Sender	=> 동보전송용 발신번호 미기재시 개별메시지 발신번호로 전송. 발신번호가 없는 개별메시지에만 동보처리함.
    *	$SenderName	=> 동보전송용 발신자명 미기재시 개별메시지 발신자명으로 전송
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
    public function SendLMS($CorpNum,$Sender,$SenderName, $Subject,$Content,$Messages = array(),$ReserveDT = null ,$adsYN = false, $UserID = null) {
    	return $this->SendMessage(ENumMessageType::LMS,$CorpNum,$Sender,$SenderName,$Subject, $Content,$Messages,$ReserveDT,$adsYN,$UserID);
    }

    /* 장/단문메시지 전송 - 메지시 길이에 따라 단문과 장문을 선택하여 전송합니다.
    *	$CorpNum => 발송사업자번호
    *	$Sender	=> 동보전송용 발신번호 미기재시 개별메시지 발신번호로 전송. 발신번호가 없는 개별메시지에만 동보처리함.
    *	$SenderName	=> 동보전송용 발신자명 미기재시 개별메시지 발신자명으로 전송
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
    public function SendXMS($CorpNum,$Sender,$SenderName,$Subject,$Content,$Messages = array(),$ReserveDT = null , $adsYN=false,$UserID = null) {
    	return $this->SendMessage(ENumMessageType::XMS,$CorpNum,$Sender,$SenderName,$Subject, $Content,$Messages,$ReserveDT,$adsYN,$UserID);
    }

	/* MMS 메시지 전송
    *	$CorpNum => 발송사업자번호
    *	$Sender	=> 동보전송용 발신번호 미기재시 개별메시지 발신번호로 전송. 발신번호가 없는 개별메시지에만 동보처리함.
    *	$SenderName	=> 동보전송용 발신자명 미기재시 개별메시지 발신자명으로 전송
    *	$Subject => 동보전송용 제목 미기재시 개별메시지 제목으로 전송, 제목이 없는 개별메시지에만 동보처리함.
    *	$Content => 동보전송용 발신내용 미기재시 개별베시지 내용으로 전송, 발신내용이 없는 개별메시지에만 동보처리함.
    *	$Messages => 발신메시지 최대 1000건, 배열
    *		'snd' => 개별발신번호
    *		'rcv' => 수신번호, 필수
    *		'rcvnm' => 수신자 성명
    *		'msg' => 메시지 내용, 미기재시 동보메시지로 전송함.
    *		'sjt' => 제목, 미기재시 동보 제목으로 전송함.
    *	$FilePaths	=> 전송할 파일경로 문자열
	*	$ReserveDT	=> 예약전송시 예약시간 yyyyMMddHHmmss 형식으로 기재
	*	$UserID		=> 발신자 팝빌 회원아이디
    */
    public function SendMMS($CorpNum,$Sender,$SenderName,$Subject,$Content,$Messages = array(),$FilePaths = array(), $ReserveDT = null , $adsYN=false, $UserID = null) {
		if(empty($Messages)) {
    		throw new PopbillException('전송할 메시지가 입력되지 않았습니다.');
    	}

		if(empty($FilePaths)) {
			throw new PopbillException('발신파일 목록이 입력되지 않았습니다.');
		}

    	$Request = array();

    	if(empty($Sender) == false)		$Request['snd'] = $Sender;
      if(empty($SenderName) == false)		$Request['sndnm'] = $SenderName;
    	if(empty($Content) == false)	$Request['content'] = $Content;
    	if(empty($Subject) == false)	$Request['subject'] = $Subject;
    	if(empty($ReserveDT) == false)	$Request['sndDT'] = $ReserveDT;

		  if($adsYN) $Request['adsYN'] = $adsYN;

	   	$Request['msgs'] = $Messages;

    	$postdata = array();
    	$postdata['form'] = json_encode($Request);

    	$i = 0;

    	foreach($FilePaths as $FilePath) {
    		$postdata['file'] = '@'.$FilePath;
    	}

    	return $this->executeCURL('/MMS', $CorpNum, $UserID, true,null,$postdata,true)->receiptNum;
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

    private function SendMessage($MessageType, $CorpNum, $Sender, $SenderName, $Subject,$Content, $Messages = array(), $ReserveDT = null , $adsYN = false, $UserID = null) {
    	if(empty($Messages)) {
    		throw new PopbillException('전송할 메시지가 입력되지 않았습니다.');
    	}

    	$Request = array();

    	if(empty($Sender) == false)		$Request['snd'] = $Sender;
      if(empty($SenderName) == false)		$Request['sndnm'] = $SenderName;
    	if(empty($Content) == false)	$Request['content'] = $Content;
    	if(empty($Subject) == false)	$Request['subject'] = $Subject;
    	if(empty($ReserveDT) == false)	$Request['sndDT'] = $ReserveDT;

		  if($adsYN) $Request['adsYN'] = $adsYN;

    	$Request['msgs'] = $Messages;

    	$postdata = json_encode($Request);
    	return $this->executeCURL('/'.$MessageType,$CorpNum,$UserID,true,null,$postdata)->receiptNum;
    }

    //문자 관련 URL함수
    public function GetURL($CorpNum ,$UserID, $TOGO) {
    	$response = $this->executeCURL('/Message/?TG='.$TOGO,$CorpNum,$UserID);
    	return $response->url;
    }

	//문자 전송내역 조회
	public function Search($CorpNum, $SDate, $EDate, $State = array(), $Item = array(), $ReserveYN = false, $SenderYN = false, $Page, $PerPage, $Order, $UserID = null){
		if ( is_null( $SDate ) || $SDate === "" ) {
			throw new PopbillException(-99999999, '시작일자가 입력되지 않았습니다.');
		}

		if ( is_null( $EDate ) || $EDate ==="" ) {
			throw new PopbillException(-99999999, '종료일자가 입력되지 않았습니다.');
		}

		$uri = '/Message/Search?SDate=' . $SDate;
		$uri .= '&EDate=' . $EDate;

		if ( !is_null( $State ) || !empty( $State ) ) {
			$uri .= '&State=' . implode(',',$State);
		}
		if ( !is_null( $Item ) || !empty( $Item ) ) {
			$uri .= '&Item=' . implode(',',$Item);
		}

		if ( $ReserveYN ) {
			$uri .= '&ReserveYN=1';
		}
		if ( $SenderYN ) {
			$uri .= '&SenderYN=1';
		}

		$uri .= '&Page=' . $Page;
		$uri .= '&PerPage=' . $PerPage;
	  $uri .= '&Order=' . $Order;

		$response = $this->executeCURL($uri,$CorpNum,$UserID);

		$SearchList = new MsgSearchResult();
		$SearchList->fromJsonInfo($response);

		return $SearchList;
	}
  // 080 수신거부목록 조회
  public function GetAutoDenyList($CorpNum){
    return $this->executeCURL('/Message/Denied', $CorpNum);
  }

  public function GetChargeInfo ( $CorpNum, $MessageType, $UserID = null) {
    $uri = '/Message/ChargeInfo?Type='.$MessageType;

    $response = $this->executeCURL($uri, $CorpNum, $UserID);
    $ChargeInfo = new ChargeInfo();
    $ChargeInfo->fromJsonInfo($response);

    return $ChargeInfo;
  }

}
class ENumMessageType {
	const SMS = 'SMS';
	const LMS = 'LMS';
	const XMS = 'XMS';
	const MMS = 'MMS';
}

class MsgSearchResult{
	public $code;
	public $total;
	public $perPage;
	public $pageNum;
	public $pageCount;
	public $message;
	public $list;

	public function fromJsonInfo($jsonInfo){
		isset($jsonInfo->code ) ? $this->code = $jsonInfo->code : null;
		isset($jsonInfo->total ) ? $this->total = $jsonInfo->total : null;
		isset($jsonInfo->perPage ) ? $this->perPage = $jsonInfo->perPage : null;
		isset($jsonInfo->pageCount ) ? $this->pageCount = $jsonInfo->pageCount : null;
		isset($jsonInfo->pageNum ) ? $this->pageNum = $jsonInfo->pageNum : null;
		isset($jsonInfo->message ) ? $this->message = $jsonInfo->message : null;

		$InfoList = array();

		for($i=0; $i < Count($jsonInfo->list);$i++){
			$InfoObj = new MessageInfo();
			$InfoObj->fromJsonInfo($jsonInfo->list[$i]);
			$InfoList[$i] = $InfoObj;
		}
		$this->list = $InfoList;
	}
}


class MessageInfo{
	public $state;
	public $subject;
	public $type;
	public $content;
	public $tranNet;
	public $sendNum;
  public $senderName;
	public $receiveNum;
	public $receiveName;
	public $reserveDT;
	public $sendDT;
	public $resultDT;
	public $sendResult;
  public $receiptDT;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->state) ? $this->state = $jsonInfo->state : null;
		isset($jsonInfo->subject) ? $this->subject = $jsonInfo->subject : null;
		isset($jsonInfo->tranNet) ? $this->tranNet = $jsonInfo->tranNet : null;
		isset($jsonInfo->type) ? $this->type = $jsonInfo->type : null;
		isset($jsonInfo->content) ? $this->content = $jsonInfo->content : null;
		isset($jsonInfo->sendNum) ? $this->sendNum = $jsonInfo->sendNum : null;
    isset($jsonInfo->senderName) ? $this->senderName = $jsonInfo->senderName : null;
		isset($jsonInfo->receiveNum) ? $this->receiveNum = $jsonInfo->receiveNum : null;
		isset($jsonInfo->receiveName) ? $this->receiveName = $jsonInfo->receiveName : null;
		isset($jsonInfo->reserveDT) ? $this->reserveDT = $jsonInfo->reserveDT : null;
		isset($jsonInfo->sendDT) ? $this->sendDT = $jsonInfo->sendDT : null;
		isset($jsonInfo->resultDT) ? $this->resultDT = $jsonInfo->resultDT : null;
		isset($jsonInfo->sendResult) ? $this->sendResult = $jsonInfo->sendResult : null;
		isset($jsonInfo->receiptDT) ? $this->receiptDT = $jsonInfo->receiptDT : null;
	}
}
?>

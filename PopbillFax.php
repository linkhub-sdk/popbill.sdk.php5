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
  
  //����ܰ� Ȯ��
  public function GetUnitCost($CorpNum) {
    return $this->executeCURL('/FAX/UnitCost', $CorpNum)->unitCost;
  }

	/* �ѽ� ���� ��û
    *	$CorpNum => �߼ۻ���ڹ�ȣ
    *	$Sender	=> �߽Ź�ȣ
    *	$Receviers => ����ó ���
    *		'rcv'	=> ���Ź�ȣ
    *		'rcvnm'	=> ������ ��Ī
    *	$FilePaths	=> ������ ���ϰ�� ���ڿ� ���, �ִ� 5��.
    *	$ReserveDT	=> ���������� �Ұ�� ���ۿ���ð� yyyyMMddHHmmss ����
    *	$UserID	=> �˺� ȸ�����̵�
    */
	public function SendFAX($CorpNum,$Sender,$Receivers = array(),$FilePaths = array(),$ReserveDT = null,$UserID = null) {
		if(empty($Receivers)) {
			throw new PopbillException('����ó ����� �Էµ��� �ʾҽ��ϴ�.');
		}
		
		if(empty($FilePaths)) {
			throw new PopbillException('�߽����� ����� �Էµ��� �ʾҽ��ϴ�.');
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
	
	/* �ѽ� ���� ���� Ȯ��
    *	$CorpNum => �߼ۻ���ڹ�ȣ
    *	$ReceiptNum	=> ������ȣ
    *	$UserID	=> �˺� ȸ�����̵�
    */
	public function GetFaxDetail($CorpNum,$ReceiptNum,$UserID) {
		if(empty($ReceiptNum)) {
    		throw new PopbillException('Ȯ���� ������ȣ�� �Է����� �ʾҽ��ϴ�.'); 
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
	
  /* �������� ���
  *	$CorpNum => �߼ۻ���ڹ�ȣ
  *	$ReceiptNum	=> ������ȣ
  *	$UserID	=> �˺� ȸ�����̵�
  */
  public function CancelReserve($CorpNum,$ReceiptNum,$UserID) {
    if(empty($ReceiptNum)) {
      throw new PopbillException('����� ������ȣ�� �Է����� �ʾҽ��ϴ�.'); 
    }
    return $this->executeCURL('/FAX/'.$ReceiptNum.'/Cancel', $CorpNum,$UserID);
  }

  
 /* �ѽ� ���� ��� URL Ȯ��
  *	$CorpNum => �߼ۻ���ڹ�ȣ
  *	$UserID	=> �˺� ȸ�����̵�
  *	$TOGO => URL ��ġ ���̵�
  */
  public function GetURL($CorpNum ,$UserID, $TOGO) {
    $response = $this->executeCURL('/FAX/?TG='.$TOGO,$CorpNum,$UserID);
    return $response->url;
  }

  // �ѽ����۳��� ��ȸ
  public function Search($CorpNum, $SDate, $EDate, $State = array(), $ReserveYN, $SenderOnly, $Page, $PerPage){

    if(is_null($SDate) || $SDate ===""){
			throw new PopbillException('�������ڰ� �Էµ��� �ʾҽ��ϴ�.');
		}

    if(is_null($EDate) || $EDate ===""){
			throw new PopbillException('�������ڰ� �Էµ��� �ʾҽ��ϴ�.');
		}

    $uri = '/FAX/Search';
    $uri .= '?SDate=' . $SDate;
    $uri .= '&EDate=' . $EDate;
		
    if(!is_null($State) || !empty($State)){
			$uri .= '&State=' . implode(',',$State);
		}

    if($ReserveYN) {
      $uri .= '&ReserveYN=1';
    }else {
      $uri .= '&ReserveYN=0';
    }

    if($SenderOnly){
      $uri .= '&SenderOnly=1';
    } else {
      $uri .= '&SenderOnly=0';
    }

    $uri .= '&Page=' . $Page;
    $uri .= '&PerPage=' . $PerPage;

    $response = $this->executeCURL($uri, $CorpNum, "");

    $SearchList = new FaxSearchResult();
    $SearchList->fromJsonInfo($response);
    
    return $SearchList;
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

class FaxSearchResult {
  public $code;
  public $total;
  public $perPage;
  public $pageNum;
  public $pageCount;
  public $message;
  
  function fromJsonInfo( $jsonInfo ){
    isset( $jsonInfo->code ) ? $this->code = $jsonInfo->code : null;
    isset( $jsonInfo->total ) ? $this->total = $jsonInfo->total : null;
    isset( $jsonInfo->perPage ) ? $this->perPage = $jsonInfo->perPage : null;
    isset( $jsonInfo->pageNum ) ? $this->pageNum = $jsonInfo->pageNum : null;
    isset( $jsonInfo->pageCount ) ? $this->pageCount = $jsonInfo->pageCount : null;
    isset( $jsonInfo->message ) ? $this->message = $jsonInfo->message : null;
    
    $InfoList = array();

    for ( $i = 0; $i < Count( $jsonInfo->list ); $i++ ) {
      $InfoObj = new FaxState();
      $InfoObj->fromJsonInfo( $jsonInfo->list[$i] );
      $InfoList[$i] = $InfoObj;
    }
    
    $this->list = $InfoList;
  }

}


?>

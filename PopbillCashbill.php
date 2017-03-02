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
* Written : 2014-09-04
* Contributor : Jeong YoHan (code@linkhub.co.kr)
* Updated : 2017-03-02
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

class CashbillService extends PopbillBase {

	public function __construct($LinkID,$SecretKey) {
    	parent::__construct($LinkID,$SecretKey);
    	$this->AddScope('140');
  }

  public function GetURL($CorpNum,$UserID,$TOGO) {
    $response = $this->executeCURL('/Cashbill/?TG='.$TOGO,$CorpNum,$UserID);
    return $response->url;
  }

    public function CheckMgtKeyInUse($CorpNum,$MgtKey) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }
    try
    {
      $response = $this->executeCURL('/Cashbill/'.$MgtKey,$CorpNum);
      return is_null($response->itemKey) == false;
    }catch(PopbillException $pe) {
      if($pe->getCode() == -14000003) {return false;}
      throw $pe;
    }
  }

  public function RegistIssue($CorpNum, $Cashbill, $Memo, $UserID = null) {
  if(!is_null($Memo) || !empty($Memo)){
    $Cashbill->memo = $Memo;
  }
    $postdata = json_encode($Cashbill);
    return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,'ISSUE',$postdata);
  }

  public function Register($CorpNum, $Cashbill, $UserID = null) {
    $postdata = json_encode($Cashbill);
    return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,null,$postdata);
  }


  public function Delete($CorpNum,$MgtKey,$UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }
    return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'DELETE','');
  }

  public function Update($CorpNum,$MgtKey,$Cashbill, $UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }

    $postdata = json_encode($Cashbill);
    return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true, 'PATCH', $postdata);
  }

  public function Issue($CorpNum,$MgtKey,$Memo = '', $UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }
    $Request = new IssueRequest();
    $Request->memo = $Memo;
    $postdata = json_encode($Request);

    return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'ISSUE',$postdata);
  }

  public function CancelIssue($CorpNum,$MgtKey,$Memo = '',$UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }
    $Request = new MemoRequest();
    $Request->memo = $Memo;
    $postdata = json_encode($Request);

    return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'CANCELISSUE',$postdata);
  }

  public function SendEmail($CorpNum,$MgtKey,$Receiver,$UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }

    $Request = array('receiver' => $Receiver);
    $postdata = json_encode($Request);

    return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'EMAIL',$postdata);
  }

  public function SendSMS($CorpNum,$MgtKey,$Sender,$Receiver,$Contents,$UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }

    $Request = array('receiver' => $Receiver,'sender'=>$Sender,'contents' => $Contents);
    $postdata = json_encode($Request);

    return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'SMS',$postdata);
  }

  public function SendFAX($CorpNum,$MgtKey,$Sender,$Receiver,$UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호 배열이 입력되지 않았습니다.');
    }

    $Request = array('receiver' => $Receiver,'sender'=>$Sender);
    $postdata = json_encode($Request);

    return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'FAX',$postdata);
  }

  public function GetInfo($CorpNum,$MgtKey) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }
    $result = $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum);

  $CashbillInfo = new CashbillInfo();
  $CashbillInfo->fromJsonInfo($result);
  return $CashbillInfo;
  }

  public function GetDetailInfo($CorpNum,$MgtKey) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }
  $result = $this->executeCURL('/Cashbill/'.$MgtKey.'?Detail', $CorpNum);

  $CashbillDetail = new Cashbill();

  $CashbillDetail->fromJsonInfo($result);
  return $CashbillDetail;
  }

  public function GetInfos($CorpNum,$MgtKeyList = array()) {
    if(is_null($MgtKeyList) || empty($MgtKeyList)) {
      throw new PopbillException('관리번호 배열이 입력되지 않았습니다.');
    }

    $postdata = json_encode($MgtKeyList);

    $result = $this->executeCURL('/Cashbill/States', $CorpNum, null, true,null,$postdata);

  $CashbillInfoList = array();

  for($i=0; $i<Count($result); $i++){
    $CashbillInfoObj = new CashbillInfo();
    $CashbillInfoObj->fromJsonInfo($result[$i]);
    $CashbillInfoList[$i] = $CashbillInfoObj;
  }

  return $CashbillInfoList;
  }

  public function GetLogs($CorpNum,$MgtKey) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }
    $result = $this->executeCURL('/Cashbill/'.$MgtKey.'/Logs', $CorpNum);

  $CashbillLogList = array();

  for($i=0; $i<Count($result); $i++){
    $CashbillLog = new CashbillLog();
    $CashbillLog->fromJsonInfo($result[$i]);
    $CashbillLogList[$i] = $CashbillLog;
  }
  return $CashbillLogList;
  }

  public function GetPopUpURL($CorpNum,$MgtKey,$UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }

    return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=POPUP', $CorpNum,$UserID)->url;
  }

  public function GetPrintURL($CorpNum,$MgtKey,$UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }

    return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=PRINT', $CorpNum,$UserID)->url;
  }

  public function GetEPrintURL($CorpNum,$MgtKey,$UserID = null) {
      if(is_null($MgtKey) || empty($MgtKey)) {
          throw new PopbillException('관리번호가 입력되지 않았습니다.');
      }

      return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=EPRINT', $CorpNum,$UserID)->url;
  }

  public function GetMailURL($CorpNum,$MgtKey,$UserID = null) {
    if(is_null($MgtKey) || empty($MgtKey)) {
      throw new PopbillException('관리번호가 입력되지 않았습니다.');
    }

    return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=MAIL', $CorpNum,$UserID)->url;
  }

  public function GetMassPrintURL($CorpNum,$MgtKeyList = array(),$UserID = null) {
    if(is_null($MgtKeyList) || empty($MgtKeyList)) {
      throw new PopbillException('관리번호 배열이 입력되지 않았습니다.');
    }

    $postdata = json_encode($MgtKeyList);

    return $this->executeCURL('/Cashbill/Prints', $CorpNum, $UserID, true,null,$postdata)->url;
  }

  public function GetUnitCost($CorpNum) {
    return $this->executeCURL('/Cashbill?cfg=UNITCOST', $CorpNum)->unitCost;
  }

  public function Search($CorpNum, $DType, $SDate, $EDate, $State = array(), $TradeType = array(), $TradeUsage = array(), $TaxationType = array(), $Page, $PerPage, $Order, $QString){
    if(is_null($DType) || empty($DType)) {
      throw new PopbillException('날자유형(DType)이 입력되지 않았습니다.');
    }

    if(is_null($SDate) || empty($SDate)) {
      throw new PopbillException('시작일자(SDate)가 입력되지 않았습니다.');
    }

    if(is_null($EDate) || empty($EDate)) {
      throw new PopbillException('종료일자(EDate)가 입력되지 않았습니다.');
    }

    $uri = '/Cashbill/Search';
    $uri .= '?DType='.$DType;
    $uri .= '&SDate='.$SDate;
    $uri .= '&EDate='.$EDate;

    if(!is_null($State) || !empty($State)){
			$uri .= '&State=' . implode(',',$State);
		}

    if(!is_null($TradeType) || !empty($TradeType)){
			$uri .= '&TradeType=' . implode(',',$TradeType);
		}

    if(!is_null($TradeUsage) || !empty($TradeUsage)){
			$uri .= '&TradeUsage=' . implode(',',$TradeUsage);
		}

    if(!is_null($TaxationType) || !empty($TaxationType)){
			$uri .= '&TaxationType=' . implode(',',$TaxationType);
		}

    $uri .= '&Page='.$Page;
    $uri .= '&PerPage='.$PerPage;
    $uri .= '&Order='.$Order;

    if(!is_null($QString) || !empty($QString)){
			$uri .= '&QString=' . $QString;
		}

    $response = $this->executeCURL($uri, $CorpNum, "");

    $SearchList = new CBSearchResult();
    $SearchList->fromJsonInfo($response);

    return $SearchList;
  }

  public function GetChargeInfo ( $CorpNum, $UserID = null) {
    $uri = '/Cashbill/ChargeInfo';

    $response = $this->executeCURL($uri, $CorpNum, $UserID);
    $ChargeInfo = new ChargeInfo();
    $ChargeInfo->fromJsonInfo($response);

    return $ChargeInfo;
  }
}

class Cashbill {

	public $mgtKey;
	public $memo;

  public $tradeDate;
  public $tradeUsage;
  public $tradeType;

  public $taxationType;
  public $supplyCost;
  public $tax;
  public $serviceFee;
  public $totalAmount;

  public $franchiseCorpNum;
  public $franchiseCorpName;
  public $franchiseCEOName;
  public $franchiseAddr;
  public $franchiseTEL;

  public $identityNum;
  public $customerName;
  public $itemName;
  public $orderNumber;

  public $email;
  public $hp;
  public $fax;
  public $smssendYN;
  public $faxsendYN;

  public $orgConfirmNum;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->mgtKey) ? $this->mgtKey = $jsonInfo->mgtKey : null;
		isset($jsonInfo->tradeDate) ? $this->tradeDate = $jsonInfo->tradeDate : null;
		isset($jsonInfo->tradeUsage) ? $this->tradeUsage = $jsonInfo->tradeUsage : null;
		isset($jsonInfo->tradeType) ? $this->tradeType = $jsonInfo->tradeType : null;
		isset($jsonInfo->taxationType) ? $this->taxationType = $jsonInfo->taxationType : null;
		isset($jsonInfo->supplyCost) ? $this->supplyCost = $jsonInfo->supplyCost : null;
		isset($jsonInfo->tax) ? $this->tax = $jsonInfo->tax : null;
		isset($jsonInfo->serviceFee) ? $this->serviceFee = $jsonInfo->serviceFee : null;
		isset($jsonInfo->totalAmount) ? $this->totalAmount = $jsonInfo->totalAmount : null;
		isset($jsonInfo->franchiseCorpNum) ? $this->franchiseCorpNum = $jsonInfo->franchiseCorpNum : null;
		isset($jsonInfo->franchiseCorpName) ? $this->franchiseCorpName = $jsonInfo->franchiseCorpName : null;
		isset($jsonInfo->franchiseCEOName) ? $this->franchiseCEOName = $jsonInfo->franchiseCEOName : null;
		isset($jsonInfo->franchiseAddr) ? $this->franchiseAddr = $jsonInfo->franchiseAddr : null;
		isset($jsonInfo->franchiseTEL) ? $this->franchiseTEL = $jsonInfo->franchiseTEL : null;
		isset($jsonInfo->identityNum) ? $this->identityNum = $jsonInfo->identityNum : null;
		isset($jsonInfo->customerName) ? $this->customerName = $jsonInfo->customerName : null;
		isset($jsonInfo->itemName) ? $this->itemName = $jsonInfo->itemName : null;
		isset($jsonInfo->orderNumber) ? $this->orderNumber = $jsonInfo->orderNumber : null;
		isset($jsonInfo->email) ? $this->email = $jsonInfo->email : null;
		isset($jsonInfo->hp) ? $this->hp = $jsonInfo->hp : null;
		isset($jsonInfo->fax) ? $this->fax = $jsonInfo->fax : null;
		isset($jsonInfo->smssendYN) ? $this->smssendYN = $jsonInfo->smssendYN : null;
		isset($jsonInfo->faxsendYN) ? $this->faxsendYN = $jsonInfo->faxsendYN : null;
		isset($jsonInfo->orgConfirmNum) ? $this->orgConfirmNum = $jsonInfo->orgConfirmNum : null;
	}
}

class CashbillInfo {

	public $itemKey;
	public $mgtKey;
	public $tradeDate;
	public $issueDT;
	public $customerName;
	public $itemName;
	public $identityNum;
	public $taxationType;
	public $totalAmount;
	public $tradeUsage;
	public $tradeType;
	public $stateCode;
	public $stateDT;
	public $printYN;
	public $confirmNum;
	public $orgTradeDate;
	public $orgConfirmNum;
	public $ntssendDT;
	public $ntsresult;
	public $ntsresultDT;
	public $ntsresultCode;
	public $ntsresultMessage;
	public $regDT;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->itemKey) ? $this->itemKey = $jsonInfo->itemKey : null;
		isset($jsonInfo->mgtKey) ? $this->mgtKey = $jsonInfo->mgtKey : null;
		isset($jsonInfo->tradeDate) ? $this->tradeDate = $jsonInfo->tradeDate : null;
		isset($jsonInfo->customerName) ? $this->customerName = $jsonInfo->customerName : null;
		isset($jsonInfo->itemName) ? $this->itemName = $jsonInfo->itemName : null;
		isset($jsonInfo->identityNum) ? $this->identityNum = $jsonInfo->identityNum : null;
		isset($jsonInfo->taxationType) ? $this->taxationType = $jsonInfo->taxationType : null;
		isset($jsonInfo->totalAmount) ? $this->totalAmount = $jsonInfo->totalAmount : null;
		isset($jsonInfo->tradeUsage) ? $this->tradeUsage = $jsonInfo->tradeUsage : null;
		isset($jsonInfo->tradeType) ? $this->tradeType = $jsonInfo->tradeType : null;
		isset($jsonInfo->stateCode) ? $this->stateCode = $jsonInfo->stateCode : null;
		isset($jsonInfo->stateDT) ? $this->stateDT = $jsonInfo->stateDT : null;
		isset($jsonInfo->printYN) ? $this->printYN = $jsonInfo->printYN : null;
		isset($jsonInfo->confirmNum) ? $this->confirmNum = $jsonInfo->confirmNum : null;
		isset($jsonInfo->orgTradeDate) ? $this->orgTradeDate = $jsonInfo->orgTradeDate : null;
		isset($jsonInfo->ntssendDT) ? $this->ntssendDT = $jsonInfo->ntssendDT : null;
		isset($jsonInfo->ntsresult) ? $this->ntsresult = $jsonInfo->ntsresult : null;
		isset($jsonInfo->ntsresultDT) ? $this->ntsresultDT = $jsonInfo->ntsresultDT : null;
		isset($jsonInfo->ntsresultCode) ? $this->ntsresultCode = $jsonInfo->ntsresultCode : null;
		isset($jsonInfo->ntsresultMessage) ? $this->ntsresultMessage = $jsonInfo->ntsresultMessage : null;
		isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
	}
}

class CashbillLog {

	public $docLogType;
	public $log;
	public $procType;
	public $procMemo;
	public $regDT;
	public $ip;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->ip) ? $this->ip = $jsonInfo->ip : null;
		isset($jsonInfo->docLogType) ? $this->docLogType = $jsonInfo->docLogType : null;
		isset($jsonInfo->log) ? $this->log = $jsonInfo->log : null;
		isset($jsonInfo->procType) ? $this->procType = $jsonInfo->procType : null;
		isset($jsonInfo->procMemo) ? $this->procMemo = $jsonInfo->procMemo : null;
		isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
	}
}

class MemoRequest {
	public $memo;
}
class IssueRequest {
  public $memo;
}

class CBSearchResult {
  public $code;
  public $total;
  public $perPage;
  public $pageNum;
  public $pageCount;
  public $message;
  public $list;

  public function fromJsonInfo($jsonInfo) {
    isset($jsonInfo->code ) ? $this->code = $jsonInfo->code : null;
    isset($jsonInfo->total ) ? $this->total = $jsonInfo->total : null;
    isset($jsonInfo->perPage ) ? $this->perPage = $jsonInfo->perPage : null;
    isset($jsonInfo->pageNum ) ? $this->pageNum = $jsonInfo->pageNum : null;
    isset($jsonInfo->pageCount ) ? $this->pageCount = $jsonInfo->pageCount : null;
    isset($jsonInfo->message ) ? $this->message = $jsonInfo->message : null;

    $InfoList = array();

    for ( $i = 0; $i < Count($jsonInfo->list); $i++ ) {
      $InfoObj = new CashbillInfo();
      $InfoObj->fromJsonInfo($jsonInfo->list[$i]);
      $InfoList[$i] = $InfoObj;
    }
    $this->list = $InfoList;
  }
}
?>

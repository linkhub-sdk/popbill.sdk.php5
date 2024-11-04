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
* Author : Kim Seongjun
* Written : 2014-09-04
* Contributor : Jeong YoHan (code@linkhubcorp.com)
* Updated : 2024-11-04
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

    // 팝빌 현금영수증 문서함 관련 URL
    public function GetURL($CorpNum, $UserID = null, $TOGO) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($TOGO)) {        
            throw new PopbillException('접근 메뉴가 입력되지 않았습니다.');
        }

        $response = $this->executeCURL('/Cashbill/?TG='.$TOGO,$CorpNum,$UserID);
        return $response->url;
    }

    // 문서번호 사용 여부 확인
    public function CheckMgtKeyInUse($CorpNum, $MgtKey, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        try{
            $response = $this->executeCURL('/Cashbill/'.$MgtKey,$CorpNum,$UserID);
            return is_null($response->itemKey) == false;
        }catch(PopbillException $pe) {
            if($pe->getCode() == -14000003) {return false;}
            throw $pe;
        }
    }

    // 즉시 발행
    public function RegistIssue($CorpNum, $Cashbill, $Memo = null, $UserID = null, $EmailSubject = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Cashbill)) {
            throw new PopbillException('현금영수증 정보가 입력되지 않았습니다.');
        }
        
        if(!$this->isNullOrEmpty($Memo)) {
            $Cashbill->memo = $Memo;
        }
        if(!$this->isNullOrEmpty($EmailSubject)) {
            $Cashbill->emailSubject = $EmailSubject;
        }

        $postdata = json_encode($Cashbill);

        return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,'ISSUE',$postdata);
    }

    // 초대량 발행 접수
    public function BulkSubmit($CorpNum, $SubmitID, $CashbillList, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SubmitID)) {
            throw new PopbillException('제출아이디가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($CashbillList)) {
            throw new PopbillException('현금영수증 정보가 입력되지 않았습니다.');
        }

        $Request = new CBBulkRequest();

        $Request->cashbills = $CashbillList;

        $postdata = json_encode($Request);

        return $this->executeCURL('/Cashbill', $CorpNum, $UserID, true, 'BULKISSUE', $postdata, false, null, false, $SubmitID);
    }

    // 초대량 접수결과 확인
    public function getBulkResult($CorpNum, $SubmitID, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SubmitID)) {
            throw new PopbillException('제출아이디가 입력되지 않았습니다.');
        }

        $response = $this->executeCURL('/Cashbill/BULK/' . $SubmitID . '/State', $CorpNum, $UserID);

        $bulkResult = new BulkCashbillResult();
        $bulkResult->fromJsonInfo($response);
        return $bulkResult;
    }

    public function Register($CorpNum, $Cashbill, $UserID = null) {
        $postdata = json_encode($Cashbill);
        return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,null,$postdata);
    }

    // 취소현금영수증 즉시발행 TradeDT 추가(RevokeRegistIssue). 2022/11/03
    public function RevokeRegistIssue($CorpNum, $mgtKey, $orgConfirmNum, $orgTradeDate, $smssendYN = false, $memo = null,
                                      $UserID = null, $isPartCancel = false, $cancelType = null, $supplyCost = null, $tax = null,
                                      $serviceFee = null, $totalAmount = null, $emailSubject = null, $tradeDT = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($mgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($orgConfirmNum)) {
            throw new PopbillException('당초 승인 현금영수증의 국세청승인번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($orgTradeDate)) {
            throw new PopbillException('당초 승인 현금영수증의 거래일자가 입력되지 않았습니다.');
        }
        if(!$this->isValidDaTe($orgTradeDate)) {
            throw new PopbillException('당초 승인 현금영수증의 거래일자가 유효하지 않습니다.');
        }
        if(!$this->isNullOrEmpty($tradeDT) && !$this->isValidDT($tradeDT)) {
            throw new PopbillException('거래일시가 유효하지 않습니다.');
        }

        $request = array(
            'mgtKey' => $mgtKey,
            'orgConfirmNum' => $orgConfirmNum,
            'orgTradeDate' => $orgTradeDate,
            'smssendYN' => $smssendYN,
            'memo' => $memo,
            'isPartCancel' => $isPartCancel,
            'cancelType' => $cancelType,
            'supplyCost' => $supplyCost,
            'tax' => $tax,
            'serviceFee' => $serviceFee,
            'totalAmount' => $totalAmount,
            'emailSubject' => $emailSubject,
            'tradeDT' => $tradeDT,
        );
        $postdata = json_encode($request);

        return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,'REVOKEISSUE',$postdata);
    }

    // 취소현금영수증 임시저장 TradeDT 추가(RevokeRegister). 2022/11/02
    public function RevokeRegister($CorpNum, $mgtKey, $orgConfirmNum, $orgTradeDate, $smssendYN = false, $UserID = null,
    $isPartCancel = false, $cancelType = null, $supplyCost = null, $tax = null, $serviceFee = null, $totalAmount = null, $tradeDT = null)
    {
        $request = array(
            'mgtKey' => $mgtKey,
            'orgConfirmNum' => $orgConfirmNum,
            'orgTradeDate' => $orgTradeDate,
            'smssendYN' => $smssendYN,
            'isPartCancel' => $isPartCancel,
            'cancelType' => $cancelType,
            'supplyCost' => $supplyCost,
            'tax' => $tax,
            'serviceFee' => $serviceFee,
            'totalAmount' => $totalAmount,
            'tradeDT' => $tradeDT,
        );
        $postdata = json_encode($request);

        return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,'REVOKE',$postdata);
    }

    // 삭제
    public function Delete($CorpNum, $MgtKey, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'DELETE','');
    }

    public function Update($CorpNum,$MgtKey,$Cashbill, $UserID = null) {
        if(is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $postdata = json_encode($Cashbill);
        return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true, 'PATCH', $postdata);
    }

    public function Issue($CorpNum,$MgtKey,$Memo = '', $UserID = null, $EmailSubject = null) {
        if(is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        $Request = new CBIssueRequest();
        $Request->memo = $Memo;
        $Request->emailSubject = $EmailSubject;

        $postdata = json_encode($Request);

        return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'ISSUE',$postdata);
    }

    public function CancelIssue($CorpNum,$MgtKey,$Memo = '',$UserID = null) {
        if(is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        $Request = new CBMemoRequest();
        $Request->memo = $Memo;
        $postdata = json_encode($Request);

        return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'CANCELISSUE',$postdata);
    }

    // 메일 재전송
    public function SendEmail($CorpNum, $MgtKey, $Receiver, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Receiver)) {
            throw new PopbillException('수신자 이메일주소가 입력되지 않았습니다.');
        }

        $Request = array('receiver' => $Receiver);
        $postdata = json_encode($Request);

        return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true, 'EMAIL', $postdata);
    }

    // 문자 재전송
    public function SendSMS($CorpNum, $MgtKey, $Sender, $Receiver, $Contents, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Sender)) {
            throw new PopbillException('발신번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Receiver)) {
            throw new PopbillException('수신번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Contents)) {
            throw new PopbillException('메시지 내용이 입력되지 않았습니다.');
        }

        $Request = array('receiver' => $Receiver,'sender'=>$Sender,'contents' => $Contents);
        $postdata = json_encode($Request);

        return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'SMS',$postdata);
    }

    // 팩스 전송
    public function SendFAX($CorpNum, $MgtKey, $Sender, $Receiver, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호 배열이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Sender)) {
            throw new PopbillException('발신번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Receiver)) {
            throw new PopbillException('수신번호가 입력되지 않았습니다.');
        }

        $Request = array('receiver' => $Receiver,'sender'=>$Sender);
        $postdata = json_encode($Request);

        return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'FAX',$postdata);
    }

    // 상태 확인
    public function GetInfo($CorpNum, $MgtKey) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $result = $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum);

        $CashbillInfo = new CashbillInfo();
        $CashbillInfo->fromJsonInfo($result);
        return $CashbillInfo;
    }

    // 상세정보 확인
    public function GetDetailInfo($CorpNum, $MgtKey) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $result = $this->executeCURL('/Cashbill/'.$MgtKey.'?Detail', $CorpNum);

        $CashbillDetail = new Cashbill();

        $CashbillDetail->fromJsonInfo($result);
        return $CashbillDetail;
    }

    // 다수건 상태 확인
    public function GetInfos($CorpNum, $MgtKeyList = array()) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyList)) {
            throw new PopbillException('문서번호 배열이 입력되지 않았습니다.');
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
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
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

    // 현금영수증 상세 정보 팝업 URL
    public function GetPopUpURL($CorpNum, $MgtKey, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=POPUP', $CorpNum, $UserID)->url;
    }

    // 현금영수증 인쇄 팝업 URL
    public function GetPrintURL($CorpNum, $MgtKey, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=PRINT', $CorpNum, $UserID)->url;
    }

    // 현금영수증 상세 정보 팝업 URL (메뉴/버튼 제외)
    public function GetViewURL($CorpNum, $MgtKey, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=VIEW', $CorpNum, $UserID)->url;
    }

    public function GetEPrintURL($CorpNum,$MgtKey,$UserID = null) {
        if(is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=EPRINT', $CorpNum,$UserID)->url;
    }

    // 현금영수증 안내메일 버튼 팝업 URL
    public function GetMailURL($CorpNum, $MgtKey, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=MAIL', $CorpNum, $UserID)->url;
    }

    // 현금영수증 대량 인쇄 팝업 URL
    public function GetMassPrintURL($CorpNum, $MgtKeyList = array(), $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyList)) {
            throw new PopbillException('문서번호 배열이 입력되지 않았습니다.');
        }

        $postdata = json_encode($MgtKeyList);

        return $this->executeCURL('/Cashbill/Prints', $CorpNum, $UserID, true, null, $postdata)->url;
    }

    // 발행 단가 확인
    public function GetUnitCost($CorpNum) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill?cfg=UNITCOST', $CorpNum)->unitCost;
    }

    // 목록 조회
    public function Search($CorpNum, $DType, $SDate, $EDate, $State = array(), $TradeType = array(), $TradeUsage = array(), $TaxationType = array(),
        $Page = null, $PerPage = null, $Order = null, $QString = null, $TradeOpt = array(null), $FranchiseTaxRegID = null){
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($DType)) {
            throw new PopbillException('일자유형(DType)이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SDate)) {
            throw new PopbillException('시작일자(SDate)가 입력되지 않았습니다.');
        }
        if(!$this->isValidDate($SDate)) {
            throw new PopbillException('시작일자(SDate)가 유효하지 않습니다.');
        }
        if($this->isNullOrEmpty($EDate)) {
            throw new PopbillException('종료일자(EDate)가 입력되지 않았습니다.');
        }
        if(!$this->isValidDate($EDate)) {
            throw new PopbillException('종료일자(EDate)가 유효하지 않습니다.');
        }

        $uri = '/Cashbill/Search';
        $uri .= '?DType='.$DType;
        $uri .= '&SDate='.$SDate;
        $uri .= '&EDate='.$EDate;

        if(!$this->isNullOrEmpty($State)) {
            $uri .= '&State=' . implode(',', $State);
        }

        if(!$this->isNullOrEmpty($TradeType)) {
            $uri .= '&TradeType=' . implode(',', $TradeType);
        }

        if(!$this->isNullOrEmpty($TradeUsage)) {
            $uri .= '&TradeUsage=' . implode(',', $TradeUsage);
        }

        if(!$this->isNullOrEmpty($TaxationType)) {
            $uri .= '&TaxationType=' . implode(',', $TaxationType);
        }

        if(!$this->isNullOrEmpty($Page)) {
            $uri .= '&Page=' . $Page;
        }

        if(!$this->isNullOrEmpty($PerPage)) {
            $uri .= '&PerPage=' . $PerPage;
        }

        if(!$this->isNullOrEmpty($Order)) {
            $uri .= '&Order=' . $Order;
        }

        if(!$this->isNullOrEmpty($QString)) {
            $uri .= '&QString=' . $QString;
        }

        if(!$this->isNullOrEmpty($TradeOpt)) {
            $uri .= '&TradeOpt=' . implode(',', $TradeOpt);
        }

        if(!$this->isNullOrEmpty($FranchiseTaxRegID)) {
            $uri .= '&FranchiseTaxRegID=' . $FranchiseTaxRegID;
        }

        $response = $this->executeCURL($uri, $CorpNum, "");

        $SearchList = new CBSearchResult();
        $SearchList->fromJsonInfo($response);

        return $SearchList;
    }

    // 과금정보 확인
    public function GetChargeInfo ($CorpNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        $uri = '/Cashbill/ChargeInfo';

        $response = $this->executeCURL($uri, $CorpNum, $UserID);
        $ChargeInfo = new ChargeInfo();
        $ChargeInfo->fromJsonInfo($response);

        return $ChargeInfo;
    }

    // 현금영수증 알림메일 발송설정 조회
    public function ListEmailConfig($CorpNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        $CBEmailSendConfigList = array();

        $result = $this->executeCURL('/Cashbill/EmailSendConfig', $CorpNum, $UserID);

        for($i=0; $i<Count($result); $i++){
            $CBEmailSendConfig = new CBEmailSendConfig();
            $CBEmailSendConfig->fromJsonInfo($result[$i]);
            $CBEmailSendConfigList[$i] = $CBEmailSendConfig;
        }
        return $CBEmailSendConfigList;
    }

    // 현금영수증 알림메일 발송설정 수정
    public function UpdateEmailConfig($corpNum, $emailType, $sendYN, $userID = null) {
        if($this->isNullOrEmpty($corpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($emailType)) {
            throw new PopbillException('발송 메일 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($sendYN)) {
            throw new PopbillException('전송 여부가 입력되지 않았습니다.');
        }
        if(!is_bool($sendYN)) {
            throw new PopbillException('메일 전송 여부가 유효하지 않습니다.');
        }

        $sendYNString = $sendYN ? 'True' : 'False';
        $uri = '/Cashbill/EmailSendConfig?EmailType='.$emailType.'&SendYN='.$sendYNString;

        return $result = $this->executeCURL($uri, $corpNum, $userID, true);
    }

    // 현금영수증 PDF 다운로드 URL
    public function GetPDFURL($CorpNum, $MgtKey, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=PDF', $CorpNum,$UserID)->url;
    }

    // get PDF
    public function GetPDF($CorpNum, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Cashbill/' . $MgtKey . '?PDF', $CorpNum, $UserID);
    }

    // 문서번호 할당
    public function AssignMgtKey($CorpNum, $itemKey, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($itemKey)) {
            throw new PopbillException('팝빌에서 할당한 식별번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('할당할 문서번호가 입력되지 않았습니다.');
        }

        $uri = '/Cashbill/' . $itemKey;
        $postdata = 'MgtKey=' . $MgtKey;
        return $this->executeCURL($uri, $CorpNum, $UserID, true, "", $postdata, false, 'application/x-www-form-urlencoded; charset=utf-8');
    }
}

class Cashbill
{
    public $mgtKey;
    public $orgConfirmNum;
    public $orgTradeDate;
    public $tradeType;
    public $tradeUsage;
    public $tradeOpt;
    public $taxationType;
    public $totalAmount;
    public $supplyCost;
    public $tax;
    public $serviceFee;
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
    public $smssendYN;
    public $memo;
    public $tradeDate;
    public $tradeDT;
    public $fax;
    public $faxsendYN;
    public $cancelType;
    public $emailSubject;
    public $franchiseTaxRegID;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->mgtKey) ? $this->mgtKey = $jsonInfo->mgtKey : null;
        isset($jsonInfo->orgConfirmNum) ? $this->orgConfirmNum = $jsonInfo->orgConfirmNum : null;
        isset($jsonInfo->orgTradeDate) ? $this->orgTradeDate = $jsonInfo->orgTradeDate : null;
        isset($jsonInfo->tradeType) ? $this->tradeType = $jsonInfo->tradeType : null;
        isset($jsonInfo->tradeUsage) ? $this->tradeUsage = $jsonInfo->tradeUsage : null;
        isset($jsonInfo->tradeOpt) ? $this->tradeOpt = $jsonInfo->tradeOpt : null;
        isset($jsonInfo->taxationType) ? $this->taxationType = $jsonInfo->taxationType : null;
        isset($jsonInfo->totalAmount) ? $this->totalAmount = $jsonInfo->totalAmount : null;
        isset($jsonInfo->supplyCost) ? $this->supplyCost = $jsonInfo->supplyCost : null;
        isset($jsonInfo->tax) ? $this->tax = $jsonInfo->tax : null;
        isset($jsonInfo->serviceFee) ? $this->serviceFee = $jsonInfo->serviceFee : null;
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
        isset($jsonInfo->smssendYN) ? $this->smssendYN = $jsonInfo->smssendYN : null;
        isset($jsonInfo->memo) ? $this->memo = $jsonInfo->memo : null;
        isset($jsonInfo->tradeDate) ? $this->tradeDate = $jsonInfo->tradeDate : null;
        isset($jsonInfo->tradeDT) ? $this->tradeDT = $jsonInfo->tradeDT : null;
        isset($jsonInfo->fax) ? $this->fax = $jsonInfo->fax : null;
        isset($jsonInfo->faxsendYN) ? $this->faxsendYN = $jsonInfo->faxsendYN : null;
        isset($jsonInfo->cancelType) ? $this->cancelType = $jsonInfo->cancelType : null;
        isset($jsonInfo->emailSubject) ? $this->emailSubject = $jsonInfo->emailSubject : null;
        isset($jsonInfo->franchiseTaxRegID) ? $this->franchiseTaxRegID = $jsonInfo->franchiseTaxRegID : null;
    }
}

class CashbillInfo
{
    public $itemKey;
    public $mgtKey;
    public $tradeDate;
    public $tradeDT;
    public $tradeType;
    public $tradeUsage;
    public $tradeOpt;
    public $taxationType;
    public $totalAmount;
    public $issueDT;
    public $regDT;
    public $stateMemo;
    public $stateCode;
    public $stateDT;
    public $identityNum;
    public $itemName;
    public $customerName;
    public $confirmNum;
    public $orgConfirmNum;
    public $orgTradeDate;
    public $ntssendDT;
    public $ntsresultDT;
    public $ntsresultCode;
    public $ntsresultMessage;
    public $printYN;
    public $ntsresult;
    public $supplyCost;
    public $tax;
    public $serviceFee;
    public $orderNumber;
    public $email;
    public $hp; 
    public $interOPYN;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->itemKey) ? $this->itemKey = $jsonInfo->itemKey : null;
        isset($jsonInfo->mgtKey) ? $this->mgtKey = $jsonInfo->mgtKey : null;
        isset($jsonInfo->tradeDate) ? $this->tradeDate = $jsonInfo->tradeDate : null;
        isset($jsonInfo->tradeDT) ? $this->tradeDT = $jsonInfo->tradeDT : null;
        isset($jsonInfo->tradeType) ? $this->tradeType = $jsonInfo->tradeType : null;
        isset($jsonInfo->tradeUsage) ? $this->tradeUsage = $jsonInfo->tradeUsage : null;
        isset($jsonInfo->tradeOpt) ? $this->tradeOpt = $jsonInfo->tradeOpt : null;
        isset($jsonInfo->taxationType) ? $this->taxationType = $jsonInfo->taxationType : null;
        isset($jsonInfo->totalAmount) ? $this->totalAmount = $jsonInfo->totalAmount : null;
        isset($jsonInfo->issueDT) ? $this->issueDT = $jsonInfo->issueDT : null;
        isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
        isset($jsonInfo->stateMemo) ? $this->stateMemo = $jsonInfo->stateMemo : null;
        isset($jsonInfo->stateCode) ? $this->stateCode = $jsonInfo->stateCode : null;
        isset($jsonInfo->stateDT) ? $this->stateDT = $jsonInfo->stateDT : null;
        isset($jsonInfo->identityNum) ? $this->identityNum = $jsonInfo->identityNum : null;
        isset($jsonInfo->itemName) ? $this->itemName = $jsonInfo->itemName : null;
        isset($jsonInfo->customerName) ? $this->customerName = $jsonInfo->customerName : null;
        isset($jsonInfo->confirmNum) ? $this->confirmNum = $jsonInfo->confirmNum : null;
        isset($jsonInfo->orgConfirmNum) ? $this->orgConfirmNum = $jsonInfo->orgConfirmNum : null;
        isset($jsonInfo->orgTradeDate) ? $this->orgTradeDate = $jsonInfo->orgTradeDate : null;
        isset($jsonInfo->ntssendDT) ? $this->ntssendDT = $jsonInfo->ntssendDT : null;
        isset($jsonInfo->ntsresultDT) ? $this->ntsresultDT = $jsonInfo->ntsresultDT : null;
        isset($jsonInfo->ntsresultCode) ? $this->ntsresultCode = $jsonInfo->ntsresultCode : null;
        isset($jsonInfo->ntsresultMessage) ? $this->ntsresultMessage = $jsonInfo->ntsresultMessage : null;
        isset($jsonInfo->printYN) ? $this->printYN = $jsonInfo->printYN : null;
        isset($jsonInfo->ntsresult) ? $this->ntsresult = $jsonInfo->ntsresult : null;
        isset($jsonInfo->supplyCost) ? $this->supplyCost = $jsonInfo->supplyCost : null;
        isset($jsonInfo->tax) ? $this->tax = $jsonInfo->tax : null;
        isset($jsonInfo->serviceFee) ? $this->serviceFee = $jsonInfo->serviceFee : null;
        isset($jsonInfo->orderNumber) ? $this->orderNumber = $jsonInfo->orderNumber : null;
        isset($jsonInfo->email) ? $this->email = $jsonInfo->email : null;
        isset($jsonInfo->hp) ? $this->hp = $jsonInfo->hp : null;
        isset($jsonInfo->interOPYN) ? $this->interOPYN = $jsonInfo->interOPYN : null;
        
    }
}

class CBBulkRequest
{
    public $cashbills;
}

class BulkCashbillResult
{
    public $code;
    public $message;
    public $receiptID;
    public $receiptDT;
    public $submitID;
    public $submitCount;
    public $successCount;
    public $failCount;
    public $txState;
    public $txStartDT;
    public $txEndDT;
    public $txResultCode;
    public $issueResult;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->code) ? $this->code = $jsonInfo->code : null;
        isset($jsonInfo->message) ? $this->message = $jsonInfo->message : null;
        isset($jsonInfo->receiptID) ? $this->receiptID = $jsonInfo->receiptID : null;
        isset($jsonInfo->receiptDT) ? $this->receiptDT = $jsonInfo->receiptDT : null;
        isset($jsonInfo->submitID) ? $this->submitID = $jsonInfo->submitID : null;
        isset($jsonInfo->submitCount) ? $this->submitCount = $jsonInfo->submitCount : null;
        isset($jsonInfo->successCount) ? $this->successCount = $jsonInfo->successCount : null;
        isset($jsonInfo->failCount) ? $this->failCount = $jsonInfo->failCount : null;
        isset($jsonInfo->txState) ? $this->txState = $jsonInfo->txState : null;
        isset($jsonInfo->txStartDT) ? $this->txStartDT = $jsonInfo->txStartDT : null;
        isset($jsonInfo->txEndDT) ? $this->txEndDT = $jsonInfo->txEndDT : null;
        isset($jsonInfo->txResultCode) ? $this->txResultCode = $jsonInfo->txResultCode : null;

        $InfoIssueResult = array();

        for ($i = 0; $i < Count($jsonInfo->issueResult); $i++) {
            $InfoObj = new BulkCashbillIssueResult();
            $InfoObj->fromJsonInfo($jsonInfo->issueResult[$i]);
            $InfoIssueResult[$i] = $InfoObj;
        }
        $this->issueResult = $InfoIssueResult;
    }
}

class BulkCashbillIssueResult
{
    public $mgtKey;
    public $code;
    public $message;
    public $confirmNum;
    public $tradeDate;
    public $tradeDT;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->mgtKey) ? $this->mgtKey = $jsonInfo->mgtKey : null;
        isset($jsonInfo->code) ? $this->code = $jsonInfo->code : null;
        isset($jsonInfo->message) ? $this->message = $jsonInfo->message : null;
        isset($jsonInfo->confirmNum) ? $this->confirmNum = $jsonInfo->confirmNum : null;
        isset($jsonInfo->tradeDate) ? $this->tradeDate = $jsonInfo->tradeDate : null;
        isset($jsonInfo->tradeDT) ? $this->tradeDT = $jsonInfo->tradeDT : null;
    }
}

class CashbillLog
{
    public $docLogType;
    public $log;
    public $procType;
    public $procMemo;
    public $regDT;
    public $ip;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->ip) ? $this->ip = $jsonInfo->ip : null;
        isset($jsonInfo->docLogType) ? $this->docLogType = $jsonInfo->docLogType : null;
        isset($jsonInfo->log) ? $this->log = $jsonInfo->log : null;
        isset($jsonInfo->procType) ? $this->procType = $jsonInfo->procType : null;
        isset($jsonInfo->procMemo) ? $this->procMemo = $jsonInfo->procMemo : null;
        isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
    }
}

class CBMemoRequest
{
    public $memo;
}

class CBIssueRequest
{
    public $memo;
    public $emailSubject;
}

class CBSearchResult
{
    public $code;
    public $total;
    public $perPage;
    public $pageNum;
    public $pageCount;
    public $message;
    public $list;

    public function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->code) ? $this->code = $jsonInfo->code : null;
        isset($jsonInfo->total) ? $this->total = $jsonInfo->total : null;
        isset($jsonInfo->perPage) ? $this->perPage = $jsonInfo->perPage : null;
        isset($jsonInfo->pageNum) ? $this->pageNum = $jsonInfo->pageNum : null;
        isset($jsonInfo->pageCount) ? $this->pageCount = $jsonInfo->pageCount : null;
        isset($jsonInfo->message) ? $this->message = $jsonInfo->message : null;

        $InfoList = array();

        for ($i = 0; $i < Count($jsonInfo->list); $i++) {
            $InfoObj = new CashbillInfo();
            $InfoObj->fromJsonInfo($jsonInfo->list[$i]);
            $InfoList[$i] = $InfoObj;
        }
        $this->list = $InfoList;
    }
}

class CBEmailSendConfig
{
    public $emailType;
    public $sendYN;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->emailType) ? $this->emailType = $jsonInfo->emailType : null;
        isset($jsonInfo->sendYN) ? $this->sendYN = $jsonInfo->sendYN : null;
    }
}

?>

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
 * Written : 2014-04-15
 * Contributor : Jeong YoHan (code@linkhubcorp.com)
 * Updated : 2024-09-19
 *
 * Thanks for your interest.
 * We welcome any suggestions, feedbacks, blames or anything.
 * ======================================================================================
 */
require_once 'popbill.php';

class FaxService extends PopbillBase {

    public function __construct($LinkID, $SecretKey)
    {
        parent::__construct($LinkID, $SecretKey);
        $this->AddScope('160');
        $this->AddScope('161');
    }

    // 전송 단가 확인
    public function GetUnitCost($CorpNum, $ReceiveNumType = null, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/FAX/UnitCost?receiveNumType=' . $ReceiveNumType, $CorpNum, $UserID)->unitCost;
    }

    // 발신번호 등록여부 확인
    public function CheckSenderNumber($CorpNum, $SenderNumber, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SenderNumber)) {
            throw new PopbillException('발신번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/FAX/CheckSenderNumber/' . $SenderNumber, $CorpNum, $UserID);
    }

    // 팩스 전송
    public function SendFAX($CorpNum, $Sender = null, $Receivers = array(), $FilePaths = array(), $ReserveDT = null, $UserID = null, $SenderName = null, $adsYN = False, $title = null, $RequestNum = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Receivers)) {
            throw new PopbillException('수신자 정보가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($FilePaths)) {
            throw new PopbillException('전송할 팩스파일경로가 입력되지 않았습니다.');
        }
        if(!$this->isNullOrEmpty($ReserveDT) && !$this->isValidDT($ReserveDT)) {
            throw new PopbillException('전송 예약일시가 유효하지 않습니다.');
        }

        $RequestForm = array();
        $RequestForm['fCnt'] = count($FilePaths);
        $RequestForm['rcvs'] = $Receivers;

        if(!$this->isNullOrEmpty($Sender))  $RequestForm['snd'] = $Sender;
        if(!$this->isNullOrEmpty($SenderName))  $RequestForm['sndnm'] = $SenderName;
        if(!$this->isNullOrEmpty($title))  $RequestForm['title'] = $title;
        if(!$this->isNullOrEmpty($ReserveDT))  $RequestForm['sndDT'] = $ReserveDT;
        if(!$this->isNullOrEmpty($RequestNum))  $RequestForm['requestNum'] = $RequestNum;
        if ($adsYN) $RequestForm['adsYN'] = $adsYN;

        $postdata = array();
        $postdata['form'] = json_encode($RequestForm);

        $i = 0;

        foreach ($FilePaths as $FilePath) {
            $postdata['file[' . $i++ . ']'] = '@' . $FilePath;
        }

        return $this->executeCURL('/FAX', $CorpNum, $UserID, true, null, $postdata, true)->receiptNum;
    }

    // SendFAXBinary
    public function SendFAXBinary($CorpNum, $Sender = null, $Receivers = array(), $FileDatas = array(), $ReserveDT = null, $UserID = null, $SenderName = null, $adsYN = False, $title = null, $RequestNum = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Receivers)) {
            throw new PopbillException('수신자 정보가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($FileDatas)) {
            throw new PopbillException('전송할 팩스파일 정보가 입력되지 않았습니다.');
        }
        if(!$this->isNullOrEmpty($ReserveDT) && !$this->isValidDT($ReserveDT)) {
            throw new PopbillException('전송 예약일시가 유효하지 않습니다.');
        }

        $RequestForm = array();
        $RequestForm['fCnt'] = count($FileDatas);
        $RequestForm['rcvs'] = $Receivers;

        if(!$this->isNullOrEmpty($Sender))  $RequestForm['snd'] = $Sender;
        if(!$this->isNullOrEmpty($SenderName))  $RequestForm['sndnm'] = $SenderName;
        if(!$this->isNullOrEmpty($title))  $RequestForm['title'] = $title;
        if(!$this->isNullOrEmpty($ReserveDT))  $RequestForm['sndDT'] = $ReserveDT;
        if(!$this->isNullOrEmpty($RequestNum))  $RequestForm['requestNum'] = $RequestNum;
        if ($adsYN) $RequestForm['adsYN'] = $adsYN;

        $postdata = array();
        $postdata['form'] = json_encode($RequestForm);

        $i = 0;
        foreach ($FileDatas as $key => $data) {
            foreach ($data as $key => $value) {
                if ($key == 'fileName') {
                    $postdata['name[' . $i . ']'] = $value;
                }
                if ($key == 'fileData') {
                    $postdata['file[' . $i++ . ']'] =  $value;
                }
            }
        }

        $isBinary= true;

        return $this->executeCURL('/FAX', $CorpNum, $UserID, true, null, $postdata, true, null, $isBinary)->receiptNum;
    }

    // 팩스 재전송
    public function ResendFAX($CorpNum, $ReceiptNum, $SenderNum = null, $SenderName = null, $Receivers = null, $ReserveDT = null, $UserID = null, $title = null, $RequestNum = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($ReceiptNum)) {
            throw new PopbillException('팩스접수번호(receiptNum)가 입력되지 않았습니다.');
        }
        if(!$this->isNullOrEmpty($ReserveDT) && !$this->isValidDT($ReserveDT)) {
            throw new PopbillException('전송 예약일시가 유효하지 않습니다.');
        }

        $RequestForm = array();

        if(!$this->isNullOrEmpty($SenderNum))  $RequestForm['snd'] = $SenderNum;
        if(!$this->isNullOrEmpty($SenderName))  $RequestForm['sndnm'] = $SenderName;
        if(!$this->isNullOrEmpty($ReserveDT))  $RequestForm['sndDT'] = $ReserveDT;
        if(!$this->isNullOrEmpty($RequestNum))  $RequestForm['requestNum'] = $RequestNum;
        if(!$this->isNullOrEmpty($Receivers))  $RequestForm['rcvs'] = $Receivers;
        if(!$this->isNullOrEmpty($title))  $RequestForm['title'] = $title;
        
        $postdata = json_encode($RequestForm);

        return $this->executeCURL('/FAX/' . $ReceiptNum, $CorpNum, $UserID, true, null, $postdata)->receiptNum;
    }

    // 팩스 재전송
    public function ResendFAXRN($CorpNum, $RequestNum = null, $SenderNum = null, $SenderName = null, $Receivers = null, $originalFAXrequestNum, $ReserveDT = null, $UserID = null, $title = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($originalFAXrequestNum)) {
            throw new PopbillException('원본 팩스의 전송요청번호(originalFAXrequestNum)가 입력되지 않았습니다.');
        }
        if(!$this->isNullOrEmpty($ReserveDT) && !$this->isValidDT($ReserveDT)) {
            throw new PopbillException('전송 예약일시가 유효하지 않습니다.');
        }

        $RequestForm = array();

        if(!$this->isNullOrEmpty($SenderNum))  $RequestForm['snd'] = $SenderNum;
        if(!$this->isNullOrEmpty($SenderName))  $RequestForm['sndnm'] = $SenderName;
        if(!$this->isNullOrEmpty($ReserveDT))  $RequestForm['sndDT'] = $ReserveDT;
        if(!$this->isNullOrEmpty($Receivers))  $RequestForm['rcvs'] = $Receivers;
        if(!$this->isNullOrEmpty($title))  $RequestForm['title'] = $title;
        if(!$this->isNullOrEmpty($RequestNum))  $RequestForm['requestNum'] = $RequestNum;
        
        $postdata = json_encode($RequestForm);

        return $this->executeCURL('/FAX/Resend/' . $originalFAXrequestNum, $CorpNum, $UserID, true, null, $postdata)->receiptNum;
    }

    public function GetFaxDetail($CorpNum, $ReceiptNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($ReceiptNum)) {
            throw new PopbillException('팩스 접수번호가 입력되지 않았습니다.');
        }

        $result = $this->executeCURL('/FAX/' . $ReceiptNum, $CorpNum, $UserID);

        $FaxInfoList = array();

        for ($i = 0; $i < Count($result); $i++) {
            $FaxInfo = new FaxState();
            $FaxInfo->fromJsonInfo($result[$i]);
            $FaxInfoList[$i] = $FaxInfo;
        }

        return $FaxInfoList;
    }

    public function GetFaxDetailRN($CorpNum, $RequestNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($RequestNum)) {
            throw new PopbillException('팩스 전송요청번호가 입력되지 않았습니다.');
        }

        $result = $this->executeCURL('/FAX/Get/' . $RequestNum, $CorpNum, $UserID);

        $FaxInfoList = array();

        for ($i = 0; $i < Count($result); $i++) {
            $FaxInfo = new FaxState();
            $FaxInfo->fromJsonInfo($result[$i]);
            $FaxInfoList[$i] = $FaxInfo;
        }

        return $FaxInfoList;
    }

    // 예약전송 취소 (접수번호)
    public function CancelReserve($CorpNum, $ReceiptNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($ReceiptNum)) {
            throw new PopbillException('팩스 접수번호가 입력되지 않았습니다.');
        }
        
        return $this->executeCURL('/FAX/' . $ReceiptNum . '/Cancel', $CorpNum, $UserID);
    }

    // 예약전송 취소 (전송 요청번호)
    public function CancelReserveRN($CorpNum, $RequestNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($RequestNum)) {
            throw new PopbillException('팩스 전송요청번호가 입력되지 않았습니다.');
        }
        
        return $this->executeCURL('/FAX/Cancel/' . $RequestNum, $CorpNum, $UserID);
    }

    public function GetURL($CorpNum, $UserID, $TOGO)
    {
        $response = $this->executeCURL('/FAX/?TG=' . $TOGO, $CorpNum, $UserID);
        return $response->url;
    }

    // 팩스 전송내역 팝업 URL
    public function GetSentListURL($CorpNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        $response = $this->executeCURL('/FAX/?TG=BOX', $CorpNum, $UserID);
        return $response->url;
    }

    // 발신번호 관리 팝업 URL
    public function GetSenderNumberMgtURL($CorpNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        
        $response = $this->executeCURL('/FAX/?TG=SENDER', $CorpNum, $UserID);
        return $response->url;
    }

    // 전송내역 목록 조회
    public function Search($CorpNum, $SDate, $EDate, $State = array(), $ReserveYN = null, $SenderOnly = null, $Page = null, $PerPage = null, $Order = null, $UserID = null, $QString = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SDate)) {
            throw new PopbillException('시작일자가 입력되지 않았습니다.');
        }
        if(!$this->isValidDate($SDate)) {
            throw new PopbillException('시작일자가 유효하지 않습니다.');
        }
        if($this->isNullOrEmpty($EDate)) {
            throw new PopbillException('종료일자가 입력되지 않았습니다.');
        }
        if(!$this->isValidDate($EDate)) {
            throw new PopbillException('종료일자가 유효하지 않습니다.');
        }

        $uri = '/FAX/Search';
        $uri .= '?SDate=' . $SDate;
        $uri .= '&EDate=' . $EDate;

        $uri .= '&State=';
        if (!is_null($State) || !empty($State)) {
            $uri .= implode(',', $State);
        }

        if ($ReserveYN) {
            $uri .= '&ReserveYN=1';
        } else {
            $uri .= '&ReserveYN=0';
        }

        if ($SenderOnly) {
            $uri .= '&SenderOnly=1';
        } else {
            $uri .= '&SenderOnly=0';
        }

        $uri .= '&Page=';
        if (!is_null($Page) || !empty($Page)) {
            $uri .= $Page;
        }
        
        $uri .= '&PerPage=';
        if (!is_null($PerPage) || !empty($PerPage)) {
            $uri .= $PerPage;
        }

        $uri .= '&Order=';
        if (!is_null($Order) || !empty($Order)) {
            $uri .= $Order;
        }

        $uri .= '&QString=';
        if (!is_null($QString) || !empty($QString)) {
            $uri .= urlencode($QString);
        }

        $response = $this->executeCURL($uri, $CorpNum, "");

        $SearchList = new FaxSearchResult();
        $SearchList->fromJsonInfo($response);

        return $SearchList;
    }

    // 과금정보 확인
    public function GetChargeInfo($CorpNum, $UserID = null, $ReceiveNumType = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        $uri = '/FAX/ChargeInfo?receiveNumType=' . $ReceiveNumType;

        $response = $this->executeCURL($uri, $CorpNum, $UserID);
        $ChargeInfo = new ChargeInfo();
        $ChargeInfo->fromJsonInfo($response);

        return $ChargeInfo;
    }

    // 발신번호 목록 조회
    public function GetSenderNumberList($CorpNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/FAX/SenderNumber', $CorpNum, $UserID);
    }

    // 팩스 변환결과 확인 팝업 URL
    public function getPreviewURL($CorpNum, $ReceiptNum, $UserID = null) {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($ReceiptNum)) {
            throw new PopbillException('접수번호가 입력되지 않았습니다.');
        }

        $response = $this->executeCURL('/FAX/Preview/'.$ReceiptNum, $CorpNum, $UserID);
        return $response->url;
    }
}


class FaxState
{
    public $state;
    public $result;
    public $title;
    public $sendState;
    public $convState;
    public $sendNum;
    public $senderName;
    public $receiveNum;
    public $receiveName;
    public $sendPageCnt;
    public $successPageCnt;
    public $failPageCnt;
    public $refundPageCnt;
    public $cancelPageCnt;
    public $receiveNumType;
    public $reserveDT;
    public $sendDT;
    public $resultDT;
    public $sendResult;
    public $fileNames;
    public $receiptDT;
    public $receiptNum;
    public $requestNum;
    public $interOPRefKey;
    public $chargePageCnt;
    public $tiffFileSize;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->state) ? $this->state = $jsonInfo->state : null;
        isset($jsonInfo->result) ? $this->result = $jsonInfo->result : null;
        isset($jsonInfo->title) ? $this->title = $jsonInfo->title : null;
        isset($jsonInfo->sendState) ? $this->sendState = $jsonInfo->sendState : null;
        isset($jsonInfo->convState) ? $this->convState = $jsonInfo->convState : null;
        isset($jsonInfo->sendNum) ? $this->sendNum = $jsonInfo->sendNum : null;
        isset($jsonInfo->senderName) ? $this->senderName = $jsonInfo->senderName : null;
        isset($jsonInfo->receiveNum) ? $this->receiveNum = $jsonInfo->receiveNum : null;
        isset($jsonInfo->receiveName) ? $this->receiveName = $jsonInfo->receiveName : null;
        isset($jsonInfo->sendPageCnt) ? $this->sendPageCnt = $jsonInfo->sendPageCnt : null;
        isset($jsonInfo->successPageCnt) ? $this->successPageCnt = $jsonInfo->successPageCnt : null;
        isset($jsonInfo->failPageCnt) ? $this->failPageCnt = $jsonInfo->failPageCnt : null;
        isset($jsonInfo->refundPageCnt) ? $this->refundPageCnt = $jsonInfo->refundPageCnt : null;
        isset($jsonInfo->cancelPageCnt) ? $this->cancelPageCnt = $jsonInfo->cancelPageCnt : null;
        isset($jsonInfo->receiveNumType) ? $this->receiveNumType = $jsonInfo->receiveNumType : null;
        isset($jsonInfo->reserveDT) ? $this->reserveDT = $jsonInfo->reserveDT : null;
        isset($jsonInfo->sendDT) ? $this->sendDT = $jsonInfo->sendDT : null;
        isset($jsonInfo->resultDT) ? $this->resultDT = $jsonInfo->resultDT : null;
        isset($jsonInfo->sendResult) ? $this->sendResult = $jsonInfo->sendResult : null;
        isset($jsonInfo->receiptDT) ? $this->receiptDT = $jsonInfo->receiptDT : null;
        isset($jsonInfo->receiptNum) ? $this->receiptNum = $jsonInfo->receiptNum : null;
        isset($jsonInfo->requestNum) ? $this->requestNum = $jsonInfo->requestNum : null;
        isset($jsonInfo->interOPRefKey) ? $this->interOPRefKey = $jsonInfo->interOPRefKey : null;
        isset($jsonInfo->chargePageCnt) ? $this->chargePageCnt = $jsonInfo->chargePageCnt : null;
        isset($jsonInfo->tiffFileSize) ? $this->tiffFileSize = $jsonInfo->tiffFileSize : null;

        if (isset ($jsonInfo->fileNames)) {
            $fileNameList = array();

            for ($i = 0; $i < Count($jsonInfo->fileNames); $i++) {
                $fileNameList[$i] = $jsonInfo->fileNames[$i];
            }

            $this->fileNames = $fileNameList;
        }
    }
}

class FaxSearchResult
{
    public $code;
    public $total;
    public $perPage;
    public $pageNum;
    public $pageCount;
    public $message;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->code) ? $this->code = $jsonInfo->code : null;
        isset($jsonInfo->total) ? $this->total = $jsonInfo->total : null;
        isset($jsonInfo->perPage) ? $this->perPage = $jsonInfo->perPage : null;
        isset($jsonInfo->pageNum) ? $this->pageNum = $jsonInfo->pageNum : null;
        isset($jsonInfo->pageCount) ? $this->pageCount = $jsonInfo->pageCount : null;
        isset($jsonInfo->message) ? $this->message = $jsonInfo->message : null;

        $InfoList = array();

        for ($i = 0; $i < Count($jsonInfo->list); $i++) {
            $InfoObj = new FaxState();
            $InfoObj->fromJsonInfo($jsonInfo->list[$i]);
            $InfoList[$i] = $InfoObj;
        }
        $this->list = $InfoList;
    }
}

?>

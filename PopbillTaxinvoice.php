1<?php
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
 * Written : 2015-06-15
 * Contributor : Jeong YoHan (code@linkhubcorp.com)
 * Updated : 2024-09-19
 *
 * Thanks for your interest.
 * We welcome any suggestions, feedbacks, blames or anything.
 * ======================================================================================
 */
require_once 'popbill.php';

class TaxinvoiceService extends PopbillBase
{

    public function __construct($LinkID, $SecretKey)
    {
        parent::__construct($LinkID, $SecretKey);
        $this->AddScope('110');
    }

    // 팝빌 세금계산서 연결 url
    public function GetURL($CorpNum, $UserID, $TOGO)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($TOGO)) {
            throw new PopbillException('접근 메뉴가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/?TG=' . $TOGO, $CorpNum, $UserID)->url;
    }

    // 문서번호 사용여부 확인
    public function CheckMgtKeyInUse($CorpNum, $MgtKeyType, $MgtKey)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        try {
            $response = $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum);
            return is_null($response->itemKey) == false;
        } catch (PopbillException $pe) {
            if ($pe->getCode() == -11000005) {
                return false;
            }
            throw $pe;
        }
    }

    // 즉시발행
    public function RegistIssue($CorpNum, $Taxinvoice, $UserID = null, $writeSpecification = false, $forceIssue = false, $memo = null, $emailSubject = null, $dealInvoiceMgtKey = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Taxinvoice)) {
            throw new PopbillException('세금계산서 정보가 입력되지 않았습니다.');
        }

        if ($writeSpecification) {
            $Taxinvoice->writeSpecification = $writeSpecification;
        }
        if ($forceIssue) {
            $Taxinvoice->forceIssue = $forceIssue;
        }

        if(!$this->isNullOrEmpty($memo)) {
            $Taxinvoice->memo = $memo;
        }
        if(!$this->isNullOrEmpty($emailSubject)) {
            $Taxinvoice->emailSubject = $emailSubject;
        }
        if(!$this->isNullOrEmpty($dealInvoiceMgtKey)) {
            $Taxinvoice->dealInvoiceMgtKey = $dealInvoiceMgtKey;
        }

        $postdata = json_encode($Taxinvoice);
        return $this->executeCURL('/Taxinvoice', $CorpNum, $UserID, true, 'ISSUE', $postdata);
    }

    // 임시저장
    public function Register($CorpNum, $Taxinvoice, $UserID = null, $writeSpecification = false)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Taxinvoice)) {
            throw new PopbillException('세금계산서 정보가 입력되지 않았습니다.');
        }

        if ($writeSpecification) {
            $Taxinvoice->writeSpecification = $writeSpecification;
        }
        $postdata = json_encode($Taxinvoice);
        return $this->executeCURL('/Taxinvoice', $CorpNum, $UserID, true, null, $postdata);
    }

    // 삭제
    public function Delete($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'DELETE', '');
    }

    // 수정
    public function Update($CorpNum, $MgtKeyType, $MgtKey, $Taxinvoice, $UserID = null, $writeSpecification = false)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Taxinvoice)) {
            throw new PopbillException('수정할 세금계산서 정보가 입력되지 않았습니다.');
        }

        if ($writeSpecification) {
            $Taxinvoice->writeSpecification = $writeSpecification;
        }

        $postdata = json_encode($Taxinvoice);
        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'PATCH', $postdata);
    }

    // 발행예정
    public function Send($CorpNum, $MgtKeyType, $MgtKey, $Memo = '', $EmailSubject = '', $UserID = null)
    {
        if(is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $Request = new TIMemoRequest();
        $Request->memo = $Memo;
        $Request->emailSubject = $EmailSubject;
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'SEND', $postdata);
    }

    // 발행예정취소
    public function CancelSend($CorpNum, $MgtKeyType, $MgtKey, $Memo = '', $UserID = null)
    {
        if(is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        $Request = new TIMemoRequest();
        $Request->memo = $Memo;
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'CANCELSEND', $postdata);
    }

    // 발행예정 승인
    public function Accept($CorpNum, $MgtKeyType, $MgtKey, $Memo = '', $UserID = null)
    {
        if(is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        $Request = new TIMemoRequest();
        $Request->memo = $Memo;
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'ACCEPT', $postdata);
    }

    // 발행예정 거부
    public function Deny($CorpNum, $MgtKeyType, $MgtKey, $Memo = '', $UserID = null)
    {
        if(is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        $Request = new TIMemoRequest();
        $Request->memo = $Memo;
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'DENY', $postdata);
    }

    // 발행
    public function Issue($CorpNum, $MgtKeyType, $MgtKey, $Memo = null, $EmailSubject = null, $ForceIssue = false, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $Request = new TIIssueRequest();

        if(!$this->isNullOrEmpty($Memo)) {
            $Request->memo = $Memo;
        }
        if(!$this->isNullOrEmpty($EmailSubject)) {
            $Request->emailSubject = $EmailSubject;
        }
        if(!$this->isNullOrEmpty($ForceIssue)) {
            $Request->forceIssue = $ForceIssue;
        }

        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'ISSUE', $postdata);
    }

    // 발행취소
    public function CancelIssue($CorpNum, $MgtKeyType, $MgtKey, $Memo = null, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $Request = new TIMemoRequest();
        
        if(!$this->isNullOrEmpty($Memo)) {
            $Request->memo = $Memo;
        }

        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'CANCELISSUE', $postdata);
    }

    // 역)즉시 요청
    public function RegistRequest($CorpNum, $Taxinvoice, $Memo = null, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Taxinvoice)) {
            throw new PopbillException('세금계산서 정보가 입력되지 않았습니다.');
        }

        if(!$this->isNullOrEmpty($Memo)) {
            $Taxinvoice->memo = $Memo;
        }

        $postdata = json_encode($Taxinvoice);

        return $this->executeCURL('/Taxinvoice', $CorpNum, $UserID, true, 'REQUEST', $postdata);
    }

    // 역)발행요청
    public function Request($CorpNum, $MgtKeyType, $MgtKey, $Memo = null, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $Request = new TIMemoRequest();

        if(!$this->isNullOrEmpty($Memo)) {
            $Request->memo = $Memo;
        }
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'REQUEST', $postdata);
    }

    // 역)발행요청 거부
    public function Refuse($CorpNum, $MgtKeyType, $MgtKey, $Memo = null, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $Request = new TIMemoRequest();

        if(!$this->isNullOrEmpty($Memo)) {
            $Request->memo = $Memo;
        }
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'REFUSE', $postdata);
    }

    // 역)발행요청 취소
    public function CancelRequest($CorpNum, $MgtKeyType, $MgtKey, $Memo = null, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $Request = new TIMemoRequest();

        if(!$this->isNullOrEmpty($Memo)) {
            $Request->memo = $Memo;
        }
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'CANCELREQUEST', $postdata);
    }

    // 전자세금계산서 초대량 발행 접수
    public function BulkSubmit($CorpNum, $SubmitID, $taxinvoiceList, $ForceIssue = null, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SubmitID)) {
            throw new PopbillException('제출아이디가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($taxinvoiceList)) {
            throw new PopbillException('세금계산서 정보 배열이 입력되지 않았습니다.');
        }

        $Request = new TIBulkRequest();
        
        if ($ForceIssue == true) {
            $Request->forceIssue = $ForceIssue;
        }
        $Request->invoices = $taxinvoiceList;

        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice', $CorpNum, $UserID, true, 'BULKISSUE', $postdata, false, null, false, $SubmitID);
    }

    // 초대량 접수결과 확인
    public function GetBulkResult($CorpNum, $SubmitID, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SubmitID)) {
            throw new PopbillException('제출아이디가 입력되지 않았습니다.');
        }

        $response = $this->executeCURL('/Taxinvoice/BULK/' . $SubmitID . '/State', $CorpNum, $UserID);

        $bulkResult = new BulkTaxinvoiceResult();
        $bulkResult->fromJsonInfo($response);
        return $bulkResult;
    }

    // 국세청 즉시전송 요청
    public function SendToNTS($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'NTS', '');
    }

    // 알림메일 재전송
    public function SendEmail($CorpNum, $MgtKeyType, $MgtKey, $Receiver, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($Receiver)) {
            throw new PopbillException('수신자 이메일주소가 입력되지 않았습니다.');
        }

        $Request = array('receiver' => $Receiver);
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'EMAIL', $postdata);
    }

    // 알림문자 재전송
    public function SendSMS($CorpNum, $MgtKeyType, $MgtKey, $Sender, $Receiver, $Contents, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
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

        $Request = array('receiver' => $Receiver, 'sender' => $Sender, 'contents' => $Contents);
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'SMS', $postdata);
    }

    // 알림팩스 재전송
    public function SendFAX($CorpNum, $MgtKeyType, $MgtKey, $Sender, $Receiver, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
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

        $Request = array('receiver' => $Receiver, 'sender' => $Sender);
        $postdata = json_encode($Request);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum, $UserID, true, 'FAX', $postdata);
    }

    // 세금계산서 요약정보 및 상태정보 확인
    public function GetInfo($CorpNum, $MgtKeyType, $MgtKey)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $result = $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey, $CorpNum);

        $TaxinvoiceInfo = new TaxinvoiceInfo();
        $TaxinvoiceInfo->fromJsonInfo($result);
        return $TaxinvoiceInfo;
    }

    // 세금계산서 상세정보 확인
    public function GetDetailInfo($CorpNum, $MgtKeyType, $MgtKey)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $result = $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?Detail', $CorpNum);

        $TaxinvoiceDetail = new Taxinvoice();
        $TaxinvoiceDetail->fromJsonInfo($result);

        return $TaxinvoiceDetail;
    }

    // 세금계산서 요약정보 다량확인 최대 1000건
    public function GetInfos($CorpNum, $MgtKeyType, $MgtKeyList = array())
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyList)) {
            throw new PopbillException('문서번호 배열이 입력되지 않았습니다.');
        }

        $postdata = json_encode($MgtKeyList);

        $TaxinvoiceInfoList = array();

        $result = $this->executeCURL('/Taxinvoice/' . $MgtKeyType, $CorpNum, null, true, null, $postdata);

        for ($i = 0; $i < Count($result); $i++) {
            $TaxinvoiceInfo = new TaxinvoiceInfo();
            $TaxinvoiceInfo->fromJsonInfo($result[$i]);
            $TaxinvoiceInfoList[$i] = $TaxinvoiceInfo;
        }

        return $TaxinvoiceInfoList;
    }

    // 세금계산서 문서이력 확인
    public function GetLogs($CorpNum, $MgtKeyType, $MgtKey)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $result = $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '/Logs', $CorpNum);
        $TaxinvoiceLogList = array();

        for ($i = 0; $i < Count($result); $i++) {
            $TaxinvoiceLog = new TaxinvoiceLog();
            $TaxinvoiceLog->fromJsonInfo($result[$i]);
            $TaxinvoiceLogList[$i] = $TaxinvoiceLog;
        }

        return $TaxinvoiceLogList;
    }

    // 파일첨부
    public function AttachFile($CorpNum, $MgtKeyType, $MgtKey, $FilePath, $UserID = null, $DisplayName)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($FilePath)) {
            throw new PopbillException('첨부파일 경로가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($DisplayName)) {
            throw new PopbillException('첨부파일명이 입력되지 않았습니다.');
        }

        if (mb_detect_encoding($this->GetBasename($FilePath)) == 'CP949') {
            $FilePath = iconv('CP949', 'UTF-8', $FilePath);
        }

        $FileName = $DisplayName;

        $postdata = array('Filedata' => '@' . $FilePath . ';filename=' . $FileName);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '/Files', $CorpNum, $UserID, true, null, $postdata, true);
    }

    // 첨부파일 목록 확인
    public function GetFiles($CorpNum, $MgtKeyType, $MgtKey)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '/Files', $CorpNum);
    }

    // 첨부파일 삭제
    public function DeleteFile($CorpNum, $MgtKeyType, $MgtKey, $FileID, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($FileID)) {
            throw new PopbillException('파일아이디가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '/Files/' . $FileID, $CorpNum, $UserID, true, 'DELETE', '');
    }

    // 팝업URL
    public function GetPopUpURL($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?TG=POPUP', $CorpNum, $UserID)->url;
    }

    // 인쇄URL
    public function GetPrintURL($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?TG=PRINT', $CorpNum, $UserID)->url;
    }

    // 구버전 양식 인쇄URL
    public function GetOldPrintURL($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?TG=PRINTOLD', $CorpNum, $UserID)->url;
    }

    // 인쇄URL
    public function GetViewURL($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?TG=VIEW', $CorpNum, $UserID)->url;
    }

    // 공급받는자 인쇄URL
    public function GetEPrintURL($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?TG=EPRINT', $CorpNum, $UserID)->url;
    }

    // 공급받는자 메일URL
    public function GetMailURL($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?TG=MAIL', $CorpNum, $UserID)->url;
    }

    // 세금계산서 다량인쇄 URL
    public function GetMassPrintURL($CorpNum, $MgtKeyType, $MgtKeyList = array(), $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyList)) {
            throw new PopbillException('문서번호 배열이 입력되지 않았습니다.');
        }

        $postdata = json_encode($MgtKeyList);

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '?Print', $CorpNum, $UserID, true, null, $postdata)->url;
    }

    // 공동인증서 정보확인
    public function GetTaxCertInfo($CorpNum, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        
        $response = $this->executeCURL('/Taxinvoice/Certificate', $CorpNum, $UserID);
        $TaxinvoiceCertificate = new TaxinvoiceCertificate();
        $TaxinvoiceCertificate->fromJsonInfo($response);

        return $TaxinvoiceCertificate;
    }

    // 회원인증서 만료일 확인
    public function GetCertificateExpireDate($CorpNum)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice?cfg=CERT', $CorpNum)->certificateExpiration;
    }

    // 발행단가 확인
    public function GetUnitCost($CorpNum)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice?cfg=UNITCOST', $CorpNum)->unitCost;
    }

    // 대용량 연계사업자 유통메일목록 확인
    public function GetEmailPublicKeys($CorpNum)
    {
        return $this->executeCURL('/Taxinvoice/EmailPublicKeys', $CorpNum);
    }

    // 세금계산서 조회
    public function Search($CorpNum, $MgtKeyType, $DType, $SDate, $EDate, $State = array(), $Type = array(), $TaxType = array(), $LateOnly = false, $Page = null, $PerPage = null, $Order = null,
                           $TaxRegIDType = null, $TaxRegIDYN = null, $TaxRegID = null, $QString = null, $InterOPYN = null, $UserID = null, $IssueType = array(),
                           $CloseDownState = array(), $MgtKey = null, $RegType = array())
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($DType)) {
            throw new PopbillException('일자유형이 입력되지 않았습니다.');
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

        $uri = '/Taxinvoice/' . $MgtKeyType;
        $uri .= '?DType=' . $DType;
        $uri .= '&SDate=' . $SDate;
        $uri .= '&EDate=' . $EDate;

        $uri .= '&State=';
        if(!$this->isNullOrEmpty($State)) {
            $uri .= implode(',', $State);
        }

        $uri .= '&Type=';
        if(!$this->isNullOrEmpty($Type)) {
            $uri .= implode(',', $Type);
        }

        $uri .= '&TaxType=';
        if(!$this->isNullOrEmpty($TaxType)) {
            $uri .= implode(',', $TaxType);
        }

        if ($LateOnly) {
            $uri .= '&LateOnly=1';
        } else {
            $uri .= '&LateOnly=0';
        }

        $uri .= '&Page=';
        if(!$this->isNullOrEmpty($Page)) {
            $uri .= $Page;
        }

        $uri .= '&PerPage=';
        if(!$this->isNullOrEmpty($PerPage)) {
            $uri .= $PerPage;
        }

        $uri .= '&Order=';
        if(!$this->isNullOrEmpty($Order)) {
            $uri .= $Order;
        }

        $uri .= '&TaxRegID=';
        if(!$this->isNullOrEmpty($TaxRegID)) {
            $uri .= $TaxRegID;
        }

        $uri .= '&TaxRegIDType=';
        if(!$this->isNullOrEmpty($TaxRegIDType)) {
            $uri .= $TaxRegIDType;
        }

        $uri .= '&TaxRegIDYN=';
        if(!$this->isNullOrEmpty($TaxRegIDYN)) {
            $uri .= $TaxRegIDYN;
        }

        $uri .= '&QString=';
        if(!$this->isNullOrEmpty($QString)) {
            $uri .= urlencode($QString);
        }

        $uri .= '&InterOPYN=';
        if(!$this->isNullOrEmpty($InterOPYN)) {
            $uri .= $InterOPYN;
        }

        $uri .= '&IssueType=';
        if(!$this->isNullOrEmpty($IssueType)) {
            $uri .= implode(',', $IssueType);
        }

        $uri .= '&CloseDownState=';
        if(!$this->isNullOrEmpty($CloseDownState)) {
            $uri .= implode(',', $CloseDownState);
        }

        $uri .= '&MgtKey=';
        if(!$this->isNullOrEmpty($MgtKey)) {
            $uri .= $MgtKey;
        }

        $uri .= '&RegType=';
        if(!$this->isNullOrEmpty($RegType)) {
            $uri .= implode(',', $RegType);
        }

        $response = $this->executeCURL($uri, $CorpNum, $UserID);

        $SearchList = new TISearchResult();
        $SearchList->fromJsonInfo($response);

        return $SearchList;

    }

    // 전자명세서 첨부
    public function AttachStatement($CorpNum, $MgtKeyType, $MgtKey, $SubItemCode, $SubMgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SubItemCode)) {
            throw new PopbillException('첨부할 전자명세서 문서유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SubMgtKey)) {
            throw new PopbillException('첨부할 전자명세서 문서번호가 입력되지 않았습니다.');
        }

        $uri = '/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '/AttachStmt';

        $Request = new TIStmtRequest();
        $Request->ItemCode = $SubItemCode;
        $Request->MgtKey = $SubMgtKey;
        $postdata = json_encode($Request);

        return $this->executeCURL($uri, $CorpNum, $UserID, true, "", $postdata);
    }

    // 전자명세서 첨부해제
    public function DetachStatement($CorpNum, $MgtKeyType, $MgtKey, $SubItemCode, $SubMgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SubItemCode)) {
            throw new PopbillException('첨부할 전자명세서 문서유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($SubMgtKey)) {
            throw new PopbillException('첨부할 전자명세서 문서번호가 입력되지 않았습니다.');
        }

        $uri = '/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '/DetachStmt';

        $Request = new TIStmtRequest();
        $Request->ItemCode = $SubItemCode;
        $Request->MgtKey = $SubMgtKey;
        $postdata = json_encode($Request);

        return $this->executeCURL($uri, $CorpNum, $UserID, true, "", $postdata);
    }

    // 과금정보 확인
    public function GetChargeInfo($CorpNum, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        $uri = '/Taxinvoice/ChargeInfo';

        $response = $this->executeCURL($uri, $CorpNum, $UserID);
        $ChargeInfo = new ChargeInfo();
        $ChargeInfo->fromJsonInfo($response);

        return $ChargeInfo;
    }

    // 문서번호 할당
    public function AssignMgtKey($CorpNum, $MgtKeyType, $itemKey, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($itemKey)) {
            throw new PopbillException('팝빌에서 할당한 식별번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('할당할 문서문서번호가 입력되지 않았습니다.');
        }
        $uri = '/Taxinvoice/' . $itemKey . '/' . $MgtKeyType;
        $postdata = 'MgtKey=' . $MgtKey;

        return $this->executeCURL($uri, $CorpNum, $UserID, true, "", $postdata, false, 'application/x-www-form-urlencoded; charset=utf-8');
    }

    //세금계산서 관련 메일전송 항목에 대한 전송여부 목록 반환
    public function ListEmailConfig($CorpNum, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        $TIEmailSendConfigList = array();

        $result = $this->executeCURL('/Taxinvoice/EmailSendConfig', $CorpNum, $UserID);

        for ($i = 0; $i < Count($result); $i++) {
            $TIEmailSendConfig = new TIEmailSendConfig();
            $TIEmailSendConfig->fromJsonInfo($result[$i]);
            $TIEmailSendConfigList[$i] = $TIEmailSendConfig;
        }
        return $TIEmailSendConfigList;
    }

    // 전자세금계산서 관련 메일전송 항목에 대한 전송여부를 수정
    public function UpdateEmailConfig($corpNum, $emailType, $sendYN, $userID = null)
    {
        if($this->isNullOrEmpty($corpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($emailType)) {
            throw new PopbillException('발송 메일 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($sendYN)) {
            throw new PopbillException('메일 전송 여부가 입력되지 않았습니다.');
        }

        $sendYNString = $sendYN ? 'True' : 'False';
        $uri = '/Taxinvoice/EmailSendConfig?EmailType=' . $emailType . '&SendYN=' . $sendYNString;

        return $result = $this->executeCURL($uri, $corpNum, $userID, true);
    }

    // 공인인증서 유효성 확인
    public function CheckCertValidation($corpNum, $userID = null)
    {
        if($this->isNullOrEmpty($corpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/CertCheck', $corpNum, $userID);
    }

    //팝빌 인감 및 첨부문서 등록 URL
    public function GetSealURL($CorpNum, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        $response = $this->executeCURL('/Member?TG=SEAL', $CorpNum, $UserID);
        return $response->url;
    }

    //공인인증서 등록 URL
    public function GetTaxCertURL($CorpNum, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }

        $response = $this->executeCURL('/Member?TG=CERT', $CorpNum, $UserID);
        return $response->url;
    }

    // PDF URL
    public function GetPDFURL($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?TG=PDF', $CorpNum, $UserID)->url;
    }

    // get PDF
    public function GetPDF($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if (is_null($MgtKey) || empty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        return $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?PDF', $CorpNum, $UserID);
    }

    // get XML
    public function GetXML($CorpNum, $MgtKeyType, $MgtKey, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKeyType)) {
            throw new PopbillException('세금계산서 유형이 입력되지 않았습니다.');
        }
        if($this->isNullOrEmpty($MgtKey)) {
            throw new PopbillException('문서번호가 입력되지 않았습니다.');
        }

        $response = $this->executeCURL('/Taxinvoice/' . $MgtKeyType . '/' . $MgtKey . '?XML', $CorpNum, $UserID);

        $TaxinvoiceXML = new TaxinvoiceXML();
        $TaxinvoiceXML->fromJsonInfo($response);

        return $TaxinvoiceXML;
    }

    // 국세청 즉시전송 확인함수
    public function GetSendToNTSConfig($CorpNum, $UserID = null)
    {
        if($this->isNullOrEmpty($CorpNum)) {
            throw new PopbillException('팝빌회원 사업자번호가 입력되지 않았습니다.');
        }
        
        return $this->executeCURL('/Taxinvoice/SendToNTSConfig', $CorpNum, $UserID)->sendToNTS;
    }
}

class Taxinvoice
{
    public $closeDownState;
    public $closeDownStateDate;

    public $writeSpecification;
    public $emailSubject;
    public $memo;
    public $dealInvoiceMgtKey;

    public $writeDate;
    public $chargeDirection;
    public $issueType;
    public $issueTiming;
    public $taxType;
    public $invoicerCorpNum;
    public $invoicerMgtKey;
    public $invoicerTaxRegID;
    public $invoicerCorpName;
    public $invoicerCEOName;
    public $invoicerAddr;
    public $invoicerBizClass;
    public $invoicerBizType;
    public $invoicerContactName;
    public $invoicerDeptName;
    public $invoicerTEL;
    public $invoicerHP;
    public $invoicerEmail;
    public $invoicerSMSSendYN;

    public $invoiceeCorpNum;
    public $invoiceeType;
    public $invoiceeMgtKey;
    public $invoiceeTaxRegID;
    public $invoiceeCorpName;
    public $invoiceeCEOName;
    public $invoiceeAddr;
    public $invoiceeBizClass;
    public $invoiceeBizType;
    public $invoiceeContactName1;
    public $invoiceeDeptName1;
    public $invoiceeTEL1;
    public $invoiceeHP1;
    public $invoiceeEmail2;
    public $invoiceeContactName2;
    public $invoiceeDeptName2;
    public $invoiceeTEL2;
    public $invoiceeHP2;
    public $invoiceeEmail1;
    public $invoiceeSMSSendYN;

    public $trusteeCorpNum;
    public $trusteeMgtKey;
    public $trusteeTaxRegID;
    public $trusteeCorpName;
    public $trusteeCEOName;
    public $trusteeAddr;
    public $trusteeBizClass;
    public $trusteeBizType;
    public $trusteeContactName;
    public $trusteeDeptName;
    public $trusteeTEL;
    public $trusteeHP;
    public $trusteeEmail;
    public $trusteeSMSSendYN;

    public $taxTotal;
    public $supplyCostTotal;
    public $totalAmount;
    public $modifyCode;
    public $purposeType;
    public $serialNum;
    public $cash;
    public $chkBill;
    public $credit;
    public $note;
    public $remark1;
    public $remark2;
    public $remark3;
    public $kwon;
    public $ho;
    public $businessLicenseYN;
    public $bankBookYN;
    public $faxsendYN;
    public $faxreceiveNum;
    public $originalTaxinvoiceKey;
    public $ntsconfirmNum;
    public $detailList;
    public $addContactList;

    public $orgNTSConfirmNum;

    function fromjsonInfo($jsonInfo)
    {
        isset($jsonInfo->closeDownState) ? ($this->closeDownState = $jsonInfo->closeDownState) : null;
        isset($jsonInfo->closeDownStateDate) ? ($this->closeDownStateDate = $jsonInfo->closeDownStateDate) : null;

        isset($jsonInfo->writeSpecification) ? ($this->writeSpecification = $jsonInfo->writeSpecification) : null;
        isset($jsonInfo->writeDate) ? ($this->writeDate = $jsonInfo->writeDate) : null;
        isset($jsonInfo->chargeDirection) ? ($this->chargeDirection = $jsonInfo->chargeDirection) : null;
        isset($jsonInfo->issueType) ? ($this->issueType = $jsonInfo->issueType) : null;
        isset($jsonInfo->issueTiming) ? ($this->issueTiming = $jsonInfo->issueTiming) : null;
        isset($jsonInfo->taxType) ? ($this->taxType = $jsonInfo->taxType) : null;
        isset($jsonInfo->invoicerCorpNum) ? ($this->invoicerCorpNum = $jsonInfo->invoicerCorpNum) : null;
        isset($jsonInfo->invoicerMgtKey) ? ($this->invoicerMgtKey = $jsonInfo->invoicerMgtKey) : null;
        isset($jsonInfo->invoicerTaxRegID) ? ($this->invoicerTaxRegID = $jsonInfo->invoicerTaxRegID) : null;
        isset($jsonInfo->invoicerCorpName) ? ($this->invoicerCorpName = $jsonInfo->invoicerCorpName) : null;
        isset($jsonInfo->invoicerCEOName) ? ($this->invoicerCEOName = $jsonInfo->invoicerCEOName) : null;
        isset($jsonInfo->invoicerAddr) ? ($this->invoicerAddr = $jsonInfo->invoicerAddr) : null;
        isset($jsonInfo->invoicerBizClass) ? ($this->invoicerBizClass = $jsonInfo->invoicerBizClass) : null;
        isset($jsonInfo->invoicerBizType) ? ($this->invoicerBizType = $jsonInfo->invoicerBizType) : null;
        isset($jsonInfo->invoicerContactName) ? ($this->invoicerContactName = $jsonInfo->invoicerContactName) : null;
        isset($jsonInfo->invoicerDeptName) ? ($this->invoicerDeptName = $jsonInfo->invoicerDeptName) : null;
        isset($jsonInfo->invoicerTEL) ? ($this->invoicerTEL = $jsonInfo->invoicerTEL) : null;
        isset($jsonInfo->invoicerHP) ? ($this->invoicerHP = $jsonInfo->invoicerHP) : null;
        isset($jsonInfo->invoicerEmail) ? ($this->invoicerEmail = $jsonInfo->invoicerEmail) : null;
        isset($jsonInfo->invoicerSMSSendYN) ? ($this->invoicerSMSSendYN = $jsonInfo->invoicerSMSSendYN) : null;

        isset($jsonInfo->invoiceeCorpNum) ? ($this->invoiceeCorpNum = $jsonInfo->invoiceeCorpNum) : null;
        isset($jsonInfo->invoiceeType) ? ($this->invoiceeType = $jsonInfo->invoiceeType) : null;
        isset($jsonInfo->invoiceeMgtKey) ? ($this->invoiceeMgtKey = $jsonInfo->invoiceeMgtKey) : null;
        isset($jsonInfo->invoiceeTaxRegID) ? ($this->invoiceeTaxRegID = $jsonInfo->invoiceeTaxRegID) : null;
        isset($jsonInfo->invoiceeCorpName) ? ($this->invoiceeCorpName = $jsonInfo->invoiceeCorpName) : null;
        isset($jsonInfo->invoiceeCEOName) ? ($this->invoiceeCEOName = $jsonInfo->invoiceeCEOName) : null;
        isset($jsonInfo->invoiceeAddr) ? ($this->invoiceeAddr = $jsonInfo->invoiceeAddr) : null;
        isset($jsonInfo->invoiceeBizClass) ? ($this->invoiceeBizClass = $jsonInfo->invoiceeBizClass) : null;
        isset($jsonInfo->invoiceeBizType) ? ($this->invoiceeBizType = $jsonInfo->invoiceeBizType) : null;
        isset($jsonInfo->invoiceeContactName1) ? ($this->invoiceeContactName1 = $jsonInfo->invoiceeContactName1) : null;
        isset($jsonInfo->invoiceeDeptName1) ? ($this->invoiceeDeptName1 = $jsonInfo->invoiceeDeptName1) : null;
        isset($jsonInfo->invoiceeTEL1) ? ($this->invoiceeTEL1 = $jsonInfo->invoiceeTEL1) : null;
        isset($jsonInfo->invoiceeHP1) ? ($this->invoiceeHP1 = $jsonInfo->invoiceeHP1) : null;
        isset($jsonInfo->invoiceeEmail2) ? ($this->invoiceeEmail2 = $jsonInfo->invoiceeEmail2) : null;
        isset($jsonInfo->invoiceeContactName2) ? ($this->invoiceeContactName2 = $jsonInfo->invoiceeContactName2) : null;
        isset($jsonInfo->invoiceeDeptName2) ? ($this->invoiceeDeptName2 = $jsonInfo->invoiceeDeptName2) : null;
        isset($jsonInfo->invoiceeTEL2) ? ($this->invoiceeTEL2 = $jsonInfo->invoiceeTEL2) : null;
        isset($jsonInfo->invoiceeHP2) ? ($this->invoiceeHP2 = $jsonInfo->invoiceeHP2) : null;
        isset($jsonInfo->invoiceeEmail1) ? ($this->invoiceeEmail1 = $jsonInfo->invoiceeEmail1) : null;
        isset($jsonInfo->invoiceeSMSSendYN) ? ($this->invoiceeSMSSendYN = $jsonInfo->invoiceeSMSSendYN) : null;

        isset($jsonInfo->trusteeCorpNum) ? ($this->trusteeCorpNum = $jsonInfo->trusteeCorpNum) : null;
        isset($jsonInfo->trusteeMgtKey) ? ($this->trusteeMgtKey = $jsonInfo->trusteeMgtKey) : null;
        isset($jsonInfo->trusteeTaxRegID) ? ($this->trusteeTaxRegID = $jsonInfo->trusteeTaxRegID) : null;
        isset($jsonInfo->trusteeCorpName) ? ($this->trusteeCorpName = $jsonInfo->trusteeCorpName) : null;
        isset($jsonInfo->trusteeCEOName) ? ($this->trusteeCEOName = $jsonInfo->trusteeCEOName) : null;
        isset($jsonInfo->trusteeAddr) ? ($this->trusteeAddr = $jsonInfo->trusteeAddr) : null;
        isset($jsonInfo->trusteeBizClass) ? ($this->trusteeBizClass = $jsonInfo->trusteeBizClass) : null;
        isset($jsonInfo->trusteeBizType) ? ($this->trusteeBizType = $jsonInfo->trusteeBizType) : null;
        isset($jsonInfo->trusteeContactName) ? ($this->trusteeContactName = $jsonInfo->trusteeContactName) : null;
        isset($jsonInfo->trusteeDeptName) ? ($this->trusteeDeptName = $jsonInfo->trusteeDeptName) : null;
        isset($jsonInfo->trusteeTEL) ? ($this->trusteeTEL = $jsonInfo->trusteeTEL) : null;
        isset($jsonInfo->trusteeHP) ? ($this->trusteeHP = $jsonInfo->trusteeHP) : null;
        isset($jsonInfo->trusteeEmail) ? ($this->trusteeEmail = $jsonInfo->trusteeEmail) : null;
        isset($jsonInfo->trusteeSMSSendYN) ? ($this->trusteeSMSSendYN = $jsonInfo->trusteeSMSSendYN) : null;

        isset($jsonInfo->taxTotal) ? ($this->taxTotal = $jsonInfo->taxTotal) : null;
        isset($jsonInfo->supplyCostTotal) ? ($this->supplyCostTotal = $jsonInfo->supplyCostTotal) : null;
        isset($jsonInfo->totalAmount) ? ($this->totalAmount = $jsonInfo->totalAmount) : null;
        isset($jsonInfo->modifyCode) ? ($this->modifyCode = $jsonInfo->modifyCode) : null;
        isset($jsonInfo->purposeType) ? ($this->purposeType = $jsonInfo->purposeType) : null;
        isset($jsonInfo->serialNum) ? ($this->serialNum = $jsonInfo->serialNum) : null;
        isset($jsonInfo->cash) ? ($this->cash = $jsonInfo->cash) : null;
        isset($jsonInfo->chkBill) ? ($this->chkBill = $jsonInfo->chkBill) : null;
        isset($jsonInfo->credit) ? ($this->credit = $jsonInfo->credit) : null;
        isset($jsonInfo->note) ? ($this->note = $jsonInfo->note) : null;
        isset($jsonInfo->remark1) ? ($this->remark1 = $jsonInfo->remark1) : null;
        isset($jsonInfo->remark2) ? ($this->remark2 = $jsonInfo->remark2) : null;
        isset($jsonInfo->remark3) ? ($this->remark3 = $jsonInfo->remark3) : null;
        isset($jsonInfo->kwon) ? ($this->kwon = $jsonInfo->kwon) : null;
        isset($jsonInfo->ho) ? ($this->ho = $jsonInfo->ho) : null;
        isset($jsonInfo->businessLicenseYN) ? ($this->businessLicenseYN = $jsonInfo->businessLicenseYN) : null;
        isset($jsonInfo->bankBookYN) ? ($this->bankBookYN = $jsonInfo->bankBookYN) : null;
        isset($jsonInfo->faxsendYN) ? ($this->faxsendYN = $jsonInfo->faxsendYN) : null;
        isset($jsonInfo->faxreceiveNum) ? ($this->faxreceiveNum = $jsonInfo->faxreceiveNum) : null;
        isset($jsonInfo->originalTaxinvoiceKey) ? ($this->originalTaxinvoiceKey = $jsonInfo->originalTaxinvoiceKey) : null;

        isset($jsonInfo->orgNTSConfirmNum) ? ($this->orgNTSConfirmNum = $jsonInfo->orgNTSConfirmNum) : null;
        isset($jsonInfo->ntsconfirmNum) ? ($this->ntsconfirmNum = $jsonInfo->ntsconfirmNum) : null;

        if (isset($jsonInfo->detailList)) {
            $DetailList = array();
            for ($i = 0; $i < Count($jsonInfo->detailList); $i++) {
                $TaxinvoiceDetailObj = new TaxinvoiceDetail();
                $TaxinvoiceDetailObj->fromJsonInfo($jsonInfo->detailList[$i]);
                $DetailList[$i] = $TaxinvoiceDetailObj;
            }
            $this->detailList = $DetailList;
        }

        if (isset($jsonInfo->addContactList)) {
            $contactList = array();
            for ($i = 0; $i < Count($jsonInfo->addContactList); $i++) {
                $TaxinvoiceContactObj = new TaxinvoiceAddContact();
                $TaxinvoiceContactObj->fromJsonInfo($jsonInfo->addContactList[$i]);
                $contactList[$i] = $TaxinvoiceContactObj;
            }

            $this->addContactList = $contactList;
        }
    }

}

class TaxinvoiceDetail
{
    public $serialNum;
    public $purchaseDT;
    public $itemName;
    public $spec;
    public $qty;
    public $unitCost;
    public $supplyCost;
    public $tax;
    public $remark;

    public function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->serialNum) ? $this->serialNum = $jsonInfo->serialNum : null;
        isset($jsonInfo->purchaseDT) ? $this->purchaseDT = $jsonInfo->purchaseDT : null;
        isset($jsonInfo->itemName) ? $this->itemName = $jsonInfo->itemName : null;
        isset($jsonInfo->spec) ? $this->spec = $jsonInfo->spec : null;
        isset($jsonInfo->qty) ? $this->qty = $jsonInfo->qty : null;
        isset($jsonInfo->unitCost) ? $this->unitCost = $jsonInfo->unitCost : null;
        isset($jsonInfo->supplyCost) ? $this->supplyCost = $jsonInfo->supplyCost : null;
        isset($jsonInfo->tax) ? $this->tax = $jsonInfo->tax : null;
        isset($jsonInfo->remark) ? $this->remark = $jsonInfo->remark : null;
    }

}

class TIBulkRequest
{
    public $forceIssue;
    public $invoices;
}

class BulkTaxinvoiceResult
{
    public $code;
    public $message;
    public $submitID;
    public $submitCount;
    public $successCount;
    public $failCount;
    public $txState;
    public $txResultCode;
    public $txStartDT;
    public $txEndDT;
    public $receiptDT;
    public $receiptID;
    public $issueResult;

    public function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->code) ? $this->code = $jsonInfo->code : null;
        isset($jsonInfo->message) ? $this->message = $jsonInfo->message : null;
        isset($jsonInfo->submitID) ? $this->submitID = $jsonInfo->submitID : null;
        isset($jsonInfo->submitCount) ? $this->submitCount = $jsonInfo->submitCount : null;
        isset($jsonInfo->successCount) ? $this->successCount = $jsonInfo->successCount : null;
        isset($jsonInfo->failCount) ? $this->failCount = $jsonInfo->failCount : null;
        isset($jsonInfo->txState) ? $this->txState = $jsonInfo->txState : null;
        isset($jsonInfo->txResultCode) ? $this->txResultCode = $jsonInfo->txResultCode : null;
        isset($jsonInfo->txStartDT) ? $this->txStartDT = $jsonInfo->txStartDT : null;
        isset($jsonInfo->txEndDT) ? $this->txEndDT = $jsonInfo->txEndDT : null;
        isset($jsonInfo->receiptDT) ? $this->receiptDT = $jsonInfo->receiptDT : null;
        isset($jsonInfo->receiptID) ? $this->receiptID = $jsonInfo->receiptID : null;

        $InfoIssueResult = array();

        for ($i = 0; $i < Count($jsonInfo->issueResult); $i++) {
            $InfoObj = new BulkTaxinvoiceIssueResult();
            $InfoObj->fromJsonInfo($jsonInfo->issueResult[$i]);
            $InfoIssueResult[$i] = $InfoObj;
        }
        $this->issueResult = $InfoIssueResult;
    }
}

class BulkTaxinvoiceIssueResult
{
    public $invoicerMgtKey;
    public $trusteeMgtKey;
    public $code;
    public $message;
    public $ntsconfirmNum;
    public $issueDT;

    public function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->invoicerMgtKey) ? $this->invoicerMgtKey = $jsonInfo->invoicerMgtKey : null;
        isset($jsonInfo->trusteeMgtKey) ? $this->trusteeMgtKey = $jsonInfo->trusteeMgtKey : null;
        isset($jsonInfo->code) ? $this->code = $jsonInfo->code : null;
        isset($jsonInfo->message) ? $this->message = $jsonInfo->message : null;
        isset($jsonInfo->ntsconfirmNum) ? $this->ntsconfirmNum = $jsonInfo->ntsconfirmNum : null;
        isset($jsonInfo->issueDT) ? $this->issueDT = $jsonInfo->issueDT : null;
    }
}

class TaxinvoiceAddContact
{
    public $serialNum;
    public $email;
    public $contactName;

    public function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->serialNum) ? $this->serialNum = $jsonInfo->serialNum : null;
        isset($jsonInfo->email) ? $this->email = $jsonInfo->email : null;
        isset($jsonInfo->contactName) ? $this->contactName = $jsonInfo->contactName : null;
    }
}

class TISearchResult
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
        isset($jsonInfo->pageCount) ? $this->pageCount = $jsonInfo->pageCount : null;
        isset($jsonInfo->pageNum) ? $this->pageNum = $jsonInfo->pageNum : null;
        isset($jsonInfo->message) ? $this->message = $jsonInfo->message : null;

        $InfoList = array();

        for ($i = 0; $i < Count($jsonInfo->list); $i++) {
            $InfoObj = new TaxinvoiceInfo();
            $InfoObj->fromJsonInfo($jsonInfo->list[$i]);
            $InfoList[$i] = $InfoObj;
        }
        $this->list = $InfoList;
    }
}


class TaxinvoiceInfo
{
    public $closeDownState;
    public $closeDownStateDate;

    public $itemKey;
    public $stateCode;
    public $taxType;
    public $purposeType;
    public $modifyCode;
    public $issueType;
    public $writeDate;
    public $lateIssueYN;
    public $invoicerCorpName;
    public $invoicerCorpNum;
    public $invoicerMgtKey;
    public $invoicerPrintYN;
    public $invoiceeCorpName;
    public $invoiceeCorpNum;
    public $invoiceeMgtKey;
    public $invoiceePrintYN;
    public $trusteeCorpName;
    public $trusteeCorpNum;
    public $trusteeMgtKey;
    public $trusteePrintYN;
    public $supplyCostTotal;
    public $taxTotal;
    public $issueDT;
    public $preIssueDT;
    public $stateDT;
    public $openYN;
    public $openDT;
    public $ntsresult;
    public $ntsconfirmNum;
    public $ntssendDT;
    public $ntsresultDT;
    public $ntssendErrCode;
    public $stateMemo;
    public $regDT;

    public $interOPYN;

    public function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->closeDownState) ? ($this->closeDownState = $jsonInfo->closeDownState) : null;
        isset($jsonInfo->closeDownStateDate) ? ($this->closeDownStateDate = $jsonInfo->closeDownStateDate) : null;
        isset($jsonInfo->itemKey) ? $this->itemKey = $jsonInfo->itemKey : null;
        isset($jsonInfo->stateCode) ? $this->stateCode = $jsonInfo->stateCode : null;
        isset($jsonInfo->taxType) ? $this->taxType = $jsonInfo->taxType : null;
        isset($jsonInfo->purposeType) ? $this->purposeType = $jsonInfo->purposeType : null;
        isset($jsonInfo->modifyCode) ? $this->modifyCode = $jsonInfo->modifyCode : null;
        isset($jsonInfo->issueType) ? $this->issueType = $jsonInfo->issueType : null;
        isset($jsonInfo->lateIssueYN) ? $this->lateIssueYN = $jsonInfo->lateIssueYN : null;
        isset($jsonInfo->writeDate) ? $this->writeDate = $jsonInfo->writeDate : null;
        isset($jsonInfo->invoicerCorpName) ? $this->invoicerCorpName = $jsonInfo->invoicerCorpName : null;
        isset($jsonInfo->invoicerCorpNum) ? $this->invoicerCorpNum = $jsonInfo->invoicerCorpNum : null;
        isset($jsonInfo->invoicerMgtKey) ? $this->invoicerMgtKey = $jsonInfo->invoicerMgtKey : null;
        isset($jsonInfo->invoicerPrintYN) ? $this->invoicerPrintYN = $jsonInfo->invoicerPrintYN : null;
        isset($jsonInfo->invoiceeCorpName) ? $this->invoiceeCorpName = $jsonInfo->invoiceeCorpName : null;
        isset($jsonInfo->invoiceeCorpNum) ? $this->invoiceeCorpNum = $jsonInfo->invoiceeCorpNum : null;
        isset($jsonInfo->invoiceeMgtKey) ? $this->invoiceeMgtKey = $jsonInfo->invoiceeMgtKey : null;
        isset($jsonInfo->invoiceePrintYN) ? $this->invoiceePrintYN = $jsonInfo->invoiceePrintYN : null;
        isset($jsonInfo->trusteeCorpName) ? $this->trusteeCorpName = $jsonInfo->trusteeCorpName : null;
        isset($jsonInfo->trusteeCorpNum) ? $this->trusteeCorpNum = $jsonInfo->trusteeCorpNum : null;
        isset($jsonInfo->trusteeMgtKey) ? $this->trusteeMgtKey = $jsonInfo->trusteeMgtKey : null;
        isset($jsonInfo->trusteePrintYN) ? $this->trusteePrintYN = $jsonInfo->trusteePrintYN : null;
        isset($jsonInfo->supplyCostTotal) ? $this->supplyCostTotal = $jsonInfo->supplyCostTotal : null;
        isset($jsonInfo->taxTotal) ? $this->taxTotal = $jsonInfo->taxTotal : null;
        isset($jsonInfo->issueDT) ? $this->issueDT = $jsonInfo->issueDT : null;
        isset($jsonInfo->preIssueDT) ? $this->preIssueDT = $jsonInfo->preIssueDT : null;
        isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
        isset($jsonInfo->stateDT) ? $this->stateDT = $jsonInfo->stateDT : null;
        isset($jsonInfo->openYN) ? $this->openYN = $jsonInfo->openYN : null;
        isset($jsonInfo->openDT) ? $this->openDT = $jsonInfo->openDT : null;
        isset($jsonInfo->ntsresult) ? $this->ntsresult = $jsonInfo->ntsresult : null;
        isset($jsonInfo->ntsconfirmNum) ? $this->ntsconfirmNum = $jsonInfo->ntsconfirmNum : null;
        isset($jsonInfo->ntssendDT) ? $this->ntssendDT = $jsonInfo->ntssendDT : null;
        isset($jsonInfo->ntsresultDT) ? $this->ntsresultDT = $jsonInfo->ntsresultDT : null;
        isset($jsonInfo->ntssendErrCode) ? $this->ntssendErrCode = $jsonInfo->ntssendErrCode : null;
        isset($jsonInfo->stateMemo) ? $this->stateMemo = $jsonInfo->stateMemo : null;
        isset($jsonInfo->interOPYN) ? $this->interOPYN = $jsonInfo->interOPYN : null;
    }
}

class TaxinvoiceLog
{
    public $ip;
    public $docLogType;
    public $log;
    public $procType;
    public $procCorpName;
    public $procContactName;
    public $procMemo;
    public $regDT;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->ip) ? $this->ip = $jsonInfo->ip : null;
        isset($jsonInfo->docLogType) ? $this->docLogType = $jsonInfo->docLogType : null;
        isset($jsonInfo->log) ? $this->log = $jsonInfo->log : null;
        isset($jsonInfo->procType) ? $this->procType = $jsonInfo->procType : null;
        isset($jsonInfo->procCorpName) ? $this->procCorpName = $jsonInfo->procCorpName : null;
        isset($jsonInfo->procContactName) ? $this->procContactName = $jsonInfo->procContactName : null;
        isset($jsonInfo->procMemo) ? $this->procMemo = $jsonInfo->procMemo : null;
        isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
    }
}

class TaxinvoiceCertificate
{
    public $regDT;
    public $expireDT;
    public $issuerDN;
    public $subjectDN;
    public $issuerName;
    public $oid;
    public $regContactName;
    public $regContactID;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
        isset($jsonInfo->expireDT) ? $this->expireDT = $jsonInfo->expireDT : null;
        isset($jsonInfo->issuerDN) ? $this->issuerDN = $jsonInfo->issuerDN : null;
        isset($jsonInfo->subjectDN) ? $this->subjectDN = $jsonInfo->subjectDN : null;
        isset($jsonInfo->issuerName) ? $this->issuerName = $jsonInfo->issuerName : null;
        isset($jsonInfo->oid) ? $this->oid = $jsonInfo->oid : null;
        isset($jsonInfo->regContactName) ? $this->regContactName = $jsonInfo->regContactName : null;
        isset($jsonInfo->regContactID) ? $this->regContactID = $jsonInfo->regContactID : null;
    }
}

class TaxinvoiceXML
{
    public $code;
    public $message;
    public $retObject;

    function fromJsonInfo($jsonInfo)
    {
        isset($jsonInfo->code) ? $this->code = $jsonInfo->code : null;
        isset($jsonInfo->message) ? $this->message = $jsonInfo->message : null;
        isset($jsonInfo->retObject) ? $this->retObject = $jsonInfo->retObject : null;
    }
}

class ENumMgtKeyType
{
    const SELL = 'SELL';
    const BUY = 'BUY';
    const TRUSTEE = 'TRUSTEE';
}

class TIMemoRequest
{
    public $memo;
    public $emailSubject;
}

class TIIssueRequest
{
    public $memo;
    public $emailSubject;
    public $forceIssue;
}

class TIStmtRequest
{
    public $ItemCode;
    public $MgtKey;
}

class TIEmailSendConfig
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

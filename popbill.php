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
* We welcome any suggestions, feedbacks, blames or anythings.
* ======================================================================================
*/

require_once 'Linkhub/linkhub.auth.php';

class PopbillBase
{
	const ServiceID_REAL = 'POPBILL';
	const ServiceID_TEST = 'POPBILL_TEST';
	const ServiceURL_REAL = 'https://popbill.linkhub.co.kr';
    const ServiceURL_TEST = 'https://popbill_test.linkhub.co.kr';
    const Version = '1.0';
    
    private $Token_Table = array();
    private $Linkhub;
    private $IsTest = false;
    private $scopes = array();
    
    public function __construct($LinkID,$SecretKey) {
    	$this->Linkhub = Linkhub::getInstance($LinkID,$SecretKey);
    	$this->scopes[] = 'member';
    }
    
    public function IsTest($T) {$this->IsTest = $T;}

    protected function AddScope($scope) {$this->scopes[] = $scope;}
    
    private function getsession_Token($CorpNum) {
    	
        $targetToken = null;

        if(array_key_exists($CorpNum, $this->Token_Table)) {
            $targetToken = $this->Token_Table[$CorpNum];
        }

    	$Refresh = false;
    	
    	if(is_null($targetToken)) {
    		$Refresh = true;
    	}
    	else {
            $Expiration = new DateTime($targetToken->expiration,new DateTimeZone("UTC"));
            $now = gmdate("Y-m-d H:i:s",time());
            $Refresh = $Expiration < $now; 
    	}
    	
    	if($Refresh) {
    		try
    		{
    			$targetToken = $this->Linkhub->getToken($this->IsTest ? PopbillBase::ServiceID_TEST : PopbillBase::ServiceID_REAL,$CorpNum, $this->scopes);
    		}catch(LinkhubException $le) {
    			throw new PopbillException($le->getMessage(),$le->getCode());
    		}
            $this->Token_Table[$CorpNum] = $targetToken;
    	}
    	
    	return $targetToken->session_token;
    }
 
    //팝빌 연결 URL함수
    public function GetPopbillURL($CorpNum ,$UserID, $TOGO) {
    	$response = $this->executeCURL('/?TG='.$TOGO,$CorpNum,$UserID);
    	return $response->url;
    }
    
 	//가입여부 확인
 	public function CheckIsMember($CorpNum , $LinkID) {
 		return $this->executeCURL('/Join?CorpNum='.$CorpNum.'&LID='.$LinkID);
 	}
    //회원가입
    public function JoinMember($JoinForm) {
    	$postdata = json_encode($JoinForm);
   		return $this->executeCURL('/Join',null,null,true,null,$postdata);
    	
    }
 
    //회원 잔여포인트 확인
    public function GetBalance($CorpNum) {
    	try {
    		return $this->Linkhub->getBalance($this->getsession_Token($CorpNum),$this->IsTest ? PopbillBase::ServiceID_TEST : PopbillBase::ServiceID_REAL);
    	}catch(LinkhubException $le) {
    		throw new PopbillException($le->message,$le->code);
    	}
    }
 
    //파트너 잔여포인트 확인
    public function GetPartnerBalance($CorpNum) {
    	try {
    		return $this->Linkhub->getPartnerBalance($this->getsession_Token($CorpNum),$this->IsTest ? PopbillBase::ServiceID_TEST : PopbillBase::ServiceID_REAL);
    	}catch(LinkhubException $le) {
    		throw new PopbillException($le->message,$le->code);
    	}
    }
    
    protected function executeCURL($uri,$CorpNum = null,$userID = null,$isPost = false, $action = null, $postdata = null,$isMultiPart=false) {
		$http = curl_init(($this->IsTest ? PopbillBase::ServiceURL_TEST : PopbillBase::ServiceURL_REAL).$uri);
		$header = array();
		
		if(is_null($CorpNum) == false) {
			$header[] = 'Authorization: Bearer '.$this->getsession_Token($CorpNum);
		}
		if(is_null($userID) == false) {
			$header[] = 'x-pb-userid: '.$userID;
		}
		if(is_null($action) == false) {
			$header[] = 'X-HTTP-Method-Override: '.$action;
		}
		if($isMultiPart == false) {
			$header[] = 'Content-Type: Application/json';
		}
		
		if($isPost) {
			curl_setopt($http, CURLOPT_POST,1);
			curl_setopt($http, CURLOPT_POSTFIELDS, $postdata);   
		}
		curl_setopt($http, CURLOPT_HTTPHEADER,$header);
		curl_setopt($http, CURLOPT_RETURNTRANSFER, TRUE);
		
		$responseJson = curl_exec($http);
		$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
		
		curl_close($http);
			
		if($http_status != 200) {
			throw new PopbillException($responseJson);
		}
		
		return json_decode($responseJson);
	}
}

class JoinForm 
{
	public $LinkID;
	public $CorpNum;
	public $CEOName;
	public $CorpName;
	public $Addr;
	public $ZipCode;
	public $BizType;
	public $BizClass;
	public $ContactName;
	public $ContactEmail;
	public $ContactTEL;
	public $ID;
	public $PWD;
}

class PopbillException extends Exception
{
	public function __construct($response,$code = -99999999, Exception $previous = null) {
       $Err = json_decode($response);
       if(is_null($Err)) {
       		parent::__construct($response, $code );
       }
       else {
       		parent::__construct($Err->message, $Err->code);
       }
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
?>
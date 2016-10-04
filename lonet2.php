<?php 
require_once("./crypt.php");

class Lonet2 {
	protected $sid = null;
	protected $username = null;
	protected $password = null;
	protected $newmails = null;
	protected $memberonline = null;
	protected $className = null;
	protected $current_site = null;
	protected $crypt = null;
	protected $pageText = null;
	
	public function __construct($username, $password, $crypt){
		$this->username = $username;
		$this->password = $password;
		$this->crypt = new Crypt($crypt);
		$this->connect("100001");
	}
	
	public function getJSON(){
		$arr = array(
			'sid' => $this->sid,
			'username' => $this->username,
			'newmails' => $this->newmails,
			'memberonline' => $this->memberonline,
			'classname' => $this->className
		);
		return json_encode($arr);
	}
	
	public function isLoggedin(){
		preg_match_all("|<title>(.*?)</title>|U", $this->pageText, $res);
		if(isset($res[1][0]) && $this->startsWith($res[1][0], $this->username)){
			return true;
		}
		return false;
	}
	
	public function update(){
		$this->connect("100001",null, true);
	}
	
//	public function getAllFiles(){
//		$this->connect("100013");
//		$this->connect("109672");
//		preg_match_all("/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", $this->pageText, $res);
//		print_r($res);
//	}
	
	protected function connect($site = "100001", $param = null, $update = false){
		if($update == true){
			$this->pageText = null;
			$this->current_site = null;
			$this->sid = null;
			$this->newmails = null;
			$this->memberonline = null;
			$this->className = null;
		}
		if((string)$this->current_site == (string)$site){
		} else {
			if($param != null){
				$param = $param . "&";
			}
			if(isset($this->sid) && array_key_exists($site, $this->sid)){
				$sid = $this->sid[$site];
				if(strpos($sid, "SID:") === false){
				} else {
					$sid = str_replace("SID:","",$sid);
					$sid = $this->sid[$sid];
				}
				$this->goToPage("https://www.lo-net2.de/wws/$site.php?sid=$sid");
				$this->updateSID();
			} else {
				$de_password = $this->crypt->decrypt($this->password);
				$this->login("https://www.lo-net2.de/wws/$site.php", $param."login_login=$this->username&login_password=$de_password");
			}
			
			$this->current_site = $site;
			$this->getSID();
			$this->getNewMails();
			$this->getMemberOnline();
			$this->getClassName();
		}
		return $this->pageText;
	}
	
	protected function getSID(){
		if($this->sid == null){
			$this->connect("100001");
			preg_match_all("/sid=(?P<zahl>\d+)'/", $this->pageText, $id);
			if(!isset($id[1][0])){
				throw new Expection('Keine Session ID.');
			}
			$this->sid["100001"] = $id[1][0];
			$this->updateSID();
		}
		return $this->sid;
	}
	
	protected function updateSID(){
		preg_match_all("|<a href=\"(?P<site>\d+).php\?sid=(?P<sid>\d+)\">|U", $this->pageText, $res);
		foreach($res['site'] as $key=>$value){
			if(!array_key_exists($value, $this->sid)){
				if(in_array($res['sid'][$key], $this->sid, true)){
					$this->sid[$value] = "SID:".array_search($res['sid'][$key], $this->sid);
				} else {
					$this->sid[$value] = $res['sid'][$key];
				}
			}
		}
	}
	
	protected function getNewMails(){
		if($this->newmails == null) {
			$this->connect("100001");
			preg_match_all("|<a href=\"105592.php\?sid=[0-9]+\">(?P<zahl>\d+) (.*)</a>|U", $this->pageText, $res);
			if(isset($res['zahl'][0])){
				$this->newmails = $res['zahl'][0];
			}
		}
		return $this->newmails;		
	}
	
	protected function getMemberOnline(){
		if($this->memberonline == null) {
			$this->connect("100001");
			preg_match_all("|<a href=\"105492.php\?sid=[0-9]+\">(?P<zahl>\d+) (.*)</a>|U", $this->pageText, $res);
			if(isset($res['zahl'][0])){
				$this->memberonline = $res['zahl'][0];
			}
		} 
		return $this->memberonline;		
	}
	
	protected function getClassName(){
		if($this->className == null) {
			$this->connect("100001");
			preg_match_all("|<a href=\"100013.php\?sid=[0-9]+\">(.*)</a>|U", $this->pageText, $res);
			if(isset($res[1][0])){
				$this->className = $res[1][0];
			} else {
				$this->className = null;
			}		
		}
		return $this->className;
	}
	
		
	protected function login($url,$data){
		$fp = fopen("cookie.txt", "w");
		fclose($fp);
		$login = curl_init();
		curl_setopt($login, CURLOPT_COOKIEJAR, "cookie.txt");
		curl_setopt($login, CURLOPT_COOKIEFILE, "cookie.txt");
		curl_setopt($login, CURLOPT_TIMEOUT, 40000);
		curl_setopt($login, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($login, CURLOPT_URL, $url);
		curl_setopt($login, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($login, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($login, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($login, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($login, CURLOPT_POST, TRUE);
		curl_setopt($login, CURLOPT_POSTFIELDS, $data);
		
		$this->pageText = curl_exec ($login);
		curl_close ($login);
		unset($login);    
	}
	
	protected function goToPage($site){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);
		curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
		curl_setopt($ch, CURLOPT_URL, $site);
		
		$this->pageText = curl_exec ($ch);
		curl_close ($ch);
	}
	
	protected function startsWith($haystack, $needle) {
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
	}
}
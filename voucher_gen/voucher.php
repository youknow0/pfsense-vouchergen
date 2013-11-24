<?php
require_once 'simple_html_dom.php';

class pfSense_Voucher {
	
	// temporary directory to use for cookie storage.
	const TEMP_DIR = '/tmp';
	const USER_AGENT = 'pfSense Voucher Generator';
	
	// allow an insecure connection to be used.
	// be sure to know what you are doing when enabling this!
	const INSECURE_CONNECTION = true;
	
	const URL_CREATE_VOUCHERS = 'services_captiveportal_vouchers_edit.php?zone=';
	const URL_ROLL_GET = 'services_captiveportal_vouchers.php?zone=';
	const URL_ROLL_GET_CSV = 'services_captiveportal_vouchers.php?zone=%1$s&act=csv&id=%2$s';
	const URL_LOGIN = 'index.php';
	
	// whether to output debug information
	// if this option is turned off, no debug information will be 
	// displayed, regardless of the value of the other debugging 
	// options.
	const DEBUG_OUTPUT = false;
	
	// output the response body
	const DEBUG_OUTPUT_HTML = false;
	
	// output POST-ed request data
	const DEBUG_OUTPUT_RQDATA = true;
	
	// output the response of requests that obtain the csrf token.
	const DEBUG_OUTPUT_HTML_CSRF_OBTAIN = false;
	
	// the name of the input field that contains the csrf token
	const CSRF_FIELD_NAME = '__csrf_magic';
	
	const MAX_ROLL_ID = 65535;
	
	/**
	 * credentials for logging into pfSense
	 */
	private $host;
	private $username;
	private $password;
	
	/**
	 * curl handle
	 */
	private $curl = null;
	private $curl_cookiejar;
	
	private $loggedIn = false;
	
	public function __construct($host, $username, $password) {
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function __destruct() {
		if (!empty($this->curl_cookiejar) && file_exists($this->curl_cookiejar)) {
			unlink($this->curl_cookiejar);
		}
	}
	
	private function _debugRequest($url, $html, $rqData = null, $csrfObtain = false) {
		if (self::DEBUG_OUTPUT) {
			$callers=debug_backtrace();
			$caller = $callers[1]['function'];
			echo 'DEBUG[', $caller, ']: URL[', $url, ']', PHP_EOL;
			
			if (self::DEBUG_OUTPUT_RQDATA && ($rqData !== null)) {
				echo 'send RQdata: ', PHP_EOL;
				echo $rqData, PHP_EOL;
			}
			
			if (self::DEBUG_OUTPUT_HTML && (!$csrfObtain || self::DEBUG_OUTPUT_HTML_CSRF_OBTAIN)) {
				echo 'returned HTML: ', PHP_EOL;
				echo $html, PHP_EOL;
				
			}
			
			echo 'END DEBUG';
			echo PHP_EOL;
		}
	}
	
	private function _getCurlHandle() {
		if ($this->curl === null) {
			$this->curl = curl_init();
			
			$this->curl_cookiejar = tempnam(self::TEMP_DIR, 'pfvoucher');
			
			if ($this->curl_cookiejar === null) {
				throw new pfSense_Voucher_Exception('Failed to create temporary file for cookie storage.');
			}
			
			// start a new cookie session
			curl_setopt($this->curl, CURLOPT_COOKIESESSION, true);
			
			// store cookies in the temp file
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->curl_cookiejar);
			
			// set the user agent
			curl_setopt($this->curl, CURLOPT_USERAGENT, self::USER_AGENT);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			
			if (self::INSECURE_CONNECTION === true) {
				curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
				//curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 1);
			}
		}
		
		return $this->curl;
	}
	
	private function _getCurlPostHandle() {
		$c = $this->_getCurlHandle();
		curl_setopt($c, CURLOPT_HTTPGET, false);
		curl_setopt($c, CURLOPT_POST, true);
		
		return $c;
	}
	
	private function _getCurlGetHandle() {
		$c = $this->_getCurlHandle();
		curl_setopt($c, CURLOPT_HTTPGET, true);
		curl_setopt($c, CURLOPT_POST, false);
		
		return $c;
	}
	
	private function _requireLogin() {
		if ($this->loggedIn) {
			return;
		}
		
		return $this->_login();
	}
	
	private function _login() {
		$login = $this->_getLoginUrl();
		$csrf = $this->_obtainCsrfHandle($login);
		
		$c = $this->_getCurlPostHandle();
		
		$rqData = http_build_query(array(
			'usernamefld' => $this->username,
			'passwordfld' => $this->password,
			'login' => 'Login',
			self::CSRF_FIELD_NAME => $csrf
		));
		
		curl_setopt($c, CURLOPT_POSTFIELDS, $rqData);
		
		$text = curl_exec($c);
		$this->_debugRequest($login, $text, $rqData);
		
		if ($text === false) {
			$err = curl_error($c);
			throw new pfSense_Voucher_Exception('Failed to login: HTTP error: ' . $err);
		}
		
		$this->loggedIn = true;
		
		return true;
	}
	
	private function _getCreateVoucherUrl($zoneName) {
		$zoneName = strtolower($zoneName);
		return $this->_getBaseUrl() . self::URL_CREATE_VOUCHERS . $zoneName;
	}
	
	private function _getGetRollsUrl($zoneName) {
		$zoneName = strtolower($zoneName);
		return $this->_getBaseUrl() . self::URL_ROLL_GET . $zoneName;
	}
	
	private function _getDownloadRollCsvUrl($zoneName, $id) {
		$zoneName = strtolower($zoneName);
		$res = $this->_getBaseUrl();
		$res .= sprintf(self::URL_ROLL_GET_CSV, $zoneName, $id);
		
		return $res;
	}
	
	private function _getLoginUrl() {
		return $this->_getBaseUrl() . self::URL_LOGIN;
	}
	
	private function _getBaseUrl() {
		return 'https://' . $this->host . '/';
	}
	
	private function _obtainCsrfHandle($url) {
		$c = $this->_getCurlGetHandle();
		
		curl_setopt($c, CURLOPT_URL, $url);
		
		$text = curl_exec($c);
		$this->_debugRequest($url, $text, null, true);
		
		if ($text === false) {
			$err = curl_error($c);
			throw new pfSense_Voucher_Exception('Failed to obtain CSRF token: HTTP error: ' . $err);
		}
		
		$html = str_get_html($text);
		$csrf = $html->find('input[name=' . self::CSRF_FIELD_NAME . ']', 0);
		
		if ($csrf === null) {
			throw new pfSense_Voucher_Exception('Failed to obtain CSRF token: csrf form element not found');
		}
		
		$token = $csrf->value;
		
		if (empty($token)) {
			throw new pfSense_Voucher_Exception('Failed to obtain CSRF token: received empty token.');
		}
		
		return $token;
	}
	
	private function _getNextRollId($zoneName) {
		$rolls = $this->getRolls($zoneName);
		
		for ($i = 1; $i < self::MAX_ROLL_ID; $i++) {
			$found = false;
			foreach ($rolls as $roll) {
				if ($roll->rollId == $i) {
					$found = true;
					break;
				}
			}
			
			if (!$found) {
				return $i;
			}
			
		}
		
		return null;
	}
	
	public function generateVoucherRoll($zoneName, $minutes, $count, $comment) {
		$this->_requireLogin();
		
		$editUrl = $this->_getCreateVoucherUrl($zoneName);
		
		$rollId = $this->_getNextRollId($zoneName);
		
		if ($rollId === null) {
			throw new pfSense_Voucher_Exception('No more free Roll IDs!');
		}
		
		$csrf = $this->_obtainCsrfHandle($editUrl);
		
		$rqData = http_build_query(array(
			'number' => $rollId,
			'minutes' => $minutes,
			'count' => $count,
			'comment' => $comment,
			self::CSRF_FIELD_NAME => $csrf
		));
		
		$c = $this->_getCurlPostHandle();
		curl_setopt($c, CURLOPT_POSTFIELDS, $rqData);
		
		$text = curl_exec($c);
		$this->_debugRequest($editUrl, $text, $rqData);
		
		if ($text === false) {
			$err = curl_error($c);
			throw new pfSense_Voucher_Exception('Failed to generate Voucher Roll: HTTP error: ' . $err);
		}
		
		return $rollId;
	}
	
	public function getRolls($zoneName) {
		$this->_requireLogin();
		
		$url = $this->_getGetRollsUrl($zoneName);
		
		$c = $this->_getCurlGetHandle();
		curl_setopt($c, CURLOPT_URL, $url);
		
		$text = curl_exec($c);
		$this->_debugRequest($url, $text);
		
		if ($text === false) {
			$err = curl_error($c);
			throw new pfSense_Voucher_Exception('Failed to get list of voucher Rolls: HTTP error: ' . $err);
		}
		
		$html = str_get_html($text);
		// try to find the table with the vouchers
		$tds = $html->find('td');
		
		$table = null;
		foreach ($tds as $td) {
			if (trim($td->innertext) == 'Voucher Rolls') {
				$table = $td->next_sibling();
				break;
			}
		}
		
		if ($table === null) {
			throw new pfSense_Voucher_Exception('Failed to get list of voucher Rolls: Unable to find voucher table in HTML');
		}
		
		$rows = $table->find('tr');
		
		$rolls = array();
		$i = 0;
		
		// remove footer
		array_pop($rows);
		
		// remove header
		array_shift($rows);
		
		foreach($rows as $row) {
			
			$roll = new StdClass;
			
			$tds = $row->find('td');
			
			$roll->rollId = trim($this->_stripNbsp($tds[0]->innertext));
			$roll->minutes = trim($this->_stripNbsp($tds[1]->innertext));
			$roll->count = trim($this->_stripNbsp($tds[2]->innertext));
			$roll->comment = trim($this->_stripNbsp($tds[3]->innertext));
			
			$editLink = $row->find('a[href^=services_captiveportal_vouchers_edit.php]', 0);
			
			if ($editLink === null) {
				echo '<pre>';
				echo htmlspecialchars($row->innertext);
				echo '</pre>';
				throw new pfSense_Voucher_Exception('Failed to get list of voucher Rolls: Unable to find edit link in HTML');
			}
			
			$editHref = $editLink->href;
			$id = strrchr($editHref, '=');
			
			if ($id === false) {
				throw new pfSense_Voucher_Exception('Failed to get list of voucher Rolls: Unable to find ID in HTML');
			}
			
			$roll->id = substr($id, 1);
			
			$rolls[$i] = $roll;
			
			$i++;
		}
		
		return $rolls;
	}
	
	private function _stripNbsp($str) {
		return str_replace('&nbsp;', '', $str);
	}
	
	private function _getIdForRollId($zoneName, $rollId) {
		$rolls = $this->getRolls($zoneName);
		
		foreach ($rolls as $roll) {
			if ($roll->rollId == $rollId) {
				return $roll->id;
			}
		}
		
		return null;
	}
	
	public function obtainVoucherRollCsv($zoneName, $rollId) {
		$this->_requireLogin();
		
		$id = $this->_getIdForRollId($zoneName, $rollId);
		
		if ($id === null) {
			throw new pfSense_Voucher_Exception('Roll not found!');
		}
		
		$csvUrl = $this->_getDownloadRollCsvUrl($zoneName, $id);
		
		$c = $this->_getCurlGetHandle();
		
		curl_setopt($c, CURLOPT_URL, $csvUrl);
		
		$text = curl_exec($c);
		$this->_debugRequest($csvUrl, $text, null, true);
		
		if ($text === false) {
			$err = curl_error($c);
			throw new pfSense_Voucher_Exception('Failed to obtain CSRF token: HTTP error: ' . $err);
		}
		
		return $text;
	}
}

class pfSense_Voucher_Exception extends Exception {}

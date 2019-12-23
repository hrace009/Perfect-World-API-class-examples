<?php

/**
 * Session data management
 *
 * @author Nicholas R. Grant
 * @version 1.0 rev 0
 * @copyright NRGsoft (c) 2003-2012
 */
class MonkeySession
{
	/**
	 * Instance of MonkeySession
	 *
	 * @var MonkeySession
	 */
	private static $Instance;
	
	
	public $sessionName = 'MonkeySession';
	public $usingCloudflare = false;
	const MSG_COLLISION_ERROR = 'Error: Something went very wrong!';
	const VAR_FINGERPRINT = 'fingerprint';
	
	/**
	 * Returns new MonkeySession object
	 * @param string $sessionName [optional]<br>
	 * 			Session name refers to the name of the session in cookies<br><br>
	 * 			If session name is left empty, a default session name will be used
	 * @return MonkeySession
	 */
	function __construct( $sessionName = '', $usingCloudflare = false )
	{
		if ( $sessionName !== '' )
			$this->sessionName = $sessionName;
		
		$this->usingCloudflare = $usingCloudflare;
	}
	
	/**
	 * Returns a singleton instance of MonkeySession
	 * 
	 * @param string $sessionName [optional]<br>
	 * 			Session name refers to the name of the session in cookies<br><br>
	 * 			If session name is left empty, a default session name will be used
	 * @param bool $usingCloudflare [optional]<br>
	 * 			When getting a new session, whether or not Cloudflare mode should be used.
	 * @return MonkeySession
	 */
	public static function Instance( $sessionName = '', $usingCloudflare = false )
	{
		if ( !self::$Instance )
		{
			self::$Instance = new MonkeySession( $sessionName, $usingCloudflare );
			self::$Instance->Start();
		}
		return self::$Instance;
	}
	
	/**
	 * Attempt to start user session (cookie blocks will prevent this from starting)
	 */
	private function Start()
	{
		$session_length =  267840; // 60 * 24 * 31 * 6
		$cookie_length = 267840; // 60 * 24 * 31 * 6 // Half a year
		
		session_cache_expire($session_length);
		session_set_cookie_params(time() + $cookie_length);
		session_name($this->sessionName);
		session_start();
		
		if ( !$this->hasValidFingerprint() )
		{
			session_destroy();
			session_regenerate_id();
			unset($_SESSION);
			session_start();
			if ( !$this->hasValidFingerprint() )
				die(self::MSG_COLLISION_ERROR); // Session collision, unlikely error (after fix)
		}
	}
	
	/**
	 * Gracefully close session writing handles
	 */
	public function Close()
	{
		session_write_close();
	}
	
	/**
	 * @desc Set the value of a session variable.<br><br>
	 * <b>NOTE:</b> There is mild session data hiding for shared hosts. Beware using
	 * this on anything but a dedicated host. As the data could be unhidden
	 * fairly easy by another user.
	 *
	 * @param string $variable <br>
	 * 			Variable is the unique name or key by which a value is found and accessed
	 * @param mixed $value <br>
	 * 			Value is the value that can be accessed by the variable name
	 */
	public function SetVariable( $variable, $value )
	{
		$_SESSION[$variable] = base64_encode(str_rot13(base64_encode($value)));
	}
	
	/**
	 * @desc Get the value of a session variable.<br><br>
	 * <b>NOTE:</b> There is mild session data hiding for shared hosts. Beware using
	 * this on anything but a dedicated host. As the data could be unhidden
	 * fairly easy by another user.
	 *
	 * @param string $variable
	 * 			Variable is the unique name or key by which a value is found and accessed
	 * @return mixed
	 */
	public function GetVariable( $variable )
	{
		if ( isset($_SESSION[$variable]) )
			return base64_decode(str_rot13(base64_decode($_SESSION[$variable])));
		return '';
	}
	
	/**
	 * Return true if session variable exists otherwise false
	 * 
	 * @param string $variable
	 * 			Variable is the unique name or key by which a value is found and accessed
	 * @return bool
	 */
	public function HasVariable( $variable )
	{
		return isset($_SESSION[$variable]);
	}
	
	/**
	 * Delete a session variable
	 *
	 * @param string $variable
	 * 			Variable is the unique name or key by which a value is found and accessed
	 */
	public function DeleteVariable( $variable )
	{
		if ( isset($_SESSION[$variable]) )
			unset($_SESSION[$variable]);
	}
	
	/**
	 * Verify session fingerprint against user fingerprint. Returns true if
	 * fingerprint is the same. Returns false if fingerprint mismatch.
	 *
	 * @return boolean
	 */
	private function hasValidFingerprint()
	{
		if ( !isset($_SESSION[self::VAR_FINGERPRINT]) )
		{
			$this->setFingerprint();
			return true;
		}
		return $this->getFingerprint() === $this->generateFingerprint();
	}
	
	/**
	 * Set session fingerprint
	 *
	 * @return void
	 */
	private function setFingerprint()
	{
		$this->SetVariable(self::VAR_FINGERPRINT, $this->generateFingerprint());
	}
	
	/**
	 * Return session fingerprint
	 *
	 * @return string
	 */
	private function getFingerprint()
	{
		return $this->GetVariable(self::VAR_FINGERPRINT);
	}
	
	/**
	 * Returns current user fingerprint (Not session fingerprint)
	 *
	 * @return string
	 */
	private function generateFingerprint()
	{
		if ($this->usingCloudflare)
			return md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['HTTP_X_FORWARDED_FOR']);
		return md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
	}
}


/**
 * Start a Monkey session
 * 
 * @param string $sessionName
 * @return MonkeySession
 */
function StartSession( $sessionName = '', $usingCloudflare = false )
{
	return MonkeySession::Instance($sessionName, $usingCloudflare);
}



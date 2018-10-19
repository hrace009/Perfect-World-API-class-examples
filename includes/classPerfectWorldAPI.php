<?php
if (!defined('ROOT'))
    exit(0);
require_once(ROOT . 'libraries/Monkey.php');

define('FILTER_MODE_NORMAL', 0);
define('FILTER_MODE_APPEND', 1);

/**
 * Returns a string that only contains the characters specified in $filter or the default filter
 *
 * @param string $text
 * @param string $filter
 * @return string
 */
function FilterText($text, $filter = '', $mode = FILTER_MODE_NORMAL)
{
    // Setup filter
    $defaultFilter = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-';

    if ($filter) {
        if ($mode === FILTER_MODE_APPEND) {
            $filter .= $defaultFilter;
        }
    } else {
        $filter = $defaultFilter;
    }

    $validChars = preg_split('//', $filter, -1, PREG_SPLIT_NO_EMPTY);
    $valid = false;
    $filteredText = '';

    $textlen = strlen($text);
    $filterCount = count($validChars);

    for ($index = 0; $index < $textlen; $index++) {
        $char = substr($text, $index, 1);
        for ($validCharIndex = 0; $validCharIndex < $filterCount; $validCharIndex++) {
            if ($char === $validChars[$validCharIndex]) {
                $valid = true;
                break;
            }
        }
        if ($valid) {
            $filteredText .= $char;
            $valid = false;
        }
    }

    return $filteredText;
}


/**
 * Perfect World v1.2.6 php API
 *
 * Extra credits to das7002, tbnanubis, renan7899, twiggy3452, Drakaer, Ronny
 *
 * @author Nicholas R. Grant a.k.a. "Goodlookinguy"
 * @version 2.4, 2/17/2012
 * @copyright NRGsoft (c) 2003-2012
 */
final class PerfectWorldAPI
{
    const DATABASE_HOST = 'localhost'; // Database Host Address
    const DATABASE_USERNAME = 'root'; // Database Username
    const DATABASE_PASSWORD = ''; // Database Password
    const DATABASE_NAME = ''; // Database Name

    const SESSION_NAME = 'PerfectWorldAPI'; // User Session Name (Don't use the default, please...)

    const SESSION_FINGERPRINT = 'pwFingerprint'; // User Fingerprint (Session Hijacking Preventer)
    const SESSION_USER_ID = 'pwUserID'; // User ID (PW UID)
    const SESSION_CHAR_ID = 'pwCharID'; // Character ID
    const SESSION_IS_GM = 'pwGM'; // GM Status (True/False) | Determined on login

    /*********************************************************/
    // On program termination messages
    const MSG_MALFORMED_QUERY = 'Error: A malformed query was executed, program has been terminated.';
    const MSG_SESSION_COLLISION = 'Error: Something went very wrong! Blame the php developers!';

    /*********************************************************/
    const GENDER_MALE = 0;

    // These can be used to store other user's data as well, if let's say perhaps
    // you wanted to make a forum and needed to gather other user's data.
    const GENDER_FEMALE = 1; // User Data
    /**
     * Singleton instance of PerfectWorldAPI
     *
     * @var PerfectWorldAPI
     */
    public static $Instance; // Character Data
    /**
     * Instance of MonkeyDatabase
     *
     * @var MonkeyDatabase
     */
    private $_database;
    private $_userData = array();
    private $_charData = array();

    /**
     * Class constructor
     *
     * @return void
     */
    function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialization components run here
     *
     * @return void
     */
    private function initialize()
    {
        $this->_database = MonkeyDatabase::Instance(self::DATABASE_HOST, self::DATABASE_USERNAME,
            self::DATABASE_PASSWORD, self::DATABASE_NAME);
        $this->startSession();
    }

    /**
     * Attempt to start user session (cookie blocks will prevent this from starting)
     *
     * @bug A bug regarding the session not being properly destroyed has been fixed
     * @return void
     */
    private function startSession()
    {
        $session_length = 267840; // 60 * 24 * 31 * 6
        $cookie_length = 267840; // 60 * 24 * 31 * 6 // Half a year

        session_cache_expire($session_length);
        session_set_cookie_params(time() + $cookie_length);
        session_name(self::SESSION_NAME);
        session_start();

        if (!$this->verifyFingerprint()) {
            session_destroy();
            session_regenerate_id();
            unset($_SESSION);
            session_start();
            if (!$this->verifyFingerprint())
                die(self::MSG_SESSION_COLLISION); // Session collision, unlikely error (after fix)
        }
    }

    /**
     * Verify session fingerprint against user fingerprint. Returns true if
     * fingerprint is the same. Returns false if fingerprint mismatch.
     *
     * @return boolean
     */
    private function verifyFingerprint()
    {
        if (!isset($_SESSION[self::SESSION_FINGERPRINT])) {
            $this->setFingerprint();
            return true;
        }
        return ($this->getFingerprint() === $this->getUserFingerprint());
    }

    /**
     * Set session fingerprint
     *
     * @return void
     */
    private function setFingerprint()
    {
        $this->setSessionVariable(self::SESSION_FINGERPRINT, $this->getUserFingerprint());
    }

    /**
     * Set a session variable.
     * NOTE: There is mild session data hiding for shared hosts. Beware using
     * this on anything but a dedicated host. As the data could be unhidden
     * fairly easy by another user.
     *
     * @param string $variable
     * @param mixed $value
     * @return void
     */
    private function setSessionVariable($variable, $value)
    {
        $_SESSION[$variable] = base64_encode(str_rot13(base64_encode($value)));
    }

    /**
     * Returns current user fingerprint (Not session fingerprint)
     *
     * @return string
     */
    private function getUserFingerprint()
    {
        return md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    }

    /**
     * Return session fingerprint
     *
     * @return string
     */
    private function getFingerprint()
    {
        return $this->getSessionVariable(self::SESSION_FINGERPRINT);
    }



    /* ---------------------------------------------------------------------------------------- */

    /**
     * Get a session variable.
     * NOTE: There is mild session data hiding for shared hosts. Beware using
     * this on anything but a dedicated host. As the data could be unhidden
     * fairly easy by another user.
     *
     * @param string $variable
     * @return mixed
     */
    private function getSessionVariable($variable)
    {
        if (isset($_SESSION[$variable]))
            return base64_decode(str_rot13(base64_decode($_SESSION[$variable])));
        return '';
    }

    /**
     * Returns singleton instance of class
     *
     * @return PerfectWorldAPI
     */
    public static function Instance()
    {
        if (!self::$Instance)
            self::$Instance = new PerfectWorldAPI();
        return self::$Instance;
    }

    /**
     * Print session debug information
     *
     * @return void
     */
    public function PrintSessionDebugData()
    {
        $sdata = $_SESSION;
        foreach ($sdata as $key => &$data) {
            $data = $this->getSessionVariable($key);
        }
        echo '<pre>' . print_r($sdata, true) . '</pre>';
    }

    /**
     * Add user to Perfect World database
     *
     * @version 2/13/2012, Severe Bug Fix (...)
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $question
     * @param string $answer
     * @param string $realName
     * @param int $gender
     * @param string $number
     * @param string $country
     * @param string $city
     * @param string $address
     * @param string $postalcode
     * @param int $birthday
     */
    public function AddUser($username, $password, $email, $question = '', $answer = '', $realName = '', $gender = 0,
                            $number = '0', $country = '0', $city = '0', $address = '0', $postalcode = '0',
                            $birthday = 0)
    {
        // Validation Checking
        if (!$this->UsernameIsValid($username) || $this->UsernameExists($username))
            return false;
        if (!$this->EmailIsValid($email) || $this->EmailExists($email))
            return false;

        //
        $password = "0x" . md5(strtolower($username) . $password);

        $sql = "CALL adduser('{$username}', {$password}, '{$question}', '{$answer}', '{$realName}', '0', '{$email}'," .
            " '{$number}', '{$country}', '{$city}', '{$number}', '{$address}', '{$postalcode}', '{$gender}', " .
            "'{$birthday}', '', {$password});";;
        return (boolean)$this->_database->Query($sql, false);
    }

    /**
     * Checks if username is valid.
     *
     * @param string $username
     * @return boolean
     */
    public function UsernameIsValid($username)
    {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $filteredUsername = substr(FilterText($username, $validChars), 0, 32);

        return ($filteredUsername === $username);
    }

    /**
     * Check if username already exists in database.
     *
     * @param string $username
     * @return boolean
     */
    public function UsernameExists($username)
    {
        if ($this->UsernameIsValid($username)) {
            $sql = "SELECT name FROM users WHERE name = '{$username}'";
            if ($this->_database->Query($sql, false)) {
                return ($this->_database->GetRowCount() > 0);
            }
        }
        return false;
    }

    /**
     * Checks if the input e-mail is valid.
     *
     * @version 2/13/2012, Bug Fix
     * @param string $email
     * @return boolean
     */
    public function EmailIsValid($email)
    {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.@';
        $filteredEmail = FilterText($email, $validChars);

        if (preg_match('%\A[A-Za-z0-9._-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}\z%', $filteredEmail))
            return ($email === $filteredEmail);
        return false;
    }

    /**
     * Check if e-mail exists.
     *
     * @version 2/13/2012
     * @param string $email
     * @return boolean
     */
    public function EmailExists($email)
    {
        if ($this->EmailIsValid($email)) {
            $sql = "SELECT email FROM users WHERE email = '{$email}';";
            if ($this->_database->Query($sql, false)) {
                return ($this->_database->GetRowCount() > 0);
            }
        }
        return false;
    }

    /**
     * Sets a password for an account. This can be useful for password resets. If you have them input an old
     * password, be sure to check that the old password length isn't 0. Otherwise they'll be able to overwrite
     * the password without inputting an old password. This isn't a bug in the API, it's by design.
     *
     * @param string $username
     * @param string $password
     * @param string $oldPassword
     * @return boolean
     */
    public function SetPassword($username = '', $password, $oldPassword = '')
    {
        if (empty($username)) {
            $username = $this->GetUsername();
        }
        if ($this->UsernameExists($username)) {
            $password = md5(strtolower($username) . $password);

            if (!empty($oldPassword)) {
                $sql = "SELECT name, passwd FROM users WHERE name = '{$username}'";
                $oldPassword = md5(strtolower($username) . $oldPassword, true);

                $results = $this->_database->Query($sql);

                if ($results[0]['passwd'] !== $oldPassword)
                    return false;
            }
            $sql = "UPDATE users SET passwd = 0x{$password}, passwd2 = 0x{$password} WHERE name = '{$username}'";
            return (boolean)$this->_database->Query($sql, false);
        }
        return false;
    }

    /**
     * Returns logged in username. If user is not logged in, empty string is returned.
     *
     * @return string
     */
    public function GetUsername($userID = 0)
    {
        $data = $this->GetUserData($userID);
        if (isset($data['name']))
            return $data['name'];
        return '';
    }

    /**
     * Get user's data based on ID or currently logged in account. If the data has already been
     * accessed, it will return that unless forceUpdate is set to true. If empty userID and user
     * is not logged in, function returns false.
     *
     * @param int $userID
     * @return mixed
     */
    public function GetUserData($userID = 0, $forceUpdate = false)
    {
        $userID = intval($userID);

        if (!isset($this->_userData[$userID]) || $forceUdpate) {
            if (empty($userID)) {
                if ($this->IsLoggedIn()) {
                    $userID = $this->GetUserID();
                } else {
                    return false;
                }
            }

            $sql = "SELECT * FROM users WHERE ID = '{$userID}';";
            $results = $this->_database->Query($sql);

            $this->_userData[$userID] = $results[0];
        }

        return $this->_userData[$userID];
    }

    /**
     * Returns true or false on whether or not the user is logged in.
     *
     * @return boolean
     */
    public function IsLoggedIn()
    {
        return (boolean)$this->getSessionVariable(self::SESSION_USER_ID);
    }

    /**
     * Get logged in user ID
     *
     * @return int
     */
    public function GetUserID()
    {
        return intval($this->getSessionVariable(self::SESSION_USER_ID));
    }

    /**
     * Alias of AddGM
     *
     * @see PerfectWorldAPI::AddGM()
     * @param string $charname
     * @return boolean
     */
    public function AddGMByCharName($charname)
    {
        return $this->AddGM($charname);
    }

    /**
     * Gives a user GM permissions based on roles.role_name (not users.name).
     * Returns true or false on whether or not query succeeded.
     *
     * @version 2/13/2012, Fixed
     * @param string $charname
     * @return boolean
     */
    public function AddGM($charname)
    {
        $charname = FilterText($charname);
        //if ( $this->UsernameIsValid($charname) && $this->UsernameExists($charname) )
        //{
        $sql = "SET @accountID = (SELECT account_id FROM roles WHERE role_name = '{$charname}');" .
            " CALL addGM(@accountID, 1);"; // MMMMMMMMMMmmmmmmm
        return (boolean)$this->_database->Query($sql, false);

        //}
        //return false;
    }

    /**
     * Gives a user GM permissions based on user ID
     * Returns true or false on whether or not query succeeded.
     *
     * @param string $userID
     * @return boolean
     */
    public function AddGMByID($userID)
    {
        $userID = intval($userID);
        $sql = "CALL addGM('{$userID}', 1);";
        return (boolean)$this->_database->Query($sql, false);
    }

    /**
     * Gives a user GM permissions based on user ID
     * Returns true or false on whether or not query succeeded.
     *
     * @param string $username
     * @return boolean
     */
    public function AddGMByUsername($username)
    {
        $username = FilterText($username);
        if ($this->UsernameIsValid($username) && $this->UsernameExists($username)) {
            $sql = "SET @accountID = (SELECT ID FROM users WHERE name = '{$username}';" .
                " CALL addGM(@accountID, 1);";
            return (boolean)$this->_database->Query($sql, false);
        }
        return false;
    }

    /**
     * Login to an account. E-Mail parameter is optional. If you plan to use the e-mail parameter,
     * make sure to check if the e-mail length is greater than 0, otherwise it will bypass the e-mail
     * check and allow the user in with just username and password.
     *
     * @param string $username
     * @param string $password
     * @param string $email
     */
    public function Login($username, $password, $email = '')
    {
        // Validation Checking
        if (!$this->UsernameExists($username))
            return false;
        if ($email)
            if (!$this->EmailExists($email))
                return false;

        // Prepare value for query
        $password = md5(strtolower($username) . $password, true);

        $sql = "SELECT users.ID, users.passwd, users.email, auth.userid FROM users LEFT JOIN auth ON " .
            "users.ID = auth.userid WHERE name = '{$username}' LIMIT 0, 1;";

        $results = $this->_database->Query($sql);
        $results = $results[0];

        if ($results['passwd'] === $password) {
            if ($email) {
                if ($email === $results['email']) {
                    $this->SetLogin($results['ID'], $results['userid']);

                    return true;
                }
                return false;
            }
            $this->SetLogin($results['ID'], $results['userid']);

            return true;
        }
        return false;
    }

    /**
     * Set login for user. Do NOT use this to log into account.
     *
     * @param string $userID
     * @param boolean $isGM
     * @return void
     */
    public function SetLogin($userID, $isGM = false)
    {
        $this->setSessionVariable(self::SESSION_USER_ID, $userID);
        $this->setSessionVariable(self::SESSION_IS_GM, (boolean)$isGM);
    }

    /**
     * Login to an account by e-mail address.
     *
     * @version 2/16/2012, Query Bug Fix + Bug Fix
     * @param string $email
     * @param string $password
     */
    public function LoginByEmail($email, $password)
    {
        if (!$this->EmailExists($email))
            return false;

        $sql = "SELECT users.ID, users.name, users.passwd, users.email, auth.userid FROM users LEFT JOIN auth ON " .
            "users.ID = auth.userid WHERE email = '{$email}' LIMIT 0, 1;";

        $results = $this->_database->Query($sql);
        $results = $results[0];

        $password = md5(strtolower($results['name']) . $password);

        if ($results['passwd'] === $password) {
            $this->SetLogin($results['ID'], $results['userid']);
            return true;
        }
        return false;
    }

    /**
     * Returns true or false on whether or not the user is a GM
     *
     * @return boolean
     */
    public function IsGM()
    {
        return (boolean)$this->getSessionVariable(self::SESSION_IS_GM);
    }

    /**
     * Logs out the character by destorying the session variables. The user remains in the
     * same session until they either delete the cookie, restart their browser, or change
     * their session ID.
     *
     * @return boolean
     */
    public function Logout()
    {
        unset($_SESSION[self::SESSION_USER_ID]);
        unset($_SESSION[self::SESSION_IS_GM]);
        unset($_SESSION[self::SESSION_CHAR_ID]);

        return true;
    }

    /**
     * Set character ID for account. If set character fails, function will return false.
     * If the function succeeds, it will return true and the character ID will be set.
     *
     * @param int $charID
     * @return boolean
     */
    public function SetCharacter($charID)
    {
        if ($this->IsLoggedIn()) {
            $charID = intval($charID);
            $userID = $this->GetUserID();

            $sql = "SELECT roles.role_id FROM roles WHERE account_id = '{$userID}' AND role_id = '{$charID}';";
            $this->_database->Query($sql);
            if ($this->_database->GetRowCount() > 0) {
                $this->setSessionVariable(self::SESSION_CHAR_ID, $charID);
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Fill all character data from given user ID. This will not check if data
     * is stored already or not.
     *
     * @param int $userID
     * @return boolean
     */
    public function FillUserCharData($userID = 0)
    {
        $userID = intval($userID);

        if (empty($userID)) {
            if ($this->IsLoggedIn()) {
                $userID = $this->GetUserID();
            } else {
                return false;
            }
        }

        $sql = "SELECT * FROM roles WHERE account_id = '{$userID}';";
        $results = $this->_database->Query($sql);

        foreach ($results as $row => $columns) {
            $this->_charData[$columns['role_id']] = $columns;
        }

        return true;
    }

    /**
     * Returns an array of characters from specified user ID. If no ID is put, function
     * grabs characters from currently logged in account. If empty userID and user is
     * not logged in, function returns false.
     *
     * @param int $userID
     * @return mixed
     */
    public function GetUserCharIDs($userID = 0)
    {
        $userID = intval($userID);

        if (empty($userID)) {
            if ($this->IsLoggedIn()) {
                $userID = $this->GetUserID();
            } else {
                return false;
            }
        }

        $sql = "SELECT role_id FROM roles WHERE account_id = '{$userID}';";
        $results = $this->_database->Query($sql);

        return $results[0];
    }

    /**
     * Returns an array map of GMs.
     * Key 'GM Name' returns GM's Name
     * Key 'Online' returns true or false to online status
     *
     * @return mixed array
     */
    public function GetGMList()
    {
        $ret = array();

        $sql = "SELECT point.zoneid, roles.role_name FROM point INNER JOIN roles ON point.uid = roles.role_id WHERE point.uid IN " .
            "(SELECT DISTINCT auth.userid FROM auth)";
        $results = $this->_database->Query($sql);

        foreach ($results as $row => $columns) {
            $ret[] = array(
                'GM Name' => $columns['role_name'],
                'Online' => (boolean)$columns['zoneid']
            );
        }

        return $ret;
    }

    /**
     * Returns an array of online user's names and IDs
     *
     * @version 2/22/2012, Deprecated
     * @deprecated
     * @return mixed array
     */
    public function GetOnlineCharList()
    {
        return array();
    }

    /**
     * Returns an array of online user's names and IDs
     *
     * @see PerfectWorldAPI::GetOnlineCharList()
     * @deprecated
     * @return mixed array
     */
    public function GetOnlineCharacterList()
    {
        return array();
    }

    /**
     * Get an array of all character's names and IDs (This can return a massive amount of data)
     *
     * @version 2/16/2012, Added $page and $limit parameters
     * @param int $limit
     * @param int $page
     * @return mixed array
     */
    public function GetCharList($limit = 0, $page = 0)
    {
        $sql = "SELECT roles.role_name, roles.role_id FROM roles";
        if ($limit) {
            $limit = intval($limit);
            $page = intval($page);
            if (!$page)
                $page = 1;

            $startPage = ($page - 1) * $limit;
            $endPage = $page * $limit;

            $sql .= " LIMIT {$startPage}, {$endPage}";
        }

        return $this->_database->Query($sql);
    }

    /**
     * Returns currently selected character's name. If no character is selected, empty
     * string is returned.
     *
     * @example
     * @return string
     */
    public function GetCharacterName($charID = 0)
    {
        $data = $this->GetCharData($charID);
        if (isset($data['role_name']))
            return $data['role_name'];
        return '';
    }

    /**
     * Get character's data based on ID or currently logged in account character. If the data
     * has already been accessed, it will return that unless forceUpdate is set to true. If
     * empty userID and user is not logged in, function returns false.
     *
     * @param int $charID
     * @return mixed
     */
    public function GetCharData($charID = 0, $forceUpdate = false)
    {
        $charID = intval($charID);

        if (!isset($this->_charData[$charID]) || $forceUpdate) {
            if (empty($charID)) {
                if ($this->GetCharID()) {
                    $charID = $this->GetCharID();
                } else {
                    return false;
                }
            }

            $sql = "SELECT * FROM roles WHERE role_id = '{$charID}';";
            $results = $this->_database->Query($sql);

            $this->_charData[$charID] = $results[0];
        }

        return $this->_charData[$charID];
    }

    /**
     * Get character ID if character is set. 0 means that no character is set.
     *
     * @return int
     */
    public function GetCharID()
    {
        return intval($this->getSessionVariable(self::SESSION_CHAR_ID));
    }
}

if (!isset($pwapi))
    $pwapi = PerfectWorldAPI::Instance();

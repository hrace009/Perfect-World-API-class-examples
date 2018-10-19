<?php
/* -------------------------------------------------- */
if (!defined('ROOT'))
    define('ROOT', '/var/www/');
/* -------------------------------------------------- */
require_once(ROOT . 'libraries/Monkey.php');
require_once(ROOT . 'includes/classPerfectWorldAPI.php');

// This is a super simple example. Please do not use this example exactly. :{

if ($_SERVER['QUERY_STRING'] == 'login') {
    if (!$pwapi->IsLoggedIn()) {
        if (!$pwapi->Login($_POST['uname'], $_POST['passwd'])) {
            echo 'You failed to login!';
        }
    }
}

if ($_SERVER['QUERY_STRING'] == 'logout') {
    $pwapi->Logout();
}

if (!$pwapi->IsLoggedIn()) {
    ?>

    <form action="?login" method="post">
        Username: <input type="text" maxlength="32" name="uname"/><br/>
        Password: <input type="password" name="passwd"/><br/>
        <input type="submit"/>
    </form>

    <?php
} else {

    echo 'Welcome back, ' . MonkeyFilter::Instance()->FilterText($pwapi->GetUsername()) . '!<br /><br />';
    ?>
    <a href="?logout">Logout of account</a>

    <?php
}

?>
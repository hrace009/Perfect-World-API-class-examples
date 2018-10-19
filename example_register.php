<?php
/* -------------------------------------------------- */
if (!defined('ROOT'))
    define('ROOT', '/var/www/');
/* -------------------------------------------------- */
require_once(ROOT . 'libraries/Monkey.php');
require_once(ROOT . 'includes/classPerfectWorldAPI.php');

// This is a super simple example. Please do not use this example exactly. :{

if ($_SERVER['QUERY_STRING'] == 'register') {
    if (!$pwapi->IsLoggedIn()) {
        if ($_POST['passwd'] === $_POST['passwd2']) {
            if ($pwapi->AddUser($_POST['uname'], $_POST['passwd'], $_POST['email'])) {
                echo 'Thank you for registering!';
            } else {
                echo 'There was an error registering account. Try shortening your username.';
            }
        } else {
            echo "Password Mismatch!<br />";
        }
    }
}

if ($_SERVER['QUERY_STRING'] == 'logout') {
    $pwapi->Logout();
}

if (!$pwapi->IsLoggedIn()) {
    ?>

    <form action="?register" method="post">
        Username: <input type="text" name="uname" maxlength="32"
                         value="<?php echo MonkeyFilter::Instance()->FilterText($_POST['uname']) ?>"/><br/>
        Password: <input type="password" name="passwd"/><br/>
        Confirm Password: <input type="password" name="passwd2"/><br/>
        E-Mail: <input type="text" name="email"
                       value="<?php echo MonkeyFilter::Instance()->FilterText($_POST['email']) ?>"/><br/>
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
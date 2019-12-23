<?php
/* -------------------------------------------------- */
if ( !defined('ROOT') )
	define('ROOT', __DIR__);
/* -------------------------------------------------- */
define('T', true);
define('F', false);

$folder = ROOT . "/Monkey";

require_once( $folder . '/MonkeyList.php' );
require_once( $folder . '/MonkeyMap.php' );
require_once( $folder . '/MonkeyStack.php' );
require_once( $folder . '/MonkeySession.php' );
require_once( $folder . '/MonkeyMarkdown.php' );
require_once( $folder . '/MonkeyDatabase.php' );
require_once( $folder . '/MonkeyFilter.php' );

unset( $folder );

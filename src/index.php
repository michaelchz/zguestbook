<?php

session_start();

define('BASEDIR', './');

define('LIBPATH', './bin/');
define('ETCPATH', './etc/');

function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
} 

function getexecutiontime(){
	global $time_start;
	return getmicrotime() - $time_start;
}

$time_start = getmicrotime();
    
if (isset($_GET['lang'])){
	$_SESSION['lang'] = $_GET['lang'];
	setcookie('lang', $_GET['lang'], time()+60*60*24*90);
} elseif (isset($_COOKIE['lang'])) {
	$_SESSION['lang'] = $_COOKIE['lang'];
}

$lang = $_SESSION['lang'];

switch ($_GET['op'] ?? '') {
case 'list':
	include(ETCPATH.'list.php');
	break;
case 'admin':
	include(ETCPATH.'admin.php');
	break;
case 'reg':
	include(ETCPATH.'reg.php');
	include(ETCPATH.'stat_reg.php');
	break;
case 'stat':
	include(ETCPATH.'stat_reg.php');
	break;
case 'regedit':
	include(ETCPATH.'regedit.php');
	break;
default:
	if (strpos($lang, 'cn') === false) {
		readfile('index_en.htm');
	} else {
		readfile('index_cn.htm');
	}
}

?>
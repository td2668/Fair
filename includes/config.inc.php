<?php

// php configuration
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

ini_set('zend.ze1_compatibility_mode', 'Off');
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.auto_start', 0);
date_default_timezone_set('America/Edmonton');

// Include the autoloader
define('ROOT_PATH', realpath(__DIR__ . '/../'));
define('VENDOR_PATH', ROOT_PATH . '/vendor');
define('PUBLIC_PATH', ROOT_PATH);
//echo(VENDOR_PATH);
require_once VENDOR_PATH . '/autoload.php';

// Load the config
$config = \Symfony\Component\Yaml\Yaml::parse(ROOT_PATH . '/app/config.yml');



// Load the custom (server specific) config if it exists
// Non-local servers (test, prep, production) will have a custom config
$customConfigPath = ROOT_PATH . '/app/config.custom.yml';
if (file_exists($customConfigPath)) {
    $customConfig = \Symfony\Component\Yaml\Yaml::parse($customConfigPath);
    $config = array_replace_recursive($config, $customConfig);
}


// Debug
if ($config['app']['debug']) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
//error_reporting(E_ALL);


// Setup logging
$log = new \Monolog\Logger($config['app']['log_name']);
$log->pushHandler(new \Monolog\Handler\StreamHandler(ROOT_PATH . '/' . $config['app']['log_path'] . '/' . $config['app']['log_name'] . '.log', $config['app']['log_level']));

// Setup the include paths
set_include_path(implode(PATH_SEPARATOR, array(
    VENDOR_PATH . '/conservatory/',
    get_include_path(),
)));



// Session
$session = new \Symfony\Component\HttpFoundation\Session\Session();

$session->start();



if (isset($_GET['session_id'])) {
    // Enable cross-site session sharing
    $session->setId($_GET['session_id']);
}



// Database
require_once('adodb5/adodb.inc.php');


$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$db = ADONewConnection('mysql');

$db->Connect(
    $config['database']['hostname'],
    $config['database']['username'],
    $config['database']['password'],
    $config['database']['database']
);
mysql_query("SET NAMES utf8");


// Templating
$loader = new Twig_Loader_Filesystem(realpath(__DIR__  . '/../html'));
$twig = new Twig_Environment($loader);


// Authentication
$ldap = new \MRU\Ldap(
    $config['auth']['ldap']['host'],
    $config['auth']['ldap']['port'],
    $config['auth']['ldap']['dn'],
    $config['auth']['ldap']['password'],
    $config['auth']['ldap']['search_dn']
);


// PDF
define ("MRUPDF_H1_FONT_FACE", 'trebuc');
define ("MRUPDF_H1_FONT_SIZE", 12);
define ("MRUPDF_H2_FONT_FACE", 'trebuc');
define ("MRUPDF_H2_FONT_SIZE", 11);
define ("MRUPDF_H3_FONT_FACE", 'trebuc');
define ("MRUPDF_H3_FONT_SIZE", 10);
define ("MRUPDF_H4_FONT_FACE", 'trebuc');
define ("MRUPDF_H4_FONT_SIZE", 12);
define ("MRUPDF_REGULAR_FONT_FACE", 'times');
define ("MRUPDF_REGULAR_FONT_SIZE", 10);
define ("MRUPDF_SMALLER_FONT_FACE", 'times');
define ("MRUPDF_SMALLER_FONT_SIZE", 9);
define ('K_PATH_IMAGES', ROOT_PATH . '/images/');
define ('PDF_AUTHOR', 'MRU TCPDF');
define ('PDF_CREATOR', 'MRU TCPDF');
define ('PDF_FONT_NAME_MAIN', 'coprg');
define ('PDF_FONT_SIZE_MAIN', 12);
define ('PDF_HEADER_LOGO', 'mount-royal-logo-227x80.png');
define ('PDF_HEADER_LOGO_WIDTH', 30);
define ('PDF_HEADER_STRING', "");
define ('PDF_HEADER_TITLE', '');
define ('PDF_IMAGE_SCALE_RATIO', 4);
define ('PDF_MARGIN_FOOTER', 20);
define ('PDF_MARGIN_HEADER', 10);
define ('PDF_MARGIN_TOP', 35);

// Include the global functions
include __DIR__ . "/global.inc.php";


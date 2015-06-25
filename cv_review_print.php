<?php

require_once('includes/config.inc.php');
require_once('includes/pdf.php');

global $session;

if (!$session->has('user')) {
    throwAccessDenied();
}

$generateWhat = (isset($_REQUEST["generate"])) ? CleanString($_REQUEST["generate"]) : '';
$style = (isset($_REQUEST["style"])) ? CleanString($_REQUEST["style"]) : '';
$userId = $session->get('user')->get('id');

if (strtolower($generateWhat) == 'caqc') {
    GenerateCAQC($userId);
} else {
    GenerateCV($userId, $generateWhat, $style);
}

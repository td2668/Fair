<?php
require_once('includes/config.inc.php');
global $session;

if (!$session->has('user')) {
    header("Location: /login.php");
    exit();
}

$vars = getPageVariables('index');
echo $twig->render('index.twig', $vars);

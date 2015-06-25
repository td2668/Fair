<?php
require_once('includes/config.inc.php');

global $session, $twig;

$status = sessionProcessForm();
if (isset($_POST['action']) && $session->has('user')) {
    if ($session->get('user')->get('is_new_visit')) {
        $redirectUrl = '/content.php?page=new_visit';
    } else if (isSiteUpdated()) {
        $redirectUrl = '/content.php?page=site_updates';
    } else {
        $redirectUrl = '/aboutme.php';
    }

    header('Location: ' . $redirectUrl);
    die();
}

if ($session->has('user')) {
    header('Location: /aboutme.php');
    die();
}

$vars = getPageVariables('login');
if ($status) {
    $vars['header']['status_messages'][] = $status;
}

echo $twig->render('login.twig', $vars);

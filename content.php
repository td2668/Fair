<?php
require_once('includes/config.inc.php');
global $twig, $session;

if (!$session->has('user')) {
    throwAccessDenied();
}

try {
    $pageName = preg_replace('/[^a-z0-9_]/', '', isset($_GET['page']) ? $_GET['page'] : null);
    $vars = getPageVariables($pageName);

    // For site_updates.twig
    $vars['is_site_updated'] = isSiteUpdated();

    echo $twig->render($pageName . '.twig', $vars);
} catch(Exception $e) {
    throwError("Not Found", "<h1>Page Not Found</h1> The page you have requested could not be found.");
}


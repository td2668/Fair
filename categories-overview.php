<?php
require_once('includes/config.inc.php');

global $session;
if (!$session->has('user')) {
    throwAccessDenied();
}

$sql = "SELECT headings.heading_name,
          (SELECT GROUP_CONCAT('<b>', type.type_name, '</b> - ', type.help_text SEPARATOR '<br/>')
           FROM cas_types AS type
           WHERE type.cas_heading_id = headings.cas_heading_id
           ORDER BY type.order)
          AS title
        FROM cas_headings AS headings
        ORDER BY headings.order";
$cvCategories = $db->getAll($sql);
global $twig;
$vars = getPageVariables("categories-overview");
$vars['cv_header_list'] = $cvCategories;
echo $twig->render('categories-overview.twig', $vars);

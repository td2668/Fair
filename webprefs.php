<?php

require_once('includes/config.inc.php');
require_once("includes/cv_item.inc.php");
global $session, $twig;

if (!$session->has('user')) {
    throwAccessDenied();
}

// the ID number of the popup that appears for all activities
$page = (isset($_GET["page"])) ? CleanString($_GET["page"]) : '';
$mrAction = (isset($_REQUEST["mr_action"])) ? CleanString($_REQUEST["mr_action"]) : '';
$getUserId = (isset($_GET["user_id"])) ? CleanString($_GET["user_id"]) : false;
$nextPageFlag = (isset($_REQUEST["next_flag"])) ? CleanString($_REQUEST["next_flag"]) : false;
$casHeadingId = (isset($_REQUEST["cas_heading_id"])) ? CleanString($_REQUEST["cas_heading_id"]) : false;

$userId = $session->get('user')->get('id');

switch ($mrAction) {

    case 'move':
        $numItems = sizeof($_GET['item']);
        foreach ($_GET['item'] as $item) {
            $db->Execute("UPDATE cas_cv_items SET rank = $numItems WHERE cv_item_id = $item");
            $numItems--;
        }

        header('Content-Type: application/json');
        echo '{"status": "ok"}';
        exit();


    default:
        $vars = getPageVariables('webprefs');


        $vars = PopulateList($userId, $casHeadingId, $vars);
        

        $categories = null;
        if ($casHeadingId) {
            $categories = $db->getAll("SELECT type_name from `cas_types` WHERE `cas_heading_id`='$casHeadingId' ORDER BY `order`");
            $vars['page']['categories'] = $categories;
			$vars['page']['cas_heading_id'] = $casHeadingId;
        }
        

        $templateName = 'webprefs';
        break;
}


//$pageTitle = "Categories";

//$vars['header']['title'] = $pageTitle;

// Render the template
$vars = BuildSidebarSubmenu($casHeadingId, $vars);
//echo('<pre>');
//print_r($vars);
//echo('</pre>');
echo $twig->render($templateName . '.twig', $vars);

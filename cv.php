<?php

require_once('includes/config.inc.php');
require_once("includes/cv_item.inc.php");
require_once('includes/pdf.php');
global $session, $twig;

if (!$session->has('user')) {
    throwAccessDenied();
}

// the ID number of the popup that appears for all activities
$page = (isset($_GET["page"])) ? CleanString($_GET["page"]) : '';
$mrAction = (isset($_REQUEST["mr_action"])) ? CleanString($_REQUEST["mr_action"]) : '';
$cvItemId = (isset($_GET["cv_item_id"])) ? CleanString($_GET["cv_item_id"]) : false;
$reportId = (isset($_GET["report_id"])) ? CleanString($_GET["report_id"]) : false;
$getUserId = (isset($_GET["user_id"])) ? CleanString($_GET["user_id"]) : false;
$nextPageFlag = (isset($_REQUEST["next_flag"])) ? CleanString($_REQUEST["next_flag"]) : false;
$casHeadingId = (isset($_REQUEST["cas_heading_id"])) ? CleanString($_REQUEST["cas_heading_id"]) : false;

$userId = $session->get('user')->get('id');

switch ($mrAction) {
    case 'generate_help':
        GenerateHelpPdf();
        exit();
    case 'move':
        $numItems = sizeof($_GET['item']);
        foreach ($_GET['item'] as $item) {
            $db->Execute("UPDATE cas_cv_items SET rank = $numItems WHERE cv_item_id = $item");
            $numItems--;
        }

        header('Content-Type: application/json');
        echo '{"status": "ok"}';
        exit();
    case 'change_type':
        $casTypeId = (isset($_REQUEST["cas_type_id"])) ? CleanString($_REQUEST["cas_type_id"]) : '';
        $cvItemId = (isset($_REQUEST["cv_item_id"])) ? CleanString($_REQUEST["cv_item_id"]) : '';
        $sql = "UPDATE cas_cv_items SET cas_type_id={$casTypeId} WHERE cv_item_id={$cvItemId}";
        $result = $db->Execute($sql);
    case 'edit':
    case 'add':
    case 'Add an item':
        if ($mrAction == 'edit') {
            $templateName = 'cv_items_generic_form';
        } else {
            // Just display the "Select an Activity / Document" dropdown
            $templateName = 'cv_items_select_type';
        }

        $vars = getPageVariables($templateName);

        //If coming from a 'favourites' call then the heading is not defined.
        if (!$casHeadingId) {
            $casHeadingId = GetCasHeadingId($casTypeId);
        }

        $vars['cas_heading_id'] = $casHeadingId;

        mergePageVariables($vars, GenerateEditForm($cvItemId, $page, $userId, $casHeadingId));
        break;

    case 'save':
        $vars = getPageVariables('cv_items_generic_form');
        $vars['cas_heading_id'] = $casHeadingId;

        $formVars = SaveForm($cvItemId, $userId);
        if ($formVars['new_header']) {
            //heading ID will have changed, so need to reload it
            $item = GetCvItem($cvItemId, $userId);
            $sql = "SELECT cas_heading_id FROM cas_types WHERE cas_type_id=$item[cas_type_id]";
            $type = $db->getRow($sql);
            header('location:/cv.php?mr_action=edit&cas_heading_id=' . $type['cas_heading_id'] . '&cv_item_id=' . $cvItemId);
        }

        mergePageVariables($vars, $formVars);
        mergePageVariables($vars, GenerateEditForm($cvItemId, $page, $userId));

        $templateName = 'cv_items_generic_form';
        break;

    case 'Delete':
    case 'back_to_list':
    default:
        $vars = getPageVariables('cv_items_generic');

        // if we don't have a casHeadingId, then don't display the 'add item' button.
        if (!$casHeadingId) {
            $vars["page"]["add_item"] = false;
        } else {
            $vars["page"]["add_item"] = true;
        }

        if ($mrAction == 'Delete') {
            if (DeleteItem($cvItemId, $userId)) {
                $vars['header']['status_messages'][] = 'The item has been successfully deleted.';
            } else {
                $vars['header']['status_messages'][] = 'The item could not be deleted.';
            }
        }

        $vars = PopulateList($userId, $casHeadingId, $vars);

        $categories = null;
        if ($casHeadingId) {
            $categories = $db->getAll("SELECT type_name from `cas_types` WHERE `cas_heading_id`='$casHeadingId' ORDER BY `order`");
        }
        $vars['page']['categories'] = $categories;
        $vars['page']['cas_heading_id'] = $casHeadingId;

        $templateName = 'cv_items_generic';
        break;
}


if ($cvItemId) {
    $pageTitle = GetPageTitle($cvItemId);
} else if ($casHeadingId) {
    $pageTitle = $db->getOne("SELECT `short_name` FROM `cas_headings` WHERE cas_heading_id = ?", array($casHeadingId));;
} else {
    $pageTitle = "All Activities";
}
$vars['header']['title'] = $pageTitle;

// Render the template
$vars = BuildSidebarSubmenu($casHeadingId, $vars);
echo $twig->render($templateName . '.twig', $vars);

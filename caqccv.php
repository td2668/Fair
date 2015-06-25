<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/config.inc.php');
global $session;
if (!$session->has('user')) {
    throwAccessDenied();
}

$userId = $session->get('user')->get('id');
if (isset($_REQUEST['togglebanner'])) {
    $sql = "SELECT * FROM user_disable_banner WHERE user_id=$userId";
    $result = $db->getRow($sql);
    if (count($result) > 0) {
        $sql = "DELETE FROM user_disable_banner WHERE user_id=$userId";
        $result = $db->Execute($sql);
        if (!$result) {
            echo $db->ErrorMsg();
        }
    } else {

        //Create a record
        $sql = "INSERT INTO user_disable_banner SET user_id=$userId";
        $result = $db->Execute($sql);
        if (!$result) {
            echo $db->ErrorMsg();
        }
    }
}

$vars = getPageVariables('caqccv');
$result = $db->GetRow("SELECT * FROM user_disable_banner WHERE user_id=$userId");
if (count($result) == 0) {
    $vars['togglebanner'] = 'checked';
}

echo $twig->render('caqccv.twig', $vars);


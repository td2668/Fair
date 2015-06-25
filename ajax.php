<?php

require_once('includes/config.inc.php');
global $session;
if (!$session->has('user')) {
    throwAccessDenied();
}

$mrAction = (isset($_REQUEST["mr_action"])) ? CleanString($_REQUEST["mr_action"]) : '';
switch ($mrAction) {
    case "ajax_set_flag":
        $table = (isset($_REQUEST["table"])) ? CleanString($_REQUEST["table"]) : '';
        $field = (isset($_REQUEST["field"])) ? CleanString($_REQUEST["field"]) : '';
        $state = (isset($_REQUEST["state"])) ? CleanString($_REQUEST["state"]) : '';
        $key = (isset($_REQUEST["key"])) ? CleanString($_REQUEST["key"]) : '';
        $id = (isset($_REQUEST["id"])) ? CleanString($_REQUEST["id"]) : '';
        $state = ($state == 'true' ? '1' : '0');
        $sql = "UPDATE `{$table}` SET `{$field}`='{$state}' where `{$key}`='{$id}'";
        if ($db->Execute($sql)) {
            echo 1;
            exit();
        } else {
            echo "error";
            exit();
        }

        break;

    case "ajax_bulk_set_flag":
        $table = (isset($_REQUEST["table"])) ? CleanString($_REQUEST["table"]) : '';
        $field = (isset($_REQUEST["field"])) ? CleanString($_REQUEST["field"]) : '';
        $state = (isset($_REQUEST["state"])) ? CleanString($_REQUEST["state"]) : '';
        $state = ($state == 'true' ? '1' : '0');
        $cas_heading_id = (isset($_REQUEST["cas_heading_id"])) ? CleanString($_REQUEST["cas_heading_id"]) : '';
        if ($cas_heading_id == '0') {

            // heading id is set to 0 when all activities are displayed.
            $sql = "UPDATE `{$table}` SET `{$field}`='{$state}' where user_id=" . $session->get('user')->get('id');
        } else {
            $sql = "UPDATE `{$table}` SET `{$field}`='{$state}' where `cas_type_id` in (SELECT cas_type_id from cas_types where cas_heading_id={$cas_heading_id}) and user_id=" . $session->get('user')->get('id');
        }

        if ($db->Execute($sql)) {
            $return = array(
                'result' => '1',
                'field' => $field,
                'state' => $_REQUEST["state"],
            );
            echo json_encode($return);
            exit();
        } else {
            echo "error";
            exit();
        }

        break;
}

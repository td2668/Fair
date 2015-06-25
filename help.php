<?php

/**
 * This file is used to load the annual report pages, the $_GET['page'] parameter is required.
 */

require_once('includes/config.inc.php');
$fieldIndexId = (isset( $_GET["field_index_id"] )) ? CleanString( $_GET["field_index_id"] ) : '';

if ($fieldIndexId != ''){
    $sql = 'select field_name,help_text from cas_field_index where field_index_id = ' . $fieldIndexId;
    $helpData = $db->GetAll($sql);
    if ($helpData[0]['help_text'] != ''){
        $helpMessage = '<h3>'.$helpData[0]['field_name'] .'</h3>'. $helpData[0]['help_text'] . '';
    }else{
        $helpMessage = '<h2>'.$helpData[0]['field_name'] .'</h2>No help defined for this field yet.<br/>Field ID:<strong>' . $fieldIndexId . '</strong> - field name: <strong>' . $helpData[0]['field_name'] . '</strong>';
    }
    echo $helpMessage;
}
$override = (isset( $_GET["override"] )) ? $_GET["override"] : '';
if ($override != ''){
    $helpMessage = $override;

    echo htmlspecialchars($helpMessage);
}

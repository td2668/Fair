<?php

/**
 * This file contains functions and classes for the appropriate annual report section.
 */
/* * *********************************
 * FUNCTIONS
 * ********************************** */
/**
 *   Generate the cv items edit form, will also create a new record if case is 'add'.  Populates the cv_item_types and cv_item_fields templates
 *
 *   @param      int     $cvItemId   the id of the cv item, if not new add case
 *   @param      int     $userId     the user id for the current user
 *   @param      string  $page       the name of the page used in the annual_report.php code and GET var
 *   @param      object  $tmpl       the actual patTemplate object so we can make changes
 *   @return     boolean             the return status of whether everything worked
 */
require_once(ROOT_PATH . '/includes/cv_functions.php');
//echo("Loading from " . ROOT_PATH);


/**
 * Generates the edit form for the current CV Item
 *
 * @param mixed $cvItemId current CV item
 * @param mixed $page Page being generated
 * @param return $userId userId for the form being generated
 * @param integer $casHeadingId The heading id
 */
function GenerateEditForm($cvItemId, $page, $userId, $casHeadingId = false) {
    global $db, $session;
    $vars = array();
    $item = array();
    $userId = $session->get('user')->get('id');
    if (isset($_REQUEST["cas_type_new"])) {
        $typeId = CleanString($_REQUEST["cas_type_new"]);
    } else {
        $typeId = (isset($_REQUEST["cas_type_id"])) ? CleanString($_REQUEST["cas_type_id"]) : false;
    }

    if (!$casHeadingId) {
        $casHeadingId = (isset($_REQUEST["cas_heading_id"])) ? CleanString($_REQUEST["cas_heading_id"]) : false;
    }
    $alertMessage = 'none';

    $vars['user_options'] = getUsers();

    // first check to see if this is an add, if so, create the new record and reload the page in 'edit' mode
    if (!$cvItemId && $typeId) {

        //generate a new item and reload it
        $now = getdate();

        //Jan 1 is the default. (no month showing)
        $insertdate = mktime(0, 0, 0, 1, 1, $now['year']);

        $sql = "SELECT max(rank) AS max_rank FROM cas_cv_items WHERE user_id = {$userId} and cas_type_id = {$typeId}";
        $result = $db->GetAll($sql);
        $cRank = $result[0]['max_rank'];
        $nextRank = ($cRank != '' ? $cRank + 1 : 1);
        $sql = "INSERT INTO cas_cv_items (cas_type_id, user_id, f2, f3, report_flag, rank)
            VALUES ({$typeId}, {$userId}, {$insertdate}, {$insertdate}, 1,{$nextRank})";
        $db->Execute($sql);
        $cvItemId = $db->insert_id();
        header("location: /cv.php?cas_heading_id={$casHeadingId}&mr_action=edit&cv_item_id={$cvItemId}");
    }

    // get the record data
    if ($cvItemId) {
        $vars['header']['currentaction'] = ' - Edit';
        $item = GetCvItem($cvItemId, $userId);
        if ($item) {
            $typeId = $item['cas_type_id'];
        } else {
            // error getting record data
            $cvItemId = false;
            $item = array();
        }
    } else {
        $vars['header']['currentaction'] = ' - Add an item';
    }

    if ($cvItemId) {
        $item = GetCvItem($cvItemId, $userId);
        $vars['cas_type_id'] = $item['cas_type_id'];
    } else {
        $vars['cas_type_id'] = false;
    }

    // get the types of cv items and populate the type drop-down
    $sql = "
        SELECT cas_type_id, type_name
        FROM `cas_types`
        WHERE cas_heading_id = {$casHeadingId}
        ORDER BY `order`, `type_name`
        ";
    $types = $db->getAll($sql);

    $typesList = array();
    if (!$typeId) {
        $typesList[] = array(
            "cas_type_id" => "0",
            "type_name" => "--- Please select a type. ---",
        );
    }

    if ($types) {
        $currentHeaderId = null;
        foreach ($types as $type) {
            if ($type["cas_type_id"] == $typeId) {
                $type["cas_type_id_selected"] = 'SELECTED="SELECTED"';
            }

            // add to drop-down list
            $typesList[] = $type;
            $currentHeaderId = isset($type['cv_item_header_id']) ? $type['cv_item_header_id'] : null;
        }
    }

    $vars['cas_types_new'] = $typesList;
    $vars['cas_types'] = $typesList;

    //Populate the dropdown with all possible type for the translate function
    $sql = "
        SELECT cas_type_id, type_name
        FROM `cas_types`
        ORDER BY `type_name`
        ";
    $types = $db->getAll($sql);
    $typesList2 = array();
    if (!$typeId) {
        $typesList2[] = array(
            "cas_type_id" => "0",
            "type_name" => "",
        );
    }

    if ($types) {
        $currentHeaderId = null;
        foreach ($types as $type) {
            if ($type["cas_type_id"] == $typeId) {
                $type["cas_type_id_selected"] = 'SELECTED="SELECTED"';
            }

            // add to drop-down list
            if (strlen($type['type_name']) > 30) {
                $type['type_name'] = substr($type['type_name'], 0, 30);
            }
            $typesList2[] = $type;
            $currentHeaderId = isset($type['cv_item_header_id']) ? $type['cv_item_header_id'] : null;
        }
    }

    $vars['cas_types2'] = $typesList2;

    // if we have a specific type, populate the form field template variables (otherwise we are just prompting for a type)
    if ($typeId && sizeof($item) > 0) {
        $preview = \MRU\Research\CV::formatitem($item, 'apa', 'screen');
        $vars['preview'] = $preview;
        $vars['relatedto'] = $item['report_flag'] == 1 ? 'block' : 'none';

        // get the type meta data
        $sql = "SELECT * FROM `cas_field_index` WHERE `cas_type_id` = {$typeId} ORDER BY `order`";
        $typeData = $db->getAll($sql);
        if ($typeData) {
            $vars['page_item'] = $item;

            // 20090309 CSN what is this?
            foreach ($typeData as $fieldMeta) {
                if ($fieldMeta["field_name"] != "") {
                    $fieldType = strtolower($fieldMeta["type"]);
                    $field = array();
                    $field["f_formname"] = $fieldMeta["cas_cv_item_field"];
                    $field["fvalue"] = htmlentities($item[$fieldMeta["cas_cv_item_field"]]);
                    if ($fieldMeta["size"] > 0) {
                        $field["fsize"] = $fieldMeta["size"];
                    } else {
                        $field["fsize"] = 75;
                    }

                    if (isset($fieldMeta["maxlength"])) {
                        $field["fmaxlength"] = $fieldMeta["maxlength"];
                    } else {
                        $field["fmaxlength"] = "255";
                    }

                    $field['fieldindexid'] = $fieldMeta["field_index_id"];
                    if (trim($fieldMeta['help_text']) == '') {
                        $field['hidehelp'] = "display:none";
                    }

                    $field["fname"] = $fieldMeta["field_name"];
                    $field["ftype"] = $fieldType;
                    switch ($fieldType) {
                        case "bool":
                            $field["ftype"] = "checkbox";
                            if (intval($field["fvalue"])) {
                                $field["f_check"] = "checked";
                            }
                            break;

                        case "date":
                            $yearOptions = $monthOptions = $dayOptions = '';
                            $arrDate = explode('-', $field["fvalue"]);
                            $selYear = $arrDate[0];
                            $selMonth = $arrDate[1];
                            $selDay = $arrDate[2];
                            $months = array(
                                '',
                                'Jan',
                                'Feb',
                                'Mar',
                                'Apr',
                                'May',
                                'Jun',
                                'Jul',
                                'Aug',
                                'Sep',
                                'Oct',
                                'Nov',
                                'Dec',
                            );
                            $yearOptions .= "<option value=\"0\">N/A</option>\r\n";
                            for ($year = date('Y') + 6; $year >= 1950; $year--) {
                                if ($year == $selYear) {
                                    $selected = ' selected=selected ';
                                } else {
                                    $selected = '';
                                }

                                $yearOptions .= '<option value="' . $year . '" ' . $selected . '>' . $year . "</option>\r\n";
                            }

                            $field["f_yearoptions"] = $yearOptions;
                            if ($selMonth == 0) {
                                $selected = ' selected=selected ';
                            }

                            $monthOptions .= '<option value="0" ' . $selected . ">N/A</option>\r\n";
                            for ($month = 1; $month <= 12; $month++) {
                                if ($month == $selMonth) {
                                    $selected = ' selected=selected ';
                                } else {
                                    $selected = '';
                                }

                                $monthOptions .= '<option value="' . $month . '" ' . $selected . '>' . $months[$month] . "</option>\r\n";
                            }

                            $field["f_monthoptions"] = $monthOptions;
                            if ($selDay == 0) {
                                $selected = ' selected=selected ';
                            }

                            $dayOptions .= '<option value="0" ' . $selected . ">N/A</option>\r\n";
                            for ($day = 1; $day <= 31; $day++) {
                                if ($day == $selDay) {
                                    $selected = ' selected=selected ';
                                } else {
                                    $selected = '';
                                }

                                $dayOptions .= '<option value="' . $day . '" ' . $selected . '>' . $day . "</option>\r\n";
                            }

                            $field["f_dayoptions"] = $dayOptions;
                            break;

                        case "list":
                            $field["foptions"] = BuildList($fieldMeta['sublist'], $field["fvalue"]);
                            if ($fieldMeta['list_add'] == 1) {
                                $field["faddfield"] = 'Or add ' . $field["fname"] . ': <input type="text" name = "new[' . $fieldMeta['sublist'] . ']" maxlength="100" size="65"/>';
                            }
                        case "text":
                            if ($fieldMeta['subtype'] == 'author') {
                                $field["ftype"] = "author";
                                $values = explode('|', $field["fvalue"]);
                                $field["fvaluelname"] = isset($values[0]) ? $values[0] : null;
                                $field["fvaluefname"] = isset($values[1]) ? $values[1] : null;
                            }
                        case "num":
                            break;

                        case "sub":
                            $fieldString = BuildSubTableFields($item, $fieldMeta);
                            $field['extrafields'] = $fieldString;
                            break;

                        default:
                            $field["ftype"] = "textarea";
                    }

                    $fields[] = $field;
                }

            }

            if (isset($typeData[0]['requires_full_text']) && $typeData[0]['requires_full_text'] == 1) {
                $fields[] = array(
                    'ftype' => 'file',
                    'f_formname' => 'document_filename',
                    'fname' => 'Add/Update Document full text',
                    'fvalue' => $item['document_filename'],
                );
            }

            $fields[] = array(
                'ftype' => 'hidden',
                'f_formname' => 'cv_item_id',
                'fvalue' => $cvItemId,
            );

            $vars['cv_item_fields'] = $fields;
            $alertMessage = 'WARNING: If you change the type of this item you will probably lose any data that you have already entered.  Do you want to proceed with the change?';
        } else {
            // error getting type meta data, cannot display form
            echo $sql;
        }

    }

    $vars['alert_message'] = $alertMessage;

    //do details for teaching
    $vars['details_teaching'] = isset($item['details_teaching']) ? $item['details_teaching'] : null;
    $vars['details_service'] = isset($item['details_service']) ? $item['details_service'] : null;
    $vars['details_scholarship'] = isset($item['details_scholarship']) ? $item['details_scholarship'] : null;


    if (isset($item['n_teaching']) && $item['n_teaching'] == '1') {
        $vars['n_teaching_check'] = "checked";
    } else {
        $vars['div_teaching_toggle'] = 'display:none';
    }

    //do details for scholarship
    if (isset($item['n_scholarship']) && $item['n_scholarship'] == '1') {
        $vars['n_scholarship_check'] = 'checked';
    } else {
        $vars['div_scholarship_toggle'] = 'display:none';
    }

    //do details for service items
    if (isset($item['n_service']) && $item['n_service'] == '1') {
        $vars['n_service_check'] = 'checked';
    } else {
        $vars['div_service_toggle'] = 'display:none';
    }

    if ($cvItemId) {
        $vars['cv_item_id'] = $cvItemId;
        if ($item['report_flag'] == '1') {
            $vars['report_flag'] = 'checked';
        }

        if ($item['web_show'] == '1') {
            $vars['web_show'] = 'checked';
        }

        if ($item['mycv1'] == '1') {
            $vars['mycv1'] = 'checked';
        }

        if ($item['mycv2'] == '1') {
            $vars['mycv2'] = 'checked';
        }
    }

    return $vars;
}

/**
 * Saves the current cv id
 *da
 * @param mixed $cvItemId current CV Item
 * @param mixed $userId current User Id
 */
function SaveForm($cvItemId, $userId) {
    global $db;
    $status = false;
    $statusMessage = '';
    $vars = array(
        'new_header' => false,
        'status_message' => null
    );

    //get the item data
    if (isset($_POST["cas_type_new"])) {
        $typeId = (isset($_POST["cas_type_new"])) ? CleanString($_POST["cas_type_id"]) : false;
    }

    if (isset($_POST["cas_type_id"])) {
        $typeId = (isset($_POST["cas_type_id"])) ? CleanString($_POST["cas_type_id"]) : false;
    }

    $item = GetCvItem($cvItemId, $userId);
    if ($item) {

        //Added by TD: Check if the typeID coming back from the translate dropdown is different from the original
        if ($_POST['cas_type_id2'] != $item['cas_type_id'] || $typeId != $item['cas_type_id']) {
            $typeId = $_POST['cas_type_id2'];
            $vars['new_header'] = true;
        }

        //Changed by TD to first save as the original type and then load as the new type.
        $sql = "SELECT * FROM `cas_field_index` WHERE `cas_type_id` = {$item['cas_type_id']}";
        $typeData = $db->getAll($sql);

        //run through the fields, process dates, load into array
        $fields = array();
        foreach ($typeData as $fieldMeta) {
            if ($fieldMeta["field_name"] != "") {
                $fieldName = $fieldMeta["cas_cv_item_field"];
                $fieldType = strtolower($fieldMeta["type"]);
                $_POST[$fieldMeta["field_name"]] = isset($_POST[$fieldName]) ? addslashes(strval($_POST[$fieldName])) : null;
                switch ($fieldType) {
                    case "sub":
                        $sql = '';
                        $subArray = $_POST[$fieldName];
                        foreach ($subArray as $row => $rowData) {
                            if ($row != 'new') {
                                if (isset($rowData['delete_row']) && $rowData['delete_row'] == 1) {
                                    $sql = "DELETE FROM `{$fieldMeta['subtable']}` WHERE id = {$row};";
                                } else {
                                    $fieldsToProcess = '';
                                    foreach ($rowData as $field => $fieldValue) {
                                        $fieldValue = mysql_real_escape_string($fieldValue);
                                        if ($fieldsToProcess != '') {
                                            $fieldsToProcess .= ',';
                                        }
                                        $fieldsToProcess .= "`{$field}`='{$fieldValue}'";
                                    }

                                    $sql = "UPDATE `{$fieldMeta['subtable']}` SET {$fieldsToProcess} WHERE id = {$row}";
                                }
                            } else {
                                $fieldsToInsert = '`cv_item_id`,`fieldname`';
                                $valuesToInsert = "{$cvItemId},'{$fieldName}'";
                                $hasValue = false;
                                foreach ($rowData as $field => $fieldValue) {
                                    if ($fieldValue != '') {
                                        $fieldValue = mysql_real_escape_string($fieldValue);
                                        if ($fieldsToInsert != '') {
                                            $fieldsToInsert .= ',';
                                        }

                                        if ($valuesToInsert != '') {
                                            $valuesToInsert .= ',';
                                        }
                                        $hasValue = TRUE;
                                        $fieldsToInsert .= "`{$field}`";
                                        $valuesToInsert .= "'{$fieldValue}'";
                                    }
                                }

                                if ($hasValue) {
                                    $sql = "INSERT INTO `{$fieldMeta['subtable']}` ({$fieldsToInsert}) VALUES ({$valuesToInsert});";
                                }
                            }

                            if ($sql) {
                                if (!$db->Execute($sql)) {
                                    $statusMessage = 'An error occured with saving a subtable item. (query error)';
                                    echo $sql;
                                }

                            }
                        }

                        continue;

                    case "date":
                        $fx = $_POST[$fieldName];
                        $value = $_POST[$fieldName]['year'] . '-' . $_POST[$fieldName]['month'] . '-' . $_POST[$fieldName]['day'];
                        $fields[] = "$fieldName = \"{$value}\"";
                        break;

                    case "bool":
                        if (!isset($_POST[$fieldName]) || $_POST[$fieldName] == '') {
                            $_POST[$fieldName] = 0;
                        }

                        $fields[] = "$fieldName = \"" . $_POST[$fieldName] . '"';
                        break;

                    case "list":
                        $id = '';
                        if ($fieldMeta['list_add'] == 1 && $_POST['new'][$fieldMeta['sublist']] != '') {
                            $name = mysql_real_escape_string($_POST['new'][$fieldMeta['sublist']]);

                            //Added by TD to eliminate leading space problems
                            $name = preg_replace('/^\s+/', '', $name);
                            $name = preg_replace('/\s+$/', '', $name);
                            $name = preg_replace('/[,\.]$/', '', $name);

                            //Check if it exists first
                            $sql = "SELECT * FROM `{$fieldMeta['sublist']}` WHERE `name`='$name'";
                            $res = $db->getRow($sql);
                            if ($res) {
                                $id = $res['id'];
                            } else {
                                $sql = "INSERT INTO `{$fieldMeta['sublist']}` (`name`) VALUES ('{$name}')";
                                $db->Execute($sql);
                                $id = $db->insert_id();
                            }
                        } else {
                            $id = $_POST[$fieldName];
                        }

                        $fields[] = "$fieldName = \"" . $id . '"';
                        break;

                    case "text":
                        if ($fieldMeta['subtype'] == 'author') {
                            $_POST[$fieldName] = implode('|', $_POST[$fieldName]);
                        }

                        $_POST[$fieldName] = mysql_real_escape_string($_POST[$fieldName]);
                    case "num":
                    default:
                        $fields[] = "$fieldName = \"" . $_POST[$fieldName] . '"';
                }
            }
        }

        //where we check for the details for teaching scholarship and service.
        if (isset($_POST['n_teaching']) && $_POST['n_teaching'] == 1) {
            $fields[] = 'n_teaching = 1';
            $fields[] = "details_teaching = '" . mysql_real_escape_string($_POST['details_teaching']) . "'";
        } else {
            $fields[] = 'n_teaching = 0';
        }

        if (isset($_POST['n_scholarship']) && $_POST['n_scholarship'] == 1) {
            $fields[] = 'n_scholarship = 1';
            $fields[] = "details_scholarship = '" . mysql_real_escape_string($_POST['details_scholarship']) . "'";
        } else {
            $fields[] = 'n_scholarship = 0';
        }

        if (isset($_POST['n_service']) && $_POST['n_service'] == 1) {
            $fields[] = 'n_service = 1';
            $fields[] = "details_service = '" . mysql_real_escape_string($_POST['details_service']) . "'";
        } else {
            $fields[] = 'n_service = 0';
        }

        if (count($fields)) {
            $updateThis = "," . implode(",", $fields);

            // if this is an add case, also set web flag to be true.  Right now, just set it all the time
            $updateThis .= (isset($_POST["report_flag"])) ? ', report_flag = ' . CleanString($_POST["report_flag"]) : '';
        } else {
            $updateThis = '';
        }

        //do the update
        $sql = "UPDATE cas_cv_items SET current_par = 1, reminder_date=NOW(), cas_type_id = {$typeId} {$updateThis}
            WHERE user_id = {$userId} AND cv_item_id = {$cvItemId}";
        if ($db->Execute($sql) == false) {
            $statusMesssage .= 'Sorry, an error occurred (update query failed).';
        } else {
            $statusMessage .= 'The record has been saved.';
            $status = true;
        }

        //Get the caqcflags and then update again
        $flags = new \MRU\Research\Caqc\Flags();
        $flags->GetStats($cvItemId);

        //Bitwise variaible saved to one field
        $sql = "UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id= {$cvItemId}";
        if ($db->Execute($sql) == false) {
            $statusMesssage .= 'Sorry, an error occurred (flag set failed).';
        } else {
            $statusMessage .= " (CAQC:";
            if ($flags->isBooksAuthored()) {
                $statusMessage .= " Book Authored/Co-authored;";
            }

            if ($flags->isBooksEdited()) {
                $statusMessage .= " Book Edited/Co-edited;'";
            }

            if ($flags->isOtherPeer()) {
                $statusMessage .= " Other Peer-reviewed;";
            }

            if ($flags->isRefJournals()) {
                $statusMessage .= " Article in Refereed Journal/Book Chapter;";
            }

            if ($flags->isNonPeer()) {
                $statusMessage .= " Non Peer-reviewed SA;";
            }

            if ($flags->isConfPres()) {
                $statusMessage .= " Conference Presentation;";
            }

            if ($flags->isConfAttend()) {
                $statusMessage .= " Conference Attendance;";
            }

            if ($flags->isStudent()) {
                $statusMessage .= " Peer-Reviewed Student Publication;";
            }

            if ($flags->isSubmitted()) {
                $statusMessage .= " Peer-Reviewed Pub, Submitted;";
            }

            if ($flags->isGrants()) {
                $statusMessage .= " Grant;";
            }

            if ($flags->isService()) {
                $statusMessage .= " Scholarly Service;";
            }
            $statusMessage .= ")";
        }
    }

    // if item
    else {

        // invalid data received
        $statusMessage = 'Sorry, an error occurred (could not get item data).';
    }

    $vars['header']['status_messages'][] = $statusMessage;

    return $vars;
}

/**
 * Deletes the selected CV item
 *
 * @param integer $cvItemId The cv item to be deleted
 * @param integer $userId The user id
 *
 * @return boolean
 */
function DeleteItem($cvItemId, $userId) {
    global $db;

    // check to make sure the item exists and belongs to this user
    $sql = "SELECT cv_item_id, cas_type_id FROM cas_cv_items WHERE user_id = {$userId} AND cv_item_id = {$cvItemId}";
    $data = $db->GetRow($sql);
    if (is_array($data) == false or count($data) == 0) {
        return false;
    }

    $sql = "DELETE FROM cas_cv_items WHERE cv_item_id = {$cvItemId}";
    if (!$db->Execute($sql)) {
        return false;
    }

    $sql = "SELECT subtable FROM `cas_field_index` WHERE `cas_type_id`={$data['cas_type_id']} AND `type` LIKE 'sub'";
    $subTableResult = $db->query($sql);
    foreach ($subTableResult as $subtable) {
        $sql = "DELETE FROM `{$subtable['subtable']}` WHERE cv_item_id = {$cvItemId}";
        if (!$db->Execute($sql)) {
            return false;
        }
    }

    return true;
}

/**
 * Builds the display list for the currently selected Heading.
 *
 * @param integer $userId current User
 * @param integer $casHeadingId Current heading ID
 * @param array $vars The array of template variables
 */
function PopulateList($userId, $casHeadingId, $vars) {
    global $db;
    $showAll = false;
    if ($casHeadingId == false || $casHeadingId == '') {
        $showAll = true;
    }

    ClearBlanks();
    if ($showAll == true) {
        $sql = "SELECT cas_heading_id,heading_name, short_name, rank from cas_headings";
    } else {
        $sql = "SELECT cas_heading_id,heading_name, short_name from cas_headings WHERE cas_heading_id = '{$casHeadingId}';";
    }

    $headers = $db->getAll($sql);
    if (!$showAll) {
        $vars['header']['title'] = $headers[0]["heading_name"];
        $vars['page']['cv_section_title'] = $headers[0]["heading_name"];
        $vars['page']['cv_section_title_short'] = $headers[0]["short_name"];
    } else {
        $vars['page']['cv_section_title'] = $vars['header']['title'];
    }

    $fields = GetCvItemTypeFields($casHeadingId);
    $items = GetCvItems($userId, $fields);
    $allReportShow = true;
    $allWebShow = true;
    $allMyCV1Show = true;
    $allMyCV2Show = true;
    $cvData = array();
    $isOddRow = true;
    $previousItemId = null;
    $sectionIndex = - 1;
    $sectionItemIndex = 0;


    $casTypeHeadings = array();
    $results = $db->getAll("SELECT cas_type_id,
                                       cas_headings.cas_heading_id
                                FROM `cas_types`
                                JOIN `cas_headings` ON `cas_headings`.`cas_heading_id` = `cas_types`.`cas_heading_id`");
    foreach ($results as $result) {
        $casTypeHeadings[$result['cas_type_id']] = $result['cas_heading_id'];
    }

    if ($items && sizeof($items) > 0) {
        foreach ($items as $item) {

            // check for new item type and add header if needed
            if ($previousItemId != $item["cas_type_id"]) {
                $sectionIndex++;
                $sectionItemIndex = 0;
                $totalItems = GetCvItemPerHeading($userId, $item["cas_type_id"]);
                $cvData[$sectionIndex] = array(
                    "title" => GetHeading($item["cas_type_id"]),
                );
                $isOddRow = true;
                $previousItemId = $item["cas_type_id"];
            }

            $itemFields = getCvItemFields($item["cas_type_id"], $fields);

            //At this point, if there is no cas_heading_id then we are working the 'show all' list.
            //Need to determine the appropriate heading to help locate the proper heading to highlight when chosen
            if ($showAll == true) {
                $casHeadingId = $casTypeHeadings[$item['cas_type_id']];
            }

            $cvItemId = $item['cv_item_id'];
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["cas_heading_id"] = $casHeadingId;
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["cas_type_id"] = $item["cas_type_id"];
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["type"] = 'item1';
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["tr_class"] = ($isOddRow ? 'oddrow' : 'evenrow');
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["cv_item_id"] = $item['cv_item_id'];
            $cvData[$sectionIndex]['items'][$sectionItemIndex]['report_flag'] = ($item['report_flag']) ? ' CHECKED' : '';
            $cvData[$sectionIndex]['items'][$sectionItemIndex]['web_show'] = ($item['web_show']) ? ' CHECKED' : '';
            $cvData[$sectionIndex]['items'][$sectionItemIndex]['mycv1'] = ($item['mycv1']) ? ' CHECKED' : '';
            $cvData[$sectionIndex]['items'][$sectionItemIndex]['mycv2'] = ($item['mycv2']) ? ' CHECKED' : '';
            if ($allReportShow) {
                $allReportShow = ($item['report_flag']) ? TRUE : FALSE;
            }

            if ($allWebShow) {
                $allWebShow = ($item['web_show']) ? TRUE : FALSE;
            }

            if ($allMyCV1Show) {
                $allMyCV1Show = ($item['mycv1']) ? TRUE : FALSE;
            }

            if ($allMyCV2Show) {
                $allMyCV2Show = ($item['mycv2']) ? TRUE : FALSE;
            }

            $cvData[$sectionIndex]['items'][$sectionItemIndex]['web_show'] = ($item['web_show']) ? ' CHECKED' : '';
            $cvData[$sectionIndex]['items'][$sectionItemIndex]['mycv1'] = ($item['mycv1']) ? ' CHECKED' : '';
            $cvData[$sectionIndex]['items'][$sectionItemIndex]['mycv2'] = ($item['mycv2']) ? ' CHECKED' : '';

            // set up the reaining fields for display in the template
            $title = \MRU\Research\CV::formatitem($item, 'apa', 'screen');
            if (!$title) {
                $title = "No title has been generated for this item yet.";
            }

            $cvData[$sectionIndex]['items'][$sectionItemIndex]["title"] = $title;
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["rank"] = $item['rank'];
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["item_id"] = $cvItemId;
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["cv_fname"] = "item_{$cvItemId}_cv";
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["profile_fname"] = "item_{$cvItemId}_profile";
            $cvData[$sectionIndex]['items'][$sectionItemIndex]["title_fname"] = "item_{$cvItemId}_title";
            $isOddRow = !$isOddRow;
            $sectionItemIndex++;
        }
    } else {
        if (is_array($items) && sizeof($items) == 0) {
            $vars['header']['status_messages'][] = "No entries yet. Use the Add an Item button to start.";
        } else {
            $vars['header']['status_messages'][] = 'A query error occured and we were not able to display the results.  Please try again later.';
        }
    }

    if ($allWebShow) {
        $vars['page']['allweb_show'] = "checked";
    }

    if ($allMyCV1Show) {
        $vars['page']['allmycv1'] = "checked";
    }

    if ($allMyCV2Show) {
        $vars['page']['allmycv2'] = "checked";
    }

    if ($showAll) {
        // 0 if all activities are displayed
        $vars["page"]["cas_heading_id"] = 0;
    }

    $vars["cv_item_list"] = $cvData;
    return $vars;
}

/**
 * Build the sidebar submenu of all the categories
 *
 * @param integer $casHeadingId The selected category
 * @param array $vars The template variables
 */
function BuildSidebarSubmenu($casHeadingId, $vars) {
    global $db;
    $submenu = array();
    $sql = "SELECT `cas_heading_id`,`short_name` FROM `cas_headings` ORDER BY `order` ASC";
    $data = $db->getAll($sql);
    foreach ($data as $row) {
        $submenu[] = array(
            'url' => 'cv.php?cas_heading_id=' . $row['cas_heading_id'],
            'name' => $row['short_name'],
            'selected' => ($casHeadingId == $row['cas_heading_id']),
        );
    }

    foreach ($vars['sidebar'] as $i => $sidebarItem) {
        if ($sidebarItem['url'] == 'cv.php') {
            $vars['sidebar'][$i]['submenu'] = $submenu;
        }
    }

    return $vars;
}

/**
 * Gets the Page title for the selected CV Item
 *
 * @param mixed $cvItemId ID of the CV item to get the head for.
 * @param mixed $mrAction
 * @return string current header.
 */
function GetPageTitle($cvItemId) {
    global $db;
    $pageTitle = '';
    if ($cvItemId == '' || $cvItemId == false) {
        return 'My Activities';
    }
    $sql = "select type_name from cas_types where cas_type_id = (select cas_type_id from cas_cv_items where cv_item_id = {$cvItemId})";
    $result = $db->getAll($sql);
    return $result[0]['type_name'];
}

/**
 * Get specified CV Item
 *
 * @param mixed $cvItemId ID of CV item to retrieve
 * @param mixed $userId current User ID
 * @return array
 */
function GetCvItem($cvItemId, $userId) {
    global $db;
    $sql = "
        SELECT *
        FROM `cas_cv_items` AS item
        WHERE item.`user_id` = {$userId} AND item.`cv_item_id` = {$cvItemId} LIMIT 1";
    $items = $db->getAll($sql);
    if ($items) {
        $item = reset($items);
    } else {
        $item = false;
    }

    return $item;
}

/**
 * Get the list of fields and labels for the given item type header id
 *
 * @param mixed $cvItemHeaderId
 * @returns array
 */
function GetCvItemTypeFields($cvItemHeaderId) {
    global $db;
    if ($cvItemHeaderId != '') {
        $sql = 'SELECT cas_field_index.*
        FROM cas_field_index
        JOIN cas_types on (cas_types.cas_type_id = cas_field_index.cas_type_id)
        WHERE cas_heading_id = ' . $cvItemHeaderId . '
        ORDER BY cas_field_index.cas_type_id, cas_field_index.`order`';
        $items = $db->getAll($sql);
        return $items;
    } else {
        return array();
    }
}

/**
 * Returns the specific fields for the given castTypeId
 *
 * @param INT $casTypeId
 * @param array $fields
 * @returns array
 */
function getCvItemFields($casTypeId, $fields) {
    $specificFields = array();
    foreach ($fields as $data) {
        if ($data['cas_type_id'] == $casTypeId) {
            $specificFields[] = $data;
        }
    }

    return $specificFields;
}

/**
 * Get The CV Items based on the Header ID being passed id.
 *
 * @param mixed $userId
 * @param string $fields list of cas_cv_items fields to retrieve.
 */
function GetCvItems($userId, $fields = array(), $flag = '') {
    global $db;
    $casTypeIds = array();
    $casTypeIdsWhere = '';
    if (is_array($fields)) {
        foreach ($fields as $data) {
            $casTypeIds[] = $data['cas_type_id'];
        }

        if (sizeof($casTypeIds) > 0) {
            $casTypeIdsWhere = implode(',', array_unique($casTypeIds));
        }

        $sql = "
            SELECT item.*, type_name, heading_name
            FROM cas_cv_items AS item
            JOIN cas_types on (cas_types.cas_type_id = item.cas_type_id)
            JOIN cas_headings on (cas_types.cas_heading_id = cas_headings.cas_heading_id)
            WHERE 1
            AND item.user_id = {$userId} ";
        $sql .= ($casTypeIdsWhere != '') ? " AND item.cas_type_id in ({$casTypeIdsWhere}) " : '';
        $sql .= ($flag != '') ? " AND item.{$flag} = 1 " : '';
        $sql .= 'ORDER BY cas_headings.order,cas_type_id,rank desc, f2 desc';
        $items = $db->getAll($sql);
        return $items;
    } else {
        return array();
    }
}


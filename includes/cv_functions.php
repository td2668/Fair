<?php

/**
 * Retrieve's the heading for the current cas_heading_id
 *
 * @param string $casTypeId  cas heading to retrieve
 * @return string returns the heading according to the id that was passed in.
 */
function GetHeading($casTypeId) {
    global $db;
    static $headings;

    if (empty($headings)) {
        $results = $db->getAll("SELECT cas_type_id, type_name FROM cas_types");
        foreach ($results as $result) {
            $headings[$result['cas_type_id']] = $result['type_name'];
        }
    }

    return $headings[$casTypeId];
}

function GetCasHeading($casTypeId) {
    global $db;
    $sql = "SELECT heading_name FROM `cas_types`
        join `cas_headings` on `cas_headings`.`cas_heading_id` = `cas_types`.`cas_heading_id`
        where `cas_type_id` = {$casTypeId}";
    $result = $db->getAll($sql);
    return $result[0]['heading_name'];
}

/**
 * Builds the selection list for a given reference table
 *
 * @param string $tableName The table that has the possible values for this select list.
 * @param string $selectValue The currently selection option
 * @returns string A string that contains the options for the given table
 */
function BuildList($tableName, $selectValue) {
    global $db;
    $returnVal = '';
    $sql = "SELECT id,name FROM {$tableName} ORDER BY name";
    $data = $db->getAll($sql);
    $returnVal = '<option value=""></option>';
    foreach ($data as $rowData) {
        $returnVal .= '<option value="' . $rowData['id'] . '" ';
        if ($rowData['id'] == $selectValue) {
            $returnVal .= ' selected="selected" ';
        }

        $returnVal .= '>';
        $returnVal .= $rowData['name'];
        $returnVal .= "</option>\r\n";
    }

    return $returnVal;
}

function GetCasHeadingId($casTypeId) {
    global $db;
    $sql = "SELECT cas_heading_id FROM `cas_types`
        where `cas_type_id` = {$casTypeId}";
    $result = $db->getRow($sql);
    return $result['cas_heading_id'];
}

/**
 * Gets The count of items for the current CV Hhading Type.
 *
 * @param int $userId
 * @param int $casTypeId
 * @return int number of rows for that item type.
 */
function GetCvItemPerHeading($userId, $casTypeId) {
    global $db, $count;

    if (empty($count)) {
        $results = $db->getAll("SELECT cas_type_id, count(*) AS items FROM cas_cv_items WHERE user_id = {$userId} GROUP BY cas_type_id");
        foreach ($results as $result) {
            $count[$result['cas_type_id']] = $result['items'];
        }
    }

    return $count[$casTypeId];
}

/**
 * Builds the list of fields for "sub" cv items
 *
 * @param array $cvItem array containing the current cvItem that is a "sub" item
 * @param array $fieldmeta array containing the current field definition for the "sub" item
 * @return string containing the built up sub form elements
 *
 */
function BuildSubTableFields($cvItem, $fieldMeta) {
    global $db;
    global $config;
    $fieldName = $fieldMeta['cas_cv_item_field'];
    $size = $fieldMeta['size'];
    if (isset($fieldMeta['maxlength'])) {
        $maxlength = $fieldMeta['maxlength'];
    }

    $sql = "select * from cas_subtables where table_name = '{$fieldMeta['subtable']}'";
    $subTableResult = $db->getAll($sql);
    if ($subTableResult[0]['order_by']) {
        $orderBy = ' ORDER BY ' . $subTableResult[0]['order_by'];
    } else {
        $orderBy = '';
    }

    $sql = "select * from {$fieldMeta['subtable']} where cv_item_id = {$cvItem['cv_item_id']} and fieldname = '{$fieldName}' {$orderBy}";
    $subTableData = $db->getAll($sql);
    $field = " <table> ";
    $field .= "<tr>\r\n";
    $cols = 0;
    if ($subTableResult[0]['field1_name'] != '') {
        $field .= '<th>' . $subTableResult[0]['field1_display'] . "</th>";
        $cols++;
    }

    if ($subTableResult[0]['field2_name'] != '') {
        $field .= '<th>' . $subTableResult[0]['field2_display'] . "</th>";
        $cols++;
    }

    if ($subTableResult[0]['field3_name'] != '') {
        $field .= '<th>' . $subTableResult[0]['field3_display'] . "</th>";
        $cols++;
    }

    $field .= "<th>Delete?</th></tr>\r\n";
    foreach ($subTableData as $data) {
        $field .= "<tr>\r\n";
        if ($subTableResult[0]['field1_name'] != '') {
            if ($subTableResult[0]['field1_size'] != 0) {
                $size = $subTableResult[0]['field1_size'];
            } else {
                if ($size == 0) {
                    switch ($subTableResult[0]['field1_type']) {
                        case "text":
                            $size = $config['cv_items']["subfields_text_length"];
                            break;

                        case "date":
                        case "num":
                            $size = $config['cv_items']["subfields_num_length"];
                            break;
                    }
                }
            }

            $field .= '<td><input type="text" name="' . $fieldName . '[' . $data['id'] . '][' . $subTableResult[0]['field1_name'] . ']" id="' . $subTableResult[0]['field1_name'] . '" value="' . $data[$subTableResult[0]['field1_name']] . '" size="' . $size . '" maxlength = "' . $size . '" /></td>';
        }

        if ($subTableResult[0]['field2_name'] != '') {
            if ($subTableResult[0]['field2_size'] != 0) {
                $size = $subTableResult[0]['field2_size'];
            } else {
                if ($size == 0) {
                    switch ($subTableResult[0]['field2_type']) {
                        case "text":
                            $size = $config['cv_items']["subfields_text_length"];
                            break;

                        case "date":
                        case "num":
                            $size = $config['cv_items']["subfields_num_length"];
                            break;
                    }
                }
            }

            $field .= '<td><input type="text" name="' . $fieldName . '[' . $data['id'] . '][' . $subTableResult[0]['field2_name'] . ']" id="' . $subTableResult[0]['field2_name'] . '" value="' . $data[$subTableResult[0]['field2_name']] . '" size="' . $size . '" maxlength = "' . $size . '" /></td>';
        }

        if ($subTableResult[0]['field3_name'] != '') {
            if ($subTableResult[0]['field3_size'] != 0) {
                $size = $subTableResult[0]['field3_size'];
            } else {
                if ($size == 0) {
                    switch ($subTableResult[0]['field3_type']) {
                        case "text":
                            $size = $config['cv_items']["subfields_text_length"];
                            break;

                        case "date":
                        case "num":
                            $size = $config['cv_items']["subfields_num_length"];
                            break;
                    }
                }
            }

            $field .= '<td><input type="text" name="' . $fieldName . '[' . $data['id'] . '][' . $subTableResult[0]['field3_name'] . ']" id="' . $subTableResult[0]['field3_name'] . '" value="' . $data[$subTableResult[0]['field3_name']] . '"  size="' . $size . '" maxlength = "' . $size . '" /></td>';
        }

        $field .= "<td><input type=\"checkbox\" name=\"{$fieldName}[{$data['id']}][delete_row]\" value=\"1\" /></td></tr>\r\n";
    }

    $field .= "<tr>\r\n";
    if ($subTableResult[0]['field1_name'] != '') {
        if ($subTableResult[0]['field1_size'] != 0) {
            $size = $subTableResult[0]['field1_size'];
        } else {
            if ($size == 0) {
                switch ($subTableResult[0]['field1_type']) {
                    case "text":
                        $size = $config['cv_items']["subfields_text_length"];
                        break;

                    case "date":
                    case "num":
                        $size = $config['cv_items']["subfields_num_length"];
                        break;
                }
            }
        }

        $field .= '<td><input type="text" name="' . $fieldName . '[new][' . $subTableResult[0]['field1_name'] . ']" id="' . $subTableResult[0]['field1_name'] . '" value="" size="' . $size . '" maxlength = "' . $size . '" /></td>';
    }

    if ($subTableResult[0]['field2_name'] != '') {
        if ($subTableResult[0]['field2_size'] != 0) {
            $size = $subTableResult[0]['field2_size'];
        } else {
            if ($size == 0) {
                switch ($subTableResult[0]['field2_type']) {
                    case "text":
                        $size = $config['cv_items']["subfields_text_length"];
                        break;

                    case "date":
                    case "num":
                        $size = $config['cv_items']["subfields_num_length"];
                        break;
                }
            }
        }

        $field .= '<td><input type="text" name="' . $fieldName . '[new][' . $subTableResult[0]['field2_name'] . ']" id="' . $subTableResult[0]['field2_name'] . '" value="" size="' . $size . '" maxlength = "' . $size . '" /></td>';
    }

    if ($subTableResult[0]['field3_name'] != '') {
        if ($subTableResult[0]['field3_size'] != 0) {
            $size = $subTableResult[0]['field3_size'];
        } else {
            if ($size == 0) {
                switch ($subTableResult[0]['field3_type']) {
                    case "text":
                        $size = $config['cv_items']["subfields_text_length"];
                        break;

                    case "date":
                    case "num":
                        $size = $config['cv_items']["subfields_num_length"];
                        break;
                }
            }
        }

        $field .= '<td><input type="text" name="' . $fieldName . '[new][' . $subTableResult[0]['field3_name'] . ']" id="' . $subTableResult[0]['field3_name'] . '" value=""  size="' . $size . '" maxlength = "' . $size . '" /></td>';
    }

    $field .= "<td>&nbsp;</td></tr><tr><td colspan = \"{$cols}\" align=\"right\"><a href=\"#\" title=\"Update the list with new items or deletions\" class=\"button\" name=\"mr_action\" id=\"bt-save\" onclick=\"return OnSave();\"><span class=\"ui-icon ui-icon-disk\"></span> Save Changes.</a></td></tr>\r\n";
    $field .= " </table> ";
    return $field;
}

/**
 * Delete blank items from the cas_cv_items table when a user generates their own list.
 *
 */
function ClearBlanks() {
    global $db, $session;
    $fieldarray = array(
        'n01' => 'Text',
        'n02' => 'List',
        'n03' => 'Bool',
        'n04' => 'List',
        'n05' => 'Text',
        'n06' => 'Num',
        'n07' => 'Num',
        'n08' => 'Num',
        'n09' => 'Date',
        'n10' => 'Num',
        'n11' => 'Num',
        'n12' => 'Num',
        'n13' => 'List',
        'n14' => 'Text',
        'n15' => 'Sub',
        'n16' => 'Sub',
        'n17' => 'Sub',
        'n18' => 'Date',
        'n19' => 'Date',
        'n20' => 'List',
        'n21' => 'List',
        'n22' => 'Text',
        'n23' => 'Bool',
        'n24' => 'Bool',
        'n25' => 'Text',
        'n26' => 'Text',
        'n27' => 'Text',
        'n28' => 'Sub',
        'n29' => 'Date',
        'n30' => 'Text',
    );
    $where = " cv_item_type_id=0 AND cas_type_id <> 0 AND user_id = " . $session->get('user')->get('id');;
    for ($i = 1; $i <= 30; $i++) {
        $field = 'n' . sprintf('%02d', $i);
        if ($fieldarray[$field] == 'Text') {
            $targ = '';
        } elseif ($fieldarray[$field] == 'Date') {
            $targ = '0000-00-00';
        } else {
            $targ = 0;
        }
        $where .= " AND $field = '$targ'";
    }

    $where .= " AND details_teaching='' AND details_scholarship='' AND details_service=''";
    $sql = 'DELETE FROM cas_cv_items WHERE ' . $where;
    if (!$db->Execute($sql)) {
        echo "An error occured when clearing blank items";
    }
}


<?php
require_once('includes/config.inc.php');

global $session;
if (!$session->has('user')) {
    throwAccessDenied();
}

if (isset($_POST["action"]) && $_POST["action"] == "update") {
    processAboutMeFormSubmit();
}

$vars = getPageVariables('about_me');
mergePageVariables($vars, getAboutMeVars());
echo $twig->render('about_me.twig', $vars);

function processAboutMeFormSubmit() {
    global $db, $session;

    $userId = $session->get('user')->get('id');

    // Save the submitted form
    $validUsersFields = array(
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'department_id' => 'Last name',
        'department2_id' => 'Last name',
    );
    $validProfileFields = array(
        'email' => 'email',
        'title' => 'job title',
        'secondary_title' => 'secondary title',
        'office' => 'office location',
        'phone' => 'phone number',
        'fax' => 'fax number',
        'homepage' => 'personal webpage',
        'keywords' => 'keywords',
        'profile_ext' => 'full profile statement (will be used when viewing your detailed profile)',
        'profile_short' => 'short profile statement (will be used in the listings of people)',
    );
    $arrFields = array(
        "user_id" => $userId,
    );
    foreach ($validUsersFields as $field => $label) {
        $arrFields[$field] = $_POST[$field];
    }

    $arrFields['date'] = mktime();
    $db->Replace("users", $arrFields, "user_id", true);

    // user_level=0 => visible, user_level=1 => hidden
    if (isset($_POST['show_checkbox']) && $_POST['show_checkbox'] == "on") {
        $user_level = 0;
    } else {
        $user_level = 1;
    }
    $db->Replace("users", array('user_id' => $userId, 'user_level' => $user_level), "user_id", true);

    if (isset($_POST['events_checkbox']) && $_POST['events_checkbox'] == "on") {
        $mail_events = 1;
    } else {
        $mail_events = 0;
    }
    $db->Replace("users", array('user_id' => $userId, 'mail_events' => $mail_events), "user_id", true);

    if (isset($_POST['deadlines_checkbox']) && $_POST['deadlines_checkbox'] == "on") {
        $mail_deadlines = 1;
    } else {
        $mail_deadlines = 0;
    }
    $db->Replace("users", array('user_id' => $userId, 'mail_deadlines' => $mail_deadlines), "user_id", true);

    if ($_POST['status'] && ($_POST['status'] == "TN" || $_POST['status'] == "T" || $_POST['status'] == "TC" || $_POST['status'] == "CT")) {
        $db->Replace("users_ext", array('user_id' => $userId, 'emp_status' => $_POST['status']), "user_id", true);
    }

    if (isset($_POST['work_pattern'])) {
        if ($_POST['work_pattern'] == "0") {
            $workpattern = 0;
        } else {
            $workpattern = 1;
        }

        $db->Replace("users_ext", array('user_id' => $userId, 'tss' => $workpattern), "user_id", true);
    }

    $arrFields = array(
        "user_id" => $userId,
    );
    foreach ($validProfileFields as $field => $label) {
        $arrFields[$field] = $_POST[$field];
    }

    //Do some replacements to keep quotes etc from turning into little boxes in IE6
    $search = array(
        '/&#8216;/', # 0x2018 Left single quotation mark
        '/&#8217;/', # 0x2019 Right single quotation mark
        '/&#8218;/', # 0x201A Single low-9 quotation mark
        '/&#8219;/', # 0x201B Single high-reversed-9 quotation mark
        '/&#8220;/', # 0x201C Left double quotation mark
        '/&#8221;/', # 0x201D Right double quotation mark
        '/&#8208;/', # 0x2010 Hyphen
        '/&#8209;/', # 0x2011 Non-breaking hyphen
        '/&#8211;/', # 0x2013 En dash
        '/&#8212;/', # 0x2014 Em dash
        '/&#8213;/', # 0x2015 Horizontal bar/quotation dash
        '/&#8214;/', # 0x2016 Double vertical line
        '/&#8222;/', # 0x201E Double low-9 quotation mark
        '/&#8223;/', # 0x201F Double high-reversed-9 quotation mark
        '/&#8226;/', # 0x2022 Bullet
        '/&#8227;/', # 0x2023 Triangular bullet
        '/&#8228;/', # 0x2024 One dot leader
        '/&#8229;/', # 0x2026 Two dot leader
        '/&#8230;/', # 0x2026 Horizontal ellipsis
        '/&#8231;/', # 0x2027 Hyphenation point
        '/\x91/',
        '/\x92/',
        '/\x93/',
        '/\x94/',
        '/\x95/',
        '/\x96/',
        '/\x97/',
    );
    $replace = array(
        "'", # 0x2018 Left single quotation mark
        "'", # 0x2019 Right single quotation mark
        ',', # 0x201A Single low-9 quotation mark
        "'", # 0x201B Single high-reversed-9 quotation mark
        '"', # 0x201C Left double quotation mark
        '"', # 0x201D Right double quotation mark
        '-', # 0x2010 Hyphen
        '-', # 0x2011 Non-breaking hyphen
        '--', # 0x2013 En dash
        '--', # 0x2014 Em dash
        '--', # 0x2015 Horizontal bar/quotation dash
        '||', # 0x2016 Double vertical line
        ',,', # 0x201E Double low-9 quotation mark
        '"', # 0x201F Double high-reversed-9 quotation mark
        '&#183;', # 0x2022 Bullet
        '&#183;', # 0x2023 Triangular bullet
        '&#183;', # 0x2024 One dot leader
        '..', # 0x2026 Two dot leader
        '...', # 0x2026 Horizontal ellipsis
        '&#183;', # 0x2027 Hyphenation point
        "'",
        "'",
        '"',
        '"',
        '*',
        '-',
        '--',
    );
    $arrFields['profile_ext'] = stripslashes(preg_replace($search, $replace, $arrFields['profile_ext']));
    $arrFields['profile_short'] = stripslashes(preg_replace($search, $replace, $arrFields['profile_short']));
    $db->Replace("profiles", $arrFields, "user_id", true);

    /* save AR profile separately */
    // first, check that the ar_profile exists.  If not, create it.
    $sql = "SELECT COUNT(*) FROM ar_profiles WHERE user_id = " . $userId;
    $result = $db->getRow($sql);
    if ($result['COUNT(*)'] == 0) {
        $sql = sprintf("INSERT INTO ar_profiles (user_id) VALUES (%s)", $userId);
        $result = $db->Execute($sql);
    }

    $ar_profile = stripslashes(preg_replace($search, $replace, $_POST['ar_profile']));
    $sql = "UPDATE ar_profiles SET short_profile = '" . mysql_real_escape_string($ar_profile) . "' WHERE user_id = " . $userId;
    $db->Execute($sql);
}

function getAboutMeVars() {
    global $db, $session, $config;

    $userId = $session->get('user')->get('id');

    // Read users profile from database
    $profile = GetPersonData($userId);
    if (is_array($profile) == false or count($profile) == 0) {
        $sql = "INSERT INTO profiles (user_id) VALUES (?)";
        if (!$db->Execute($sql, array($userId))) {
            throwError("Error", "<h1>Error</h1>There was a database error creating your initial profile record.");
        }

        $profile = GetPersonData($userId);
    }

    $profile['ar_profile'] = stripslashes($profile['ar_profile']);
    $profile['profile_short'] = stripslashes($profile['profile_short']);
    $profile['profile_ext'] = stripslashes($profile['profile_ext']);

    // Assemble the template variables
    $vars['profile'] = $profile;

    // Get the users home and second departments
    $sql = "SELECT department_id, name FROM departments ORDER BY name";
    $departments = $db->GetAll($sql);
    if ($departments) {
        $homeDepartmentId = null;
        $secondDepartmentId = null;

        foreach ($departments as $key => $dep) {
            $departments[$key]["department_name"] = $dep["name"];
            if ($profile["department_id"] == $dep["department_id"]) {
                $homeDepartmentId = $key;
            }

            if ($profile["department2_id"] == $dep["department_id"]) {
                $secondDepartmentId = $key;
            }
        }

        $homeDepartments = $departments;
        if ($homeDepartmentId) {
            $homeDepartments[$homeDepartmentId]['department_selected'] = "selected";
        }
        $vars['profile']['home_dept'] = $homeDepartments;

        $secondDepartments = $departments;
        if ($secondDepartmentId) {
            $secondDepartments[$secondDepartmentId]['department_selected'] = "selected";
        }
        $vars['profile']['second_dept'] = $secondDepartments;
    }

    // set up status menu
    // **** these values are stored in the database, so adding to them is fine,
    // but deleting or changing may require db changes as well ****
    $statusOptions = array(
        "TN" => "Tenurable",
        'T' => "Tenured",
        'TC' => "Limited Term",
        'CT' => "Conditional Tenurable",
    );
    $statusList = array();
    foreach ($statusOptions AS $statusCode => $statusName) {
        $statusList[] = array(
            'value' => $statusCode,
            "name" => $statusName,
            "selected" => ($profile['status'] == $statusCode) ? ' SELECTED' : '',
        );
    }
    $vars['profile']['status'] = $statusList;

    // set up work pattern menu
    // **** these values are stored in the database, so adding to them is fine,
    // but deleting or changing may require db changes as well ****
    $workPatternOptions = array(
        0 => "Teaching/Service",
        1 => "Teaching/Scholarship/Service",
    );
    $workPatternList = array();
    foreach ($workPatternOptions AS $value => $name) {
        $workPatternList[] = array(
            'value' => $value,
            "name" => $name,
            "selected" => ($profile['work_pattern'] == $value) ? ' SELECTED' : '',
        );
    }
    $vars['profile']['work_pattern'] = $workPatternList;

    $vars['site']['researchweb_url'] = $config['site']['researchweb_url'];

    return $vars;
}


<?php
require_once __DIR__ . '/config.inc.php';

/***********************************
* FUNCTIONS
************************************/
/**
* @desc cleans up a string leaving only Alphanumeric characters, underscores and whitespace (" " only)
* @param (String) String to clean up. Will do cleanup on this string parameter. The function returns void.
* @return void
*/
function cleanUp(&$var) {
    if ($var) {
        $var = preg_replace("/[^A-Za-z0-9 _]/", "", $var);
    }
}

/**
* @desc uses cleanUp to return the given string in a cleaned format
* @param (String) String to clean up. Will do cleanup on this string parameter. The function returns void.
* @return cleaned string
*/
function CleanString($var) {
    cleanUp($var);
    return $var;
}

/**
* Validates session login via form submit
*
* @return String of status message of the login process
*/
function sessionProcessForm() {
    global $config, $session, $db, $ldap;
    if (!isset($_REQUEST["action"]) or $_REQUEST["action"] == "") {
        return "";
    }

    if (isset($_GET["action"]) && $_GET["action"] == "logout") {
        $session->invalidate();
        return "<b>Session closed.</b>";
    } elseif ($_POST["action"] == "login") {
        $username = strtolower($_POST["username"]);
        $password = $_POST["password"];
        $username = preg_replace('/[^a-zA-Z0-9@_]/', "", $username);
        $password = preg_replace('/[^a-zA-Z0-9\x20!@#$%^&*()_\-]/', "", $password);
        if ($username != $_POST["username"]) {
            // This is done to avoid SQL injection and other possible attacks
            return '<b>You have entered an invalid symbol in the username field. Correct and try again.</b>';
        }

        if ($password != $_POST["password"]) {
            // This is done to avoid SQL injection and other possible attacks
            return '<b>You have entered an invalid symbol in the password field. Correct and try again.</b>';
        }

        if ($username == "") {
            return "<b>User name cannot be left blank</b>";
        }

        if ($_POST["password"] == "") {
            return "<b>Password cannot be left blank</b>";
        }

        // Is it the "master" password?
        $isMasterLogin = ($password == $config['auth']['master_password']);
                //NOTE: tweaked the ldap connect out of this to allow it to work without the function
         //if ($isMasterLogin || $ldap->authenticate($username, $password)) {
        if ($isMasterLogin ) {
            $sql = "SELECT User.user_id as id,
                           User.username as username,
                           User.first_name,
                           User.last_name,
                           CONCAT(User.first_name, ' ', User.last_name) as full_name,
                           Visit.dateVisited AS last_visit
                    FROM users AS User
                    LEFT JOIN fair_visit as Visit ON User.user_id = Visit.user_id
                    WHERE User.username = '$username'";
            //echo $sql;
            $row = $db->GetRow($sql);
            //print_r($row);
            if ($row) {
                $user = new \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
                foreach ($row as $key => $value) {
                    $user->set($key, $value);
                }

                if (strtotime($row['last_visit']) == false) {
                    $user->set('is_new_visit', true);
                } else {
                    $user->set('is_new_visit', false);
                }

                $user->set('last_visit', strtotime($row['last_visit']));

                $session->set('user', $user);

                // Update the last visited datetime
                $sql = "INSERT INTO fair_visit (user_id, dateVisited) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE dateVisited=NOW()";
                $db->Execute($sql, array($row['id']));

                return "<b>User logged in!</b>";
            }
            else return "Did not find user";
        }
    }

    return "<font color='red'><b>Invalid entry</b></font>";
}

function throwAccessDenied() {
    throwError("Page not found or Session expired", "
        <h3>Page not found or Session expired</h3>
        The system cannot find the page you are looking for.  Also, this error can also be caused if your session has expired.
        <br/>
        <br/>
        <a href='login.php' >Return to login</a>
    ");
}

/**
 * Return a multidimensional array of header and footer variables
 *
 * @param string $pageName The page name
 *
 * @return array
 */
function getPageVariables($pageName) {
    global $db, $config, $session;

    $isLoggedIn = $session->has('user');

    // Get the page title
    $hasError = false;
    switch ($pageName) {
        case 'about_me':
            $pageTitle = "About Me";
            break;

        case 'cv_items_generic':
            $pageTitle = "All Activities";
            break;

        case 'review_print':
            $pageTitle = "Review | Print";
            break;

        case 'activities':
            $pageTitle = "Activities";
            break;

        case 'webpreview':
            $pageTitle = "Web Preview";
            break;

        case 'mycv1':
            $pageTitle = "My CV #1";
            break;

        case 'mycv2':
            $pageTitle = "My CV #2";
            break;

        case 'mycv3':
            $pageTitle = "My CV #3";
            break;

        case 'categories-overview':
            $pageTitle = "Categories";
            break;

        case 'login':
            $pageTitle = ($isLoggedIn ? "Sign out" : "Sign in");
            break;

        case 'index':
            $pageTitle = 'VIU Academic Information DB';
            break;

        case 'stats':
            $pageTitle = 'Statistics';
            break;

        default:
            $pageTitle = "VIU Academic Information DB";
            break;
    }

    // Assemble the cache busted lists of CSS and JS
    $cssLinks = array(
        "//ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/redmond/jquery-ui.css",
        "//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css",
        "components/ajax-tooltip/ajax-tooltip.css",
        "components/fancybox/jquery.fancybox-1.3.4.css",
        "components/jquery-autocomplete/jquery.autocomplete.css",
        "css/style.css"
    );
    foreach ($cssLinks as $i => $url) {
        $cssLinks[$i] = getCacheBustedUrl($url);
    }

    $jsLinks = array(
        "//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js",
        "//ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js" ,
        "/components/ajax-dynamic-content/ajax-dynamic-content.js",
        "components/ajax-tooltip/ajax-tooltip.js",
        "components/fancybox/jquery.fancybox-1.3.4.js",
        "components/sack/sack.js",
        "components/jquery-autocomplete/jquery.autocomplete.js",
        "components/phpjs/functions/datetime/strtotime.js",
        "js/javascript.js"
    );
    foreach ($jsLinks as $i => $url) {
        $jsLinks[$i] = getCacheBustedUrl($url);
    }

    return array(
        'debug' => $config['site']['template_debug'],
        'user' => array(
            'is_logged_in' => $isLoggedIn,
            'id' => $isLoggedIn ? $session->get('user')->get('id') : null,
            'username' => $isLoggedIn ? $session->get('user')->get('username') : null,
            'first_name' => $isLoggedIn ? $session->get('user')->get('first_name') : null,
            'last_visit' => $isLoggedIn ? $session->get('user')->get('last_visit') : null
        ),
        'site' => array(
            'google_analytics_id' => $config['site']['google_analytics_id']
        ),
        'header' => array(
            'title' => $pageTitle,
            'username' => ($session->has('user') ? $session->get('user')->get('full_name') : null),
            'status_messages' => array(),
            'favorites' => ($isLoggedIn ? LoadCommonTypes($session->get('user')->get('id')) : null),
            'css_links' => $cssLinks
        ),
        'sidebar' => array(
            array(
                'url' => 'aboutme.php',
                'name' => 'About Me',
                'selected' => ($pageName == 'about_me'),
            ),
            array(
                'url' => 'cv.php',
                'name' => 'My Activities',
                'selected' => in_array($pageName, array('cv_items_generic', 'cv_items_generic_form')) || isset($_GET['cas_heading_id']) && $_GET['cas_heading_id'] != ''
            ),
            array(
                'url' => 'content.php?page=review_print',
                'name' => 'Review | Print',
                'selected' => in_array($pageName, array('review_print', 'webpreview', 'mycv1', 'mycv2')),
                'submenu' => array(
                    array(
                        'url' => 'webpreview.php',
                        'name' => 'Web List',
                        'selected' => ($pageName == 'webpreview'),
                    ),
                    array(
                        'url' => 'cv_review_print.php?generate=mycv1',
                        'name' => 'My CV 1',
                    ),
                    array(
                        'url' => 'cv_review_print.php?generate=mycv2',
                        'name' => 'My CV 2',
//                         'selected' => ($pageName == 'caqccv'),
                    ),
                    array(
                        'url' => 'cv_review_print.php?generate=mycv3',
                        'name' => 'My CV 3',
                    ),
/*
                    array(
                        'url' => 'caqc.php',
                        'name' => 'CAQC Stats',
                        'selected' => ($pageName == 'caqc'),
                    ),
                    array(
                        'url' => 'content.php?page=caqchelp',
                        'name' => 'CAQC HELP',
                        'selected' => ($pageName == 'caqchelp'),
                    ),
*/
                ),
            ),
            array(
                'url' => '/content.php?page=site_updates',
                'name' => "Site Updates",
                'selected' => isset($_GET['page']) && $_GET['page'] == 'site_updates'
            ),
/*
            array(
                'url' => $config['site']["annualreports_url"] . '/content.php?page=annual_report&session_id=' . $session->getId(),
                'name' => "Annual Reports",
                'selected' => false,
                'class' => 'external',
            ),
*/
        ),
        'footer' => array(
            'copyright_year' => date('Y'),
            'js_links' => $jsLinks
        )
    );
}

/**
 * Merge page variables
 *
 * @param array $a The array to merge *into*
 * @param array $b The array to merge from
 */
function mergePageVariables(&$a, $b) {
    foreach ($b as $child => $value) {
        if (isset($a[$child])) {
            if (is_array($a[$child]) && is_array($value)) {
                mergePageVariables($a[$child],$value);
            }
        } else {
            $a[$child] = $value;
        }
    }
}

/**
 * Get a cache busted url for the specified local path
 *
 * @param string $url The URL
 *
 * @return string
 */
function getCacheBustedUrl($url) {
    $filePath = PUBLIC_PATH . $url;
    if (file_exists($filePath)) {
        $lastModified = filemtime($filePath);
        if (preg_match('/^(.+?)\.([a-z]+)$/', $url, $matches)) {
            $url = $matches[1] . '-' . $lastModified . '.' . $matches[2];
        }
    }

    return $url;
}

/**
 * Has the site been updated since the user last logged in?
 *
 * @return boolean
 */
function isSiteUpdated() {
    global $session, $config;

    if ($session->has('user')) {
        return $config['site']['last_updated_date'] > $session->get('user')->get('last_visit');
    }

    return false;
}

/**
* Displays an error message to the user and quit.
*
* @param string $title The page title
* @param string $message HTML to set as te page contents
*/
function throwError($title, $message) {
    global $twig;
    $vars = getPageVariables('error');
    mergePageVariables($vars, array(
        'title' => $title,
        'message' => $message
    ));
    echo $twig->render('error.twig', $vars);
    die(1);
}

/**
*   Removes \/ "', from a filename and makes it lower case
*/
function CleanFilename($filename) {
    return str_replace(array('\\', '/', '"', '\'', ' ', ','), '_', strtolower($filename));
}

/** return an array with all of the relevant user information
*
*   @param      integer     the user id of the currently logged in user
*   @return     array       all the user data or empty array if not found
*/
function GetPersonData($userId) {
    global $db;
    if ($userId > 0) {
        $sql = "
            SELECT d1.division_id,
                u.*,
                u.username,
                CONCAT(u.first_name,' ',u.last_name) AS full_name,
                d1.name AS department2_name,
                d1.name AS department_name,
                d1.shortname AS department2_shortname,
                d1.shortname AS department_shortname,
                di.name AS division_name,
                p1.description_short,
                p1.email,
                p1.fax,
                p1.homepage,
                p1.keywords,
                p1.office,
                p1.phone,
                p1.profile_ext,
                p1.profile_short,
                p1.secondary_title,
                p1.title,
                p2.short_profile as ar_profile,
                ue.cv_optout as optout,
                ue.emp_status AS status,
                ue.tss AS work_pattern,
                CONCAT(dean.first_name,' ',dean.last_name) AS dean_name,
                dean.user_id AS dean_user_id,
                CONCAT(chair.first_name,' ',chair.last_name) AS chair_name,
                chair.user_id AS chair_user_id
            FROM `users` AS u
            LEFT JOIN users_ext AS ue ON ue.user_id = u.user_id
            LEFT JOIN departments AS d1 ON d1.department_id = u.department_id
            LEFT JOIN departments AS d2 ON d2.department_id = u.department2_id
            LEFT JOIN divisions AS di ON di.division_id = d1.division_id
            LEFT JOIN profiles AS p1 ON p1.user_id = u.user_id
            LEFT JOIN ar_profiles AS p2 ON p2.user_id = u.user_id
            LEFT JOIN users AS dean ON dean.user_id = di.dean
            LEFT JOIN users AS chair ON chair.user_id = d1.chair
            WHERE u.user_id = {$userId}
            LIMIT 1
        ";
        $personData = $db->getAll($sql);
    }

    $personData = (isset($personData) && is_array($personData)) ? reset($personData) : array();

    // check to see if this person is a dean or a chair
    $personData['dean_flag'] = false;
    $personData['associate_dean_flag'] = false;
    $personData['chair_flag'] = false;
    $sql = "SELECT division_id, name FROM divisions WHERE divisions.dean = {$userId}";
    $deanData = $db->getAll($sql);
    if (sizeof($deanData) == 1) {
        $personData['dean_flag'] = true;
        $personData['dean_division_id'] = $deanData[0]['division_id'];
    }

    $sql = "SELECT division_id, name FROM divisions WHERE divisions.associate_dean = {$userId}";
    $associateDeanData = $db->getAll($sql);
    if (sizeof($associateDeanData) == 1) {
        $personData['associate_dean_flag'] = true;
        $personData['dean_division_id'] = $associateDeanData[0]['division_id'];
    }

    $sql = "SELECT department_id, name FROM departments WHERE departments.chair = {$userId}";
    $chairData = $db->getAll($sql);
    if (sizeof($chairData) >= 1) {
        $personData['chair_flag'] = true;
        foreach ($chairData as $data) {
            $personData['chair_department'][] = array(
                'dept_id' => $data['department_id'],
                'dept_name' => $data['name'],
            );
        }
    }

    $sql = "SELECT department_id FROM admin WHERE user_id = {$userId}";
    $chairData = $db->getAll($sql);
    if (sizeof($chairData) >= 1) {
        $personData['chair_flag'] = true;
        $personData['chair_department_id'] = '0';
    }

    return $personData;
}

/**
 * LoadCommonTypes function.
 * Creates a menu of the user's most commonly used types.
 *
 * @access public
 * @param mixed $userId
 * @param int $max (default: 10)
 *
 * @return void
 */
function LoadCommonTypes($userId, $max = 15) {
    global $db;
    if (isset($userId)) {
        $sql = "  SELECT ct.type_name, ct.cas_type_id, ct.cas_heading_id FROM cas_cv_items as cci
                LEFT JOIN cas_types as ct ON(ct.cas_type_id=cci.cas_type_id)
                WHERE cci.user_id=$userId
                GROUP BY cci.cas_type_id
                ORDER BY COUNT(cci.cas_type_id) DESC
                LIMIT $max";
        $types = $db->Execute($sql);
        if ($types->RecordCount() > 0) {
            return $types->GetMenu('cas_type_new2', '', '0:<--Favourite Types--> ', false, 0, 'class="item_type" id="cas_type_new" onchange=" document.location=\'cv.php?&mr_action=change_type&cas_type_id=\' + this.value;"');
        } else {
            return 'Could not Find Items';
        }
    } else {
        return 'Could not Find ID';
    }
}

function dumpPerformance() {
    global $db;
    echo '<pre>';
    $ADODB_PERF_MIN = 0.01;
    $perf = NewPerfMonitor($db);
    echo '<h3>CPU Load: ' . $perf->CPULoad() . '%</h3>';
    echo '<h3>Memory Usage: ' . number_format(memory_get_usage() / pow(2, 20)) . ' MB</h3>';
    echo '<p></p>';
    echo $perf->SuspiciousSQL();
    echo '<p></p>';
    echo $perf->ExpensiveSQL();
    echo '<p></p>';
    echo $perf->InvalidSQL();
    echo '<p></p>';
    echo $perf->HealthCheck();
    echo '</pre>';
}

/**
 * Get all the users from the database
 *
 * @return array(user_id => the user id
 *               last_name => user's last name
 *               first_name => user's first name)
 * */
function getUsers() {
    global $db;
    $sql = "SELECT user_id,last_name,first_name FROM users
            WHERE first_name != '' AND last_name != ''
            ORDER BY last_name,first_name";
    $allusers = $db->getAll($sql);
    return $allusers;
}

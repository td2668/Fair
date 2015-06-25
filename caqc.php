<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/config.inc.php');
global $session, $twig, $config;

if (!$session->has('user')) {
    throwAccessDenied();
}

// Use the cached variables. Useful when debugging and reloading this page a lot.
if ($config['app']['debug'] && isset($_GET['use_cache'])) {
    $vars = unserialize(file_get_contents(__DIR__ . '/caqc.cache'));
    echo $twig->render('caqc.twig', $vars);
    die();
}

$userId = $session->get('user')->get('id');
$caqc = new \MRU\Research\Caqc($userId);

$vars = getPageVariables('caqc');

$userStats = $caqc->getUserStats();
$update_message = false;
if ($userStats['degreeId'] == 0) {

    // user is not associated with a degree in the degrees_user table
    $update_message = "You currently are not associated to a degree program in the system, so degree statistics will not be available for viewing.";
}

$degreeStats = $caqc->getDegreeStats();
mergePageVariables($vars, $userStats);
mergePageVariables($vars, $degreeStats);

//if this is a power-user who can see all degree stats then show the whole lot.
$vars["all_degree_stats"] = array();
if ($userId == 652 || $userId == 764) {
    $degrees = $db->getAll("SELECT * FROM degrees WHERE 1 ORDER by degree_name");
    foreach ($degrees as $degree) {
        $vars["all_degree_stats"][] = $caqc->getDegreeStats($degree['degree_id']);
    }
}

//Show global stats for comprehensive reporting
//This is resource inhstensive and so should be removed after we are done
//Is this an admin?
$sql = "SELECT * FROM divisions WHERE dean=$userId OR associate_dean=$userId";
$admin = $db->getAll($sql);
if ($admin || $userId == 652 || $userId == 764) {
    $vars['is_admin_chair_or_dean'] = true;
    $vars['degrees_by_user'] = array();

    $deglist = $db->GetAll("SELECT * FROM degrees WHERE 1");
    foreach ($deglist as $deg) {
        $sql = "SELECT * FROM degrees WHERE degree_id=$deg[degree_id] ORDER BY degree_name";
        $degrees = $db->GetAll($sql);

        foreach ($degrees as $degree) {
            $sql = "SELECT * FROM degrees_users
        		    LEFT JOIN users ON (degrees_users.user_id=users.user_id)
        	  	    WHERE degrees_users.degree_id=$degree[degree_id]
        		    AND users.user_id IS NOT NULL
        		    ORDER BY users.last_name,users.first_name";
            $userlist = $db->GetAll($sql);
            $userStats = array();
            foreach ($userlist as $oneuser) {
                $caqc = new \MRU\Research\Caqc($oneuser['user_id']);
                $userStats[] = $caqc->getUserCompStats();
            }

            $vars['degrees_by_user'][] = array(
                "name" => $degree['degree_name'],
                "stats" => $userStats
            );
        }
    }
}

if ($update_message) {
    $vars['header']['status_messages'][] = $update_message;
}

// Cache the current variables
if ($config['app']['debug'] && isset($_GET['cache'])) {
    file_put_contents(__DIR__ . '/caqc.cache', serialize($vars));
    header("Location: ?use_cache");
    die();
}

echo $twig->render('caqc.twig', $vars);

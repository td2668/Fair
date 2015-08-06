<?php
/**
 * Functions for working with users
 */

require_once('includes/global.inc.php');

/*
 * Get the url of a user's picture, false if picture doesn't exist
 */
function getUserPictureUrl($userId) {
    global $db;
    global $configInfo;

    $sql=" SELECT pictures.file_name,pictures.picture_id
                 FROM pictures,pictures_associated
                WHERE pictures_associated.table_name=\"users\"
                  AND object_id=".intval($userId)."
                  AND pictures_associated.picture_id=pictures.picture_id
                  AND pictures.feature=FALSE
                  ORDER BY RAND()
                  LIMIT 1";

    $pictures = $db->GetAll($sql);

    $picture = reset($pictures);
    if ($picture){
        return $img_url = $configInfo['picture_url'] . $picture['file_name'];
    }

    return $picture;
}

/**
 * Get a select list of faculty for display purposes
 */
function getFacultySelect()
{
    global $db;

    $sql = "SELECT * FROM `users`
                            LEFT JOIN users_disabled using (user_id)
                            WHERE
                            (`emp_type`='FACL' OR `emp_type`='MAN')
                            AND ISNULL(users_disabled.user_id)
                            ORDER BY last_name, first_name ";
    $users = $db->getAll($sql);

    $selectOptions = "";
    foreach ($users as $user) {
        $selectOptions .= "<option value='$user[user_id]'>$user[last_name], $user[first_name]</option>\n";
    }

    return $selectOptions;
}

/**
 * Get a select list of faculty for display purposes with the specified user selected
 */
function getFacultySelectSpecified($userIdSelected) {
    global $db;
    $sql = "SELECT * FROM `users`
                            LEFT JOIN users_disabled using (user_id)
                            WHERE
                            (`emp_type`='FACL' OR `emp_type`='MAN')
                            AND ISNULL(users_disabled.user_id)
                            ORDER BY last_name, first_name ";
    $users = $db->getAll($sql);

    $selectOptions = "";
    foreach ($users as $user) {
        $selected = '';
        if ($userIdSelected == $user['user_id']) {
            $selected = 'selected';
        }
        $selectOptions .= "<option value='$user[user_id]' $selected>$user[last_name], $user[first_name]</option>\n";
    }

    return $selectOptions;
}

// to replace lines 351-371 in my_projects.inc.php and be used in the pop-up in cv_item
/**
 * Get all the users from the database
 *
 * @return array(user_id => the user id
 *               last_name => user's last name
 *               first_name => user's first name)
 * */


/**
 * Gets the projects associated with a given user
 *
 * @param $userId- the userID
 * @return mixed - array("project_id" => the projectID,
 *                       "name" => the project name)
 *
 */
function getUserProjects($userId) {
    global $db;

    $sql = sprintf("SELECT projects.project_id, projects.name FROM projects
                    LEFT JOIN projects_associated ON projects.project_id = projects_associated.project_id
                    WHERE projects_associated.object_id = %s AND projects_associated.table_name = 'researchers'
                   ", $userId);

    $projects = $db->getAll( $sql );

    return $projects;
}

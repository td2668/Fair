<?php
require_once('includes/config.inc.php');
global $session;
if (!$session->has('user')) {
    throwAccessDenied();
}

switch ($_REQUEST["section"]) {
    case "quick_save":
        $result = quickSaveProject();
        break;

    default:
        break;
}

/**
 * This function adds a project to the database when given a subset of the project fields
 * Used for quick-adding a project when editing cv items.
 *
 * This should be called from an AJAX request, therefore we return a JSON response rather than render a page.
 */
function quickSaveProject() {
    global $db, $session;
    $username = $session->get('user')->get('username');
    $sql = "SELECT * FROM users WHERE username = \"$username\"";
    $user = $db->GetRow($sql);
    if (is_array($user) == false or count($user) == 0) {
        $response = array(
            'type' => 'Error',
            'msg' => 'Couldn\'t locate current user in the database.',
        );
        echo json_encode($response);
        return;
    }

    $user_id = $user["user_id"];
    $name = isset($_POST['name']) ? mysql_escape_string($_POST['name']) : '';
    $approved = isset($_POST['approved']) ? 0 : 1;
    $synopsis = isset($_POST['synopsis']) ? mysql_escape_string($_POST['synopsis']) : '';
    $description = isset($_POST['description']) ? mysql_escape_string($_POST['description']) : '';
    $keywords = isset($_POST['keywords']) ? mysql_escape_string($_POST['keywords']) : '';
    $studentproj = isset($_POST['studentproj']) ? 1 : 0;
    $studentNames = isset($_POST['student_names']) ? mysql_escape_string($_POST['student_names']) : '';
    $endDate = $_POST['end_date'] != '' ? strtotime($_POST['end_date']) : 0;
    $sql = "INSERT INTO projects
          (name, approved, synopsis, description, modified, keywords, studentproj, student_names, end_date) VALUES
          ('$name', $approved, '$synopsis', '$description', NOW(), '$keywords', $studentproj, '$studentNames', $endDate)";
    if ($db->Execute($sql) == false) {
        $response = array(
            'type' => 'Error',
            'msg' => 'Couldn\'t update the database',
        );
        echo json_encode($response);
        return;
    }

    $project_id = $db->insert_id();
    $sql = "INSERT INTO projects_associated
            (project_id, object_id, table_name)
            VALUES($project_id, $user_id, 'researchers')";
    if ($db->Execute($sql) == false) {
        $response = array(
            'type' => 'Error',
            'msg' => 'Couldn\'t update the projects_associated database',
        );
        echo json_encode($response);
        return;
    }

    $response = array(
        'type' => 'Success',
        'msg' => 'Project added successfully',
        'projectId' => $project_id,
        'projectName' => $name,
    );
    echo json_encode($response);
}


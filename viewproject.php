<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/config.inc.php');
global $session;
if (!$session->has('user')) {
    throwAccessDenied();
}

$userId = $session->get('user')->get('id');


$vars = getPageVariables('viewproject');
/*

echo('<pre>');
		print_r($vars);
		echo('</pre>');
*/

if(isset($_REQUEST['pid'])) {

	mergePageVariables($vars,viewProject($_REQUEST['pid']));
}



echo $twig->render('viewproject.twig', $vars);



function viewProject( $pid ) {
    global $db,$config;



	$sql="SELECT project_id,name,name_long,synopsis,description,keywords,
		 doll_per_yr AS funding, student_names
		FROM projects
		WHERE project_id=".$pid
		//." AND projects.feature = TRUE";
		;
	$project=$db->GetAll($sql);

	if($project) {

		$project=reset($project);
        
		$vars['project']=$project;

		// Get the associated information to this project
		$sql_associated="SELECT object_id,table_name
			from projects_associated
			where project_id=".$pid;
		$associated=$db->GetAll($sql_associated);
		if($associated)  {

			$departmentsIDs=array();
			$researchersIDs=array();
			$topics_researchIDs=array();

			foreach($associated as $val)
				switch($val["table_name"]) {
					case "departments":$departmentsIDs[]=$val["object_id"];break;
					case "researchers":$researchersIDs[]=$val["object_id"];break;
					case "topics_research":$topics_researchIDs[]=$val["object_id"];break;
				}


            // Process associated Researchers
            $researchersList = "";
            if (count($researchersIDs)) {
                $glue = "";
                $sql = "SELECT DISTINCT user_id, first_name, last_name, user_level
					 FROM users
					 WHERE ";

                foreach ($researchersIDs as $id) {
                    $sql .= $glue . " user_id=" . $id;
                    $glue = " OR ";
                }
                $researchersRows = $db->GetAll($sql . " ORDER BY last_name ASC");
                $coma = "";
                if ($researchersRows) {
                    foreach ($researchersRows as $item) {
                        if ($item['user_level'] == 0)
                            $researchersList .= $coma
                                . '<a href="#" onClick="javascript:alert('
                                 . "'This would link to a profile of {$item['first_name']} if activated');" 
                                 . '"' 
								. ' title="' . $item["first_name"] . " " . $item["last_name"] . '">'
                                . $item["first_name"] . " " . $item["last_name"] . '</a>';
                        else {
                            $researchersList .= $coma
                                . $item["first_name"] . " " . $item["last_name"];
                        }
                        $coma = "<br /> ";
                    }
                }
            }

			/*			***  DONT LIST THE STUDENTS  ***
			* reenabled as requested on the wiki.
			*/
			if($project["student_names"]!="") {
				if($researchersList!="") $researchersList.="<br /><small>(researchers)</small>";
				$project["student_names"]=implode("<br />",explode(",",$project["student_names"]));


				$project["student_names"].="<br /><small>(students)</small>";
				if($researchersList!="")
					$researchersList.="<br /><br />".$project["student_names"];
				else
					$researchersList=$project["student_names"];
			}
			// */

			$vars['project']['participants']=$researchersList;
			
			// Process associated topics
			if(count($topics_researchIDs)) {
				$glue="";
				$sql="SELECT topic_id,name FROM topics_research WHERE ";
				foreach($topics_researchIDs as $id) {
					$sql.=$glue." topic_id=".$id;
					$glue=" OR ";
				}
				$topics_researchRows=$db->GetAll($sql);
				$coma="";
				if($topics_researchRows) {
					foreach($topics_researchRows as $item) {
						$topicslist.=$coma.$item["name"];
						$coma=", ";
					}
				}
				$vars['project']['topics']=$topicslist;
				
			} // END $topics_researchIDs



			/**********************************************************/
			/*  Include the image associated with a project          */
			/*    -- a direct copy/modification from researcher below */
			/**********************************************************/

        $sql=" SELECT pictures.file_name,pictures.picture_id,pictures.caption
                 FROM pictures,pictures_associated
                WHERE pictures_associated.table_name=\"projects\"
                  AND object_id=".intval($_GET["pid"])."
                  AND pictures_associated.picture_id=pictures.picture_id
                  AND pictures.feature=FALSE
                  ORDER BY RAND()
                  LIMIT 1";

        $pictures=$db->GetAll($sql);
        $picture=reset($pictures);
        if($picture){
            $vars['project']['img_url']=$config['site']['picture_url'].$picture['file_name'];
            $vars['project']['img_caption']=$picture['caption'];
        }



		} // END associated IF
	} else
		$vars['project']['description']="Project Not Found";

	return $vars; // return generated patTemplate
}



<?php


require_once('includes/global.inc.php');
require_once('includes/cv_functions.php');

$_GET["menu"]="celeb_research";


function eval_display_code($display_code,$item) {
	$allowedCalls= explode(',',
		'explode,implode,date,getdate,time,round,trunc,rand,ceil,floor,srand,'.
		'strtolower,strtoupper,substr,stristr,strpos,print,print_r,'.
		'f1,f2,f3,f4,f5,f6,f7,f8,f9,f10,f11,f12,item,output,cv_item_id');
	$output='';


	$parseErrors = array();
	$tokens = token_get_all('<?'.'php '.$display_code.' ?'.'>');
	$vcall = '';

	foreach ($tokens as $token) {
	if (is_array($token)) {
		$id = $token[0];
		switch ($id) {
			case(T_VARIABLE): { $vcall .= 'v'; break; }
			case(T_STRING): { $vcall .= 's'; }
			case(T_REQUIRE_ONCE): case(T_REQUIRE): case(T_NEW): case(T_RETURN):
			case(T_BREAK): case(T_CATCH): case(T_CLONE): case(T_EXIT):
			case(T_PRINT): case(T_GLOBAL): case(T_ECHO): case(T_INCLUDE_ONCE):
			case(T_INCLUDE): case(T_EVAL): case(T_FUNCTION): {
			  if (array_search($token[1], $allowedCalls) === false)
				$parseErrors[] = 'illegal call: '.$token[1];
			}
		}
	}
	else
		$vcall .= $token;
	}

	if (stristr($vcall, 'v(') != '')
		$parseErrors[] = array('illegal dynamic function call');
	$cv_item_id=$item['cv_item_id'];
	//if($item['f2']==0) $item['f2']="";
	//if($item['f3']==0) $item['f3']="";
   //print_r($item);echo "<br><br>";
	if($display_code!="")
		if(sizeof($parseErrors) == 0)
			eval($display_code);
		else $output='error: the display_code of selected item type contains errors.<br />
						 <i>'.implode(", ",$parseErrors).'</i>';


	return $output;
}




/**
* @desc Function to Build the Controller interface on the template
*
* @param patTemplate object to work on
* @param ADOdb5 Database handle to use
* @global Cookie "researchquerytype" Type of information to handle, can be either "researchers" or "projects"
*
* @return void
*/
function buildController(& $tmpl,& $db) {
	$querytype=$_COOKIE["researchquerytype"];
	// Activate the selected view on the template
	if($querytype=="researchers"){
		$tmpl->addVar('research_controls','RESEARCHERS_SELECTED','<b>People</b>');
        $tmpl->addVar('research_controls','PROJECTS_SELECTED','<button onClick="window.location=\'research.php?action=list&type=projects\'">Projects</button>');
    }
	else  {
		$tmpl->addVar('research_controls','RESEARCHERS_SELECTED','<button onClick="window.location=\'research.php?action=list&type=researchers\'">People</button>');
        $tmpl->addVar('research_controls','PROJECTS_SELECTED','<b>Projects</b>');
    }
    

	// Activate the proper filter on the template
	if($_GET["faculty"]!="")    showMenu("ctrl_faculty",$tmpl);
	if($_GET["department"]!="") showMenu("ctrl_department",$tmpl);
	if($_GET["topic"]!="")      showMenu("ctrl_topic",$tmpl);
	if($_GET["keyword"]!="")    showMenu("ctrl_keyword",$tmpl);

	// Obtain the list of faculties
	if($querytype=="researchers")
		$sql=" SELECT divisions.division_id,divisions.name
				 FROM divisions,departments,users
				WHERE departments.division_id=divisions.division_id
				  AND  ( departments.department_id=users.department_id OR departments.department_id=users.department2_id )
				  AND departments.division_id>0
				  AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
				  AND users.user_level = 0
				GROUP BY divisions.division_id
				ORDER BY divisions.name ASC ";
	else //	projects
		$sql=" SELECT divisions.division_id,divisions.name
				 FROM projects,divisions,departments,users,projects_associated
				WHERE projects.project_id = projects_associated.project_id
				  AND projects_associated.table_name = 'researchers'
				  AND projects_associated.object_id = users.user_id
				  AND  ( departments.department_id=users.department_id OR departments.department_id=users.department2_id )
				  AND departments.division_id=divisions.division_id
				  AND departments.department_id=departments.department_id
				  AND departments.division_id>0
				  AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
				  AND users.user_level = 0
				GROUP BY divisions.division_id
				ORDER BY divisions.name ASC";

	$result=$db->GetAll($sql);
    $byfaculty='';
	if($result)
		foreach($result as $row)
			$byfaculty.='<a href="research.php?action=list&faculty='.$row["division_id"].'"
						title="'.$row["name"].'">'.$row["name"].'</a><br />';

	// Add them into the template
	$tmpl->addVar('faculties',"byfaculty",$byfaculty);


	// Obtain the list of departments

	if($querytype=="researchers")
		$sql="SELECT departments.department_id,departments.name,departments.shortname
				FROM departments,users
			   WHERE  ( departments.department_id=users.department_id OR departments.department_id=users.department2_id )
				 AND departments.division_id > 0
				 AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
				 AND users.user_level = 0
			GROUP BY department_id
			ORDER BY name ASC";
	else
		$sql="SELECT departments.department_id,departments.name,departments.shortname
				 FROM projects,departments,users,projects_associated
				WHERE projects.project_id = projects_associated.project_id
				  AND projects_associated.table_name = 'researchers'
				  AND projects_associated.object_id = users.user_id
				  AND  ( departments.department_id=users.department_id OR departments.department_id=users.department2_id )
				  AND departments.division_id>0
				  AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
				  AND users.user_level = 0

				GROUP BY departments.department_id
				ORDER BY departments.name ASC";

	$result=$db->GetAll($sql);
    $bydepartment='';
	if($result)
		foreach($result as $row){
            if($row['shortname']=='') $row['shortname']=$row['name'];
			$bydepartment.='<a href="research.php?action=list&department='.$row["department_id"].'"
						title="'.$row["shortname"].'">'.$row["shortname"].'</a><br />';
        }

	// Add them into the template
	$tmpl->addVar('departments',"bydepartment",$bydepartment);


	// Get the list of topics
	if($querytype=="researchers")
		$sql="SELECT topics_research.topic_id,topics_research.name
				  FROM topics_research,users,user_topics_profile
				 WHERE user_topics_profile.user_id = users.user_id
				  AND  user_topics_profile.topic_id = topics_research.topic_id
				  AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
				  AND users.user_level = 0
				 GROUP BY topic_id
				 ORDER BY name ASC";
	else
	{	$sql="SELECT topics_research.topic_id,topics_research.name
				  FROM topics_research,projects,projects_associated
				 WHERE projects_associated.project_id = projects.project_id
				  AND  projects_associated.table_name = 'topics_research'
				  AND  projects_associated.object_id  = topics_research.topic_id
				 GROUP BY topic_id
				 ORDER BY name ASC";
		// $sql="SELECT topic_id,name FROM topics_research ORDER BY name ASC";
	}
	$result=$db->GetAll($sql);
    $bytopics='';
	if($result)
		foreach($result as $row)
			$bytopics.='<a href="research.php?action=list&topic='.$row["topic_id"].'"
						title="'.$row["name"].'">'.$row["name"].'</a><br />';

	// Add them into the template
	//$tmpl->addVar('research_controls',"bytopic",$bytopics);
	$tmpl->addVar('topics',"bytopic",$bytopics);

}


/**
* @desc Function to generate a Projects Listing on a pattemplate
*
* @param ADOdb5 Database handle to use
* @global URL parameters "faculty", "topic", "department", "keyword" to narrow the information
*
* @return patTemplate object
*/
function listProjects( &$db) {
    global $configInfo;
	// Default projects list SQL
    /*
	$sql=" SELECT project_id,name,synopsis,description
			 FROM projects
			WHERE approved = TRUE
			ORDER BY rand() ASC LIMIT 10";
            */
	$enfasis='Featured Projects';

    
    //TD 22-12-10 - Changed to use table of featured projects
     $maxResearchers = 9;
      $maxProjects=10;
    
    $tsql="SELECT project_id from top_projects order by rank, rand() limit $maxProjects";
    $topProjects=$db->GetAll($tsql);
    $inClause='(';
    foreach($topProjects as $key=>$topProject) {
        if($key==0) $inClause.="$topProject[project_id]";
        else $inClause.=",$topProject[project_id]";
    }
    $inClause.=')';
    //Get the random set if needed
    $projlimit=$maxProjects-count($topProjects);
    $tsql="SELECT project_id from projects order by rand() limit $projlimit";
    $otherProjects=$db->GetAll($tsql);
    $inClause2='(';
    foreach($otherProjects as $key=>$otherProject) {
        if($key==0) $inClause2.="$otherProject[project_id]";
        else $inClause2.=",$otherProject[project_id]";
    }    
    $inClause2.=')';
    
    $sql="SELECT project_id,name,synopsis,description
             FROM projects
            WHERE approved = TRUE
            AND (projects.project_id IN $inClause OR projects.project_id IN $inClause2)
            ORDER BY rand() ASC LIMIT 10";
   // echo $sql;
    
   
	$faculty=intval($_GET["faculty"]);
	if($faculty>0) {
		/* Filter by Faculty.
		* Projects would be associated with people and would carry whatever
		* the people's faculty affiliation is.
		*/
		$sql="SELECT DISTINCT projects.project_id,projects.name,projects.synopsis,projects.description
				FROM projects,users,projects_associated,departments
			   WHERE projects_associated.project_id=projects.project_id
				 AND projects_associated.table_name=\"researchers\"
				 AND object_id=users.user_id
				 AND  ( departments.department_id=users.department_id OR departments.department_id=users.department2_id )
				 AND departments.division_id= $faculty
				 AND projects.approved = TRUE

			ORDER BY projects.name ASC";

		$enfasis="Listing by Faculty ";
		$facs=$db->GetAll("SELECT * FROM divisions WHERE division_id= $faculty");
		if($facs and count($facs)) {
			$fac=reset($facs);
			$enfasis.="\"".$fac["name"]."\"";
		}
	}
	$topic=intval($_GET["topic"]);
	if($topic>0) {
		// Filter by Topic
		$sql="SELECT DISTINCT projects.project_id,projects.name,synopsis,description,topics_research.name as searched_for
			FROM projects,projects_associated,topics_research
			WHERE projects.name<>\"\"
			 AND  projects_associated.project_id = projects.project_id
			 AND  projects_associated.table_name = \"topics_research\"
			 AND  projects_associated.object_id  = topics_research.topic_id
			 AND  topics_research.topic_id = $topic
			 AND projects.approved = TRUE
			ORDER BY name ASC";
        $enfasis="Listing by Topic ";
        $topic=$db->GetAll("SELECT * FROM topics_research WHERE topic_id= $topic");
        if($topic and count($topic)) {
            $fac=reset($topic);
            $enfasis.="\"".$fac["name"]."\"";
        }
	}
	$department=intval($_GET["department"]);
	if($department>0) {
		/* Filter by Department.
		* Projects would be associated with people and would carry whatever
		* the people's Department affiliation is.
		*/


		$sql="SELECT DISTINCT projects.project_id,projects.name,projects.synopsis,projects.description
				FROM projects,users,projects_associated
			   WHERE projects_associated.project_id=projects.project_id
				 AND projects_associated.table_name=\"researchers\"
				 AND projects_associated.object_id=users.user_id
				 AND  ( users.department_id = $department OR users.department2_id = $department)
				 AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
				 AND users.user_level = 0
				 AND projects.approved = TRUE

			ORDER BY projects.name ASC";

		$enfasis="Listing by Department ";
        $dept=$db->GetAll("SELECT * FROM departments WHERE department_id= $department");
        if($dept and count($dept)) {
            $fac=reset($dept);
            $enfasis.="\"".$fac["name"]."\"";
		}
    }
	$keyword=$_GET["keyword"];
	if($keyword!="") {
		// Filter by keyword
		$sql="SELECT DISTINCT project_id,name,synopsis,description
			FROM projects
			WHERE projects.name<>\"\"
			 AND (   keywords    like '%$keyword%'
				  OR description like '%$keyword%' )
			 AND projects.approved = TRUE
			ORDER BY name ASC";
		// If keyword search was used, add the keyword list into the "enfasis" part of the template
		$enfasis="Listing by keyword(s) "
			.'"'.$keyword.'"';
		}

	// Load the projects list template
	$tmpl=loadPage("research_projectslist", 'Research at MRU',"research");

	// query the database
	$projects=$db->GetAll($sql);


	if($projects) {
		// Process the results

		foreach($projects as $k=>$proj) {
            
            //Fake synopsis if missing
            if($projects[$k]["synopsis"]=='') {
                $projects[$k]["synopsis"]=substr($projects[$k]["description"],0,500);
                if(strlen($projects[$k]["description"])> 500) $projects[$k]["synopsis"].="...";
            }
            
			$sql="SELECT first_name,last_name,user_id,user_level
				 FROM users,projects_associated
				WHERE projects_associated.table_name=\"researchers\"
				  AND object_id=users.user_id
				  AND projects_associated.project_id=".$proj["project_id"]."

				ORDER BY first_name ASC,last_name ASC";
			$researchers=$db->GetAll($sql);
			if($researchers) {
				$res_list=array();
                $projects[$k]['has_participants']=TRUE;
				if(count($researchers)) {
					foreach($researchers as $res) {
						$projects[$k]["user_id"][]=$res['user_id'];
						$projects[$k]["first_name"][]=$res['first_name'];
						$projects[$k]["last_name"][]=$res['last_name'];
						$projects[$k]["user_level"][]=$res['user_level'];
					}
				}
				else $projects[$k]["first_name"][]="none";
			} else $projects[$k]["first_name"][]="none";


            // add media
            $sql = "
                SELECT m.media_id AS media_id, m.title AS media_title, m.synopsis AS media_synopsis
                FROM media_associated AS ma
                LEFT JOIN media AS m ON m.media_id = ma.media_id
                WHERE ma.object_id = {$proj['project_id']} AND ma.table_name = 'projects'
            ";
            $media = $db->GetAll($sql);
            if ($media) {
                if (count($media) > 0) {
                    $projects[$k]["has_media"] = "TRUE";
                    $associatedMediaHtml = "<br /><h4>Associated Media</h4>\n<ul>\n";
                    foreach($media as $mediaData) {
                        $associatedMediaHtml .= "   <li><a href=\"/media.php?mr_action=detail&media_id={$mediaData['media_id']}\" title=\"{$mediaData['media_synopsis']}\">{$mediaData['media_title']}</a></li>\n";
                    } // if
                    $associatedMediaHtml .= "</ul>\n";
                    $projects[$k]["associated_media_html"] = $associatedMediaHtml;
                } // if
            } // if
		}
        
        
        

		$results=count($projects);
		/**
		* PAGINATION DISABLED
		* $tmpl->addRows("projects", getPagedArray($projects));
		*/
		$tmpl->addRows("projects", $projects);
        if(!isset($fakedResults))$fakedResults='';
		$tmpl->addVar("page","LISTNOTES", "<b>$results projects found. $fakedResults</b>");

		/**
		* PAGINATION DISABLED
		* $tmpl->addVar( 'PAGE',"pages",getPagedLinks($projects));
		*/

		}
	else {

		$tmpl->addVar("page","LISTNOTES", "<b>No project found.</b>".$error);
	}
	if($enfasis) $tmpl->addVar('page', 'enfasis', $enfasis);
	return $tmpl;
}


/**
* @desc Function to generate a Projects view on a pattemplate
*
* @param ADOdb5 Database handle to use
* @global URL parameters "pid" to specify the ID of the project
*
* @return patTemplate object
*/
function viewProject( &$db) {
    global $configInfo;
	cleanUp($_GET["pid"]);
	// Load the project temlpate
	//$tmpl=loadPage("research_viewproject", 'Research at MRU',"research");

	// Query the projects database for selected project
	$sql="SELECT project_id,name,name_long,synopsis,description,keywords,
		 doll_per_yr AS funding, student_names
		FROM projects
		WHERE project_id=".$_GET["pid"]
		//." AND projects.feature = TRUE";
		;
	$project=$db->GetAll($sql);

	if($project) {

		$project=reset($project);
        $tmpl=loadPage("research_viewproject", "$project[name]","research");
		//$project["img"]='images/research_imgs/professor.jpg';
		//$project["funding"]="\$ ". number_format(floatval($project["funding"]), 2, '.', ',');
		$tmpl->addVars("PROJECT", $project);

		// Get the associated information to this project
		$sql_associated="SELECT object_id,table_name
			from projects_associated
			where project_id=".intval($_GET["pid"]);
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
                                . '<a href="research.php?action=view&type=researchers&rid=' . $item["user_id"] . '"
								title="' . $item["first_name"] . " " . $item["last_name"] . '">'
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

			$tmpl->addVar("PROJECT","participants",$researchersList);

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
				$tmpl->addVar("PROJECT","topics",$topicslist);
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
            $img_url=$configInfo['picture_url']."$picture[file_name]";
            $tmpl->addVar("PROJECT","img_url","$img_url");
            $tmpl->addVar("PROJECT","img_caption",$picture['caption']);
        }



		} // END associated IF
	} else
        {$tmpl=loadPage("research_viewproject", 'Research at MRU',"research");
		$tmpl->addVar("PROJECT","DESCRIPTION","Project not found.");}

	return $tmpl; // return generated patTemplate
}


/**
* @desc Function to generate a Researchers Listing on a pattemplate
*
* @param ADOdb5 Database handle to use
* @global URL parameters "faculty", "topic", "department", "keyword" to narrow the information
*
* @return patTemplate object
*/
function listResearchers( &$db) {
    global $configInfo;
	// Load the researchers list
	$tmpl=loadPage("research_researcherslist", 'Researcher List',"research");

	// Default researchres SQL
	/*$sql="SELECT users.user_id,first_name,last_name,keywords,profile_short
        FROM users
        LEFT JOIN profiles ON users.user_id=profiles.user_id
        LEFT JOIN users_hidden on users.user_id = users_hidden.user_id
        WHERE users.emp_type = 'FACL'
            AND users.user_level = 0
            AND ASCII(profiles.profile_short) != 0
        ORDER BY RAND()
        LIMIT 20
    "; // ORDER BY RAND()
    */
    
    
    //TD 19/12/10  Changed default rand to use a new top_researchers table
    //Logic: Grab the users and order by rank with randomization, then if < min display # grab some additional random ones
    //So what if I simply pre-select the IDs? Saves me learning more SQL
    $maxResearchers = 9;
    
    $tsql="SELECT user_id from top_researchers order by rank,rand() limit $maxResearchers";
    $topResearchers=$db->GetAll($tsql);
    $inClause='(';
    foreach($topResearchers as $key=>$topResearcher) {
        if($key==0) $inClause.="$topResearcher[user_id]";
        else $inClause.=",$topResearcher[user_id]";
    }
    $inClause.=')';
    
    $othernum=$maxResearchers-count($topResearchers);
    $tsql="SELECT users.user_id from users 
        LEFT JOIN profiles ON users.user_id=profiles.user_id
        WHERE (users.emp_type = 'FACL' OR users.emp_type='MAN')
        AND users.user_level = 0
        AND ASCII(profiles.profile_short) != 0
        order by rand() 
        limit $othernum";
    $otherResearchers=$db->GetAll($tsql);
    $inClause2='(';
    foreach($otherResearchers as $key=>$otherResearcher) {
        if($key==0) $inClause2.="$otherResearcher[user_id]";
        else $inClause2.=",$otherResearcher[user_id]";
    }
    $inClause2.=')';
    
    $sql="SELECT users.user_id,first_name,last_name,keywords,profile_short
        FROM users
        LEFT JOIN profiles ON users.user_id=profiles.user_id
        LEFT JOIN users_hidden on users.user_id = users_hidden.user_id
        WHERE (users.emp_type = 'FACL' OR users.emp_type='MAN')
            AND users.user_level = 0
            AND (users.user_id IN $inClause OR users.user_id IN $inClause2)
            AND ASCII(profiles.profile_short) != 0
            ORDER BY RAND()
            ";
   // echo $sql;

	// Filter by Faculty
	$faculty=intval($_GET["faculty"]);
	if($faculty>0) {
		/*$sql="SELECT researcher_id,first_name,last_name
			FROM researchers
			WHERE researcher_id % 30 = $faculty
			 ORDER BY last_name ASC,first_name ASC";*/
        $sql="SELECT name from divisions WHERE division_id=$faculty";
        $fName=$db->getAll($sql);
        reset($fName);
        $name=$fName[0]['name'];
        $enfasis="Listing by Faculty \"$name\"";
		$sql=" SELECT users.user_id,first_name,last_name,keywords,profile_short
            FROM departments,users
            LEFT JOIN profiles ON users.user_id = profiles.user_id
            WHERE departments.department_id = users.department_id
                AND departments.division_id = $faculty
                AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
                AND users.user_level = 0
            ORDER BY last_name ASC,first_name ASC";

		}

	// Filter by Topic
	$topic=intval($_GET["topic"]);
	if($topic>0) {
        $sql="SELECT name from topics_research WHERE topic_id=$topic";
        $topicName=$db->getAll($sql);
        reset($topicName);
        $name=$topicName[0]['name'];
        $enfasis="Listing by Topic \"$name\"";
		$sql="SELECT  users.user_id,first_name,last_name,keywords,profile_short
            FROM  user_topics_profile,topics_research,users
            LEFT JOIN profiles ON users.user_id=profiles.user_id
            WHERE user_topics_profile.user_id=users.user_id
                AND user_topics_profile.topic_id=topics_research.topic_id
                AND topics_research.topic_id=$topic
                AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
                AND users.user_level = 0
            ORDER BY last_name ASC,first_name ASC";
		}

	// Filter by Department
	$department=intval($_GET["department"]);
	if($department>0) {
        $sql="SELECT name from departments WHERE department_id=$department";
        $deptName=$db->getAll($sql);
        reset($deptName);
        $name=$deptName[0]['name'];
        $enfasis="Listing reseachers inby Department \"$name\"";
		$sql="SELECT users.user_id,first_name,last_name,keywords,profile_short
            FROM users
            LEFT JOIN profiles ON users.user_id=profiles.user_id
            WHERE department_id = $department
                AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
                AND users.user_level = 0
            ORDER BY first_name ASC, last_name ASC";
		}

	// Filter by keywords (search)
	$keyword=$_GET["keyword"];
	if($keyword!="") {
		$sql="SELECT users.user_id,first_name,last_name,keywords,profile_short
            FROM users
            LEFT JOIN profiles ON users.user_id=profiles.user_id
            WHERE (    keywords like '%$keyword%'
                OR first_name like '%$keyword%'
                OR last_name like '%$keyword%' )
                AND (users.emp_type = 'FACL' OR users.emp_type='MAN')
                AND users.user_level = 0
            ORDER BY last_name ASC,first_name ASC";
		// If keyword search was used, add the keyword list into the "enfasis" part of the template
		$enfasis="Listing by Keyword(s) "
			.'"'.$keyword.'"';
		}

	// execute Query on database for researchers
	$researchers=$db->GetAll($sql);
    //echo $sql;
    //PrintR($researchers);
	if($researchers) {
		foreach($researchers as $k=>$res) {
			if($researchers[$k]["profile_short"]!="")
				$researchers[$k]["profile_short"]=", ".$researchers[$k]["profile_short"];
            
			$sql="SELECT projects.name,projects.project_id
				FROM projects,projects_associated
				WHERE projects_associated.table_name=\"researchers\"
				AND object_id=".$res["user_id"]."
				AND projects_associated.project_id=projects.project_id";
			$projects=$db->GetAll($sql);
			if($projects) {
                $researchers[$k]["has_projects"]="TRUE";
				$proj_list=array();
				if(count($projects)) {
					foreach($projects as $proj) {
						$researchers[$k]["project_id"][]=$proj['project_id'];
						$researchers[$k]["project_name"][]=$proj['name'];
					}
				}
				//else $researchers[$k]["project_name"][]="none";
			} //else $researchers[$k]["project_name"][]="none";

            // add media
            $sql = "
                SELECT m.media_id AS media_id, m.title AS media_title, m.synopsis AS media_synopsis
                FROM media_associated AS ma
                LEFT JOIN media AS m ON m.media_id = ma.media_id
                WHERE ma.object_id = {$res["user_id"]} AND ma.table_name = 'users'
            ";
            $media = $db->GetAll($sql);
            if ($media) {
                if (count($media) > 0) {
                    $researchers[$k]["has_media"] = "TRUE";
                    $associatedMediaHtml = "<h4>Associated Media</h4>\n<ul>\n";
                    foreach($media as $mediaData) {
                        $associatedMediaHtml .= "   <li><a href=\"/media.php?mr_action=detail&media_id={$mediaData['media_id']}\" title=\"{$mediaData['media_synopsis']}\">{$mediaData['media_title']}</a></li>\n";
                    } // if
                    $associatedMediaHtml .= "</ul>\n";
                    $researchers[$k]["associated_media_html"] = $associatedMediaHtml;
                } // if
            } // if


            //Process images
            
            $sql=" SELECT pictures.file_name,pictures.picture_id
                 FROM pictures,pictures_associated
                WHERE pictures_associated.table_name=\"users\"
                  AND object_id=".$res['user_id']."
                  AND pictures_associated.picture_id=pictures.picture_id
                  AND pictures.feature=FALSE
                  ORDER BY RAND()
                  LIMIT 1";

            $pictures=$db->GetAll($sql);
            $picture=reset($pictures);
            if($picture){
                $img_url=$configInfo['picture_url']."$picture[file_name]";
                $researchers[$k]["img"]="<IMG SRC='$img_url' WIDTH='60' style='margin-right:5px; border:black; border-width:thin;float:left;' >";
                //Pad the profile so that a picture, if inserted, wraps properly
                $padlen=280;
                if(strlen($res['profile_short']) < $padlen) {
                    for($x=0; $x<(($padlen-strlen($res['profile_short']))/2); $x++){
                        $researchers[$k]['profile_short']=$researchers[$k]['profile_short'].' &nbsp;';
                    }
                }
                
                
            }
            
     //**********FIX PICTURES***************       
            
            //$researchers[$k]["media_html"] = ($researchers[$k]['media_title'] != '') ? 'MEDIA' : '';

		}

		$numResearchers=count($researchers);
		/**
		* PAGINATIION DISABLED
		* $tmpl->addRows("RESEARCHERS",getPagedArray($researchers));
		*/
		$tmpl->addRows("RESEARCHERS",$researchers);
        if(!isset($fakedResults)) $fakedResults='';
		$tmpl->addVar("page","LISTNOTES", "<b>$numResearchers researchers found. $fakedResults</b>");
		/**
		* PAGINATION DISABLED
		* $tmpl->addVar("page","PAGES",getPagedLinks($researchers));
		*/

		}
	else {
		$tmpl->clearVar("page","RESEARCHERS");
		/*$error="";
		$error="[error code:".mysql_error()."]";
		$tmpl->addVar("page","LISTNOTES", "<b>No researcher found.</b>".$error);*/
	}
	if(isset($enfasis)) if($enfasis)
		$tmpl->addVar('page', 'enfasis', $enfasis);
	return $tmpl; // return generated patTemplate
}


/**
* @desc Function to generate a Researcher viewon a pattemplate
*
* @param ADOdb5 Database handle to use
* @global URL parameters "rid" to specify the ID of the researcher
*
* @return patTemplate object
*/
function viewResearcher( &$db) {
    global $configInfo;
	cleanUp($_GET["rid"]);
    cleanUp($_GET['rname']);
	$rid = $_GET['rid'];

	// Load the view researcher template
	//$tmpl=loadPage("research_viewresearcher", 'Research at MRU',"research");
    if(isset($_GET['rname']))
        $sql="    SELECT *
                FROM users LEFT JOIN profiles ON users.user_id=profiles.user_id
             "// LEFT JOIN faculties  ON users.department_id = departments.department_id
            ."   WHERE users.username='".$_GET["rname"]."'";
	// Query the researchers database for selected researcher
	else $sql="    SELECT *
				FROM users LEFT JOIN profiles ON users.user_id=profiles.user_id
			 "// LEFT JOIN faculties  ON users.department_id = departments.department_id
			."   WHERE users.user_id=".intval($_GET["rid"]);
	$researchers=$db->GetAll($sql);
	if($researchers) {

		$researcher=reset($researchers);
        $tmpl=loadPage("research_viewresearcher", "$researcher[first_name] $researcher[last_name]","research");
        $rid=$researcher['user_id'];
		// Get departments names
		$sql=" SELECT department_id,name FROM departments WHERE department_id=". $researcher["department_id"];
		$dep1=$db->getAll($sql);
		if($dep1) {
			$dep1=reset($dep1);
			$tmpl->addVar("RESEARCHER","department",$dep1["name"]);
		}
		$sql=" SELECT department_id,name FROM departments WHERE department_id=". $researcher["department2_id"];
		$dep2=$db->getAll($sql);
		if($dep1) {
			$dep2=reset($dep2);
			$tmpl->addVar("RESEARCHER","secondary_department",$dep2["name"]);
		}


		// Query the projects database to get the researcher projects
		$sql=" SELECT projects.name,projects.project_id
				 FROM projects,projects_associated
				WHERE projects_associated.table_name=\"researchers\"
				  AND object_id=".intval($rid)."
				  AND projects_associated.project_id=projects.project_id";

		$projects=$db->GetAll($sql);
		if($projects) {

			foreach($projects as $proj) {
				$projects_list["project_id"][]=$proj['project_id'];
				$projects_list["project_name"][]=$proj['name'];

			}
			$tmpl->addVars("PROJECTS_LIST",$projects_list);

		}


		//$researcher["email"]=str_replace("@",' <small style="color:black;background:#FEFF8F;">&nbsp;AT&nbsp;</small>  ', str_replace(".",' <small style="color:black;background:#FEFF8F;">&nbsp;DOT&nbsp;</small> ', $researcher["email"]));
        if($researcher['email']){
            $researcher['email']= strrev($researcher['email']);
        }
        $sql=" SELECT pictures.file_name,pictures.picture_id
                 FROM pictures,pictures_associated
                WHERE pictures_associated.table_name=\"users\"
                  AND object_id=".intval($rid)."
                  AND pictures_associated.picture_id=pictures.picture_id
                  AND pictures.feature=FALSE
                  ORDER BY RAND()
                  LIMIT 1";

        $pictures=$db->GetAll($sql);
        $picture=reset($pictures);
        if($picture){
            $img_url=$configInfo['picture_url']."$picture[file_name]";
            $tmpl->addVar("RESEARCHER","img_url","$img_url");
        }
        //Some cleanups
        if(strcasecmp($researcher['title'],'Instructor')==0) unset($researcher['title']);
        if ($researcher['profile_ext']=='') $researcher['profile_ext']=$researcher['profile_short'];
		$tmpl->addVars("RESEARCHER",$researcher);

       //////////////////////////////// //Process all CV Items///////////////////////////////////////


        //Degrees
        $degrees="";
        $sql="  SELECT *
                 FROM cas_cv_items
                 WHERE user_id=".intval($rid)."
                 AND cas_type_id = 1
                 AND web_show=TRUE
                 ORDER BY `rank` desc, n09 DESC";
        $degrees=$db->GetAll($sql);
        if(is_array($degrees)){
            $degree_list=array();
            foreach ($degrees as $item){
                $output="";
                //$sql="SELECT * from cv_item_types WHERE cv_item_type_id=$item[cv_item_type_id]";
                //$types=$db->GetAll($sql);
                //$type=reset($types);

                //if($type) {
                    //if($type['display_code']!=""){
                        //eval($type['display_code']);
                        //$degree_list[]= $output;
                    //}
                //} //if type
                $degree_list[]=formatitem($item,'apa','list');
            } // foreach
            $tmpl->addVar('educ_list','DEGREES',$degree_list);
        } //if degrees




        //Awards
        $stuff="";
        $sql="  SELECT *
                 FROM cas_cv_items
                 WHERE user_id=".intval($rid)."
                 AND cas_type_id = 28
                 AND web_show=TRUE
                 ORDER BY rank desc, n09 DESC";
        $stuff=$db->GetAll($sql);

        if(is_array($stuff)){
            $stuff_list=array();
            foreach ($stuff as $item){
                /*
                $output="";
                $sql="SELECT * from cv_item_types WHERE cv_item_type_id=$item[cv_item_type_id]";
                $types=$db->GetAll($sql);
                $type=reset($types);

                if($type) {
                    if($type['display_code']!=""){
                        eval($type['display_code']);
                        $stuff_list[]= $output;
                    }
                } //if type
                */
                $stuff_list[]= formatItem($item,'apa','list');
            } // foreach
            $tmpl->addVar('awards_list','AWARDS',$stuff_list);
        } //if stuff


        //Publications
        $stuff="";
        $sql="  SELECT *
                 FROM cas_cv_items
                 WHERE user_id=".intval($rid)."
                 AND (
                 cas_type_id = 35
                 OR cas_type_id = 36
                 OR cas_type_id = 37
                 OR cas_type_id = 38
                 OR cas_type_id = 39
                 OR (cas_type_id = 45 AND n03=TRUE)
                 
                 )
                 AND web_show=TRUE
                 ORDER BY n09 DESC";
        $stuff=$db->GetAll($sql);

        if(is_array($stuff)){
            $stuff_list=array();
            foreach ($stuff as $item){
                /*
                $output="";
                $sql="SELECT * from cv_item_types WHERE cv_item_type_id=$item[cv_item_type_id]";
                $types=$db->GetAll($sql);
                $type=reset($types);

                if($type) {
                    if($type['display_code']!=""){
                        eval($type['display_code']);
                        $stuff_list[]= $output;
                    }
                } //if type
                */
                $stuff_list[]= formatItem($item,'apa','list');
            } // foreach
            $tmpl->addVar('pubs_list','PUBS',$stuff_list);
        } //if stuff
        
        //Proceedings
        $stuff="";
        $sql="  SELECT *
                 FROM cas_cv_items
                 WHERE user_id=".intval($rid)."
                 AND (
                 cas_type_id = 45 AND n03=FALSE              
                 )
                 AND web_show=TRUE
                 ORDER BY rank desc, n09 DESC";
        $stuff=$db->GetAll($sql);
        //echo("Got ".count($stuff));
        if(is_array($stuff)){
            $stuff_list=array();
            foreach ($stuff as $item){
                $stuff_list[]= formatItem($item,'apa','list');
            } // foreach
            $tmpl->addVar('proc_list','PROC',$stuff_list);
        } //if stuff

        //Everything Else
        $stuff="";
        $output="";
        $sql="SELECT * from cas_headings ORDER BY `order`";
        $headers=$db->GetAll($sql);
        if(is_array($headers)) foreach($headers as $header){
            $sql="SELECT * from cas_types WHERE 
             (
             (cas_type_id > 1 AND cas_type_id < 28)
             OR (cas_type_id > 28 AND cas_type_id < 35)
             OR (cas_type_id > 39 AND cas_type_id < 45)
             OR cas_type_id > 46
             )
             AND cas_heading_id=$header[cas_heading_id] ORDER BY `order`";
            $types=$db->GetAll($sql);
            if(is_array($types)) foreach($types as $type){
                $sql="  SELECT *
                     FROM cas_cv_items
                     WHERE user_id=".intval($rid)."
                     AND cas_type_id = $type[cas_type_id]
                     AND web_show=TRUE
                     ORDER BY rank desc , n09 DESC";
                $items=$db->GetAll($sql);


                if(is_array($items)) if(count($items) > 0){
                    $output.="<div class='cv_title'>$type[type_name] </div>";
                    foreach ($items as $item){
                        /*
                        if($type['display_code']!="") {
                            $output.="<div class='cv_entries'>";
                            eval($type['display_code']);
                            $output.="</div>";
                        }
                        */
                        $output.="<div class='cv_entries'>";
                        $output.=formatItem($item,'apa','list');
                        $output.="</div>";
                    }

                }


            }
        }
        // echo $output;
        $tmpl->addVar('else','ELSE_LIST',$output);

/*

            $stuff_list=array();
            foreach ($stuff as $item){
                $output="";
                $sql="SELECT * from cv_item_types WHERE cv_item_type_id=$item[cv_item_type_id]";
                $types=$db->GetAll($sql);
                $type=reset($types);

                if($type) {
                    if($type['display_code']!=""){
                        eval($type['display_code']);
                        $stuff_list[]= $output;
                    }
                } //if type
            } // foreach
            $tmpl->addVar('pubs_list','PUBS',$stuff_list);
        } //if stuff

        /*




        //Everything Else
        $types=mysqlFetchRows("cv_item_types","cv_item_type_id >=12 order by rank");
        foreach($types as $type){
            $items=mysqlFetchRows("cv_items","cv_item_type_id=$type[cv_item_type_id] AND user_id=$user[user_id] AND web_show=1 order by f2 desc");
            if(is_array($items)){
                $online_cv.="<tr><td colspan=4><b><u>$type[title_plural]</u></b></td></tr>
                <tr><td width=10>&nbsp;</td><td colspan=4>";
                $output="<table border='0' cellpadding='2'>";
                foreach ($items as $item){
                    $output.="<tr><td>";
                    if($type['display_code']!="") eval($type['display_code']);
                    $output.="</td></tr>\n";
                }
                $output .="</table>";
                $online_cv .= $output;
                $online_cv .= "<br></td></tr>";
            }
        }//each type


      */
//Debug code
      //$status=system("svn info /opt/lampp/htdocs/ | grep 'Changed Rev'",$retval);         echo ("<br>$retval<br>");

/* Code copied from my_research.inc.php:research_list() */



	$cvdata=Array();
	$cv_type=-1;
	$cv_header_type=-1;
	$index=0;
	$headers=$db->getAll("SELECT cv_item_header_id,title FROM cv_item_headers ORDER BY rank ");
	foreach($headers as $header) {

		$odd_even="oddrow";
        $sql="SELECT     cv_items.*,
                        cv_item_types.cv_item_header_id,
                        cv_item_types.title,f1,f1_name,f4,f4_name,
                        current_par,display_code
                 FROM     cv_items,cv_item_types
                WHERE   cv_items.user_id=$rid
								  AND web_show=1
                  AND cv_items.cv_item_type_id = cv_item_types.cv_item_type_id
                  AND cv_item_types.cv_item_header_id=".$header["cv_item_header_id"]."
            ORDER BY  cv_item_types.rank";
		$items=$db->getAll($sql);
		if($items){
			$cvdata[$index]=Array("type"=> "header1","title"=>$header["title"]);
			$index++;
		foreach($items as $item) {
			if($cv_item_type_id!=$item["cv_item_type_id"]) { 	// current item type is not he one we got, insert new type label
				$cvdata[$index]=Array("type"=> "header2","title"=>$item["title"]);
				$odd_even="oddrow";						// reset to odd row for CSS
				$cv_item_type_id=$item["cv_item_type_id"];
				$index++;
			}

			$cv_item_id=$item["cv_item_id"];
			// add new row to the table
			if($item["f1_name"]=="")						// If first field name is empty
				$title_field="f4";							// use fourth field as main field
			else
			if(strcasecmp($item["f1_name"],"title")==0) 	// if First field is "title"
				$title_field="f1";							// then use it as main field
			else
			if(strcasecmp($item["f4_name"],"title")==0) 	// if fourth field is "title"
				$title_field="f4";							// then use it as main field
			else $title_field="f1";							// if no field is "title" fall back to first field as main field


			$output=eval_display_code($item["display_code"],$item);
			if($output!="") {
				$item["output"]=$output;
				$title_field="output";
			}

			$cvdata[$index]["type"]=$odd_even;
			if($odd_even=="oddrow")
				$odd_even="evenrow";
			else
				$odd_even="oddrow";

			if($item[$title_field]=="")
				$item[$title_field]="...";
			$cvdata[$index]["title"]=$item[$title_field];
			$cvdata[$index]["item_id"]=$cv_item_id;

			$cvdata[$index]["cv_fname"]="item_{$cv_item_id}_cv";
			$cvdata[$index]["profile_fname"]="item_{$cv_item_id}_profile";
			if($item["web_show"]==1) $cvdata[$index]["cv_check"]="checked";
			if($item["current_par"]==1) $cvdata[$index]["profile_check"]="checked";
			$cvdata[$index]["title_fname"]="item_{$cv_item_id}_title";
			$index++;
		}
		}
	}

	if(count($cvdata) > 0)
		$tmpl->addRows("research_list",$cvdata);





	}
    else $tmpl=loadPage("research_viewresearcher", 'Research at MRU',"research");

	return $tmpl;
}


function researchersHome($db) {
    global $configInfo;
	$sql="SELECT project_id,name,synopsis,description FROM projects WHERE feature = TRUE  ORDER BY RAND() LIMIT 1 ";
	$tmpl=loadPage("research_home", 'Research at MRU',"research");

	//$featuredProject=$db->GetOne($sql);
	$featuredProject=$db->GetAll($sql);
	if($featuredProject) {
		//$featuredProject=$featuredProject[ rand(0,count($featuredProject)) ];
		$featuredProject=reset($featuredProject);

//		$featuredProject["img"]='images/research_imgs/professor.jpg';

//	Include the image associated with the feature project
		$sql=" SELECT pictures.file_name,pictures.picture_id
						 FROM pictures,pictures_associated
						WHERE pictures_associated.table_name=\"projects\"
							AND object_id=".$featuredProject["project_id"]."
							AND pictures_associated.picture_id=pictures.picture_id
							AND pictures.feature=FALSE
							ORDER BY RAND()
							LIMIT 1";

		$pictures=$db->GetAll($sql);
		$picture=reset($pictures);
		if($picture){
				$img_url=$configInfo['picture_url']."$picture[file_name]";
				$featuredProject["img"]=$img_url;
		}


		//$featuredProject["url"]='research.php?action=viewproj&pid='.$featuredProject["project_id"];
		$tmpl->addVars("featured_project", $featuredProject);
		}
	else {
		$tmpl->addVar('featured_project',"img", 'images/research_imgs/professor.jpg');
		$tmpl->addVar('featured_project',"synopsis", "Couldnt load project information.");
	}
	return $tmpl;
}


// Cleanup the parameters gotten from the browser ( we dont want to be hacked :P )
cleanUp($_GET["faculty"]);
cleanUp($_GET["topic"]);
cleanUp($_GET["department"]);
cleanUp($_GET["keyword"]);
cleanUp($_GET["type"]);

// We need to store the querytype into a cookie to remember it
if($_GET["type"]!="") {
	setcookie("researchquerytype",$_GET["type"]);
	$querytype=$_GET["type"];
	$_COOKIE["researchquerytype"]=$querytype;
}
else
	if($_COOKIE["researchquerytype"]!="")
		$querytype=$_COOKIE["researchquerytype"];
	else {
		setcookie("researchquery","projects");
		$querytype="projects";
		$_COOKIE["researchquerytype"]=$querytype;
	}



if(isset($_GET['action'])) $action=$_GET["action"].$querytype;
else $action='';
switch($action) {
	case "listprojects":
    case "listproject":
		$tmpl=listProjects($db);
		break;

	case "viewproject":
	case "viewprojects": // Project view
		$tmpl=viewProject($db);
		break;

	case "listresearchers": // List the researchers
		$tmpl=listResearchers($db);
		break;

	case "viewresearcher":
	case "viewresearchers":	// View a researcher
		$tmpl=viewResearcher($db);
		break;

	case "":
	case "home":
	default:
		$tmpl=researchersHome($db);
		break;
}

showMenu("celeb_research",$tmpl);

buildController($tmpl,$db,$querytype);

$tmpl->displayParsedTemplate('page');



?>
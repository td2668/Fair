<?php
require_once('includes/config.inc.php');
include_once('includes/image-functions.php');


global $session,$config;
//print_r($config);

if (!$session->has('user')) {
    throwAccessDenied();
}

if (isset($_POST["action"]) && $_POST["action"] == "update") {
    processMyProjectsFormSubmit();
}

$subsection=(isset($_REQUEST['subsection'])) ? $_REQUEST["subsection"] : '';
switch ($subsection) {
	case "edititem":
		$project_id=(isset($_REQUEST['project_id'])) ? $_REQUEST["project_id"] : '';
		
		if(isset($_REQUEST['addnew_project'])) {
			$project_id=addnew_project();
			
		}
		if(isset($project_id)){
        	$vars = getPageVariables('my_projects_edit');
			mergePageVariables($vars, getMyProjectsEditVars($project_id));
			echo $twig->render('my_projects_edit.twig', $vars);
		}
		else {
			throwError("Error","<h1>Error</h1>Project not found.");
                die;
			}
		break;
	//case "addnew":
		//$tmpl=addnew_research_item();
		//break;
	
	case "":
	default:
		$vars = getPageVariables('my_projects_home');
		mergePageVariables($vars, getMyProjectsHomeVars());
		echo $twig->render('my_projects_home.twig', $vars);
		break;
}//switch





//echo('<pre>');
//print_r($vars);
//echo('</pre>');


//
function getMyProjectsHomeVars() {
    global $db, $session, $config;

    $userId = $session->get('user')->get('id');
   

   

   	$projdata=Array();
    $index=0;
	$odd_even="oddrow";
    $sql="SELECT *
                FROM projects  as p
                LEFT JOIN projects_associated as pa ON(p.project_id=pa.project_id)
                WHERE pa.table_name='researchers'
                AND pa.object_id=$userId
                ORDER BY approved DESC, name ";

    $items=$db->getAll($sql);
   
   	if($items) {
	    foreach($items as $item) {
		    $projdata[$index]["type"]=$odd_even;
		    if($odd_even=="oddrow")
			    $odd_even="evenrow";
		    else
			    $odd_even="oddrow";
           // if($item['approved']) $projdata[$index]["name"]=$item['name'];
           // else $projdata[$index]["name"]=$item['name']. ' (hidden)';
           	if($item['name']=='') $item['name']='(no name)';
            $projdata[$index]["name"]=$item['name'];
            $projdata[$index]["project_id"]=$item['project_id'];
            if(strlen($item['synopsis'])>100) $item['synopsis']=substr($item['synopsis'],0,98) . '...';
            $projdata[$index]['synopsis']=$item['synopsis'];
            if($item['modified']=='0000-00-00') $mod='Undefined';
            else $mod=date('M d Y',strtotime($item['modified']));
            $projdata[$index]['modified']=$mod;
	    $index++;	
	    }
    }
    else $projdata[$index]["type"]='empty';
   
	$vars['projects']=$projdata;
    return $vars;
}



function getMyProjectsEditVars($project_id) {
    global $db, $session, $config;

    $userId = $session->get('user')->get('id');
    
 

    //Process delete request
    if(!isset($_REQUEST['action'])) $_REQUEST['action']='';
    if($_REQUEST['action'] == "delete_project"){
        $delete_project_id = $_REQUEST['delete_project_id'];

        if($delete_project_id>0){
            $sql="DELETE FROM projects
                  WHERE project_id=$delete_project_id ";

            if($db->Execute($sql)==false) {
              throwError("Error","<h1>Error</h1>Couldn't update the database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
                die;
            }
            $sql="DELETE FROM projects_associated
                  WHERE project_id=$delete_project_id ";

            if($db->Execute($sql)==false) {
              throwError("Error","<h1>Error</h1>Couldn't update the database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
                die;
            }
            
            unset($items);
            unset($item);
            unset($fields);
        }

        header("location: /my_projects.php");
    } // end delete
    
    //Process delete pictures request
    if($_REQUEST['action'] == "delete_picture"){
        $delete_picture_id = $_REQUEST['delete_picture_id'];
        //echo("Deleting $delete_picture_id");
        $sql="SELECT * FROM pictures WHERE picture_id=$delete_picture_id";
        $pic=$db->getRow($sql);
        $sql="DELETE from pictures WHERE picture_id=$delete_picture_id";
        if($db->Execute($sql)==false) {
            throwError("Error","<h1>Error</h1>Couldn't update the database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
            die;
        }
        $sql="DELETE from pictures_associated WHERE picture_id=$delete_picture_id";
        if($db->Execute($sql)==false) {
            throwError("Error","<h1>Error</h1>Couldn't update the database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
            die;
        }
        //echo("Unlinking ". $configInfo['picture_path'].$pic['file_name']);
        unlink($config['site']['picture_path'].$pic['file_name']);
        unlink($config['site']['picture_path']."thumb_".$pic['file_name']);
        
    }
    
    
    //First save 
    if($_REQUEST["action"]=="update_project" ) {
        //Make sure item to save is legit
        
        $project_id=intval($_POST["project_id"]);
        if($project_id==0)
            $project_id=intval($_GET["project_id"]);
        if($project_id==0)  {
            throwError("Error","<h1>Error</h1>Couldn't locate the record.<br /><br />");
            die;
        } 
        $update_project_id = $_POST['update_project_id'];
              
        //do the update
        if(strtotime($_POST['end_date'])){
            $tmp_date = explode("-", $_POST['end_date']);
            $end_date = mktime(0,0,0,$tmp_date[1],$tmp_date[2],$tmp_date[0]);
        }
        else $end_date=0;
        $mod=date('Y-m-d');
        //echo("END DATE: $end_date");
    

    $boyerDiscovery = isset($_POST['boyerDiscovery']) ? 1 : 0;
    $boyerIntegration = isset($_POST['boyerIntegration']) ? 1 : 0;
    $boyerApplication = isset($_POST['boyerApplication']) ? 1 : 0;
    $boyerTeaching = isset($_POST['boyerTeaching']) ? 1 : 0;
    $boyerService = isset($_POST['boyerService']) ? 1 : 0;

        $sql="UPDATE projects SET
              name='".mysql_real_escape_string($_POST['name'])."',
              synopsis='".mysql_real_escape_string($_POST['synopsis'])."',
              description='".mysql_real_escape_string($_POST['description'])."',
              keywords='".mysql_real_escape_string($_POST['keywords'])."',
              studentproj=".(isset($_POST["studentproj"]) ? 1 : 0).",
              student_names='".mysql_real_escape_string($_POST['student_names'])."',
              end_date=$end_date,
              approved=".(isset($_POST['approved']) ? 0 : 1) .",
              modified=NOW(),
              who_modified=$userId,
    	      boyerDiscovery = $boyerDiscovery, 
    	      boyerIntegration = $boyerIntegration, 
    	      boyerApplication = $boyerApplication,
    	      boyerTeaching = $boyerTeaching,
   	          boyerService = $boyerService
              WHERE project_id=$update_project_id ";

        if($db->Execute($sql)==false) {
            throwError("Error","<h1>Error</h1>Couldn't update the database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
            die;
        }

        // update the project collaborators in case they have changed
        $user_options=isset($_POST['user_options']) ? $_POST['user_options'] : 0 ;
        updateProjectCollaborators($project_id, $userId, $user_options);

        //end of user_options save
        
        
        //image processing - New Picture
        if(is_uploaded_file($_FILES['uploadimage']['tmp_name'])) {
            
            $ext = explode(".", $_FILES['uploadimage']['name']);
            $file_name_noext = "picture".time();
            $file_name = $file_name_noext.".".$ext[1];
            //echo($configInfo['picture_path'].$file_name);
            copy($_FILES['uploadimage']['tmp_name'], $config['site']['picture_path'].$file_name);
            unlink($_FILES['uploadimage']['tmp_name']);
            
            //thumbnail
            resizeImage($config['site']['picture_path'].$file_name, $config['site']['picture_path']."thumb_".$file_name);
        
            //now resize the image if neccessary. Need to check with a variety of images
            resizeImage($config['site']['picture_path'].$file_name, $config['site']['picture_path'].$file_name, 140);
            shadowImage($config['site']['picture_path'].$file_name, $config['site']['picture_path'].$file_name, 6, 87);
            
            $sql="INSERT INTO pictures
                 (caption,file_name,feature)
                 VALUES('".mysql_real_escape_string($_POST['caption'])."','$file_name',0)";
            if($db->Execute($sql)==false) {
                throwError("Error","<h1>Error</h1>Couldn't update the picture database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
                die;
            }
            $picture_id=mysql_insert_id();
            
            //And save the relate
            
            $sql="INSERT INTO pictures_associated
                  (picture_id,object_id,table_name)
                  VALUES($picture_id,$project_id,'projects')";
            if($db->Execute($sql)==false) {
                throwError("Error","<h1>Error</h1>Couldn't update the picture-relate database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
                die;
            }
            
       
        }//is upload image
        
        //Process incoming caption changes
        $sql="SELECT * FROM pictures
              LEFT JOIN pictures_associated as pa
              ON (pictures.picture_id=pa.picture_id)
              WHERE pa.table_name='projects'
              AND pa.object_id=$project_id
              ";
        $pics=$db->getAll($sql);
        if($pics){
            foreach($pics as $pic)
            if(isset($_POST['caption_'.$pic['picture_id']])){
                $sql="UPDATE pictures
                      SET caption='".mysql_real_escape_string($_POST['caption_'.$pic['picture_id']])."'
                      WHERE picture_id=$pic[picture_id]";
                if($db->Execute($sql)==false) {
                    throwError("Error","<h1>Error</h1>Couldn't update the caption.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
                    die;
                }
            }
        }
        
        unset($items);
        unset($item);
        unset($fields);

        
    }//save section
 
/*
	if($_REQUEST["action"]=="addnew_project" ) {
		die('Here again');
		//generate a new item and reload it
        $mod=mktime();
        $sql="INSERT INTO projects
             (name,approved,modified,who_modified)
             VALUES ('Untitled Project',1,NOW(),$user_id)";
        $db->Execute($sql);
        $project_id=$db->insert_id();
         $sql="INSERT INTO projects_associated 
               (`project_id`, `object_id`, `table_name`)
               VALUES($project_id, $user_id, 'researchers')";
                        
         if($db->Execute($sql)==false) {
            throwError("Error","<h1>Error</h1>Couldn't update the user list.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
            die;
         }        
        header("location: /my_projects.php?subsection=edititem&project_id=$project_id");
        
	} //new section
*/
	

    // If we are simply updating, then reload the item
    if(isset($project_id) || isset($_REQUEST['project_id'])){
        if(!(isset($project_id))) $project_id=$_REQUEST['project_id'];
        $sql="SELECT     *
             FROM     projects as p
             LEFT JOIN projects_associated as pa 
             ON (p.project_id=pa.project_id)
            WHERE  p.project_id=$project_id";
        $items=$db->getAll($sql);
        $item=reset($items); 
        if($item['studentproj']) $item['studentproj']='checked'; else $item['studentproj']='';
        if($item['approved']) $item['approved']=''; else $item['approved']='checked';
        if($item['end_date']==0) $item['end_date']='';
        else $item['end_date']=date('Y-m-d',$item['end_date']);

        //Deal with quotes, etc
        $item['name']= htmlentities($item['name']);
        $item['keywords']= htmlentities($item['keywords']);
        $item['student_names']= htmlentities($item['student_names']);
        if($item['boyerDiscovery']) $item['boyerDiscovery']='checked'; else $item['boyerDiscovery']='';
        if($item['boyerIntegration']) $item['boyerIntegration']='checked'; else $item['boyerIntegration']='';
        if($item['boyerApplication']) $item['boyerApplication']='checked'; else $item['boyerApplication']='';
        if($item['boyerTeaching']) $item['boyerTeaching']='checked'; else $item['boyerTeaching']='';
        if($item['boyerService']) $item['boyerService']='checked'; else $item['boyerService']='';


        //Load the last modifier
        if($item['who_modified']!=0) {
            $sql="SELECT last_name,first_name FROM users where user_id=$userId";
            $moduser=$db->getRow($sql);
            if($moduser) $item['who_modified']=  "$moduser[last_name], $moduser[first_name]";
            else $item['who_modified']='ORS'; 
        }
        else $item['who_modified']='ORS';
        if($item['modified']==0) $item['modified']='';
        else $item['modified']=date('Y-m-d',strtotime($item['modified']));

       //Load Users
        $sql="SELECT * FROM projects_associated
            WHERE project_id=$project_id
            AND object_id != $userId
            AND table_name='researchers'";
        $people=$db->getAll($sql) ;
        require_once('includes/user_functions.php');
        $allusers = getUsers();
        if (is_array($people)) {
            foreach ($people as $person) $ids[] = $person['object_id'];
        }
        if (is_array($allusers)) {
	        $user_options='';
            foreach ($allusers as $oneuser) {
                if (isset($ids) && in_array($oneuser['user_id'], $ids)) {
                    $user_options .= "<option selected value='$oneuser[user_id]'> $oneuser[last_name], $oneuser[first_name]</option>\n";
                }
                else {
                    $user_options .= "<option value='$oneuser[user_id]'>$oneuser[last_name], $oneuser[first_name]</option>\n";
                }
            }
        }
        //now the images
        $sql="SELECT * FROM pictures
              LEFT JOIN pictures_associated as pa
              ON (pictures.picture_id=pa.picture_id)
              WHERE pa.table_name='projects'
              AND pa.object_id=$project_id
              ";
              
        $pics=$db->getAll($sql);
        $imagerows=Array();
        if($pics){
            foreach($pics as $key=>$pic) {
                $imagerows[$key]['url']=$config['site']['picture_url'].$pic['file_name'];
                $imagerows[$key]['caption']=htmlentities($pic['caption']);
                $imagerows[$key]['capnum']=$pic['picture_id'];
            }
        }
        
           
        //$tmpl->addRows("image_list",$imagerows);
        $vars['image_list']=$imagerows;
              
    } //end reload the item
    
    
    //Default Actions
    if(!(is_array($item))) {
       throwError("Error","<h1>Error</h1>Couldn't update the item.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
       die;
    }
    
    //$tmpl->addVars("page",$item);
    //$tmpl->addVar("page",'user_options',$user_options);
	$vars['item']=$item;
	$vars['user_options']=$user_options;
	//print_r($vars);

//    $tmpl->addRows("research_item_fields",$fields);
    return $vars;

}

/**
 * Update the collaborators associated with a project
 *
 * @param $project_id - the project ID
 * @param $user_id - the user id that the project belongs to
 * @param $collaborators - the collaborators
 */
function updateProjectCollaborators($project_id, $user_id, $collaborators)
{
    global $db;

    // first - clear out the associated collaborators, if any.
    $sql = "DELETE FROM projects_associated
                WHERE project_id=$project_id AND table_name='researchers' AND object_id !=  $user_id";
    if ($db->Execute($sql) == false) {
        throwError("Error","<h1>Error</h1>Couldn't update the database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
        die;
    }

    if ($db->Execute($sql) == false) {
        throwError("Error","<h1>Error</h1>Couldn't update the user list.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
        die;
    }

    if (isset($collaborators)) if($collaborators !=0){
        foreach ($collaborators as $index) {
            $sql = "SELECT * FROM projects_associated
                      WHERE project_id=$project_id AND object_id=$index AND table_name='researchers'";
            if (!$db->getAll($sql)) {
                if ($index != "") {
                    $sql = "INSERT INTO projects_associated
                        (`project_id`, `object_id`, `table_name`)
                        VALUES($project_id, $index, 'researchers')";

                    if ($db->Execute($sql) == false) {
                        throwError("Error","<h1>Error</h1>Couldn't update the user list.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
                        die;
                    }
                }
            }
        }
    }
}




function addnew_project() {
	 global $db, $session, $config;
	 $userId = $session->get('user')->get('id');
	

	$sql="INSERT INTO projects
		 (modified)
		 VALUES (NOW())";

	if($db->Execute($sql)==false) {
		echo('Error Inserting');
		throwError("Error","<h1>Error</h1>Couldn't update the database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
		die;
	}
        
	unset($_POST);
	$project_id=$db->insert_id();
    $sql="INSERT INTO projects_associated
    (project_id,object_id,table_name)
    VALUES($project_id,$userId,'researchers')";
    if($db->Execute($sql)==false) {
        throwError("Error","<h1>Error</h1>Couldn't update the database.<br /><br />$sql<br /><br />" . $db->ErrorMsg() . "<br /><br />");
        die;
    }
    return $project_id;
	
}



?>

<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/config.inc.php');
global $session;
if (!$session->has('user')) {
    throwAccessDenied();
}

$userId = $session->get('user')->get('id');


$vars = getPageVariables('webpreview');
/*

echo('<pre>');
		print_r($vars);
		echo('</pre>');
*/

mergePageVariables($vars,viewResearcher($userId));

//$vars=viewResearcher($userId);



echo $twig->render('webpreview.twig', $vars);


function viewResearcher( $rid ) {
    global $db,$session;
    
    $var=array();
	//cleanUp($_GET["rid"]);
    //cleanUp($_GET['rname']);
	//$rid = $_GET['rid'];

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
			."   WHERE users.user_id=".intval($rid);
	$researcher=$db->GetRow($sql);
	
	$researcher=getPersonData(intval($rid));
	
	if($researcher) {

		//$researcher=reset($researchers);
        //$tmpl=loadPage("research_viewresearcher", "$researcher[first_name] $researcher[last_name]","research");
        //$rid=$researcher['user_id'];
		// Get departments names
/*
		$sql=" SELECT department_id,name FROM departments WHERE department_id=". $researcher["department_id"];
		$dep1=$db->getAll($sql);
		if($dep1) {
			$dep1=reset($dep1);
			//$tmpl->addVar("RESEARCHER","department",$dep1["name"]);
			$vars['department']=$dep1['name'];
		}
*/
		
/*
		$sql=" SELECT department_id,name FROM departments WHERE department_id=". $researcher["department2_id"];
		$dep2=$db->getAll($sql);
		if($dep2) {
			$dep2=reset($dep2);
			//$tmpl->addVar("RESEARCHER","secondary_department",$dep2["name"]);
			$vars['secondary_department']=$dep2['name'];
		}
*/


		// Query the projects database to get the researcher projects
		$sql=" SELECT projects.name,projects.project_id
				 FROM projects,projects_associated
				WHERE projects_associated.table_name=\"researchers\"
				  AND object_id=".intval($rid)."
				  AND projects_associated.project_id=projects.project_id";

		$projects=$db->GetAll($sql);
		if($projects) {

			foreach($projects as $proj) {
				$projects_list[] = array('project_id'=>$proj['project_id'], 'project_name'=>$proj['name']);

			}
			//$tmpl->addVars("PROJECTS_LIST",$projects_list);
			$vars['projects_list']=$projects_list;
			//print_r($vars);
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

        $picture=$db->GetRow($sql);
        //$picture=reset($pictures);
        if($picture){
            $img_url='/documents/pictures/'."$picture[file_name]";
            //$tmpl->addVar("RESEARCHER","img_url","$img_url");
            $vars['img_url']=$img_url;
        }
        //Some cleanups
        //if(strcasecmp($researcher['title'],'Instructor')==0) unset($researcher['title']);
        if ($researcher['profile_ext']=='') $researcher['profile_ext']=$researcher['profile_short'];
		//$tmpl->addVars("RESEARCHER",$researcher);
		$vars['researcher']=$researcher;
		

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
                $degree_list[]=\MRU\Research\CV::formatitem($item,'apa','list');
            } // foreach
            //$tmpl->addVar('educ_list','DEGREES',$degree_list);
            $vars['educ_list']=$degree_list;
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
                $stuff_list[]= \MRU\Research\CV::formatItem($item,'apa','list');
            } // foreach
            //$tmpl->addVar('awards_list','AWARDS',$stuff_list);
            $vars['awards_list']=$stuff_list;
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
                $stuff_list[]= \MRU\Research\CV::formatItem($item,'apa','list');
            } // foreach
            //$tmpl->addVar('pubs_list','PUBS',$stuff_list);
            $vars['pubs_list']=$stuff_list;
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
                $stuff_list[]= \MRU\Research\CV::formatItem($item,'apa','list');
            } // foreach
            //$tmpl->addVar('proc_list','PROC',$stuff_list);
            $vars['proc_list']=$stuff_list;
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
                    $output.="<div class='cv_title'>$type[type_name] </div>
                    ";
                    foreach ($items as $item){
                        /*
                        if($type['display_code']!="") {
                            $output.="<div class='cv_entries'>";
                            eval($type['display_code']);
                            $output.="</div>";
                        }
                        */
                        $output.="<div class='cv_entries'>";
                        $output.=\MRU\Research\CV::formatItem($item,'apa','list');
                        $output.="</div>
                        ";
                    }

                }


            }
        }
        // echo $output;
        //$tmpl->addVar('else','ELSE_LIST',$output);
        $vars['else_list']=$output;

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
		//$tmpl->addRows("research_list",$cvdata);
		$vars['research_list']=$cvdata;


	}
    //else $tmpl=loadPage("research_viewresearcher", 'Research at MRU',"research");
    else $vars['research_list']='No CV Data';


		
	return $vars;
}



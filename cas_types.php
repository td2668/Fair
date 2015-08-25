<?php

//error_reporting(E_ALL);

include("includes/config.inc.php");

include("includes/functions-required.php");

$tmpl=loadPage("cas_types", 'CASRAI Types');



$success='';

//pre-defined set of types for the CV fields used in 'cas_types'

$fieldarray=array(  'n01'=>'Text',

	'n02'=>'List',

	'n03'=>'Bool',

	'n04'=>'List',

	'n05'=>'Text',

	'n06'=>'Num',

	'n07'=>'Num',

	'n08'=>'Num',

	'n09'=>'Date',

	'n10'=>'Num',

	'n11'=>'Num',

	'n12'=>'Num',

	'n13'=>'List',

	'n14'=>'Text',

	'n15'=>'Sub',

	'n16'=>'Sub',

	'n17'=>'Sub',

	'n18'=>'Date',

	'n19'=>'Date',

	'n20'=>'List',

	'n21'=>'List',

	'n22'=>'Text',

	'n23'=>'Bool',

	'n24'=>'Bool',

	'n25'=>'Text',

	'n26'=>'Text',

	'n27'=>'Text',

	'n28'=>'Sub',

	'n29'=>'Date',

	'n30'=>'Text'

);



//Header Shuffle Up - Move One header up one in rank

//Get all headers and rewrite rank order (if necessary - to eliminate manual vagarities), then switch two of them

if(isset($_REQUEST['heading_up'])){

	//Get all headers and rewrite rank order (if necessary - to eliminate manual vagarities), then switch two of them

	$sql="SELECT * FROM `cas_headings` WHERE 1 ORDER BY `order`";

	$headings=$db->getAll($sql);

	$order=1;

	foreach($headings as $heading){

		$sql="UPDATE `cas_headings` SET `order`=$order WHERE `cas_heading_id`=$heading[cas_heading_id]";

		$result=$db->Execute($sql);

		$order++;

	}

	if(isset($_REQUEST['id'])){

		$id=$_REQUEST['id'];

		$sql="SELECT * FROM `cas_headings` WHERE `cas_heading_id`=$id";

		$current=$db->getRow($sql);

		if($current['order'] > 1){

			$sql="SELECT * FROM `cas_headings` WHERE `order`={$current['order']}-1";

			$above=$db->getRow($sql);

			$sql="UPDATE `cas_headings` SET `order`='$current[order]' WHERE `cas_heading_id`='$above[cas_heading_id]'";

			$result=$db->Execute($sql);

			$sql="UPDATE `cas_headings` SET `order`='$above[order]' WHERE `cas_heading_id`='$current[cas_heading_id]'";

			$result=$db->Execute($sql);

		}



	}

}

//Header shuffle down - Move header down in rank

if(isset($_REQUEST['heading_down'])){

	//Get all headers and rewrite rank order (if necessary - to eliminate manual vagarities), then switch two of them

	$sql="SELECT  * FROM `cas_headings` WHERE 1 ORDER BY `order`";

	$headings=$db->getAll($sql);

	$order=1;

	$sql="SELECT MAX(`order`) as `max` FROM `cas_headings` WHERE 1 ORDER BY `order`";

	$max=$db->getRow($sql);



	foreach($headings as $heading){

		$sql="UPDATE `cas_headings` SET `order`=$order WHERE `cas_heading_id`=$heading[cas_heading_id]";

		$result=$db->Execute($sql);

		$order++;

	}



	if(isset($_REQUEST['id'])){

		$id=$_REQUEST['id'];

		$sql="SELECT * FROM `cas_headings` WHERE `cas_heading_id`=$id";

		$current=$db->getRow($sql);

		//

		if($current['order'] < $max['max']){

			$sql="SELECT * FROM `cas_headings` WHERE `order`={$current['order']}+1";

			$below=$db->getRow($sql);

			$sql="UPDATE `cas_headings` SET `order`='$current[order]' WHERE `cas_heading_id`='$below[cas_heading_id]'";

			$result=$db->Execute($sql);

			$sql="UPDATE `cas_headings` SET `order`='$below[order]' WHERE `cas_heading_id`='$current[cas_heading_id]'";

			$result=$db->Execute($sql);

		}



	}



}

//Item shuffle up - move item up in rank

if(isset($_REQUEST['type_up']) && isset($_REQUEST['heading_id']) && isset($_REQUEST['type_id'])){

	//Get all items and rewrite rank order (if necessary - to eliminate manual vagarities), then switch two of them

	$sql="SELECT * FROM `cas_types` WHERE `cas_heading_id`='$_REQUEST[heading_id]' ORDER BY `order`";

	$types=$db->getAll($sql);

	$order=1;

	foreach($types as $type){

		$sql="UPDATE `cas_types` SET `order`=$order WHERE `cas_type_id`=$type[cas_type_id]";

		$result=$db->Execute($sql);

		$order++;

	}



	$type_id=$_REQUEST['type_id'];

	$sql="SELECT * FROM `cas_types` WHERE `cas_type_id`='$_REQUEST[type_id]'";

	$current=$db->getRow($sql);

	if($current['order'] > 1){

		$sql="SELECT * FROM `cas_types` WHERE `cas_heading_id`='$_REQUEST[heading_id]' AND `order`={$current['order']}-1";

		$above=$db->getRow($sql);

		$sql="UPDATE `cas_types` SET `order`='$current[order]' WHERE `cas_type_id`='$above[cas_type_id]'";

		$result=$db->Execute($sql);

		$sql="UPDATE `cas_types` SET `order`='$above[order]' WHERE `cas_type_id`='$current[cas_type_id]'";

		$result=$db->Execute($sql);

	}





}

//Item shuffle down - move item down in rank

if(isset($_REQUEST['type_down']) && isset($_REQUEST['heading_id']) && isset($_REQUEST['type_id'])){

	//Get all items and rewrite rank order (if necessary - to eliminate manual vagarities), then switch two of them

	$sql="SELECT * FROM `cas_types` WHERE `cas_heading_id`='$_REQUEST[heading_id]' ORDER BY `order`";

	$types=$db->getAll($sql);

	$sql="SELECT MAX(`order`) as `max` FROM `cas_types` WHERE `cas_heading_id`='$_REQUEST[heading_id]' ORDER BY `order`";

	$max=$db->getRow($sql);

	$order=1;

	foreach($types as $type){

		$sql="UPDATE `cas_types` SET `order`=$order WHERE `cas_type_id`=$type[cas_type_id]";

		$result=$db->Execute($sql);

		$order++;

	}



	$type_id=$_REQUEST['type_id'];

	$sql="SELECT * FROM `cas_types` WHERE `cas_type_id`='$_REQUEST[type_id]'";

	$current=$db->getRow($sql);

	if($current['order'] < $max['max']){

		$sql="SELECT * FROM `cas_types` WHERE `cas_heading_id`='$_REQUEST[heading_id]' AND `order`={$current['order']}+1";

		$below=$db->getRow($sql);

		$sql="UPDATE `cas_types` SET `order`='$current[order]' WHERE `cas_type_id`='$below[cas_type_id]'";

		$result=$db->Execute($sql);

		$sql="UPDATE `cas_types` SET `order`='$below[order]' WHERE `cas_type_id`='$current[cas_type_id]'";

		$result=$db->Execute($sql);

	}





}





//Save Item

if(isset($_REQUEST['saveme'])){



	if(isset($_REQUEST['cas_type_id'])){

		$_REQUEST['section']='edit';

		$_REQUEST['type_id']= $_REQUEST['cas_type_id'];

		foreach($fieldarray as $fieldindex=>$fieldtype){

			//if its not selected then skip

			if(isset($_REQUEST["{$fieldindex}_active"])){

				//convert data

				$fieldname = (isset($_REQUEST["{$fieldindex}_field_name"])) ? mysql_real_escape_string($_REQUEST["{$fieldindex}_field_name"]) : '';

				$help_text = (isset($_REQUEST["{$fieldindex}_help_text"])) ? mysql_real_escape_string($_REQUEST["{$fieldindex}_help_text"]) : '';

				$size= (isset($_REQUEST["{$fieldindex}_size"])) ? intval($_REQUEST["{$fieldindex}_size"]) : '0';

				$sublist = (isset($_REQUEST["{$fieldindex}_sublist"])) ? mysql_real_escape_string($_REQUEST["{$fieldindex}_sublist"]) : '';

				$subtable = (isset($_REQUEST["{$fieldindex}_subtable"])) ? mysql_real_escape_string($_REQUEST["{$fieldindex}_subtable"]) : '';

				$subtype = (isset($_REQUEST["{$fieldindex}_subtype"])) ? mysql_real_escape_string($_REQUEST["{$fieldindex}_subtype"]) : '';

				$required= (isset($_REQUEST["{$fieldindex}_required"]) ? TRUE : FALSE);

				$list_add= (isset($_REQUEST["{$fieldindex}_list_add"]) ? TRUE : FALSE);

				//Replace the following with a re-ranking of all elements using a floating number and convert to int

				$order= (isset($_REQUEST["{$fieldindex}_order"])) ? intval($_REQUEST["{$fieldindex}_order"]) : '0';



				//echo ("Fieldname = $fieldname");

				$sql="SELECT * FROM `cas_field_index` WHERE `cas_type_id`='$_REQUEST[cas_type_id]' AND `cas_cv_item_field`='$fieldindex'";

				$result=$db->getRow($sql);

				if($result) {

					//do an update

					$sql="UPDATE `cas_field_index` SET

                            `type`='$fieldtype',

                            `field_name`='$fieldname' ,

                            `sublist`='$sublist',

                            `subtable`='$subtable',

                            `subtype`='$subtype',

                            `required`='$required',

                            `order`='$order',

                            `size`='$size',

                            `list_add`='$list_add',

                            `help_text`='$help_text'

                            WHERE `cas_type_id`='$_REQUEST[cas_type_id]' AND `cas_cv_item_field`='$fieldindex'";

					$result=$db->Execute($sql);

					//var_dump ($result);

				}

				else{

					//create the row. Not going to bother deleting anything.

					$sql="INSERT INTO `cas_field_index`

                        (`field_index_id`,

                        `cas_type_id`,

                        `cas_cv_item_field`,

                        `field_name`,

                        `type`,

                        `subtype`,

                        `size`,

                        `subtable`,

                        `sublist`,

                        `order`,

                        `description`,

                        `required`,

                        `help_text`,

                        `list_add`)



                        VALUES(

                        NULL,

                        '$_REQUEST[cas_type_id]',

                        '$fieldindex',

                        '$fieldname' ,

                        '$fieldtype',

                        '$subtype',

                        '$size',

                        '$subtable',

                        '$sublist',

                        '$order',

                        '',

                        '$required',

                        '$help_text',

                        '$list_add')";

					$result=$db->Execute($sql);



				}



				//$= (isset($_REQUEST['financial']) ? TRUE : FALSE);

				//$relationship = (isset($_REQUEST['relationship'])) ? mysql_real_escape_string($_REQUEST['relationship']) : '';

				//$relationship = (isset($_REQUEST['relationship'])) ? mysql_real_escape_string($_REQUEST['relationship']) : '';

			} //active item

			else {

				$sql="DELETE FROM `cas_field_index` WHERE `cas_type_id`='$_REQUEST[cas_type_id]' AND `cas_cv_item_field`='$fieldindex'";

				$result=$db->Execute($sql);

			}

		} //foreach type

	}

}



if(!isset($_REQUEST['section'])) $_REQUEST['section']="view";

switch($_REQUEST['section']){

case 'view':

	//total count

	$sql="select COUNT(*) as count from cas_cv_items where converted=0";

	$result=$db->getRow($sql);

	$not=$result['count'];



	$sql="select count(*) as count from cas_cv_items where converted=1";

	$result=$db->getRow($sql);

	$done=$result['count'];



	$sql="select count(*) as count from cas_cv_items where converted>1";

	$result=$db->getRow($sql);

	$shelved=$result['count'];

	$counter="Converted: $done, To Do: $not, Shelved: $shelved";



	$tmpl->addVar('PAGE','counter',$counter);

	$tmpl->setAttribute('chooser','visibility','visible')   ;

	$sql="SELECT * FROM `cas_headings` left join `cas_menu_headings` ON (cas_headings.cas_menu_heading_id=cas_menu_headings.id) ORDER BY `cas_headings`.`order`";

	$headings=$db->getAll($sql);

	if(count($headings > 0)) {

		foreach($headings as $heading){

			$tmpl->addVar('chooser','heading_name',$heading['heading_name']);

			$tmpl->addVar('chooser','cas_heading_id',$heading['cas_heading_id']);

			$sql="SELECT * FROM `cas_types` WHERE `cas_heading_id`='$heading[cas_heading_id]' order by `order`";

			$types=$db->getAll($sql);

			$items='';

			if(count($types > 0)) foreach($types as $type)

				{

					$items.="<tr><td bgcolor='#D7D7D9' style='border:left 10px;'><a href='cas_types.php?section=edit&type_id=$type[cas_type_id]'>$type[type_name]</a></td>

                                <td bgcolor='#D7D7D9'><a href='cas_types.php?type_up&heading_id=$heading[cas_heading_id]&type_id=$type[cas_type_id]'><img src='images/arrow_up_red.jpg' border='0'/></a></td>

                                <td bgcolor='#D7D7D9'><a href='cas_types.php?type_down&heading_id=$heading[cas_heading_id]&type_id=$type[cas_type_id]'><img src='images/arrow_down_red.jpg' border='0'/></a></td></tr>\n";

				}

			$tmpl->addVar('chooser','items',$items);

			$tmpl->parseTemplate("chooser","a");

		} //foreach heading

	}//if count headings



	break;



case 'edit':



	if(isset($_REQUEST['type_id'])){

		$sql="SELECT * FROM `cas_types` WHERE `cas_type_id`='$_REQUEST[type_id]'";

		$type=$db->getRow($sql);

		if($type){

			$tmpl->setAttribute('edit','visibility','visible');

			$tmpl->addVar('edit','typename',$type['type_name']);

			$tmpl->addVar('edit','cas_type_id',$_REQUEST['type_id']);

			//Display all lines so others can be activated



			$item=array(); $fields=array();

			foreach($fieldarray as $fieldindex=>$fieldtype){

				$sql="SELECT * FROM `cas_field_index` WHERE `cas_type_id`='$_REQUEST[type_id]' AND `cas_cv_item_field`='$fieldindex'";

				$field=$db->getRow($sql);

				//var_dump ($field);

				$item['type']=$fieldtype;

				$item['fieldindex']=$fieldindex;

				//now set the optional items - every line is different







				$item['checkbox']="<input type='checkbox' name='" . $fieldindex . "_active' ".

					(($field) ? 'checked' : '') ." />";

				$item['fieldindex']=$fieldindex;

				//now set the optional items - every line is different



				//now set the optional items - every line is different

				$item['content']='';



				$item['content']="<input type='text' name='" . $fieldindex . "_field_name' value='" .

					(($field) ? $field['field_name'] : '') . "' />";





				$item['required']="<input type='checkbox' name='" . $fieldindex . "_required' " .

					(($field) ? (($field['required']) ? 'checked' : '') : '') . " />";



				$item['order']="<input type='text' name='" . $fieldindex . "_order' value='" .

					(($field) ? $field['order'] : '') . "' />";



				$item['help_text']="<input size='100' type='text' name='" . $fieldindex . "_help_text' value='" .

					(($field) ? htmlentities($field['help_text'],ENT_QUOTES) : '') . "' />";



				$item['options']='';

				if($fieldtype=='Text'){

					$item['options']="Length: <input type='text' name='" . $fieldindex . "_size' size='3' value='" .

						(($field) ? $field['size'] : '0') . "' />";

					$texttypes=array('author');

					$item['options'].=" Type:<select name='" . $fieldindex . "_subtype'><option value=''></option>\n";

					foreach($texttypes as $texttype){

						if($field) {

							if($field['subtype']==$texttype) $selected='selected'; else $selected='';

						}

						else $selected='';

						$item['options'].="<option value='$texttype' $selected>$texttype</option>\n";

					}//foreach

					$item['options'].='</select>';

				}

				$item['list_add']='';

				if($fieldtype=='List'){

					$item['options']="<select name='" . $fieldindex . "_sublist'><option value='0'></option>\n";

					$textypes=array('author');

					$sql="SELECT * FROM `cas_lists` WHERE 1 ORDER BY `listname`";

					$lists=$db->getAll($sql);

					foreach($lists as $list){

						if($field) {

							if(strcmp($list['listname'],$field['sublist'])==0) $selected='selected'; else $selected='';

						}

						else $selected='';

						$item['options'].="<option value='$list[listname]' $selected >$list[listname]</option>\n";

					}//foreach

					$item['options'].='</select>';

					$item['list_add']="<input type='checkbox' name='" . $fieldindex . "_list_add' " .

						(($field) ? (($field['list_add']) ? 'checked' : '') : '') . " />";

				}

				if($fieldtype=='Sub'){

					$item['options']="<select name='" . $fieldindex . "_subtable'><option value='0'></option>\n";

					$sql="SELECT * FROM `cas_subtables` WHERE 1 ORDER BY `table_name`";

					$lists=$db->getAll($sql);

					foreach($lists as $list){

						if($field) {

							if($list['table_name']==$field['subtable']) $selected='selected'; else $selected='';

						}

						else $selected='';

						$item['options'].="<option value='$list[table_name]' $selected>$list[table_name]</option>\n";

					}//foreach

					$item['options'].='</select>';

				}

				if($fieldtype=='Date'){

					$datetypes=array('YYYY-MMM-DD','YYYY-MMM','YYYY');

					$item['options']="<select name='" . $fieldindex . "_subtype'>\n";

					foreach($datetypes as $datetype){

						if($field) {

							if($field['subtype']==$datetype) $selected='selected'; else $selected='';

						}

						else $selected='';

						$item['options'].="<option value='$datetype' $selected>$datetype</option>\n";

					}//foreach

					$item['options'].='</select>';

				}







				$fields[]=$item;

			}//foreach

			$tmpl->addRows('fields',$fields);

		}

	}

	break;



case 'convert':

	//Run through items with blank 'n' fields and make guesses at conversion from old to new.

	//Present one at a time for user input.





	break;



}//switch



















$tmpl->addVar('edit','success',$success);



$tmpl->displayParsedTemplate('page');

?>

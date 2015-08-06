<?php

require_once('includes/config.inc.php');
global $session, $twig;

if (!$session->has('user')) {
    throwAccessDenied();
}

$page = (isset($_GET["page"])) ? CleanString($_GET["page"]) : '';

$vars = getPageVariables('help');

switch ($page) {
	case 'cv_items_generic':
		$vars['header']['title']='Activities - Help';
		$vars['content']="  ";
	break;
	
	case 'cv_items_generic_form':
		$vars['header']['title']='Item Form - Help';
		$vars['content']="  ";
	break;
	
	case 'about_me':
		$vars['header']['title']='About Me - Help';
		$vars['content']="  ";
	break;
	
	case 'my_projects_home':
		$vars['header']['title']='My Projects - Help';
		$vars['content']="  ";
	break;
	
	case 'my_projects_edit':
		$vars['header']['title']='Edit Project - Help';
		$vars['content']="  ";
	break;
	
	default:
		$vars['content']="There is no help available for this page ($page).";
} //switch

echo $twig->render('help.twig', $vars);

?>
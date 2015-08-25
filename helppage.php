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
		$vars['content']="<p>Use this form to enter a single activity item. There are two ways.</p> <p>1. The simplest is to type or cut and paste into the 'Formatted Item' box. After you save, the item will be visible in its final form in 'Preview'. Use the Italics and Bold controls above the box to insert formatting commands. The format information is stored in 'markdown' format, making it easy to store in a database. If , for eg, you cut-and-paste a journal article item from MSWord, you would then double click the journal name and click the Italics icon.<p>2. You can use the detailed input section, designed for (future) compatibility with the Canadian Common CV. Here, you first click' Show Detailed Input Form', ensure the 'Formatted' box is clear, and follow the prompts. Save to see your results.</p>  <p>
		In both cases, anything entered in the 'Details' box is appended to the item (below, smaller font, italics) when it is displayed on the web. ";
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
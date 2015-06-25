<?php
// Standalone page to generate a decision tree to assist faculty in selecting cv item types
require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/config.inc.php');
global $twig, $session;

if (!$session->has('user')) {
    throwAccessDenied();
}

//First parse through and build the structure
$dtree = array();
$sql = "SELECT * from dtree WHERE 1 GROUP BY level DESC";
$cols = $db->getAll($sql);
if ($cols) {
    foreach ($cols as $col) {
        $sql = "SELECT * FROM dtree where level=$col[level] GROUP BY parent";
        $groups = $db->GetAll($sql);
        foreach ($groups as $groupkey => $group) {
            $dtree[$col['level']][$groupkey]['parent'] = $group['parent'];
            $sql = "SELECT * FROM dtree where level=$col[level] AND parent=$group[parent] ORDER BY `order`";
            $items = $db->GetAll($sql);
            foreach ($items as $itemkey => $item) {
                $dtree[$col['level']][$groupkey]['items'][$itemkey] = array(
                    'name' => $item['text'],
                    'target' => $item['target'],
                    'id' => $item['id'],
                );
            }
        }
    }
}

//Now parse through and tie parents and children - done this way to make the javascript a bit faster
$numcols = count($dtree);
foreach ($dtree as $lkey => $level) {
    foreach ($level as $gkey => $group) {
        if ($group['parent'] != 0) {

            //find and tell the parent what group this is
            foreach ($dtree as $lkey2 => $level2) {
                foreach ($level2 as $gkey2 => $group2) {
                    foreach ($group2['items'] as $ikey2 => $item) {
                        if ($item['id'] == $group['parent']) {
                            $dtree[$lkey2][$gkey2]['items'][$ikey2]['child'] = $gkey;
                        }
                    }
                }
            }
        }
    }
}

// Get the list of type titles
$types = $db->getAll("SELECT cas_type_id as id, cas_heading_id as heading_id, type_name as name from cas_types");
$vars = getPageVariables('dtree');
$vars['dtree'] = json_encode($dtree);
$vars['types'] = json_encode($types);
echo $twig->render('dtree.twig', $vars);

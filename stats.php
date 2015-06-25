<?php
require_once('includes/config.inc.php');

global $session;
if (!$session->has('user')) {
    throwAccessDenied();
}

$unit = (isset($_REQUEST["unit"])) ? CleanString($_REQUEST["unit"]) : false;
$unitb = (isset($_REQUEST["unitb"])) ? CleanString($_REQUEST["unitb"]) : false;
$unitb2 = (isset($_REQUEST["unitb2"])) ? CleanString($_REQUEST["unitb2"]) : false;
$targ1 = (isset($_REQUEST["targ1"])) ? CleanString($_REQUEST["targ1"]) : false;
$targ2 = (isset($_REQUEST["targ2"])) ? CleanString($_REQUEST["targ2"]) : false;
$targb = (isset($_REQUEST["targb"])) ? CleanString($_REQUEST["targb"]) : false;
$caqc_flag = (isset($_REQUEST["caqc_flag"])) ? CleanString($_REQUEST["caqc_flag"]) : 1;
$norm_flag = (isset($_REQUEST["norm_flag"])) ? CleanString($_REQUEST["norm_flag"]) : 1;

$fac = 0;
$dept = 0;
$facb = 0;
$facb2 = 0;
$dep = 0;
$depb = 0;
$depb2 = 0;

if ($unit) {
    if ($unit > 1000) {
        $dep = $unit - 1000;
    } else {
        $fac = $unit;
    }
}

if ($unitb) {
    if ($unitb > 1000) {
        $depb = $unitb - 1000;
    } else {
        $facb = $unitb;
    }
}

if ($unitb2) {
    if ($unitb2 > 1000) {
        $depb2 = $unitb2 - 1000;
    } else {
        $facb2 = $unitb2;
    }
}

//First do a cleanup - fix any null values in the db.
$sql = "SELECT cv_item_id from cas_cv_items WHERE caqc_flags IS NULL";
$nulls = $db->GetAll($sql);
if ($nulls) {
    foreach ($nulls as $item) {
        $flags = new \MRU\Research\Caqc\Flags();
        $flags->GetStats($item['cv_item_id']);
        $sql = "UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id=$item[cv_item_id]";
        $result = $db->Execute($sql);
    }
}

//Just load the choice lists
$sql = "SELECT * FROM divisions WHERE
		name !='Academic Affairs' AND
		name != 'Iniskim Centre' AND
		name != 'Research Services'
		ORDER BY name";
$divs = $db->GetAll($sql);
$div_options = '';
$div_optionsb = '';
$div_optionsb2 = '';

foreach ($divs as $div) {
    if ($fac == $div['division_id']) {
        $sel = "selected='selected'";
    } else {
        $sel = '';
    }

    if ($facb == $div['division_id']) {
        $selb = "selected='selected'";
    } else {
        $selb = '';
    }

    if ($facb2 == $div['division_id']) {
        $selb2 = "selected='selected'";
    } else {
        $selb2 = '';
    }

    $div_options .= "<option value='$div[division_id]' $sel>$div[name]</option>\n";
    $div_optionsb .= "<option value='$div[division_id]' $selb>$div[name]</option>\n";
    $div_optionsb2 .= "<option value='$div[division_id]' $selb2>$div[name]</option>\n";
}

$div_options .= "<option value='0'>----- Departments ------</option>\n";
$div_optionsb .= "<option value='0'>----- Departments ------</option>\n";
$div_optionsb2 .= "<option value='0'>----- Departments ------</option>\n";
$sql = "SELECT * FROM departments WHERE division_id!=0
		AND name != 'Anachronism'
		ORDER BY name";
$depts = $db->GetAll($sql);
foreach ($depts as $dept) {
    $newone = $dept['department_id'] + 1000;
    if ($dep == $dept['department_id']) {
        $sel = "selected='selected'";
    } else {
        $sel = '';
    }

    if ($depb == $dept['department_id']) {
        $selb = "selected='selected'";
    } else {
        $selb = '';
    }

    if ($depb2 == $dept['department_id']) {
        $selb2 = "selected='selected'";
    } else {
        $selb2 = '';
    }

    $div_options .= "<option value='$newone' $sel>$dept[name]</option>\n";
    $div_optionsb .= "<option value='$newone' $selb>$dept[name]</option>\n";
    $div_optionsb2 .= "<option value='$newone' $selb2>$dept[name]</option>\n";
}

$targ_options1 = '';
$targ_options2 = '';
$targ_optionsb = '';
if (!$caqc_flag) {
    $sql = "SELECT ct.cas_type_id,ct.type_name, ch.heading_name, ch.cas_heading_id, ct.cas_type_id
			FROM cas_types as ct
			LEFT JOIN cas_headings as ch ON(ct.cas_heading_id=ch.cas_heading_id)
			WHERE 1
			ORDER BY ch.order, ct.order, ct.type_name";
    $types = $db->GetAll($sql);
    $lasttype = 0;
    foreach ($types as $type) {
        if ($lasttype != $type['cas_heading_id']) {
            $targ_options1 .= "<option value='0'>----$type[heading_name]----</option>\n";
            $targ_options2 .= "<option value='0'>----$type[heading_name]----</option>\n";
            $targ_optionsb .= "<option value='0'>----$type[heading_name]----</option>\n";
        }

        if ($targ1 == $type['cas_type_id']) {
            $sel1 = "selected='selected'";
        } else {
            $sel1 = '';
        }

        if ($targ2 == $type['cas_type_id']) {
            $sel2 = "selected='selected'";
        } else {
            $sel2 = '';
        }

        if ($targb == $type['cas_type_id']) {
            $selb = "selected='selected'";
        } else {
            $selb = '';
        }

        $targ_options1 .= "<option value='$type[cas_type_id]' $sel1>$type[type_name]</option>\n";
        $targ_options2 .= "<option value='$type[cas_type_id]' $sel2>$type[type_name]</option>\n";
        $targ_optionsb .= "<option value='$type[cas_type_id]' $selb>$type[type_name]</option>\n";

        $lasttype = $type['cas_heading_id'];
    }
} else {
    $targ_options_array = array(
        1 => 'Books Authored',
        2 => 'Books Edited',
        4 => 'Referred Journal Articles/Chapters',
        8 => 'Other Peer-Reviewed Sch Activity',
        16 => 'Non Peer-Reviewed Sch Activity',
        32 => 'Conference Presentations',
        64 => 'Conference Attendance',
        128 => 'Student Publications',
        256 => 'Peer-Reviewed Pubs, Submitted',
        512 => 'Grants',
        1024 => 'Scholarly Service',
    );

    foreach ($targ_options_array as $key => $option) {
        if ($targ1 == $key) {
            $sel1 = "selected='selected'";
        } else {
            $sel1 = '';
        }

        if ($targ2 == $key) {
            $sel2 = "selected='selected'";
        } else {
            $sel2 = '';
        }

        if ($targb == $key) {
            $selb = "selected='selected'";
        } else {
            $selb = '';
        }

        $targ_options1 .= "<option value='$key' $sel1>$option</option>\n'";
        $targ_options2 .= "<option value='$key' $sel2>$option</option>\n'";
        $targ_optionsb .= "<option value='$key' $selb>$option</option>\n'";
    }
}

//Header Chooser
if ($caqc_flag == 1) {
    $sel = 'checked';
} else {
    $sel = '';
}

if ($norm_flag == 1) {
    $nsel = 'checked';
} else {
    $nsel = '';
}

if (!$caqc_flag) {
    $disclaimer = "<div style='font-size:10px;'>*Note that the raw category data can include 'in progress' and 'rejected' items. Use the CAQC categories for better filtering</div>";
} else {
    $disclaimer = '';
}

$vars = getPageVariables('stats');
$vars['header']['favorites'] = "<div style='font-size: 13px;'>
    <input type='checkbox' name='caqc_check' $sel onClick='if(document.form1.caqc_flag.value==0) document.form1.caqc_flag.value=1 ; else document.form1.caqc_flag.value=0; document.form1.targ1.value=0;document.form1.targ2.value=0; document.form1.targb.value=0; document.forms.form1.submit();'>
    Use CAQC Categories
    <input type='checkbox' name='norm_check' $nsel onClick='if(document.form1.norm_flag.value==0) document.form1.norm_flag.value=1 ; else document.form1.norm_flag.value=0; document.forms.form1.submit();'>
    Normalize by Faculty #
</div>";

$pageVars = array(
    'caqc_flag'=> $caqc_flag,
    'norm_flag'=> $norm_flag,
    'div_options'=> $div_options,
    'div_optionsb'=> $div_optionsb,
    'div_optionsb2'=> $div_optionsb2,
    'unit'=> isset($_REQUEST['unit']) ? $_REQUEST['unit'] : null,
    'unitb'=> isset($_REQUEST['unitb']) ? $_REQUEST['unitb'] : null,
    'unitb2'=> isset($_REQUEST['unitb2']) ? $_REQUEST['unitb2'] : null,
    'targ1'=> isset($_REQUEST['targ1']) ? $_REQUEST['targ1'] : null,
    'targ2'=> isset($_REQUEST['targ2']) ? $_REQUEST['targ2'] : null,
    'targb'=> isset($_REQUEST['targb']) ? $_REQUEST['targb'] : null,
    'targ_options1'=> $targ_options1,
    'targ_options2'=> $targ_options2,
    'targ_optionsb'=> $targ_optionsb,
    'disclaimer'=> $disclaimer
);

mergePageVariables($vars, $pageVars);
echo $twig->render('stats.twig', $vars);

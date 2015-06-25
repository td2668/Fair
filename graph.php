<?php
require_once __DIR__ . '/includes/config.inc.php';
require_once __DIR__ . '/vendor/conservatory/jpgraph/src/jpgraph.php';
require_once __DIR__ . '/vendor/conservatory/jpgraph/src/jpgraph_line.php';

//for the bitwise routines
global $db, $session;

if (!$session->has('user')) {
    throwAccessDenied();
}

//preload the caqc categories
$targ_options_array = array(
    1 => 'Books Authored',
    2 => 'Books Edited',
    4 => 'Refereed Journal Articles/Chapters',
    8 => 'Other Peer-Reviewed Sch Activity',
    16 => 'Non Peer-Reviewed Sch Activity',
    32 => 'Conference Presentations',
    64 => 'Conference Attendance',
    128 => 'Student Publications',
    256 => 'Peer-Reviewed Pubs, Submitted',
    512 => 'Grants',
    1024 => 'Scholarly Service',
);
$unit = (isset($_REQUEST["unit"])) ? CleanString($_REQUEST["unit"]) : false;
$unitb = (isset($_REQUEST["unitb"])) ? CleanString($_REQUEST["unitb"]) : false;
$targ1 = (isset($_REQUEST["targ1"])) ? CleanString($_REQUEST["targ1"]) : false;
$targ2 = (isset($_REQUEST["targ2"])) ? CleanString($_REQUEST["targ2"]) : false;

//caqc categories is the default
$caqc_flag = (isset($_REQUEST["caqc_flag"])) ? CleanString($_REQUEST["caqc_flag"]) : 1;
$norm_flag = (isset($_REQUEST["norm_flag"])) ? CleanString($_REQUEST["norm_flag"]) : 0;

//If first item is missing, swap for simplicity.
if ($unitb && !$unit) {
    $unit = $unitb;
    $unitb = false;
}

if ($targ2 && !$targ1) {
    $targ1 = $targ2;
    $targ2 = false;
}

//if unit is <1000 its a faculty. If greater it is a dept.
if ($unit) {
    if ($unit > 1000) {
        $fdtarg = $unit - 1000;
        $fac_dept = "departments.department_id=$fdtarg";
        $sql = "SELECT name from departments WHERE department_id=$fdtarg";
        $result = $db->GetRow($sql);
        $fdname = $result['name'];

        //Get the data for normalizing
        $sql = "SELECT count(*) as count from users LEFT JOIN users_ext on(users.user_id=users_ext.user_id) WHERE tss=1 and department_id=$fdtarg";
        $res = $db->GetRow($sql);
        $fac1 = $res['count'];
    } else {
        $fdtarg = $unit;
        $fac_dept = "divisions.division_id=$fdtarg";
        $sql = "SELECT name from divisions WHERE division_id=$fdtarg";
        $result = $db->GetRow($sql);
        $fdname = $result['name'];

        //Get the data for normalizing
        $sql = "SELECT count(*) as count from users
    			LEFT JOIN users_ext on(users.user_id=users_ext.user_id)
    			LEFT JOIN departments on(users.department_id=departments.department_id)
    			LEFT JOIN divisions on (departments.division_id=divisions.division_id)
    			WHERE tss=1 and divisions.division_id=$fdtarg";
        $res = $db->GetRow($sql);
        $fac1 = $res['count'];
    }
}

//Repeat for 2nd unit
if ($unitb) {
    if ($unitb > 1000) {
        $fdtarg = $unitb - 1000;
        $fac_dept2 = "departments.department_id=$fdtarg";
        $sql = "SELECT name from departments WHERE department_id=$fdtarg";
        $result = $db->GetRow($sql);
        $fdname2 = $result['name'];

        //Get the data for normalizing
        $sql = "SELECT count(*) as count from users LEFT JOIN users_ext on(users.user_id=users_ext.user_id) WHERE tss=1 and department_id=$fdtarg";
        $res = $db->GetRow($sql);
        $fac2 = $res['count'];
    } else {
        $fdtarg = $unitb;
        $fac_dept2 = "divisions.division_id=$fdtarg";
        $sql = "SELECT name from divisions WHERE division_id=$fdtarg";
        $result = $db->GetRow($sql);
        $fdname2 = $result['name'];

        //Get the data for normalizing
        $sql = "SELECT count(*) as count from users
    			LEFT JOIN users_ext on(users.user_id=users_ext.user_id)
    			LEFT JOIN departments on(users.department_id=departments.department_id)
    			LEFT JOIN divisions on (departments.division_id=divisions.division_id)
    			WHERE tss=1 and divisions.division_id=$fdtarg";
        $res = $db->GetRow($sql);
        $fac2 = $res['count'];
    }
}

if (($unit) && ($targ1 || $targ2)) {
    if ($targ1) {

        //lookup by type_id if caqc not set.
        if ($caqc_flag) {
            $cattarg = "cci.caqc_flags & $targ1";
        } else {
            $cattarg = "cas_type_id=$targ1";
        }
        $sql = "SELECT COUNT(*) as count,YEAR(cci.n09) as year, divisions.name as fname, departments.name as dname
				FROM cas_cv_items as cci
				LEFT JOIN users on(cci.user_id=users.user_id)
				LEFT JOIN departments on(users.department_id=departments.department_id)
				LEFT JOIN divisions on(departments.division_id=divisions.division_id)
				WHERE $fac_dept AND $cattarg AND YEAR(cci.n09)>2000 GROUP BY YEAR(cci.n09)";
        $result = $db->GetAll($sql);

        //separate into two arrays for graphing calls. No missing values allowed
        $datay = array();
        $datax = array();
        $count = 0;
        for ($year = 2000; $year <= date('Y'); $year++) {
            $datax[] = $year;

            //zero returned here if no value found
            $total = findvalue($result, $year);
            if ($norm_flag && $total > 0 && $fac1 > 0) {
                $datay[] = $total / $fac1;
            } else {
                $datay[] = $total;
            }
            if ($year == '2008') {
                $amin = $count;
            }

            // This is the first year that reporting was required
            if ($year == date('Y') - 1) {
                $amax = $count;
            }
            $count++;
        }
    }

    if ($targ2) {
        if ($caqc_flag) {
            $cattarg = "cci.caqc_flags & $targ2";
        } else {
            $cattarg = "cas_type_id=$targ2";
        }
        $sql = "SELECT COUNT(*) as count,YEAR(cci.n09) as year, divisions.name as fname, departments.name as dname
				FROM cas_cv_items as cci
				LEFT JOIN users on(cci.user_id=users.user_id)
				LEFT JOIN departments on(users.department_id=departments.department_id)
				LEFT JOIN divisions on(departments.division_id=divisions.division_id)
				WHERE $fac_dept AND $cattarg AND YEAR(cci.n09)>2000 GROUP BY YEAR(cci.n09)";
        $result = $db->GetAll($sql);

        //separate into two arrays
        $datay2 = array();
        $datax2 = array();
        $count = 0;
        for ($year = 2000; $year <= date('Y'); $year++) {
            $datax2[] = $year;
            $total = findvalue($result, $year);
            if ($norm_flag && $total > 0 && $fac1 > 0) {
                $datay2[] = $total / $fac1;
            } else {
                $datay2[] = $total;
            }
            if ($year == '2008') {
                $amin = $count;
            }
            if ($year == date('Y') - 1) {
                $amax = $count;
            }
            $count++;
        }
    }
}

//Repeat for second unit
if (($unitb) && ($targ1)) {
    if ($caqc_flag) {
        $cattarg = "cci.caqc_flags & $targ1";
    } else {
        $cattarg = "cas_type_id=$targ1";
    }
    $sql = "SELECT COUNT(*) as count,YEAR(cci.n09) as year, divisions.name as fname, departments.name as dname
			FROM cas_cv_items as cci
			LEFT JOIN users on(cci.user_id=users.user_id)
			LEFT JOIN departments on(users.department_id=departments.department_id)
			LEFT JOIN divisions on(departments.division_id=divisions.division_id)
			WHERE $fac_dept2 AND $cattarg AND YEAR(cci.n09)>2000 GROUP BY YEAR(cci.n09)";
    $result2 = $db->GetAll($sql);

    //separate into two arrays
    $datay2 = array();
    $count = 0;
    for ($year = 2000; $year <= date('Y'); $year++) {
        $total = findvalue($result2, $year);
        if ($norm_flag && $total > 0 && $fac2 > 0) {
            $datay2[] = $total / $fac2;
        } else {
            $datay2[] = $total;
        }
        $count++;
    }
}

// Setup the graph
$graph = new Graph(500, 350);
$graph->SetMargin(40, 40, 20, 30);
$graph->SetScale("textlin");

// Option1 - Title is dept/faculty
if ($unit && !$unitb) {
    if ($unit > 1000) {
        $graph->title->Set('Department: ' . $fdname);
    } else {
        $graph->title->Set('Faculty: ' . $fdname);
    }
}

// Option2: Title is Type
if ($unit && $unitb && $targ1) {
    if ($caqc_flag) {
        $graph->title->Set($targ_options_array[$targ1]);
    } else {
        $sql = "SELECT type_name from cas_types WHERE cas_type_id=$targ1";
        $result = $db->getRow($sql);
        $graph->title->Set($result['type_name']);
    }
}

$graph->title->SetFont(FF_FONT2, FS_BOLD, 14);
$graph->xaxis->SetPos('min');
if (($unit || $unitb) && $targ1) {
    $p1 = new LinePlot($datay);
    $graph->Add($p1);
    if ($unitb) {
        if ($unit > 1000) {
            $p1->SetLegend($fdname);
        } else {
            $p1->SetLegend($fdname);
        }
    } else {
        if ($caqc_flag) {
            $p1->SetLegend($targ_options_array[$targ1]);
        } else {
            $sql = "SELECT type_name from cas_types WHERE cas_type_id=$targ1";
            $result = $db->getRow($sql);
            $p1->SetLegend($result['type_name']);
        }
    }

    $p1->SetColor("blue");
    $p1->AddArea($amin, $amax, LP_AREA_FILLED, 'red@0.4', LP_AREA_BORDER);
    $p1->SetWeight(4);
}

if ($unitb && $targ1) {
    $p3 = new LinePlot($datay2);
    $graph->Add($p3);
    if ($unitb > 1000) {
        $p3->SetLegend($fdname2);
    } else {
        $p3->SetLegend($fdname2);
    }
    $p3->SetColor("orange");
    $p3->SetWeight(4);
}

if (($unit || $unitb) && $targ2) {
    $p2 = new LinePlot($datay2);
    $graph->Add($p2);
    if ($caqc_flag) {
        $p2->SetLegend($targ_options_array[$targ2]);
    } else {
        $sql = "SELECT type_name from cas_types WHERE cas_type_id=$targ2";
        $result = $db->getRow($sql);
        $p2->SetLegend($result['type_name']);
    }

    $p2->SetColor("green");
    $p2->SetWeight(4);
}

$graph->xaxis->SetTickLabels($datax);
$graph->yaxis->scale->SetGrace(10, 0);
$graph->legend->SetPos(0.5, 0.9, 'center', 'bottom');

// Output line
if (isset($p1) || isset($p2) || isset($p3)) {
    $graph->Stroke();
}
/**
 * findvalue function.
 * Locates a value assocaited with a year, or return a zer0
 * @access public
 * @param mixed $array
 * @return void
 */
function findvalue($array, $year) {
    foreach ($array as $item) {
        if ($item['year'] == $year) {
            return $item['count'];
        }
    }
    return 0;
}

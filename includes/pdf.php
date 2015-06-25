<?php

@require_once('tcpdf/tcpdf.php');
require_once('includes/cv_functions.php');
require_once('includes/cv_item.inc.php');

/**
 * Generate the CV for the current user
 *
 * @param int $userId userId to generated the PDF for
 * @param string $flag determines which CV set to generate
 * @return string return error message if something goes wrong
 */
function GenerateCV($userId, $flag = '', $style = 'apa') {
    if (!$style) {
        $style = 'apa';
    }

    $cvData = array();

    // get the profile information
    $cvInformation = GetPersonData($userId);
    if (sizeof($cvInformation) > 0) {
        $cvData['cv_information'] = $cvInformation;
    } else {
        // no data, failed
        $status = false;
        $statusMessage = 'An error occured while getting the personal information.';
    }

    $localFileName = (isset($options['local_file_name'])) ? $options['local_file_name'] : false;

    // get the report data
    // get for all header types
    $cvItemData = GetCvItems($userId, array(), $flag);

    if (is_array($cvItemData) && sizeof($cvItemData) == 0) {
        // no items found
    } elseif (is_array($cvItemData)) {
        // got some data
        $cvItems = array();
        foreach ($cvItemData AS $key => $data) {

            // ignore items with no type assigned
            if ($data['cas_type_id'] > 0) {

                //20091030 Changed by Trevor to switch from category display to types display.
                $currentHeaderCategory = GetCasHeading($data['cas_type_id']);
                $currentHeaderTitle = GetHeading($data['cas_type_id']);

                // store the data by header type
                $cvData['cv_items'][$currentHeaderCategory][$currentHeaderTitle][] = $data;
            }
        }
    } else {
        // an error occured
        $status = false;
        $statusMessage = "An error occurred while getting the cv item data. ({$cvItemData})";
    }

    if (sizeof($cvData['cv_items']) >= 0) {

        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDisplayMode('default', 'continuous');

        // set up some personalized data parameters
        $userFullName = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'];
        $fileName = "{$userFullName}.pdf";
        $fileName = CleanFilename($fileName);
        $headerTitle = "Curriculum Vitae: $userFullName";
        $headerText = ' ';

        // set document information
        // added by TDavis to avoid jumping at bottom of pages
        $pdf->SetDisplayMode('real', 'OneColumn', 'UseNone');

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($userFullName);
        $pdf->SetTitle("Curriculum Vitae for $userFullName");
        $pdf->SetSubject('CV: ' . $userFullName);
        $pdf->SetKeywords("cv, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}");

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle, $headerText);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        //set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------
        // Create the PDF Document:
        $pdf->AddPage();

        // adds a new page / page break
        $tagvs = array(
            'h1' => array(
                0 => array(
                    'h' => '',
                    'n' => 2,
                ),
                1 => array(
                    'h' => 1.3,
                    'n' => 1,
                ),
            ),
        );
        $pdf->setHtmlVSpace($tagvs);
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, '', '');

        // ***************************************
        if (is_array($cvData['cv_items']) && sizeof($cvData['cv_items']) > 0) {
            SetNormal($pdf);
            foreach ($cvData['cv_items'] as $heading => $cvItem) {
                AddH1($pdf, $heading);
                DisplayCvData($cvItem, $pdf, true);
                $pdf->Ln(3);
            }
        }

        //Close and output PDF document
        if ($localFileName) {

            // send to a local file
            $pdf->Output($localFileName, 'F');
        } else {

            // stream to the browser
            // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
            $pdf->Output($fileName, 'D');
        }
    } else {

        // failed to get data
        return $cvData['status_message'];
    }
}

/**
 * Generate the CV for the current user
 *
 * @param int $userId userId to generated the PDF for
 * @param string $flag determines which CV set to generate
 * @return string return error message if something goes wrong
 */
function GenerateCAQC($userId, $flag = '', $style = 'apa') {
    global $db;
    if (!$style) {
        $style = 'apa';
    }

    $cvData = array();

    // get the profile information
    $cvInformation = GetPersonData($userId);
    if (sizeof($cvInformation) > 0) {
        $cvData['cv_information'] = $cvInformation;
    } else {

        // no data, failed
        $status = false;
        $statusMessage = 'An error occurred while getting the personal information.';
    }

    $localFileName = (isset($options['local_file_name'])) ? $options['local_file_name'] : false;
    if (1) {

        // Extend the TCPDF class to create custom Header and Footer
        class MYPDF extends TCPDF {

            //Page header
            public function Header() {
                global $userId;
                $cvData = array();

                // get the profile information
                $cvInformation = GetPersonData($userId);
                if (sizeof($cvInformation) > 0) {
                    $cvData['cv_information'] = $cvInformation;
                } else {

                    // no data, failed
                    $status = false;
                    $statusMessage = 'An error occurred while getting the personal information.';
                }

                // Logo
                $image_file = K_PATH_IMAGES . PDF_HEADER_LOGO;
                $this->Image($image_file, 14, 12, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

                // Set font
                $this->setFont('coprg', '', 13);
                $headerTitle = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'] . ' - ' . $cvData['cv_information']['title'];
                $headerText = "Curriculum Vitae";
                $this->SetY($this->GetY() + 5);

                // Title
               $this->Cell(
                    0,
                    15,
                    $headerText,
                    0,
                    2,
                    'C', //align
                    0, //fill
                    '', //link
                    0, //stretch
                    false, //ignore min
                    'C', //calign
                    'C' //valign
                );

                $this->Cell(
                    0,
                    15,
                    $headerTitle,
                    0,
                    2,
                    'C', //align
                    0, //fill
                    '', //link
                    0, //stretch
                    false, //ignore min
                    'C', //calign
                    'C' //valign
                );

            }
        }

        // create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set up some personalized data parameters
        $userFullName = $cvData['cv_information']['first_name'] . ' ' . $cvData['cv_information']['last_name'];
        $userTitle = $cvData['cv_information']['title'];
        $fileName = "{$userFullName}.pdf";
        $fileName = CleanFilename($fileName);

        // set document information
        // added by TDavis to avoid jumping at bottom of pages
        $pdf->SetDisplayMode('real', 'OneColumn', 'UseNone');

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($userFullName);
        $pdf->SetTitle("Curriculum Vitae for $userFullName");
        $pdf->SetSubject('CV: ' . $userFullName);
        $pdf->SetKeywords("cv, mru, {$cvData['cv_information']['first_name']}, {$cvData['cv_information']['last_name']}");
        define('CAQC_NORMAL_FONT_SIZE', 10);
        define('CAQC_SMALLER_FONT_SIZE', 9);

        // set header data
        $pdf->SetHeaderMargin(15);
        $pdf->SetFooterMargin(20);
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        //set margins
        $pdf->SetMargins(
            15, //PDF_MARGIN_LEFT
            38, //PDF_MARGIN_TOP
            15 //PDF_MARGIN_RIGHT
        );

        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------
        // Create the PDF Document:
        $pdf->AddPage();

        // adds a new page / page break
        //Now reset some params for first-page only header
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(
            15, //PDF_MARGIN_LEFT
            15, //PDF_MARGIN_TOP
            15 //PDF_MARGIN_RIGHT
        );

        $pdf->SetHeaderMargin(10);
        $tagvs = array(
            'h1' => array(
                0 => array(
                    'h' => '',
                    'n' => 2,
                ),
                1 => array(
                    'h' => 1.3,
                    'n' => 1,
                ),
            ),
        );
        $pdf->setHtmlVSpace($tagvs);
        $pdf->SetCellHeightRatio(1.2);
        $pdf->ln(5);

        // ***************************************   Degrees
        $sql = "SELECT item.n05,YEAR(item.n18) as year,types.name as degree_name,cas_institutions.name as institution_name
            FROM cas_cv_items as item
            LEFT JOIN cas_degree_types as types on item.n02=types.id
            LEFT JOIN cas_institutions on item.n04=cas_institutions.id
            WHERE user_id = {$userId}
            AND cas_type_id=1
            AND item.n13=2
            AND mycv2=1
            ORDER BY rank desc";
        $list = $db->GetAll($sql);
        if ($list) {
            AddCAQC1($pdf, 'Completed Academic Degrees');
            CAQC_Header($pdf, array('Degree Name', 'Subject Area', 'Where Completed', 'Date of Completion'), array(0.18, 0.3, 0.32, 0.2));
            $count = 1;
            foreach ($list as $key => $item) {
                if ($item['year'] == 0) {
                    $item['year'] = '';
                }
                $maxH = CAQC_list($pdf, array($item['degree_name'], $item['n05'], $item['institution_name'], $item['year']), array('L', 'L', "l", 'C'), array(0.18, 0.3, 0.32, 0.2));
                if ($count != count($list)) {
                    cvline($pdf, $maxH);
                }
                $count++;
                $pdf->Ln($maxH);
            }
        }

        // ************************* Advanced Studies in Progress
        $sql = "SELECT item.n05,YEAR(item.n19) as year,types.name as degree_name,cas_institutions.name as institution_name
            FROM cas_cv_items as item
            LEFT JOIN cas_degree_types as types on item.n02=types.id
            LEFT JOIN cas_institutions on item.n04=cas_institutions.id
            WHERE user_id = {$userId}
            AND cas_type_id=1
            AND mycv2=1
            AND (item.n13=1 OR item.n13=3)
            ORDER BY rank desc";
        $list = $db->GetAll($sql);
        if ($list) {
            AddCAQC1($pdf, 'Advanced Studies in Progress');
            CAQC_Header($pdf, array('Degree Name', 'Subject Area', 'Where Enrolled', 'Est. Completion'), array(0.2, 0.3, 0.32, 0.18));
            $count = 1;
            foreach ($list as $item) {
                if ($item['year'] == 0) {
                    $item['year'] = '';
                }
                $maxH = CAQC_list($pdf, array($item['degree_name'], $item['n05'], $item['institution_name'], $item['year']), array('L', 'L', "L", 'C'), array(0.2, 0.3, 0.32, 0.18));
                if ($count != count($list)) {
                    cvline($pdf, $maxH);
                }
                $count++;
                $pdf->Ln($maxH);
            }
        }

        // ************************* Academic Appointments
        $sql = "SELECT
                item.n01,
                YEAR(item.n09) as start_year,
                YEAR(item.n18) as end_year,
                cas_institutions.name as institution_name,
                depts.name as subject
              FROM
                cas_cv_items as item
                LEFT JOIN cas_institution_departments as depts on item.n13=depts.id
                LEFT JOIN cas_institutions on item.n04=cas_institutions.id
              WHERE user_id = {$userId}
                AND item.n01 NOT LIKE '%Chair%'
                AND item.n01 NOT LIKE '%Co-ordinator%'
                AND item.n01 NOT LIKE '%Coordinator%'
                AND item.n01 NOT LIKE '%Director%'
                AND item.n01 NOT LIKE '%Manager%'
                AND cas_type_id=3
                AND mycv2=1
              ORDER BY rank desc";
        $list = $db->GetAll($sql);
        if ($list) {
            AddCAQC1($pdf, 'Academic Appointments');
            CAQC_Header($pdf, array('Appointment Level', 'Institution', 'Dates', 'Subject Area'), array(0.25, 0.35, 0.15, 0.25));
            $count = 1;
            foreach ($list as $item) {
                if ($item['start_year'] == 0 && $item['start_year'] == 0) {
                    $years = '';
                } elseif ($item['start_year'] == 0) {
                    $years = '    -' . $item['end_year'];
                } elseif ($item['end_year'] == 0) {
                    $years = $item['start_year'] . ' -';
                } else {
                    $years = $item['start_year'] . ' - ' . $item['end_year'];
                }
                $maxH = CAQC_list($pdf, array($item['n01'], $item['institution_name'], $years, $item['subject']), array('L', 'L', "L", 'L'), array(0.25, 0.35, 0.15, 0.25));
                if ($count != count($list)) {
                    cvline($pdf, $maxH);
                }
                $count++;
                $pdf->Ln($maxH);
            }
        }

        // ************************* Administrative Appointments
        $sql = "SELECT
                item.n01,
                YEAR(item.n09) as start_year,
                YEAR(item.n18) as end_year,
                cas_institutions.name as institution_name,
                depts.name as subject
              FROM
                cas_cv_items as item
                LEFT JOIN cas_institution_departments as depts on item.n13=depts.id
                LEFT JOIN cas_institutions on item.n04=cas_institutions.id
              WHERE user_id = {$userId}
                AND (item.n01 LIKE '%Chair%'
                    OR item.n01 LIKE '%Co-ordinator%'
                    OR item.n01 LIKE '%Coordinator%'
                    OR item.n01 LIKE '%Director%'
                    OR item.n01 LIKE '%Manager%')
                AND cas_type_id=3
                AND mycv2=1
              ORDER BY rank desc";
        $list = $db->GetAll($sql);
        if ($list) {
            AddCAQC1($pdf, 'Administrative Appointments');
            CAQC_Header($pdf, array('Appointment Level', 'Institution', 'Dates'), array(0.4, 0.42, 0.18));
            $count = 1;
            foreach ($list as $item) {
                if ($item['start_year'] == 0 && $item['start_year'] == 0) {
                    $years = '';
                } elseif ($item['start_year'] == 0) {
                    $years = '    -' . $item['end_year'];
                } elseif ($item['end_year'] == 0) {
                    $years = $item['start_year'] . ' -';
                } else {
                    $years = $item['start_year'] . ' - ' . $item['end_year'];
                }
                $maxH = CAQC_list($pdf, array($item['n01'], $item['institution_name'], $years), array('L', 'L', "L"), array(0.4, 0.42, 0.18));
                if ($count != count($list)) {
                    cvline($pdf, $maxH);
                }
                $count++;
                $pdf->Ln($maxH);
            }
        }

        // *************************  TEACHING EXPERIENCE SECTION
        $output = array();
        $useditems = array();
        $sql = "SELECT * FROM user_disable_banner WHERE user_id=$userId";
        $disable_banner = $db->GetRow($sql);
        if (!$disable_banner) {

            //Grab all the info from the 'courses' table - group by course number
            $sql = "SELECT subject,crsenumb,crsedescript FROM course_teaching LEFT JOIN courses on (course_teaching.course_id=courses.course_id) WHERE course_teaching.user_id={$userId} group by CONCAT(subject,crsenumb)";
            $courses = $db->GetAll($sql);
            $sql = "SELECT * FROM cas_institutions WHERE name LIKE '%Mount Royal%' OR name LIKE '%MRU%' OR name LIKE '%MRC%'";
            $inst = $db->getAll($sql);
            $list = 'AND (n04=0 OR ';
            foreach ($inst as $one) {
                $list .= "n04=$one[id] OR ";
            }

            //filler
            $list .= "n02=9999) ";

            $excludes = '';
            foreach ($courses as $course) {
                if ($course['subject'] != '' AND $course['crsenumb'] != '') {

                    //Then pull all year data for the particular course
                    $sql = "SELECT course_teaching_id, LEFT(term,4) as term FROM course_teaching LEFT JOIN courses on (course_teaching.course_id=courses.course_id) WHERE course_teaching.user_id={$userId} AND subject='$course[subject]' AND crsenumb='$course[crsenumb]'  GROUP BY LEFT(term,4)";
                }
                $results = $db->GetAll($sql);

                //now make the array key the course_teaching_id to allow sorting
                $years = array();
                foreach ($results as $result) {
                    $years[$result['course_teaching_id']] = $result['term'];
                }

                //Now load the course record from the cv items into the same table
                $sql = "SELECT cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n05 as descrip FROM cas_cv_items WHERE cas_type_id=29 AND user_id=$userId $list AND (n01 LIKE '$course[subject] $course[crsenumb]' OR n01 LIKE '$course[subject]$course[crsenumb]') AND mycv2=1 ORDER BY start_year";
                $type29 = $db->GetAll($sql);
                if (count($type29) > 0) {

                    //Add entries to the years table and then sort on the term
                    foreach ($type29 as $item) {
                        $useditems[] = $item['cv_item_id'];

                        //Search (the hard way) for duplicate entries before adding
                        for ($x = $item['start_year']; $x <= $item['end_year']; $x++) {
                            $found = false;
                            foreach ($years as $year) {
                                if ($year == $x) {
                                    $found = true;
                                }
                            }
                            if (!$found) {
                                $years[] = $x;
                            }
                        }
                    }
                }

                if (count($useditems) > 0) {
                    foreach ($useditems as $item) {
                        $excludes .= "AND cv_item_id != $item ";
                    }
                }

                //Sort it all by year
                sort($years);

                //Now assemble - continuous stretch of years becomes xxxx-yyyy.
                $start = false;
                $end = false;
                foreach ($years as $year) {
                    if ($start == false) {
                        $start = $year;
                    } elseif ($end == false && $year == $start + 1) {
                        $end = $year;
                    } elseif ($year == ($end + 1)) {
                        $end = $year;
                    } else {
                        if ($end == false) {
                            $output[] = array(
                                'course' => "$course[subject]$course[crsenumb]",
                                'year' => $start,
                                'descrip' => $course['crsedescript'],
                            );
                        } else {
                            $output[] = array(
                                'course' => "$course[subject]$course[crsenumb]",
                                'year' => "$start - $end",
                                'descrip' => $course['crsedescript'],
                            );
                        }
                        $start = false;
                        $end = false;
                    }
                }

                if ($start == false) {
                    $output[] = array(
                        'course' => "$course[subject]$course[crsenumb]",
                        'year' => $year,
                        'descrip' => $course['crsedescript'],
                    );
                } elseif ($end == false) {
                    $output[] = array(
                        'course' => "$course[subject]$course[crsenumb]",
                        'year' => $start,
                        'descrip' => $course['crsedescript'],
                    );
                } else {
                    $output[] = array(
                        'course' => "$course[subject]$course[crsenumb]",
                        'year' => "$start - $end",
                        'descrip' => $course['crsedescript'],
                    );
                }
            }
        }

        //Now get remaining MRU ones as selected
        $sql = "SELECT n01 as course, cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n04,n05 as crsedescript, ca.name
       		FROM cas_cv_items
       		LEFT JOIN cas_institutions as ca on(cas_cv_items.n04=ca.id)
       		WHERE cas_type_id=29
       			AND user_id=$userId
       			and mycv2=1
       			AND YEAR(n09)!=0
       			$excludes
       			AND (
       			(ca.name LIKE '%Mount Royal%' OR ca.name LIKE '%MRU%' OR ca.name LIKE '%MRC%')
       			OR ca.name IS NULL)
       			GROUP BY course
       			";
        $courses = $db->GetAll($sql);

        //These data are grouped by course - now get all items
        // note - if the name is NULL then it is assumed to be MRU
        foreach ($courses as $course) {
            $sql = "SELECT n01 as course, cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n04,n05 as crsedescript, ca.name
       		FROM cas_cv_items
       		LEFT JOIN cas_institutions as ca on(cas_cv_items.n04=ca.id)
       		WHERE cas_type_id=29
       			AND user_id=$userId
       			and mycv2=1
       			AND YEAR(n09)!=0
       			$excludes
       			AND (
       			(ca.name LIKE '%Mount Royal%' OR ca.name LIKE '%MRU%' OR ca.name LIKE '%MRC%')
       			OR ca.name IS NULL)
       			AND n01='$course[course]'
       			";
            $datelist = $db->GetAll($sql);
            if (count($datelist) > 0) {
                $years = array();
                foreach ($datelist as $item) {
                    $useditems[] = $item['cv_item_id'];

                    //Search (the hard way) for duplicate entries before adding
                    for ($x = $item['start_year']; $x <= $item['end_year']; $x++) {
                        $found = false;
                        foreach ($years as $year) {
                            if ($year == $x) {
                                $found = true;
                            }
                        }
                        if (!$found) {
                            $years[] = $x;
                        }
                    }
                }
            }

            //Sort it all by year
            sort($years);

            //Now assemble - continuous stretch of years becomes xxxx-yyyy.
            $start = false;
            $end = false;
            foreach ($years as $year) {
                if ($start == false) {
                    $start = $year;
                } elseif ($end == false && $year == $start + 1) {
                    $end = $year;
                } elseif ($year == ($end + 1)) {
                    $end = $year;
                } else {
                    if ($end == false) {
                        $output[] = array(
                            'course' => "$course[course]",
                            'year' => $start,
                            'descrip' => $course['crsedescript'],
                        );
                    } else {
                        $output[] = array(
                            'course' => "$course[course]",
                            'year' => "$start - $end",
                            'descrip' => $course['crsedescript'],
                        );
                    }
                    $start = false;
                    $end = false;
                }
            }

            if ($start == false) {
                $output[] = array(
                    'course' => "$course[course]",
                    'year' => $year,
                    'descrip' => $course['crsedescript'],
                );
            } elseif ($end == false) {
                $output[] = array(
                    'course' => "$course[course]",
                    'year' => $start,
                    'descrip' => $course['crsedescript'],
                );
            } else {
                $output[] = array(
                    'course' => "$course[course]",
                    'year' => "$start - $end",
                    'descrip' => $course['crsedescript'],
                );
            }
        }

        //Sort by year (otherwise its by course)
        foreach ($output as $key => $row) {
            $course1[$key] = $row['course'];
            $year1[$key] = $row['year'];
            $descrip1[$key] = $row['descrip'];
        }

        if (!empty($output)) {
            if (array_multisort($year1, SORT_DESC, SORT_STRING, $course1, SORT_ASC, SORT_STRING, $descrip1, SORT_ASC, SORT_STRING, $output)) {
                $biglist[] = array(
                    'institution' => 'Mount Royal University',
                    'courses' => $output,
                );
            }
        }

        //Now deal with all the courses in the cas_cv_items that weren't found above.
        $excludes = '';
        if (count($useditems) > 0) {
            foreach ($useditems as $item) {
                $excludes .= "AND cv_item_id != $item ";
            }
        }

        //Grab all unique institutions
        //Note: May need to collapse multiple names
        $sql = "SELECT cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n04,n05, cas_institutions.name
       		FROM cas_cv_items
       		LEFT JOIN cas_institutions on(cas_cv_items.n04=cas_institutions.id)
       		WHERE cas_type_id=29
       			AND user_id=$userId
       			and mycv2=1
       			AND YEAR(n09)!=0
       			$excludes
       			AND n04 != 0
       			AND id IS NOT NULL
       			GROUP BY n04";
        $institutions = $db->GetAll($sql);

        //For each institution, grab courses. If they did individual entries per year then this is a problem. But they have to fix it.
        foreach ($institutions as $inst) {
            $sql = "SELECT n01 as course, cv_item_id, YEAR(n09) AS start_year, YEAR(n18) as end_year, n04,n05, cas_institutions.name
		       		FROM cas_cv_items
		       		LEFT JOIN cas_institutions on(cas_cv_items.n04=cas_institutions.id)
		       		WHERE cas_type_id=29
		       			AND user_id=$userId
		       			and mycv2=1
		       			AND YEAR(n09)!=0
		       			$excludes
		       			AND n04=$inst[n04]
		       			GROUP BY course,start_year,end_year
		       			ORDER BY start_year DESC
		       			";
            $instlist = $db->getAll($sql);
            if ($instlist) {
                $output = array();
                foreach ($instlist as $item) {

                    //I don't collect by years here. Just a simple dump. Only issue is that a start year with no end year
                    //  might be a '2013 - on' or just '2013'. I assume the latter
                    if ($item['end_year'] == 0 OR $item['start_year'] == $item['end_year']) {
                        $output[] = array(
                            'course' => $item['course'],
                            'year' => $item['start_year'],
                            'descrip' => $item['n05'],
                        );
                    } else {
                        $output[] = array(
                            'course' => $item['course'],
                            'year' => "$item[start_year] - $item[end_year]",
                            'descrip' => $item['n05'],
                        );
                    }
                }
            }

            $biglist[] = array(
                'institution' => $inst['name'],
                'courses' => $output,
            );
        }

        if (isset($biglist) && count($biglist) > 0) {
            $spacer = array(
                0.25,
                0.14,
                0.18,
                0.43,
            );
            AddCAQC1($pdf, 'Teaching Experience');
            CAQC_Header($pdf, array('Institution', 'Years', 'Course', 'Description'), $spacer);
            foreach ($biglist as $ikey => $institution) {
                foreach ($institution['courses'] as $key => $course) {
                    if ($key == 0) {
                        if ($ikey != 0) {
                            cvline($pdf, 0);
                        }
                        $maxH = CAQC_List($pdf, array($institution['institution'], $course['year'], $course['course'], $course['descrip']), array('L', 'L', 'L', 'L'), $spacer);
                        $pdf->Ln($maxH);
                    } else {
                        $maxH = CAQC_List($pdf, array('', $course['year'], $course['course'], $course['descrip']), array('L', 'L', 'L', 'L'), $spacer);
                        $pdf->Ln($maxH);
                    }
                }
            }
        }

        // *************************
        $sql = "SELECT YEAR(n09) as theyear, cas_cv_items.* FROM cas_cv_items WHERE user_id=$userId AND mycv2=1 ORDER BY theyear DESC";
        $allitems = $db->getAll($sql);
        if ($allitems) {
            foreach ($allitems as $key => $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                $sql = "UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id=$item[cv_item_id]";
                $result = $db->Execute($sql);
                $allitems[$key]['caqc_flags'] = $flags->AsInt();
            }

            //Now do a pre-check to see if it is worth printing a header. I know this is inefficient but
            // I didn't fel like revising everything to fix a bug. Shoot me.
            $h1 = false;
            $h2 = false;
            foreach ($allitems as $item) {
                if ($item['caqc_flags'] > 0) {
                    $h1 = true;
                }
                if ($item['cas_type_id'] == 26 ||
                    $item['cas_type_id'] == 82 ||
                    $item['cas_type_id'] == 15 ||
                    $item['cas_type_id'] == 23 ||
                    $item['cas_type_id'] == 24 ||
                    $item['cas_type_id'] == 87 ||
                    $item['cas_type_id'] == 90)
                {
                    $h2 = true;
                }
            }

            if ($h1) {
                AddCAQC1($pdf, 'Scholarly Participation');
            }
            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isBooksAuthored()) {
                    $output = \MRU\Research\CV::formatitem($item);
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Books Authored');
                        $ignore = false;
                    }

                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isBooksEdited()) {
                    $output = \MRU\Research\CV::formatitem($item);
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Books Edited');
                        $ignore = false;
                    }

                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isRefJournals()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Articles in Refereed Journals / Book Chapters');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isOtherPeer()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Other Peer-reviewed Scholarly Activity');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isNonPeer()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Non Peer-reviewed Scholarly Activity');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isConfPres()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Conference Presentations');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isConfAttend()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Conference Attendance');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isStudent()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Student Publications');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isSubmitted()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Peer-reviewed Publications, Submitted');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isGrants()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Grants');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                $flags = new \MRU\Research\Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);
                if ($flags->isService()) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Scholarly Service');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            //**************************
            if ($h2) {
                AddCAQC1($pdf, 'Professional Memberships, Qualifications and Experience');
            }
            $ignore = true;
            foreach ($allitems as $item) {
                if ($item['cas_type_id'] == 26 && $item['mycv2'] == true) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Professional Memberships');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                if (($item['cas_type_id'] == 2 || $item['cas_type_id'] == 82) && $item['mycv2'] == true) {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Professional Qualifications');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }

            $ignore = true;
            foreach ($allitems as $item) {
                if (
                    (
                        $item['cas_type_id'] == 15 ||
                        $item['cas_type_id'] == 23 ||
                        $item['cas_type_id'] == 24 ||
                        $item['cas_type_id'] == 87 ||
                        $item['cas_type_id'] == 90 ||
                        $item['cas_type_id'] == 4 ||
                        $item['cas_type_id'] == 63 ||
                        $item['cas_type_id'] == 64 ||
                        $item['cas_type_id'] == 10 ||
                        $item['cas_type_id'] == 66 ||
                        (
                            (
                                $item['cas_type_id'] >= 47 &&
                                $item['cas_type_id'] <= 62
                            ) ||
                            $item['cas_type_id'] == 80 ||
                            $item['cas_type_id'] == 94
                        ) && $item['n23'] == true
                    ) && $item['mycv2'] == true
                )
                {
                    if ($ignore == true) {
                        AddCAQC2($pdf, 'Professional Experience');
                        $ignore = false;
                    }

                    $output = \MRU\Research\CV::formatitem($item);
                    AddParagraphPlain($pdf, $output);
                }
            }
        }

        //Close and output PDF document
        if ($localFileName) {
            // send to a local file
            $pdf->Output($localFileName, 'F');
        } else {

            // stream to the browser
            // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
            $pdf->Output($fileName, 'D');
        }
    } else {
        // failed to get data
        return $cvData['status_message'];
    }
}

/**
 * Generate a Help PDF
 *
 * @global ADODB-object $db ADODB database object
 */
function GenerateHelpPdf() {
    global $db;

    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetDisplayMode('default', 'continuous');
    $fileName = "cv_help.pdf";
    $fileName = CleanFilename($fileName);

    // set document information
    $pdf->SetDisplayMode('real', 'OneColumn', 'UseNone');

    // added by TDavis to avoid jumping at bottom of pages
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Help Document');
    $pdf->SetSubject('Help for CV Item Types');
    $pdf->SetKeywords("cv, annual report, mru");

    //set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, 18, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    //set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    //set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    //set some language-dependent strings
    $indent1 = 20;
    define("PAGE_WIDTH", 180);
    define("INDENT_1_LEFT", 20);
    define("INDENT_1_RIGHT", 0);
    define("INDENT_2_LEFT", 30);
    define("INDENT_2_RIGHT", 10);
    $sql = "SELECT heading_name,type_name,help_text FROM cas_headings ch JOIN cas_types ct ON (ch.cas_heading_id=ct.cas_heading_id) ORDER BY ch.`order`,ct.`order`";
    $helpData = $db->GetAll($sql);

    // ---------------------------------------------------------
    // Create the PDF Document:
    $pdf->AddPage();

    // adds a new page / page break
    $currentHeading = "";
    foreach ($helpData as $help) {
        if ($currentHeading != $help['heading_name']) {
            $currentHeading = $help['heading_name'];
            $pdf->SetFont(MRUPDF_H1_FONT_FACE, 'BU', 10);
            $pdf->Ln(3);
            $pdf->setX(15);
            $pdf->Cell(0, 5, $currentHeading, '', 1, 'L', 0);
            $pdf->Ln(1);
        }

        $pdf->SetFont(MRUPDF_H2_FONT_FACE, '', 9);
        $pdf->Cell(0, 4, $help['type_name'], '', 1, 'L');
        if ($help['help_text'] != '') {
            $helpText = $help['help_text'];
            $pdf->SetFont(MRUPDF_REGULAR_FONT_FACE, 'I', 8);
            $text = htmlentities($helpText, ENT_COMPAT, 'cp1252');
            $pdf->WriteHTML(nl2br($helpText), true, 0, true, true);
            $pdf->Ln(1);
        } else {
            $pdf->Ln(3);
        }
    }

    //Close and output PDF document
    if (isset($localFileName)) {
        // send to a local file
        $pdf->Output($localFileName, 'F');
    } else {
        // stream to the browser
        // (use 'I' for inline mode (ignores filename), use 'D' for prompt to download mode with filename)
        $pdf->Output($fileName, 'D');
    }
}

/**
 * Draw HR
 *
 * @param TCPDF $pdf
 */
function doHR(&$pdf) {
    $y = $pdf->GetY();
    if ($y < 270) {
        $pdf->Line(35, $y, 170, $y, array('width' => 0.4, 'color' => array(
            0,
            102,
            153,
        )));
        $pdf->Ln(3);
    }
}

/**
 * draw thin HR
 *
 * @param TCPDF $pdf
 */
function thinHR(&$pdf) {
    $y = $pdf->GetY();
    if ($y < 270) {
        $pdf->Line(55, $y, 150, $y, array('width' => 0.2, 'color' => array(
            51,
            204,
            255,
        )));
        $pdf->Ln(3);
    }
}

function cvline(&$pdf, $maxH = 0) {
    $y = $pdf->GetY();
    if ($y < 270) {
        $pdf->ln(1);
        $pdf->Line(15, $y + $maxH + 1, 194, $y + $maxH + 1, array('width' => 0.2, 'color' => array(
            100,
            100,
            100,
        )));
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->ln(1);
    }
}

/**
 * Set H1
 *
 * @param TCPDF $pdf
 */
function SetH1(&$pdf) {
    $pdf->SetFont(MRUPDF_H1_FONT_FACE, 'B', 12);
}

/**
 * Set H2
 *
 * @param TCPDF $pdf
 */
function SetH2(&$pdf) {
    $pdf->SetFont(MRUPDF_H2_FONT_FACE, 'B', 12);
}

/**
 * Set H3
 *
 * @param TCPDF $pdf
 */
function SetH3(&$pdf) {
    $pdf->SetFont(MRUPDF_H3_FONT_FACE, 'B', MRUPDF_H3_FONT_SIZE);
}

/**
 * Set H4
 *
 * @param TCPDF $pdf
 */
function SetH4(&$pdf) {
    $pdf->SetFont(MRUPDF_H4_FONT_FACE, 'B', MRUPDF_H4_FONT_SIZE);
}

function SetCAQC1(&$pdf) {
    $pdf->SetFont(MRUPDF_H4_FONT_FACE, 'B', MRUPDF_H4_FONT_SIZE);
}

function AddCAQC1(&$pdf, $text) {
    global $EvenOdd;
    $y = $pdf->getY();
    if ($y > 250) {
        $pdf->AddPage();
    }
    SetCAQC1($pdf);
    $pdf->Ln();
    $pdf->SetTextColor(0);
    $pdf->SetFillColor(255);
    $pdf->setX(15);
    $utext = " " . strtoupper($text);
    $pdf->Cell(0, 7, $utext, '', 1, 'L', 1);
    $pdf->Bookmark($text, 0, - 1);
    $EvenOdd = 0;
}

function AddCAQC2(&$pdf, $text) {
    global $EvenOdd;
    $y = $pdf->getY();
    if ($y > 250) {
        $pdf->AddPage();
    }
    SetCAQC1($pdf);
    $pdf->Ln(2);
    $pdf->SetTextColor(0);
    $pdf->SetFillColor(255);
    $pdf->setX(15);
    $text = " $text";
    $pdf->Cell(0, 7, $text, '', 1, 'L', 1);
    $pdf->Ln(0);
    $pdf->Bookmark($text, 1, - 1);
    $EvenOdd = 0;
}

/**
 * Set text back to normal
 *
 * @param TCPDF $pdf
 */
function SetNormal(&$pdf) {
    $pdf->SetFont(MRUPDF_REGULAR_FONT_FACE, '', 12);
}

/**
 * Set text to smaller font
 *
 * @param TCPDF $pdf
 */
function SetSmaller(&$pdf) {
    $pdf->SetFont(MRUPDF_SMALLER_FONT_FACE, '', 11);
}

/**
 * Set to bold text
 *
 * @param TCPDF $pdf
 */
function SetNormalBold(&$pdf) {
    $pdf->SetFont(MRUPDF_REGULAR_FONT_FACE, 'B', 12);
}

/**
 * Draw a H1
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddH1(&$pdf, $text) {
    $y = $pdf->getY();
    if ($y > 250) {
        $pdf->AddPage();
    }
    SetH1($pdf);
    $pdf->Ln(8);
    $pdf->SetTextColor(255);
    $pdf->SetFillColor(10, 106, 144);
    $pdf->setX(15);
    $text = " $text";
    $pdf->Cell(0, 7, $text, '', 1, 'L', 1);
    $pdf->SetTextColor(0);
    $pdf->Ln(4);
    $pdf->Bookmark((($text)), 0, 0);
}

/**
 * Draw a H2
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddH2(&$pdf, $text) {
    $y = $pdf->getY();
    if ($y > 260) {
        $pdf->AddPage();
    }
    SetH2($pdf);
    $pdf->setX(20);
    $pdf->Cell(0, 7, $text, '', 1, 'L');
}

/**
 * Draw a H3
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddH3(&$pdf, $text) {
    SetH3($pdf);
    $pdf->Cell(0, 4, $text, 0, 1, 'L');
}

/**
 * Add a HTML formatted paragraph
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddParagraph(&$pdf, $text) {
    SetNormal($pdf);
    $pdf->SetX(20);
    $pdf->writeHTMLCell(155, 4, $pdf->GetX(), $pdf->GetY(), nl2br($text), 0, 1);
    $pdf->Ln(2);
}

/**
 * Render a line of text
 *
 * @param TCPDF $pdf
 */
function AddLine(&$pdf, $text) {
    SetNormal($pdf);
    $pdf->Cell(0, 5, $text, 0, 1, 'L');
}

/**
 * Add a plain text paragraph
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddParagraphPlain(&$pdf, $text) {
    SetNormal($pdf);

    //First convert everything to HTML - to get extended char set across
    $text = htmlentities($text, ENT_COMPAT, 'cp1252');

    //But this also converts existing markup, so now change all &lt and &gt back so that italics will work.
    $text = htmlspecialchars_decode($text, ENT_NOQUOTES);
    $pdf->SetX(20);
    $pdf->SetCellPadding(0);
    $pdf->WriteHTMLCell(160, 5, $pdf->GetX(), $pdf->GetY(), $text, 0, 1, 0, true, 'L', false);
    $pdf->Ln(2);
}

/**
 * Add a Summary style paragraph.  Has larger margins and the text is smaller
 *
 * @param TCPDF $pdf
 * @param string $text text to render
 */
function AddParagraphSummary(&$pdf, $text) {
    SetNormal($pdf);
    $text = htmlentities($text, ENT_COMPAT, 'cp1252');
    $text = htmlspecialchars_decode($text, ENT_NOQUOTES);
    SetSmaller($pdf);
    $pdf->SetX(30);
    $pdf->SetCellPadding(0);
    $pdf->SetY($pdf->GetY() - 2, false);
    $pdf->WriteHTMLCell(130, 5, $pdf->GetX(), $pdf->GetY(), nl2br(($text)), 0, 1, 0, true, 'L', false);
    SetNormal($pdf);
    $pdf->Ln(2);
}

/**
 * Builds up the list of CV items to draw
 *
 * @param array $cvItemsData list of items to add the CV
 * @param TCPDF $pdf
 * @param bool $forCv switches what kind of CV item data was passed in.
 * @param string $type used to render the details of the n_teaching,n_scholarship &n_service details.
 */
function DisplayCvData($cvItemsData, &$pdf, $forCv = false, $type = "") {

    //echo("In Display CV Data");
    global $style;
    if ($style == '') {
        $style = 'apa';
    }

    if ($forCv) {
        foreach ($cvItemsData AS $key1 => $cvHeader) {
            // add the section header?
            $pdf->SetX(16);
            AddH2($pdf, $key1);
            $pdf->SetX(20);
            foreach ($cvHeader AS $key2 => $data) {
                $cvItemSummary = \MRU\Research\CV::formatitem($data, $style, 'report');
                $cvItemSummary = ($cvItemSummary != '') ? $cvItemSummary : 'unavailable';
                AddParagraphPlain($pdf, $cvItemSummary);
            }
        }
    } else {
        $currentHeader = GetHeading($cvItemsData[0]['cas_type_id']);
        AddH2($pdf, $currentHeader);
        foreach ($cvItemsData AS $key1 => $cvHeader) {

            // add the section header?
            $pdf->SetX(16);
            $cvItemSummary = \MRU\Research\CV::formatitem($cvHeader, $style, 'report');
            $cvItemSummary = ($cvItemSummary != '') ? $cvItemSummary : 'unavailable';
            $pdf->SetX(20);
            if ($currentHeader != GetHeading($cvHeader['cas_type_id'])) {
                $currentHeader = GetHeading($cvHeader['cas_type_id']);
                AddH2($pdf, $key1);
            }

            $pdf->SetX(25);
            AddParagraphPlain($pdf, $cvItemSummary);
            if (trim($cvHeader['details_' . $type]) != '') {
                AddParagraphSummary($pdf, $cvHeader['details_' . $type]);
            }
        }
    }
}

/**
* Adds a formatted header to the PDF.
*
* @param mixed $pdf
* @param array $titles  Array of titles as text
*/
function CAQC_Header(&$pdf, $titles, $width = '') {
    SetNormal($pdf);
    $pdf->Ln(3);
    if (count($titles) > 0) {
        foreach ($titles as $key => $title) {
            if ($width != '') {
                $w = $width[$key] * 180.0;
            } else {
                $w = 180 / count($titles);
            }
            $pdf->Cell($w, 6, $title, 1, 0, 'L', 0, '', 0, 0, 'C', 'C');
        }

        $pdf->Ln(5);
    }
}

/**
* Generate a list under a header
*
* @param mixed $pdf
* @param array $list The items to list
* @param array $align Optional array of alignment codes (L,R,C)
* @param array $width Optional array of relative width amounts. Should add up to 1
*/
function CAQC_List(&$pdf, $list, $align = '', $width = '') {
    global $evenodd;
    SetNormal($pdf);
    if (count($list) > 0) {
        $maxH = 0;
        foreach ($list as $key => $item) {
            $item = htmlentities($item, ENT_COMPAT, 'cp1252');
            $item = htmlspecialchars_decode($item, ENT_NOQUOTES);
            if ($align != '') {
                $al = $align[$key];
            } else {
                $al = 'L';
            }
            if ($width != '') {
                $w = $width[$key] * 180;
            } else {
                $w = 180 / count($list);
            }
            $pdf->ResetLastH();
            $pdf->MultiCell(
                $w, //width
                6, //height
                $item, //text
                0, //border
                'L', //align
                0, //fill
                0, //ln
                '', //x position
                '', //y position
                1, //reset height
                0, //stretch
                1 //ishtml
            );

            if ($pdf->GetLastH() > $maxH) {
                $maxH = $pdf->GetLastH();
            }
        }

        $y = $pdf->GetY();
        if ($y > 250) {
            $pdf->AddPage();
        }
        return $maxH;
    }
}


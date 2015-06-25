<?php

namespace MRU\Research;

/**
 * Class to calculate CAQC totals
 */
class Caqc {
    const USER_REPORT = 1;
    const DEGREE_REPORT = 2;

    function __construct($userId) {
        global $db;
        $this->userId = $userId;
        $sql = "SELECT COUNT(*) FROM users WHERE user_id = " . $userId;
        $result = $db->getRow($sql);
        if ($result['COUNT(*)'] == 0) {
            throw new \Exception('User not found exception : userid = ' . $userId);
        }

        $this->reportStats = array();
        $sql = "SELECT degrees_users.degree_id, degrees.degree_name, users.department_id, CONCAT(users.first_name, ' ', users.last_name) AS user_name
                FROM users
                LEFT JOIN departments AS dep ON users.department_id = dep.department_id
                LEFT JOIN divisions ON dep.division_id = divisions.division_id
                LEFT JOIN degrees_users ON users.user_id = degrees_users.user_id
                LEFT JOIN degrees ON degrees_users.degree_id = degrees.degree_id
        WHERE users.user_id = " . $userId;
        $result = $db->getRow($sql);
        $this->userName = $result['user_name'];
        $this->degreeId = $result['degree_id'] == NULL ? 0 : $result['degree_id'];
        $this->degreeName = $result['degree_name'] == NULL ? 'No Associated Degree' : $result['degree_name'];
        $this->departmentId = $result['department_id'];
        $this->sqlHeaderBase = "SELECT COUNT(*) FROM cas_cv_items AS cia
                            LEFT JOIN users AS u ON u.user_id=cia.user_id
                            LEFT JOIN departments AS dep ON u.department_id = dep.department_id
                            LEFT JOIN cas_types ON cia.cas_type_id = cas_types.cas_type_id
                             ";
    }

    /**
     * Get the statistics from the caqc table for a given degree and academic year
     *
     * @param $degreeId - the degree
     * @param $academicYear - the academic year as a string in the format '2012-2013'
     * @return mixed - degree stats
     */
    public static function getArchivedDegreeStats($degreeId, $academicYear) {
        global $db;
        $sql = "SELECT  SUM(booksEdited) AS degree_booksEdited,
                        SUM(booksAuthored) AS degree_booksAuthored,
                        SUM(journals) AS degree_journals,
                        SUM(otherPeer) AS degree_otherPeer,
                        SUM(nonPeer) AS degree_nonPeer,
                        SUM(confPresentation) AS degree_confPresentation,
                        SUM(confAttendance) AS degree_confAttendance,
                        SUM(studentPub) AS degree_studentPub,
                        SUM(peerReviewed) AS degree_peerReviewed,
                        SUM(grants) AS degree_grants,
                        SUM(scholarly) AS degree_scholarly
                FROM caqc WHERE degreeId = " . $degreeId . " AND academicYear = '" . $academicYear . "'";
        $result = $db->getRow($sql);
        return $result;
    }

    /**
     * Get the archived stats from the caqc table for the current user
     *
     * @return mixed - array of stats
     */
    public function getArchivedStats() {
        global $db;
        $currentAcademicYear = getCurrentAcademicYearRange();
        $sql = sprintf("SELECT caqc.*, degree_name FROM caqc
                        LEFT JOIN degrees ON caqc.degreeId = degrees.degree_id
                        WHERE user_id = " . $this->userId . " AND academicYear != '" . $currentAcademicYear . "'");
        $result = $db->getAll($sql);

        // add user and degree name, as well as degree stats
        foreach ($result AS $key => $archivedYear) {
            $result[$key]['user_name'] = $this->userName;
            if ($result[$key]['degree_name'] == NULL) {
                $result[$key]['degree_name'] = "No Associated Degree";
            }

            $degreeStats = $this->getArchivedDegreeStats($archivedYear['degreeId'], $archivedYear['academicYear']);
            $result[$key] = array_merge($result[$key], $degreeStats);
        }

        return $result;
    }

    /**
     * Get the CAQC stats for the user
     *
     */
    public function getUserStats() {
        global $db;
        $this->sqlHeader = $this->sqlHeaderBase;
        $this->reportType = self::USER_REPORT;

        // clear existing stats, if any
        $this->reportStats = NULL;

        $this->reportStats['user_name'] = $this->userName;
        $this->whereClause = " u.user_id = " . $this->userId . " AND cia.report_flag = 1 ";

        //Check if any of the user's items have a NULL binary flag field value and fix them.
        $sql = "SELECT * FROM cas_cv_items WHERE user_id=" . $this->userId . " AND report_flag = 1 AND caqc_flags IS NULL";
        $list = $db->getAll($sql);
        if (count($list) > 0) {
            foreach ($list as $item) {
                $flags = new Caqc\Flags();

                //Bitwise variaible saved to one field
                $flags->GetStats($item['cv_item_id']);

                $sql = "UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id= {$item['cv_item_id']}";
                $db->Execute($sql);
            }
        }

        $this->reportStats['user_Books_Authored'] = $this->getBooksAuthoredStats();
        $this->reportStats['user_Books_Edited'] = $this->getBooksEditedStats();
        $this->reportStats['user_Journals'] = $this->getJournalStats();
        $this->reportStats['user_Other_Peer'] = $this->getOtherPeer();
        $this->reportStats['user_Non_Peer_Scholarly'] = $this->getNonPeer();
        $this->reportStats['user_Conference_Presentation'] = $this->getConferencePresentations();
        $this->reportStats['user_Conference_Attendance'] = $this->getConferenceParticipation();
        $this->reportStats['user_Student_Publications'] = $this->getStudentPublications();
        $this->reportStats['user_Peer_Review_Submitted'] = $this->getPeerReviewedSubmitted();
        $this->reportStats['user_Grants'] = $this->getGrants();
        $this->reportStats['user_Scholarly_Service'] = $this->getScholarlyService();
        $this->reportStats['degreeId'] = $this->degreeId;
        $this->reportStats['departmentId'] = $this->departmentId;
        return $this->reportStats;
    }

    /**
    * Gets the Comprehensive (Full) Stats for the user.
    *
    */
    public function getUserCompStats() {
        global $db;
        $this->sqlHeader = $this->sqlHeaderBase;
        $this->reportType = self::USER_REPORT;

        // clear existing stats, if any
        $this->reportStats = NULL;

        $this->reportStats['user_name'] = $this->userName;
        $this->whereClause = " u.user_id = " . $this->userId . " AND cia.mycv2 = 1 ";

        //Check if any of the user's items have a NULL binary flag field value and fix them.
        $sql = "SELECT * FROM cas_cv_items WHERE user_id=" . $this->userId . " AND mycv2 = 1 AND caqc_flags IS NULL";
        $list = $db->getAll($sql);
        if (count($list) > 0) {
            foreach ($list as $item) {
                $flags = new Caqc\Flags();
                $flags->GetStats($item['cv_item_id']);

                //Bitwise variaible saved to one field
                $sql = "UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id= {$item['cv_item_id']}";
                $db->Execute($sql);
            }
        }

        $this->reportStats['user_Books_Authored'] = ($this->getBooksAuthoredStats() == 0) ? '' : $this->getBooksAuthoredStats();
        $this->reportStats['user_Books_Edited'] = ($this->getBooksEditedStats() == 0) ? '' : $this->getBooksEditedStats();
        $this->reportStats['user_Journals'] = ($this->getJournalStats() == 0) ? '' : $this->getJournalStats();
        $this->reportStats['user_Other_Peer'] = ($this->getOtherPeer() == 0) ? '' : $this->getOtherPeer();
        $this->reportStats['user_Non_Peer_Scholarly'] = ($this->getNonPeer() == 0) ? '' : $this->getNonPeer();
        $this->reportStats['user_Conference_Presentation'] = ($this->getConferencePresentations() == 0) ? '' : $this->getConferencePresentations();
        $this->reportStats['user_Conference_Attendance'] = ($this->getConferenceParticipation() == 0) ? '' : $this->getConferenceParticipation();
        $this->reportStats['user_Student_Publications'] = ($this->getStudentPublications() == 0) ? '' : $this->getStudentPublications();
        $this->reportStats['user_Peer_Review_Submitted'] = ($this->getPeerReviewedSubmitted() == 0) ? '' : $this->getPeerReviewedSubmitted();
        $this->reportStats['user_Grants'] = ($this->getGrants() == 0) ? '' : $this->getGrants();
        $this->reportStats['user_Scholarly_Service'] = ($this->getScholarlyService() == 0) ? '' : $this->getScholarlyService();
        $this->reportStats['degreeId'] = $this->degreeId;
        $this->reportStats['departmentId'] = $this->departmentId;

        //additional ones
        $this->reportStats['degrees'] = ($this->getDegrees() == 0) ? '' : $this->getDegrees();
        $this->reportStats['inProg'] = ($this->getInProg() == 0) ? '' : $this->getInProg();
        $this->reportStats['appointments'] = ($this->getAppointments() == 0) ? '' : $this->getAppointments();
        $this->reportStats['admin'] = ($this->getAdmin() == 0) ? '' : $this->getAdmin();
        $this->reportStats['memberships'] = ($this->getMemberships() == 0) ? '' : $this->getMemberships();
        $this->reportStats['qualifications'] = ($this->getQuals() == 0) ? '' : $this->getQuals();
        $this->reportStats['experience'] = ($this->getExperience() == 0) ? '' : $this->getExperience();
        $sql = "SELECT MAX(reminder_date) as max, count(*) as count from cas_cv_items WHERE user_id=" . $this->userId;
        $date = $db->getRow($sql);
        if (!is_null($date['max'])) {
            $this->reportStats['date'] = $date['max'];
        } else {
            $this->reportStats['date'] = '';
        }
        $this->reportStats['items'] = $date['count'];
        $sql = "SELECT count(*) AS count from cas_cv_items WHERE user_id=" . $this->userId . " AND report_flag=1 ";
        $ar = $db->getRow($sql);
        $this->reportStats['ar'] = $ar['count'];
        return $this->reportStats;
    }

    /**
     * CAQC Annual stats for the degrees relies on the annual reporting checkboxes rather than using the year.
     *   - count everything checked 'ar' for people with no approval yet.
     *  But those already approved for the current year have the stats archived. THose are drawn from teh 'caqc' table
     *  ToDo: Change whole stats archiving system so that IDs are archived rather than counts.
     */
    public function getDegreeStats($degreeId = 0) {

        //
        global $db;
        if ($degreeId != 0) {

            //I'm firing the stats routine without setting a specific userId, so need to get some info
            $this->degreeId = $degreeId;
            $degree = $db->getRow("SELECT * FROM degrees WHERE degree_id=$degreeId");
            if ($degree) {
                $this->degreeName = $degree['degree_name'];
            }
        }

        $this->reportType = self::DEGREE_REPORT;

        // clear existing stats, if any
        $this->reportStats = NULL;

        $this->reportStats['degree_name'] = $this->degreeName;
        if ($this->degreeId == 0) {

            // user has no associated degree, so we cannot display degree stats
            return $this->reportStats;
        }

        //Check if any of the  items have a NULL binary flag field value and fix them.
        $sql = "SELECT * FROM cas_cv_items LEFT JOIN degrees_users ON cas_cv_items.user_id = degrees_users.user_id WHERE degrees_users.degree_id = " . $this->degreeId . " AND report_flag = 1 AND caqc_flags IS NULL";
        $list = $db->getAll($sql);
        if (count($list) > 0) {
            foreach ($list as $item) {
                $flags = new Caqc\Flags();

                //Bitwise variaible saved to one field
                $flags->GetStats($item['cv_item_id']);

                $sql = "UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id= {$item['cv_item_id']}";
                $db->Execute($sql);
            }
        }

        // where clause for degree report
        $this->whereClause = " degrees_users.degree_id = " . $this->degreeId . " AND cia.report_flag = 1 ";

        //the degrees_users table was omitted from the sql header because of the possibility of returning two (or more) records in a JOIN  - (faculty can be in two degrees) - however the where clause negates the issue here by focusing on one only.
        $this->sqlHeader = $this->sqlHeaderBase . " LEFT JOIN degrees_users on cia.user_id=degrees_users.user_id ";

        //Now pull stats stored in the archive when a report is approved
        $sql = "SELECT * FROM caqc WHERE YEAR(dateCreated)=YEAR(NOW()) AND degreeId=$this->degreeId";
        $archive = $db->getRow($sql);
        $this->reportStats['degree_books_authored'] = $this->getBooksAuthoredStats();
        $this->reportStats['degree_books_edited'] = $this->getBooksEditedStats();
        $this->reportStats['degree_journals'] = $this->getJournalStats();
        $this->reportStats['degree_other_peer'] = $this->getOtherPeer();
        $this->reportStats['degree_non_peer'] = $this->getNonPeer();
        $this->reportStats['degree_conference_presentation'] = $this->getConferencePresentations();
        $this->reportStats['degree_conference_attendance'] = $this->getConferenceParticipation();
        $this->reportStats['degree_student_publications'] = $this->getStudentPublications();
        $this->reportStats['degree_peer_reviewed_submitted'] = $this->getPeerReviewedSubmitted();
        $this->reportStats['degree_grants'] = $this->getGrants();
        $this->reportStats['degree_scholarly_service'] = $this->getScholarlyService();

        //Now pull stats stored in the archive when a report is approved
        $sql = "  SELECT
                    SUM(booksAuthored) as booksAuthored,
                    SUM(booksEdited) as booksEdited,
                    SUM(journals) as journals,
                    SUM(otherPeer) as otherPeer,
                    SUM(nonPeer) as nonPeer,
                    SUM(confPresentation) as confPresentation,
                    SUM(confAttendance) as confAttendance,
                    SUM(studentPub) as studentPub,
                    SUM(peerReviewed) as peerReviewed,
                    SUM(grants) as grants,
                    SUM(scholarly) as scholarly
                FROM caqc WHERE YEAR(dateCreated)=YEAR(NOW()) AND degreeId=$this->degreeId";
        $archive = $db->getRow($sql);
        if ($archive) {
            $this->reportStats['degree_books_authored'] += $archive['booksAuthored'];
            $this->reportStats['degree_books_edited'] += $archive['booksEdited'];
            $this->reportStats['degree_journals'] += $archive['journals'];
            $this->reportStats['degree_other_peer'] += $archive['otherPeer'];
            $this->reportStats['degree_non_peer'] += $archive['nonPeer'];
            $this->reportStats['degree_conference_presentation'] += $archive['confPresentation'];
            $this->reportStats['degree_conference_attendance'] += $archive['confAttendance'];
            $this->reportStats['degree_student_publications'] += $archive['studentPub'];
            $this->reportStats['degree_peer_reviewed_submitted'] += $archive['peerReviewed'];
            $this->reportStats['degree_grants'] += $archive['grants'];
            $this->reportStats['degree_scholarly_service'] += $archive['scholarly'];
        }

        return $this->reportStats;
    }

    /**
     * Gather books authored / co-authored stats
     *
     * @var $students - whether or not to count only items that involved students
     * @var submitted - whether or not to count only items that were submitted
     */
    private function getDegrees() {
        global $db;
        $sql = $this->sqlHeader . "WHERE cia.cas_type_id=1 AND cia.n13=2 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getInProg() {
        global $db;
        $sql = $this->sqlHeader . "WHERE cia.cas_type_id=1 AND (cia.n13=1 OR cia.n13=3) AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getAppointments() {
        global $db;
        $sql = $this->sqlHeader . "WHERE cia.cas_type_id=3 AND cia.n01 NOT LIKE '%Chair%'
                AND cia.n01 NOT LIKE '%Co-ordinator%'
                AND cia.n01 NOT LIKE '%Coordinator%'
                AND cia.n01 NOT LIKE '%Director%'
                AND cia.n01 NOT LIKE '%Manager%' AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getMemberships() {
        global $db;
        $sql = $this->sqlHeader . "WHERE cia.cas_type_id=26  AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getQuals() {
        global $db;
        $sql = $this->sqlHeader . "WHERE cia.cas_type_id=2 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getExperience() {
        global $db;
        $sql = $this->sqlHeader . "WHERE (cia.cas_type_id=15 OR cia.cas_type_id=23 OR cia.cas_type_id=24 OR cia.cas_type_id=87 OR cia.cas_type_id=90) AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getAdmin() {
        global $db;
        $sql = $this->sqlHeader . "WHERE cia.cas_type_id=3 AND (cia.n01 LIKE '%Chair%'
                OR cia.n01 LIKE '%Co-ordinator%'
                OR cia.n01 LIKE '%Coordinator%'
                OR cia.n01 LIKE '%Director%'
                OR cia.n01 LIKE '%Manager%') AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getBooksAuthoredStats() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 1 AND " . $this->whereClause;

        //echo $sql;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather books edited / co-edited stats
     *
     * @var submitted - whether or not to count only items that were submitted
     * @return int - the number of booked edited
     */
    private function getBooksEditedStats() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 2 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather refereed journals / book chapter stats
     *
     * @var $students - whether or not to count only items that involved students
     * @var submitted - whether or not to count only items that were submitted
     * @return int - the total
     */
    private function getJournalStats() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 4 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather other peer-reviewed scholarly activities
     *
     * @var submitted - whether or not to count only items that were submitted
     * @return int - total number of other peer reviewed publications
     */
    private function getOtherPeer() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 8 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather other non-peer-reviewed scholarly activities
     *
     */
    private function getNonPeer() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 16 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather conference presentation activities
     *
     */
    private function getConferencePresentations() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 32 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather stats on conference participation
     *
     */
    private function getConferenceParticipation() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 64 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather stats on peer reviewed student publications
     *
     */
    private function getStudentPublications() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 128 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getPeerReviewedSubmitted() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 256 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Get the total number of grants recieved or completed
     */
    private function getGrants() {
        global $db;;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 512 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Get the total number of scholarly service events
     */
    private function getScholarlyService() {
        global $db;;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 1024 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * @var array - the type of report (divison or user)
     */
    protected $reportType;
    /**
     * @var array - array of caqc stats
     */
    protected $reportStats;
    /**
     * @var - int - the userId
     */
    protected $userId;
    /**
     * @var int - the user's degree ID
     */
    protected $degreeId;
    /**
     * @var int - the user's division ID
     */
    protected $departmentId;
    /**
     * @var string - the degree name
     */
    protected $degreeName;
    /**
     * @var string - the users name
     */
    protected $userName;
    /**
     * @var string - the SQL query header
     */
    protected $sqlHeader;
    /**
     * @var string - the WHERE clause for users/divisions
     */
    protected $whereClause;
}

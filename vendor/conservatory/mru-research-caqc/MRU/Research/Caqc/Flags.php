<?php

namespace MRU\Research\Caqc;

class Flags  {

    // BIT #1 of $flags has the value 1
    const FLAG_BOOKS_AUTHORED = 1;
    const FLAG_BOOKS_EDITED = 2;
    const FLAG_REFJOURNALS = 4;
    const FLAG_OTHER_PEER = 8;
    const FLAG_NONPEER = 16;
    const FLAG_CONF_PRES = 32;
    const FLAG_CONF_ATTEND = 64;
    const FLAG_STUDENT = 128;
    const FLAG_SUBMITTED = 256;
    const FLAG_GRANTS = 512;
    const FLAG_SERVICE = 1024;

    protected $flags;

    protected function isFlagSet($flag) {
        return(($this->flags&$flag) == $flag);
    }

    protected function setFlag($flag, $value) {
        if ($value) {
            $this->flags |= $flag;
        } else {
            $this->flags &= ~ $flag;
        }
    }

    public function isBooksAuthored() {
        return $this->isFlagSet(self::FLAG_BOOKS_AUTHORED);
    }

    public function isBooksEdited() {
        return $this->isFlagSet(self::FLAG_BOOKS_EDITED);
    }

    public function isRefJournals() {
        return $this->isFlagSet(self::FLAG_REFJOURNALS);
    }

    public function isOtherPeer() {
        return $this->isFlagSet(self::FLAG_OTHER_PEER);
    }

    public function isNonPeer() {
        return $this->isFlagSet(self::FLAG_NONPEER);
    }

    public function isConfPres() {
        return $this->isFlagSet(self::FLAG_CONF_PRES);
    }

    public function isConfAttend() {
        return $this->isFlagSet(self::FLAG_CONF_ATTEND);
    }

    public function isStudent() {
        return $this->isFlagSet(self::FLAG_STUDENT);
    }

    public function isSubmitted() {
        return $this->isFlagSet(self::FLAG_SUBMITTED);
    }

    public function isGrants() {
        return $this->isFlagSet(self::FLAG_GRANTS);
    }

    public function isService() {
        return $this->isFlagSet(self::FLAG_SERVICE);
    }

    public function AsInt() {
        $bin= ($this->isService() ? '1':'0') .
              ($this->isGrants() ? '1':'0') .
              ($this->isSubmitted() ? '1':'0') .
              ($this->isStudent() ? '1':'0') .
              ($this->isConfAttend() ? '1':'0') .
              ($this->isConfPres() ? '1':'0') .
              ($this->isNonPeer() ? '1':'0') .
              ($this->isOtherPeer() ? '1':'0') .
              ($this->isRefJournals() ? '1':'0') .
              ($this->isBooksEdited() ? '1':'0') .
              ($this->isBooksAuthored() ? '1':'0');

        return bindec($bin);
    }

    public function setBooksAuthored($value) {
        $this->setFlag(self::FLAG_BOOKS_AUTHORED, $value);
    }

    public function setBooksEdited($value) {
        $this->setFlag(self::FLAG_BOOKS_EDITED, $value);
    }

    public function setRefJournals($value) {
        $this->setFlag(self::FLAG_REFJOURNALS, $value);
    }

    public function setOtherPeer($value) {
        $this->setFlag(self::FLAG_OTHER_PEER, $value);
    }

    public function setNonPeer($value) {
        $this->setFlag(self::FLAG_NONPEER, $value);
    }

    public function setConfPres($value) {
        $this->setFlag(self::FLAG_CONF_PRES, $value);
    }

    public function setConfAttend($value) {
        $this->setFlag(self::FLAG_CONF_ATTEND, $value);
    }

    public function setStudent($value) {
        $this->setFlag(self::FLAG_STUDENT, $value);
    }

    public function setSubmitted($value) {
        $this->setFlag(self::FLAG_SUBMITTED, $value);
    }

    public function setGrants($value) {
        $this->setFlag(self::FLAG_GRANTS, $value);
    }

    public function setService($value) {
        $this->setFlag(self::FLAG_SERVICE, $value);
    }

    /**
     * GetStats function.
     *   given an ID for a specific CV item, returns an 11 bit flag indicating CAQC contributions.
     * @access public
     * @param array $cv_item
     * @return int
     */
    public function GetStats($cv_item_id) {
        global $db;
        $sql = "SELECT * FROM cas_cv_items WHERE cv_item_id=$cv_item_id";
        $cv_item = $db->getRow($sql);

        //If it doesn't have an AR flag then it doesn't count, so return a zero
        //ToDo
        if (is_array($cv_item)) {
            switch ($cv_item['cas_type_id']) {
                case 1:
                    ////////  Degrees  //////////////////////
                    break;

                case 2:
                    ////////// Professional Designations //////////////////
                    break;

                case 3:
                    ////////  Educ Institution Employment ////////////////
                    break;

                case 4:
                    //////// Other Employment ////////////////
                    break;

                case 5:
                    //////// Other Studies //////////////////////
                    break;

                case 6:
                    ///////// Professional Leaves ////////////////
                    break;

                case 7:
                    ///////// Personal Leaves ////////////////
                    break;

                case 8:
                    ///////// Grants ////////////////
                    if ($cv_item['n04'] == 2) {
                        //Only 'received'. This will ignore zero values.
                        $this->setGrants(true);
                    }
                    break;

                case 9:
                    ///////// Contracts ////////////////
                    if ($cv_item['n04'] == 2) {
                        //Only 'received' to keep from multiple
                        $this->setNonPeer(true);
                    }
                    break;

                case 10:
                    ///////// Non-research presentations ////////////////
                    break;

                case 11:
                    ///////// Committee memberships ////////////////
                    //I can't fit this into Scholarly Service very easily.
                    break;

                case 12:
                    ///////// Offices Held ////////////////
                    break;

                case 13:
                    ///////// Event Admin ////////////////
                    if ($cv_item['n04'] == 1) {
                        $this->setService(true);
                    }
                    break;

                case 14:
                    ///////// Editorial Activities ////////////////
                    $this->setService(true);
                    break;

                case 15:
                    ///////// Consulting/Advising ////////////////
                    break;

                case 16:
                    ///////// Expert Witness ////////////////
                    break;

                case 17:
                    ///////// Journal Reviewing/Refereeing ////////////////
                    //Looks like any type of Refereeing should fit
                    $this->setService(true);
                    break;

                case 18:
                    ///////// Conferenece Reviewing ////////////////
                    $this->setService(true);
                    break;

                case 19:
                    ///////// Graduate Exam ////////////////
                    break;

                case 20:
                    ///////// Grant Applic Assessment ////////////////
                    //Funder assessment rather than Institution
                    if ($cv_item['n02'] == 2) {
                        $this->setService(true);
                    }

                    // the specs say "Major" funder but hard to delineate
                    break;

                case 21:
                    ///////// Promotion/Tenure Assessment ////////////////
                    break;

                case 22:
                    ///////// Institutional Review ////////////////
                    $this->setService(true);
                    break;

                case 23:
                    ///////// Broadcast Interviews ////////////////
                    break;

                case 24:
                    ///////// Text Interviews ////////////////
                    break;

                case 25:
                    ///////// Event Participation ////////////////
                    if ($cv_item['n02'] == 1) { //Only conferences, not workshops, etc
                        $this->setConfAttend(true);
                    }
                    break;

                case 26:
                    ///////// Memberships ////////////////
                    break;

                case 27:
                    ///////// Community Service ////////////////
                    break;

                case 28:
                    ///////// Awards and Distinctions ////////////////
                    break;

                case 29:
                    ///////// Courses Taught ////////////////
                    break;

                case 30:
                    ///////// Course Development ////////////////
                    break;

                case 31:
                    ///////// Program Development ////////////////
                    break;

                case 32:
                    ///////// Research-based degree ////////////////
                    break;

                case 33:
                    ///////// Course-based degree ////////////////
                    break;

                case 34:
                    ///////// Employee Supervisions ////////////////
                    break;

                case 35:
                    ////////////////// Journal Article  //////////////////////////
                    if ($cv_item['n03'] == true && // Refereed
                        ($cv_item['n04'] >= 5 || $cv_item['n04'] == 0) && //Accepted, In press, In print
                        ($cv_item['n13'] == 1 || $cv_item['n13'] == 3 || $cv_item['n13'] == 0)) { // Author or co-author
                        $this->setRefJournals(true);
                    } elseif ($cv_item['n03'] == true && //Refereed
                             ($cv_item['n04'] == 2 || $cv_item['n04'] == 3 || $cv_item['n04'] == 4) &&  //Submitted
                             ($cv_item['n13'] == 1 || $cv_item['n13'] == 3 || $cv_item['n13'] == 0)) { // Author or co-author OR BLANK
                        $this->setSubmitted(true);
                    }

                    if ($cv_item['n23']) { // Doesn't depend on submitted status
                        $this->setStudent(true);
                    }

                    break;

                case 36:
                    ///////// Journal Issues ////////////////
                    //Editor on a journal issue should fit service
                    //By definition you are an editor in this category.
                    $this->setService(true);
                    break;

                case 37:
                    ///////// Books ////////////////
                    if ($cv_item['n04'] != 1 && //anything except in prep
                       ($cv_item['n02'] == 1 || $cv_item['n02'] == 3)) { //authored or coauthored
                        $this->setBooksAuthored(true);
                    }

                    if ($cv_item['n04'] != 1 && //anything except in prep
                       ($cv_item['n02'] == 2 || $cv_item['n02'] == 4)) { //authored or coauthored
                        $this->setBooksEdited(true);
                    }

                    if ($cv_item['n23']) { // Doesn't depend on submitted status
                        $this->setStudent(true);
                    }

                    // Note - by including submitted but not published items, it could be counted in two subsequent years.
                    break;

                case 38:
                    ///////// Edited Books ////////////////
                    if (($cv_item['n04'] >= 2 || $cv_item['n04'] == 0) && //anything except in prep
                       ($cv_item['n02'] == 1 || $cv_item['n02'] == 3)) { //authored or coauthored
                        $this->setBooksAuthored(true);
                    }

                    if (($cv_item['n04'] >= 2 || $cv_item['n04'] == 0) && //anything except in prep
                        ($cv_item['n02'] == 2 || $cv_item['n02'] == 4)) { //authored or coauthored
                        $this->setBooksEdited(true);
                    }
                    break;

                case 39:
                    ///////// Book Chapters ////////////////
                    if (($cv_item['n02'] == 1 || $cv_item['n02'] == 3) && ($cv_item['n04'] >= 5 || $cv_item['n04'] == 0)) {
                        $this->setRefJournals(true);
                    }

                    if (($cv_item['n02'] == 1 || $cv_item['n02'] == 3) && // author or c-author
                       ($cv_item['n04'] >= 5 || $cv_item['n04'] == 0) && // accepted at least
                       $cv_item['n23'] == true) {
                        $this->setStudent(true);
                    }
                    break;

                case 40:
                    ///////// Book Reviews ////////////////
                    if ($cv_item['n03'] == true) {
                        $this->setOtherPeer(true);
                    } else {
                        $this->setNonPeer(true);
                    }
                    break;

                case 41:
                    ///////// Translations //////////////// --- REMOVED FOR NOW
                    break;

                case 42:
                    ///////// Dissertations ////////////////
                    $this->setOtherPeer(true);
                    break;

                case 43:
                    ///////// Supervised Student Pubs ////////////////
                    if ($cv_item['n02'] >= 5) {
                        $this->setStudent(true);
                    }
                    break;

                case 44:
                    ///////// Litigation ///////////
                    break;

                case 45:
                    //////////////////// Conference Papers ////////////////////////////
                    if ($cv_item['n03'] == true &&  //published
                       ($cv_item['n04'] >= 5 || $cv_item['n04'] == 0) && //accepted at least
                       $cv_item['n23'] == true) //refereed
                    {
                        $this->setRefJournals(true);
                    } elseif ($cv_item['n03'] == true && //published
                             ($cv_item['n20'] == 1 || $cv_item['n20'] == 3) && //Author
                             ($cv_item['n04'] >= 5 || $cv_item['n04'] == 0) && //accepted at least or blank
                             $cv_item['n23'] == false)
                    {
                        $this->setNonPeer(true);
                    } elseif (($cv_item['n20'] == 1 || $cv_item['n20'] == 3) && //An author
                             $cv_item['n03'] == false)
                    {
                        $this->setConfPres(true);
                    } elseif ($cv_item['n20'] == 4 || $cv_item['n20'] == 7) { //A presenter
                        $this->setConfPres(true);
                    } else {
                        $this->setConfPres(true);
                    }

                    if ($cv_item['n03'] == true && //published
                        ($cv_item['n04'] >= 5 || $cv_item['n04'] == 0) && //accepted at least
                        $cv_item['n23'] == true && //refereed
                        $cv_item['n24'] == true && //student
                        ($cv_item['n20'] == 1 || $cv_item['n20'] == 3)) //An author
                    {
                        $this->setStudent(true);
                    }
                    break;

                case 46:
                    ///////// Conference Abstracts ////////////////
                    $this->setNonPeer(true);
                    break;

                case 47:
                    ///////// Artistic Exhibitions ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) {  //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 48:
                    ///////// Audio Recording ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 49:
                    ///////// Exhibition Catalogues ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 50:

                    ///////// Musical Compositions ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 51:
                    ///////// Musical Performances ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 52:

                    ///////// Radio/TV Programs ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 53:

                    ///////// Scripts ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 54:
                    ///////// Short Fiction ////////////////
                    if (($cv_item['n04'] >= 5 || $cv_item['n04'] == 0)) {
                        $this->setNonPeer(true);
                    }
                    break;

                case 55:
                    ///////// Theatre Performances ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 56:
                    ///////// Video Recording ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 57:
                    ///////// Visual Artworks ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 58:
                    ///////// Sound Design ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 59:
                    ///////// Light Design ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 60:
                    ///////// Choreography ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) {
                        $this->setNonPeer(true);
                    }
                    break;

                case 61:
                    ///////// Curatorial ////////////////
                    $this->setNonPeer(true);
                    break;

                case 62:
                    ///////// Performance Art ////////////////
                    if ($cv_item['n03'] == true) {
                        $this->setOtherPeer(true);
                    } else {
                        $this->setNonPeer(true);
                    }
                    break;

                case 63:
                    ///////// Newspaper Articles ////////////////
                    break;

                case 64:
                    ///////// Newsletter Articles ////////////////
                    break;

                case 65:
                    ///////// Encyclopedia Entries ////////////////
                    break;

                case 66:
                    ///////// Magazine Articles ////////////////
                    break;

                case 67:
                    ///////// Dictionary ////////////////
                    break;

                case 68:
                    ///////// Reports ////////////////
                    $this->setNonPeer(true);
                    break;

                case 69:
                    ///////// Working Papers ////////////////
                    $this->setNonPeer(true);
                    break;

                case 70:
                    ///////// Research Tools ////////////////
                    break;

                case 71:
                    ///////// Manuals ////////////////
                    break;

                case 72:
                    ///////// Online Resources ////////////////
                    $this->setNonPeer(true);
                    break;

                case 73:
                    ///////// Tests ////////////////
                    break;

                case 74:
                    ///////// Patents ////////////////
                    $this->setNonPeer(true);
                    break;

                case 75:
                    ///////// Licenses ////////////////
                    $this->setNonPeer(true);
                    break;

                case 76:
                    ///////// Disclosures ////////////////
                    $this->setNonPeer(true);
                    break;

                case 77:
                    ///////// Registered Copyrights ////////////////
                    break;

                case 78:
                    ///////// Trademarks ////////////////
                    break;

                case 79:
                    ///////// Posters ////////////////
                    if ($cv_item['n03'] == true) {
                        $this->setOtherPeer(true);
                    } else {
                        $this->setNonPeer(true);
                    }

                    if ($cv_item['n23'] == true && $cv_item['n03'] == true) {
                        $this->setStudent(true);
                    }
                    break;

                case 80:
                    ///////// Set Design ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;

                case 81:
                    ///////// Other Communications ////////////////
                    break;


                //////////////// New Stuff not in CASRAI Standard
                case 82:
                    break;

                case 83:
                    /// Coordination////
                    break;

                case 84:
                    ///////// research presentations ////////////////
                    if (stristr($cv_item['n05'], 'conference') || stristr($cv_item['n05'], 'symposium')) {
                        $this->setConfPres(true);
                    } elseif ($cv_item['n24'] == true) {
                        $this->setOtherPeer(true);
                    } else {
                        $this->setNonPeer(true);
                    }

                    break;

                case 85:
                    /// Teaching in progress and other////
                    break;

                case 86:
                    /// Other Service////
                    break;

                case 87:
                    /// Clinical ////
                    break;

                case 88:
                    /// Professional CUrrency////
                    break;

                case 89:
                    /// Other Media ////
                    if ($cv_item['n03'] == true) {
                        $this->setOtherPeer(true);
                    } else {
                        $this->setNonPeer(true);
                    }
                    break;

                case 90:
                    /// Other Professional Act////
                    break;

                case 91:
                    ///////// Projects in Progress ////////////////
                    break;

                case 92:
                    ///policy development
                    break;

                case 93:
                    ///Mentorship
                    break;

                case 94:
                    ///////// Costume Design ////////////////
                    if ($cv_item['n03'] == true && $cv_item['n23'] == false) { //Juried
                        $this->setOtherPeer(true);
                    } else if ($cv_item['n23'] == false) { //Not professional
                        $this->setNonPeer(true);
                    }
                    break;
            }
        }
    }

    public function __toString() {
        return ($this->isGrants() ? '1' : '0') . ($this->isNonPeer() ? '1' : '0');
    }
}

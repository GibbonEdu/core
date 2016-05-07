<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Module\Markbook ;

/**
 * Markbook display & edit class
 *
 * @version	3rd May 2016
 * @since	3rd May 2016
 * @author	Sandra Kuipers
 */
class markbookView
{

	/**
	 * Gibbon\sqlConnection
	 */
	private $pdo ;
	
	/**
	 * Gibbon\session
	 */
	private $session ;
	
	/**
	 * Gibbon\config
	 */
	private $config ;

	/**
	 * Gibbon Settings
	 */
	private $settings = array();

	/**
	 * Markbook Class Settings - readonly
	 */
	private $columnsPerPage = 12;
	private $columnsThisPage;

	private $columnCountTotal = -1;
	private $minSequenceNumber = -1;

	/**
	 * Cache markbook values to reduce queries
	 */
	private $primaryAssessmentScale;
	private $externalAssessmentFields;
	private $personalizedTargets;
	private $weightings;

    private $columns = array();

	public $gibbonCourseClassID;

	/**
     * Constructor
     *
     * @version  3rd May 2016
     * @since    3rd May 2016
     * @param    Gibbon\session
     * @param    Gibbon\config
     * @param    Gibbon\sqlConnection
     * @return   void
     */
    public function __construct(\Gibbon\session $session = NULL, \Gibbon\config $config = NULL, \Gibbon\sqlConnection $pdo = NULL, $gibbonCourseClassID)
    {
        if ($session === NULL)
            $this->session = new \Gibbon\session();
        else
            $this->session = $session ;

        if ($config === NULL)
            $this->config = new \Gibbon\config();
        else
            $this->config = $config ;

        if ($pdo === NULL)
            $this->pdo = new \Gibbon\sqlConnection();
        else
            $this->pdo = $pdo ;

        $this->gibbonCourseClassID = $gibbonCourseClassID;

        // Build the initial column counts for this class
        try {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $where = $this->getColumnFilters();
            $sql = 'SELECT count(*) as count, min(sequenceNumber) as min FROM gibbonMarkbookColumn WHERE '.$where;
            $result=$this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) { $this->error( $e->getMessage() ); }

        $row = $result->fetch();

        $this->minSequenceNumber = (isset($row['min']))? $row['min'] : 0;
        $this->columnCountTotal = (isset($row['count']))? $row['count'] : 0;

        // Get Gibbon settings
		$this->settings['enableColumnWeighting'] = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'enableColumnWeighting');
        $this->settings['enableRawAttainment'] = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'enableRawAttainment');

		// Get alternative header names
		$attainmentAltName = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'attainmentAlternativeName');
		$attainmentAltNameAbrev = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'attainmentAlternativeNameAbrev');
		$effortAltName = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'effortAlternativeName');
		$effortAltNameAbrev = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'effortAlternativeNameAbrev');

		$this->settings['attainmentName'] = (!empty($attainmentAltName))? $attainmentAltName : __($this->config->get('guid'), 'Attainment');
		$this->settings['attainmentAbrev'] = (!empty($attainmentAltNameAbrev))? $attainmentAltNameAbrev : __($this->config->get('guid'), 'Att');

		$this->settings['effortName'] = (!empty($effortAltName))? $effortAltName : __($this->config->get('guid'), 'Effort');
		$this->settings['effortAbrev'] = (!empty($effortAltNameAbrev))? $effortAltNameAbrev : __($this->config->get('guid'), 'Eff');
    }

    public function getSetting( $key ) {
    	return (isset($this->settings[$key]))? $this->settings[$key] : NULL;
    }

    public function getMinimumSequenceNumber() {
    	return $this->minSequenceNumber;
    }

    public function getColumnsPerPage() {
    	return $this->columnsPerPage;
    }

    public function getColumnCountThisPage() {
        return $this->columnsThisPage;
    }

    public function getColumnCountTotal() {
        return $this->columnCountTotal;
    }

    public function getColumns( $pageNum ) {

    	try {
    		$data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
    		$where = $this->getColumnFilters();

    		$sql = 'SELECT * FROM gibbonMarkbookColumn WHERE '.$where.' ORDER BY sequenceNumber, date, complete, completeDate LIMIT '.($pageNum * $this->columnsPerPage).', '.$this->columnsPerPage;

    		//echo $sql;

	        $result=$this->pdo->executeQuery($data, $sql);
	    } catch (PDOException $e) { $this->error( $e->getMessage() ); }

	    $this->columnsThisPage = $result->rowCount();
        $this->columns = array();

        for ($i = 0; $i < $this->columnsThisPage; ++$i) {

            $column = new markbookColumn( $result->fetch() );

            if ($column != NULL) {
                $this->columns[ $i ] = $column;

                //WORK OUT IF THERE IS SUBMISSION
                if ( !empty($column->getData('gibbonPlannerEntryID'))) {
                    try {
                        $dataSub=array("gibbonPlannerEntryID"=>$column->getData('gibbonPlannerEntryID') ); 
                        $sqlSub="SELECT homeworkDueDateTime, date FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y' LIMIT 1" ;
                        $resultSub=$this->pdo->executeQuery($data, $sql);
                    } catch (PDOException $e) { $this->error( $e->getMessage() ); }

                    if ($resultSub->rowCount()>=1) {
                        $column->setSubmissionDetails( $resultSub->fetch() );
                    }
                }
            }
        }

        if ($this->columnsThisPage != count($this->columns)) {
            $this->error( "Column count mismatch. Something went horribly wrong loading column data." );
        }

	    return (count($this->columns) > 0);
    }

    public function getColumn( $i ) {
        return (isset($this->columns[$i]))? $this->columns[$i] : NULL;
    }
    

    private function getColumnFilters() {

    	$where = 'gibbonCourseClassID=:gibbonCourseClassID';

    	$gibbonSchoolYearTermID = (isset($_GET['gibbonSchoolYearTermID']))? $_GET['gibbonSchoolYearTermID'] : '';
        $columnFilter = (isset($_GET['columnFilter']))? $_GET['columnFilter'] : '';

        if (empty($gibbonSchoolYearTermID)) {
            $gibbonSchoolYearTermID = $_SESSION[$this->config->get('guid')]['markbookTerm'];
        }

    	if (!empty($gibbonSchoolYearTermID)) {

        	try {
		        $data=array("gibbonSchoolYearTermID"=>$gibbonSchoolYearTermID );
		        $sql="SELECT firstDay, lastDay FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID" ;
		        $resultTerms=$this->pdo->executeQuery($data, $sql);
		    } catch (PDOException $e) { $this->error( $e->getMessage() ); }

		    if ($resultTerms->rowCount() > 0) {
		    	$termRow = $resultTerms->fetch();
        		$where .= " AND (date IS NOT NULL AND date BETWEEN '".$termRow['firstDay']."' AND '".$termRow['lastDay']."' )";
        	}
        }

        if (!empty($columnFilter)) {
        	switch ($columnFilter) {
        		case 'marked':		$where .= " AND complete = 'Y'"; break;
        		case 'unmarked':	$where .= " AND complete = 'N'"; break;
        		case 'week':		$where .= " AND WEEKOFYEAR(date)=WEEKOFYEAR(NOW())"; break;
        		case 'month':		$where .= " AND MONTH(date)=MONTH(NOW())"; break;
        	}
        }

        return $where;
    }

    public function getTargetForStudent( $gibbonPersonID ) {
    	return (isset($this->personalizedTargets[$gibbonPersonID]))? $this->personalizedTargets[$gibbonPersonID] : '';
    }

    public function getPersonalizedTargetsCount() {
    	return (isset($this->personalizedTargets))? count($this->personalizedTargets) : 0;
    }

    public function cachePersonalizedTargets( ) {

    	$this->personalizedTargets = array();

     	try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $sql = 'SELECT gibbonPersonIDStudent, value FROM gibbonMarkbookTarget JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
            $result=$this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) { $this->error( $e->getMessage() ); }
        
        if ($result->rowCount() > 0) {
	        while ($row = $result->fetch() ) {
	        	$this->personalizedTargets[ $row['gibbonPersonIDStudent'] ] = $row['value'];
	        }
	    }
    }

    public function getWeightingForStudent( $gibbonPersonID ) {
    	
        $output = '';
        $totalWeight = 0;
        $cummulativeWeightedScore = 0;
        $percent = false;

        if (!isset($this->weightings[$gibbonPersonID])) return $output;

        foreach ($this->weightings[$gibbonPersonID] as $weighting) {
            $totalWeight += $weighting['weighting'];
            if (strpos($weighting['value'], '%') !== false) {
                $weighting['value'] = str_replace('%', '', $weighting['value']);
                $percent = true;
            }
            $cummulativeWeightedScore += ($weighting['value'] * $weighting['weighting']);
        }
        
        if ($totalWeight > 0) {
            $output = round($cummulativeWeightedScore / $totalWeight, 0);
            if ($percent) {
                $output .= '%';
            }
        }

        return $output;
    }

    public function cacheWeightings( ) {

    	$this->weightings = array();
        //$weightingsCount = 0;
        try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $sql = "SELECT attainmentWeighting, attainmentValue, gibbonPersonIDStudent FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleIDAttainment=gibbonScale.gibbonScaleID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonScale.numeric='Y' AND gibbonScaleID=(SELECT value FROM gibbonSetting WHERE scope='System' AND name='primaryAssessmentScale') AND complete='Y' AND NOT attainmentValue='' ORDER BY gibbonPersonIDStudent";
            $result=$this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) { $this->error( $e->getMessage() ); }

        while ($rowWeightings = $result->fetch()) {

            $id = $rowWeightings['gibbonPersonIDStudent'];
            $this->weightings[$id][] = array( 'weighting' => $rowWeightings['attainmentWeighting'], 'value' => $rowWeightings['attainmentValue'] );
        }
    }

    public function getPrimaryAssessmentScale() {

    	if (!empty($this->primaryAssessmentScale)) return $this->primaryAssessmentScale; 

    	$PAS = getSettingByScope($this->pdo->getConnection(), 'System', 'primaryAssessmentScale');
        try {
            $data = array('gibbonScaleID' => $PAS);
            $sql = 'SELECT name FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
            $result = $this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) { $this->error( $e->getMessage() ); }

        if ($result->rowCount() == 1) {
            $row = $result->fetch();
            $this->primaryAssessmentScale = (isset($row['name']))? $row['name'] : '';
        }

        return $this->primaryAssessmentScale;
    }

    public function hasExternalAssessments() {
    	return (isset($this->externalAssessmentFields))? (count($this->externalAssessmentFields) > 0) : false;
    }

    public function cacheExternalAssessments( $courseName, $gibbonYearGroupIDList ) {

		$gibbonYearGroupIDListArray = (explode(',', $gibbonYearGroupIDList));
		if (count($gibbonYearGroupIDListArray) == 1) {
		    $primaryExternalAssessmentByYearGroup = unserialize(getSettingByScope($this->pdo->getConnection(), 'School Admin', 'primaryExternalAssessmentByYearGroup'));

            if (!isset($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]])) return;

		    if ($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]] != '' and $primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]] != '-') {

		        $gibbonExternalAssessmentID = substr($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]], 0, strpos($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]], '-'));
		        $gibbonExternalAssessmentIDCategory = substr($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]], (strpos($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]], '-') + 1));

		        try {
		            $dataExternalAssessment = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID, 'category' => $gibbonExternalAssessmentIDCategory);
		            $courseNameTokens = explode(' ', $courseName);
		            $courseWhere = ' AND (';
		            $whereCount = 1;
		            foreach ($courseNameTokens as $courseNameToken) {
		                if (strlen($courseNameToken) > 3) {
		                    $dataExternalAssessment['token'.$whereCount] = '%'.$courseNameToken.'%';
		                    $courseWhere .= "gibbonExternalAssessmentField.name LIKE :token$whereCount OR ";
		                    ++$whereCount;
		                }
		            }

		            $courseWhere = ($whereCount < 1)? '' : substr($courseWhere, 0, -4).')';
		            
		            $sqlExternalAssessment = "SELECT gibbonExternalAssessment.name AS assessment, gibbonExternalAssessmentField.name, gibbonExternalAssessmentFieldID, category FROM gibbonExternalAssessmentField JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND category=:category $courseWhere ORDER BY name LIMIT 1";
		            $resultExternalAssessment = $this->pdo->executeQuery($dataExternalAssessment, $sqlExternalAssessment);
		        } catch (PDOException $e) { $this->error( $e->getMessage() ); }

		        if ($resultExternalAssessment->rowCount() >= 1) {
		            $rowExternalAssessment = $resultExternalAssessment->fetch();
		            $this->externalAssessmentFields = array();
		            $this->externalAssessmentFields[0] = $rowExternalAssessment['gibbonExternalAssessmentFieldID'];
		            $this->externalAssessmentFields[1] = $rowExternalAssessment['name'];
		            $this->externalAssessmentFields[2] = $rowExternalAssessment['assessment'];
		            $this->externalAssessmentFields[3] = $rowExternalAssessment['category'];
		        }
		    }
		}

    }

    private function error( $message ) {
    	echo "<div class='error'>".$e->getMessage().'</div>';
    }


}

?>
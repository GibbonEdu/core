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
	protected $pdo ;
	
	/**
	 * Gibbon\session
	 */
	protected $session ;
	
	/**
	 * Gibbon\config
	 */
	protected $config ;

	/**
	 * Gibbon Settings
	 */
	protected $settings = array();

	/**
	 * Markbook Values
	 */
	protected $columnsPerPage = 12;
	protected $columnsThisPage = -1;
	protected $columnCountTotal = -1;
	protected $minSequenceNumber = -1;

	/**
	 * Cache markbook values to reduce queries
	 */
	protected $primaryAssessmentScale;
	protected $externalAssessmentFields;
	protected $personalizedTargets;

    /**
     * Weightings
     * @var array
     */
    protected $categoryWeightings;
	protected $weightedMarkbookEntry;

    protected $weightedAverages;

    protected $termAverages;
    protected $endOfYearAverages;
    
    /**
     * Filters
     * @var array
     */
    protected $columnFilters;
    protected $sortFilters;

    /**
     * Column Row Data
     * @var array
     */
    protected $columns = array();

    public $terms = array();
    public $categories = array();

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

        // Get Gibbon settings
		$this->settings['enableColumnWeighting'] = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'enableColumnWeighting');
        $this->settings['enableRawAttainment'] = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'enableRawAttainment');
        $this->settings['enableGroupByTerm'] = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'enableGroupByTerm');

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

        if ($this->columnCountTotal > -1) return $this->columnCountTotal;

        // Build the initial column counts for this class
        try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $where = $this->getColumnFilters();
            $sql = 'SELECT count(*) as count, min(sequenceNumber) as min FROM gibbonMarkbookColumn WHERE '.$where;
            $result=$this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) { $this->error( $e->getMessage() ); }

        if ($result->rowCount() > 0) {
            $row = $result->fetch();

            $this->minSequenceNumber = (isset($row['min']))? $row['min'] : 0;
            $this->columnCountTotal = (isset($row['count']))? $row['count'] : 0;
        }
        return $this->columnCountTotal;
    }

    public function getColumns( $pageNum ) {

        // First ensure the total has been laoded, and cancel out early if there are no columns
        if ($this->getColumnCountTotal() < 1) return false;

    	try {
    		$data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
    		$where = $this->getColumnFilters();

    		$sql = 'SELECT * FROM gibbonMarkbookColumn WHERE '.$where.' ORDER BY sequenceNumber, date, complete, completeDate LIMIT '.($pageNum * $this->columnsPerPage).', '.$this->columnsPerPage;

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

        if (!isset($this->weightedMarkbookEntries[$gibbonPersonID])) return $output;

        foreach ($this->weightedMarkbookEntries[$gibbonPersonID] as $weighting) {
            $totalWeight += $weighting['weight'];
            $cummulativeWeightedScore += ($weighting['value'] * $weighting['weight']);
        }
        
        if ($totalWeight > 0) {
            $output = round($cummulativeWeightedScore / $totalWeight, 0);
            $output = ($weighting['percent'])? $output.'%' : $output;
        }

        return $output;
    }

    public function formattedAverage( $average ) {
        $PAS = $this->getPrimaryAssessmentScale();
        $percent = ( stripos($PAS['name'], 'percent') !== false || $PAS['nameShort'] == '%')? '%' : '';
        return "<span title='".round($average, 2)."'>". round($average, 0) ."$percent</span>";
    }

    public function getTypeAverage( $gibbonPersonID, $gibbonSchoolYearTermID, $type ) {
        return (isset($this->weightedAverages[$gibbonPersonID]['type'][$gibbonSchoolYearTermID][$type]))? $this->weightedAverages[$gibbonPersonID]['type'][$gibbonSchoolYearTermID][$type] : '';
    }

    public function getTermAverage( $gibbonPersonID, $gibbonSchoolYearTermID ) {
        return (isset($this->weightedAverages[$gibbonPersonID]['term'][$gibbonSchoolYearTermID]))? $this->weightedAverages[$gibbonPersonID]['term'][$gibbonSchoolYearTermID] : '';
    }

    public function getEndOfYearAverage( $gibbonPersonID ) {
        return (isset($this->weightedAverages[$gibbonPersonID]['endOfYear']))? $this->weightedAverages[$gibbonPersonID]['endOfYear'] : '';
    }

    public function getOverallAverage( $gibbonPersonID ) {
        return (isset($this->weightedAverages[$gibbonPersonID]['allTerms']))? $this->weightedAverages[$gibbonPersonID]['allTerms'] : '';
    }

    public function getFinalGradeAverage( $gibbonPersonID ) {
        return (isset($this->weightedAverages[$gibbonPersonID]['finalGrade']))? $this->weightedAverages[$gibbonPersonID]['finalGrade'] : '';
    }

    public function getTypeDescription( $type ) {
        if (isset($this->categoryWeightings[$type])) {
            return '<span title="'.floatval($this->categoryWeightings[$type]['weighting']).'% of '.ucfirst($this->categoryWeightings[$type]['calculate']).'">' . $this->categoryWeightings[$type]['description'] . '<span>';
        } else {
            return $type;
        }
        return (isset($this->categoryWeightings[$type]))? $this->categoryWeightings[$type]['description'] : $type;
    }

    public function getWeightingByType( $type ) {
        return (isset($this->categoryWeightings[$type]))? $this->categoryWeightings[$type]['weighting'] : 1;
    }

    public function getReportableByType( $type ) {
        return (isset($this->categoryWeightings[$type]))? $this->categoryWeightings[$type]['reportable'] : 1;
    }

    public function getCurrentTerms() {
        return (isset($this->terms))? $this->terms : array();
    } 

    protected function calculateWeightedAverages( ) {

        foreach($this->termAverages as $gibbonPersonID => $averages) {

            if (count($averages) < 1) continue;

            $weightedAverages = array();
            
            $overallTotal = 0;
            $overallCumulative = 0;

            foreach ($averages as $termID => $term) {

                if ($termID === 'endOfYear') continue;

                $termTotal = 0;
                $termCumulative = 0;
                foreach ($term as $type => $weighted) {

                    if ($weighted['total'] <= 0) continue;
                        
                    $typeWeight = $this->getWeightingByType( $type );
                    $typeAverage = ( $weighted['cumulative'] / $weighted['total'] );

                    $termTotal += $typeWeight;
                    $termCumulative += ($typeAverage * $typeWeight);

                    $weightedAverages['type'][$termID][$type] = $typeAverage;
                }

                $termWeight = 1;
                $termAverage = ($termTotal > 0)? ( $termCumulative / $termTotal ) : 0;

                $weightedAverages['term'][$termID] = $termAverage;

                $overallTotal += $termWeight;
                $overallCumulative += ($termAverage * $termWeight);
            }

            $finalTotal = 0;
            $finalCumulative = 0;

            if (isset($averages['endOfYear'])) {
                foreach ($averages['endOfYear'] as $type => $weighted) {

                    if ($weighted['total'] <= 0) continue;
                            
                    $typeWeight = $this->getWeightingByType( $type );
                    $typeAverage = ( $weighted['cumulative'] / $weighted['total'] );

                    $finalTotal += $typeWeight;
                    $finalCumulative += ($typeAverage * $typeWeight);

                    $weightedAverages['type']['endOfYear'][$type] = $typeAverage;
                }
            }

            $weightedAverages['endOfYear'] = ($finalTotal > 0)? ( $finalCumulative / $finalTotal ) : 0;

            $overallWeight = min(100.0, max(0.0, 100.0 - $finalTotal));
            $overallAverage = ( $overallCumulative / $overallTotal );

            $weightedAverages['allTerms'] = $overallAverage;

            $finalTotal += $overallWeight;
            $finalCumulative += ($overallAverage * $overallWeight);

            $weightedAverages['finalGrade'] = ($finalTotal > 0)? ( $finalCumulative / $finalTotal ) : 0;

            $this->weightedAverages[$gibbonPersonID] = $weightedAverages;
        }
    }

    public function cacheWeightings( ) {

        $this->categoryWeightings = array();

        try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $sql = 'SELECT type, description, weighting, reportable, calculate FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY calculate, type';
            $resultWeights = $this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($resultWeights->rowCount() > 0) {
            while ($rowWeightings = $resultWeights->fetch()) {
                $this->categoryWeightings[ $rowWeightings['type'] ] = $rowWeightings;
            }
        }


    	$this->weightedMarkbookEntries = array();
        $this->termAverages = array();

        try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $sql = "SELECT attainmentWeighting, attainmentValue, type, gibbonSchoolYearTermID, gibbonPersonIDStudent FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleIDAttainment=gibbonScale.gibbonScaleID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonScale.numeric='Y' AND gibbonScaleID=(SELECT value FROM gibbonSetting WHERE scope='System' AND name='primaryAssessmentScale') AND complete='Y' AND NOT attainmentValue='' ORDER BY gibbonPersonIDStudent";
            $result=$this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) { $this->error( $e->getMessage() ); }

        if ($result->rowCount() > 0) {
            while ($entry = $result->fetch()) {

                // Exclude incomplete values -- maybe make this a setting later?
                if ($entry['attainmentValue'] == 'Incomplete' || stripos($entry['attainmentValue'], 'Inc') !== false ) {
                    continue;
                }

                $gibbonPersonID = $entry['gibbonPersonIDStudent'];
                $type = (isset($entry['type']))? $entry['type'] : 'Unknown';

                if ($this->settings['enableGroupByTerm'] == 'Y' && isset($entry['gibbonSchoolYearTermID']) ) {
                    $term = $entry['gibbonSchoolYearTermID'];
                } else {
                    $term = 0;
                }

                $weight = floatval($entry['attainmentWeighting']);
                $value = floatval($entry['attainmentValue']);

                if (isset($this->categoryWeightings[$type]) && $this->categoryWeightings[$type]['calculate'] == 'year') {
                    $term = 'endOfYear';
                }

                if (isset($this->termAverages[$gibbonPersonID][$term][$type])) {
                    $this->termAverages[$gibbonPersonID][$term][$type]['total'] += $weight;
                    $this->termAverages[$gibbonPersonID][$term][$type]['cumulative'] += ($value * $weight);
                } else {
                    $this->termAverages[$gibbonPersonID][$term][$type] = array(
                        'total' => $weight,
                        'cumulative' => ($value * $weight),
                    );
                }

                $this->categories[] = $type;
                $this->terms[] = $term;
            }
        }

        $this->categories = array_unique($this->categories);
        $this->terms = array_unique($this->terms);


        $this->calculateWeightedAverages();
    }

    public function getPrimaryAssessmentScale() {

    	if (!empty($this->primaryAssessmentScale)) return $this->primaryAssessmentScale; 

    	$PAS = getSettingByScope($this->pdo->getConnection(), 'System', 'primaryAssessmentScale');
        try {
            $data = array('gibbonScaleID' => $PAS);
            $sql = 'SELECT name, nameShort FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
            $result = $this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) { $this->error( $e->getMessage() ); }

        if ($result->rowCount() == 1) {
            $this->primaryAssessmentScale = $result->fetch();
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

    public function filterByDateRange( $startDate, $endDate ) {

        // Check for properly formatted, valid dates
        $checkStart = explode('-', $startDate);
        $checkEnd = explode('-', $endDate);
        if (empty($checkStart) || count($checkStart) != 3 || empty($checkEnd) || count($checkEnd) != 3) {
            return false;
        }

        if (!checkdate($checkStart[1], $checkStart[2], $checkStart[0]) || !checkdate($checkEnd[1], $checkEnd[2], $checkEnd[0])) {
            return false;
        }
        
        // Use a key in the array to limit to one date filter at a time
        $this->columnFilters['daterange'] = "(date IS NOT NULL AND date BETWEEN '".$startDate."' AND '".$endDate."' )";
        return true;
    }

    public function filterByTerm( $gibbonSchoolYearTermID ) {
        if (empty($gibbonSchoolYearTermID)) return false;

        try {
            $data=array("gibbonSchoolYearTermID"=>$gibbonSchoolYearTermID );
            $sql="SELECT firstDay, lastDay FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID" ;
            $resultTerms=$this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) { $this->error( $e->getMessage() ); }

        if ($resultTerms->rowCount() > 0) {
            $termRow = $resultTerms->fetch();
            return $this->filterByDateRange( $termRow['firstDay'], $termRow['lastDay'] );
        } else {
            return false;
        }
    }

    public function filterByFormOptions( $filter ) {
        if (empty($filter)) return false;

        switch ($filter) {
             case 'marked':      return $this->filterByQuery( "complete = 'Y'" ); break;
             case 'unmarked':    return $this->filterByQuery( "complete = 'N'" ); break;
             case 'week':        return $this->filterByQuery( "WEEKOFYEAR(date)=WEEKOFYEAR(NOW())" ); break;
             case 'month':       return $this->filterByQuery( "MONTH(date)=MONTH(NOW())" ); break;
         }
    }

    public function filterByQuery($query) {
        if (empty($query)) return false;

        $this->columnFilters[] = $query;
        return true;
    }

    protected function getColumnFilters() {

        $where = 'gibbonCourseClassID=:gibbonCourseClassID';
        if (!empty($this->columnFilters)) {
            $where .= ' AND '. implode(' AND ', $this->columnFilters );
        }

        return $where;
    }

    protected function error( $message ) {
    	echo "<div class='error'>".$e->getMessage().'</div>';
    }


}

?>
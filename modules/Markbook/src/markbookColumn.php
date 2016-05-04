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
 * Holds and retrieves the information for a single column
 *
 * @version	4th May 2016
 * @since	4th May 2016
 * @author	Sandra Kuipers
 */
class markbookColumn
{
	public $gibbonMarkbookColumnID;

	/**
	 * Table row data from gibbonMarkbookColumn
	 * @var array
	 */
	private $data = array();

	private $spanCount;

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
    public function __construct( $row )
    {
    	$this->gibbonMarkbookColumnID = $row['gibbonMarkbookColumnID'];

    	$this->data = $row;

    	// $data['columnID'] = $row['gibbonMarkbookColumnID'];
     //    $data['attainmentOn'] = $row['attainment'];
     //    $data['attainmentID'] = $row['gibbonScaleIDAttainment'];
     //    $data['effortOn'] = $row['effort'];
     //    $data['effortID'] = $row['gibbonScaleIDEffort'];
     //    $data['gibbonPlannerEntryID'] = $row['gibbonPlannerEntryID'];
     //    $data['gibbonRubricIDAttainment'] = $row['gibbonRubricIDAttainment'];
     //    $data['gibbonRubricIDEffort'] = $row['gibbonRubricIDEffort'];
     //    $data['comment'] = $row['comment'];
     //    $data['uploadedResponse'] = $row['uploadedResponse'];
     //    $data['submission'] = false;

    	$this->spanCount = 0;

    	if ( $this->displayAttainment() ) $this->spanCount++;
    	if ( $this->displayEffort() ) $this->spanCount++;
    	if ( $this->displayComment() ) $this->spanCount++;
    	if ( $this->displayUploadedResponse() ) $this->spanCount++;
    	if ( $this->displaySubmission() ) $this->spanCount++;

    }

    public function getData( $key ) {
    	return (isset($this->data[$key]))? $this->data[$key] : NULL;
    }

    public function displayAttainment() {
    	if (isset($this->data['attainment'])) {
    		return ( $this->data['attainment'] == 'Y' && ($this->hasAttainmentGrade() || $this->hasAttainmentRubric()) );
    	} else {
    		return false;
    	}
    }

    public function displayEffort() {
    	if (isset($this->data['effort'])) {
    		return ( $this->data['effort'] == 'Y' && ($this->hasEffortGrade() || $this->hasEffortRubric()) );
    	} else {
    		return false;
    	}
    }

    public function displayComment() {
    	return (isset($this->data['comment']))? $this->data['comment'] == 'Y' : false;
    }

    public function displayUploadedResponse() {
    	return (isset($this->data['uploadedResponse']))? $this->data['uploadedResponse'] == 'Y' : false;
    }

    public function displaySubmission() {
    	return (isset($this->data['submission']))? $this->data['submission'] == 'Y' : false;
    }

    public function hasAttainmentGrade() {
    	return (isset($this->data['gibbonScaleIDAttainment']))? !empty($this->data['gibbonScaleIDAttainment']) : false;
    }

    public function hasAttainmentRubric() {
    	return (isset($this->data['gibbonRubricIDAttainment']))? !empty($this->data['gibbonRubricIDAttainment']) : false;
    }

    public function hasAttainmentWeighting() {
    	return (isset($this->data['attainmentWeighting']))? !empty($this->data['attainmentWeighting']) : false;
    }

    public function hasEffortGrade() {
    	return (isset($this->data['gibbonScaleIDEffort']))? !empty($this->data['gibbonScaleIDEffort']) : false;
    }

    public function hasEffortRubric() {
    	return (isset($this->data['gibbonRubricIDEffort']))? !empty($this->data['gibbonRubricIDEffort']) : false;
    }

    public function hasAttachment( $path ) {
    	return (isset($this->data['attachment']) && !empty($this->data['attachment']) && file_exists( $path.'/'.$this->data['attachment']));
    }

    public function getSpanCount() {
    	return $this->spanCount;
    }

    public function setSubmissionDetails( $row ) {
    	if (empty($row)) return false;
    	
    	$this->data['lessonDate'] = (isset($row['date']))? $row['date'] : '';
    	$this->data['homeworkDueDateTime'] = (isset($row['homeworkDueDateTime']))? $row['homeworkDueDateTime'] : '';
    	$this->data['homeworkSubmissionRequired'] = (isset($row['homeworkSubmissionRequired']))? $row['homeworkSubmissionRequired'] : '';

    	$this->data['submission'] = 'Y';

    }


}

?>
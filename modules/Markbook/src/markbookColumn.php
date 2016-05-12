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
 * Helper class to holds and retrieve information for a single markbook column.
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
     * Takes a row from gibbonMarkbookColumn and builds a helper class
     *
     * @version  3rd May 2016
     * @since    3rd May 2016
     * @param    array  SQL Data Row
     * @return   void
     */
    public function __construct( $row )
    {
    	$this->gibbonMarkbookColumnID = $row['gibbonMarkbookColumnID'];

    	$this->data = $row;
    	$this->spanCount = 0;

    	if ( $this->displayAttainment() ) $this->spanCount++;
    	if ( $this->displayEffort() ) $this->spanCount++;
    	if ( $this->displayComment() ) $this->spanCount++;
    	if ( $this->displayUploadedResponse() ) $this->spanCount++;
    	if ( $this->displaySubmission() ) $this->spanCount++;

    }

    /**
     * Get Data
     * Returns field data from the column's row
     * 
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @param   string $key
     * @return  mixed
     */
    public function getData( $key ) {
    	return (isset($this->data[$key]))? $this->data[$key] : NULL;
    }

    /**
     * Display Attainment
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function displayAttainment() {
    	if (isset($this->data['attainment'])) {
    		return ( $this->data['attainment'] == 'Y' && ($this->hasAttainmentGrade() || $this->hasAttainmentRubric()) );
    	} else {
    		return false;
    	}
    }

    /**
     * Display Effort
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function displayEffort() {
    	if (isset($this->data['effort'])) {
    		return ( $this->data['effort'] == 'Y' && ($this->hasEffortGrade() || $this->hasEffortRubric()) );
    	} else {
    		return false;
    	}
    }

    /**
     * Display Comment
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function displayComment() {
    	return (isset($this->data['comment']))? $this->data['comment'] == 'Y' : false;
    }

    /**
     * Display Uploaded Response
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function displayUploadedResponse() {
    	return (isset($this->data['uploadedResponse']))? $this->data['uploadedResponse'] == 'Y' : false;
    }

    /**
     * Display Submission
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function displaySubmission() {
    	return (isset($this->data['submission']))? $this->data['submission'] == 'Y' : false;
    }

    /**
     * Display Raw Marks
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function displayRawMarks() {
        return (isset($this->data['attainmentRaw']))? $this->data['attainmentRaw'] == 'Y' : false;
    }

    /**
     * Has Attainment Grade
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function hasAttainmentGrade() {
    	return (isset($this->data['gibbonScaleIDAttainment']))? !empty($this->data['gibbonScaleIDAttainment']) : false;
    }

    /**
     * Has Attainment Raw Max
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function hasAttainmentRawMax() {
        return (isset($this->data['attainmentRawMax']))? !empty($this->data['attainmentRawMax']) : false;
    }

    /**
     * Has Attainment Rubric
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function hasAttainmentRubric() {
    	return (isset($this->data['gibbonRubricIDAttainment']))? !empty($this->data['gibbonRubricIDAttainment']) : false;
    }

    /**
     * Ha sAttainment Weighting
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function hasAttainmentWeighting() {
    	return (isset($this->data['attainmentWeighting']))? !empty($this->data['attainmentWeighting']) : false;
    }

    /**
     * Has Effort Grade
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function hasEffortGrade() {
    	return (isset($this->data['gibbonScaleIDEffort']))? !empty($this->data['gibbonScaleIDEffort']) : false;
    }

    /**
     * Has Effort Rubric
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  bool
     */
    public function hasEffortRubric() {
    	return (isset($this->data['gibbonRubricIDEffort']))? !empty($this->data['gibbonRubricIDEffort']) : false;
    }

    /**
     * Has Attachment
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @param   string $path  File path to attachment directory
     * @return  bool
     */
    public function hasAttachment( $path ) {
    	return (isset($this->data['attachment']) && !empty($this->data['attachment']) && file_exists( $path.'/'.$this->data['attachment']));
    }

    /**
     * Get Span Count
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @return  int
     */
    public function getSpanCount() {
    	return $this->spanCount;
    }

    /**
     * Set Submission Details
     * @version 3rd May 2016
     * @since   3rd May 2016
     * @param   array $row
     */
    public function setSubmissionDetails( $row ) {
    	if (empty($row)) return false;
    	
    	$this->data['lessonDate'] = (isset($row['date']))? $row['date'] : '';
    	$this->data['homeworkDueDateTime'] = (isset($row['homeworkDueDateTime']))? $row['homeworkDueDateTime'] : '';
    	$this->data['homeworkSubmissionRequired'] = (isset($row['homeworkSubmissionRequired']))? $row['homeworkSubmissionRequired'] : '';

    	$this->data['submission'] = 'Y';
    }


}

?>
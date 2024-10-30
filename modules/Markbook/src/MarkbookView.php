<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Module\Markbook;

use Gibbon\Core;
use Gibbon\Domain\DataSet;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\System\SettingGateway;

/**
 * Markbook display & edit class
 *
 * @version 3rd May 2016
 * @since   3rd May 2016
 */
class MarkbookView
{

    /**
     * Gibbon\Contracts\Database\Connection
     */
    protected $pdo;

    /**
     * Gibbon Settings - preloaded
     */
    protected $settings = array();

    /**
     * Markbook Values
     */
    protected $columnsPerPage = 25;
    protected $columnsThisPage = -1;
    protected $columnCountTotal = -1;
    protected $minSequenceNumber = 9999999;

    /**
     * Cache markbook values to reduce queries
     */
    protected $defaultAssessmentScale;
    protected $externalAssessmentFields;
    protected $personalizedTargets;

    
    /**
     * Row data from gibbonMarkbookWeight
     * @var array
     */
    protected $markbookWeights;

    /**
     * Holds the sums for total and cumulative weighted values from markbookEntry
     * @var array
     */
    protected $weightedAverages;

    /**
     * Holds the sums for total and cumulative raw values from markbookEntry
     * @var array
     */
    protected $rawAverages;
    
    /**
     * SQL statements to be appended to the query to filter the current view
     * @var array
     */
    protected $columnFilters;
    protected $sortFilters;

    /**
     * Array of markbookColumn objects for each gibbonMarkbookColumn
     * @var array
     */
    protected $columns = array();

    /**
     * Array of the currently used gibbonSchoolYearTerms, populated by cacheWeightings
     * @var array
     */
    protected $terms = array();

    /**
     * Array of the currently used Markbook Types, populated by cacheWeightings
     * @var array
     */
    protected $types = array();

    /**
     * The database ID of the gibbonCourseClass
     * @var int
     */
    public $gibbonCourseClassID;

    /**
     * The database ID of the gibbonSchoolYear
     * @var int
     */
    public $gibbonSchoolYearID;

    /**
     * @var SettingGateway
     */
    private $settingGateway;

    /**
     * Constructor
     *
     * @version  3rd May 2016
     * @since    3rd May 2016
     * @param    Gibbon\Core
     * @param    Gibbon\Contracts\Database\Connection
     * @param    int  gibbonCourseClassID
     * @param    SettingGateway $settingGateway
     * @return   void
     */
    public function __construct(Core $gibbon, Connection $pdo, $gibbonCourseClassID, SettingGateway $settingGateway)
    {   
        global $session;
        $this->pdo = $pdo;

        $this->gibbonCourseClassID = $gibbonCourseClassID;
        $this->gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
        $this->settingGateway = $settingGateway;

        // Preload Gibbon settings - we check them a lot
        $this->settings['enableColumnWeighting'] = $this->settingGateway->getSettingByScope('Markbook', 'enableColumnWeighting');
        $this->settings['enableRawAttainment'] = $this->settingGateway->getSettingByScope('Markbook', 'enableRawAttainment');
        $this->settings['enableGroupByTerm'] = $this->settingGateway->getSettingByScope('Markbook', 'enableGroupByTerm');
        $this->settings['enableTypeWeighting'] = 'N';

        // Get settings
        $enableEffort = $this->settingGateway->getSettingByScope('Markbook', 'enableEffort');
        $enableRubrics = $this->settingGateway->getSettingByScope('Markbook', 'enableRubrics');
        $attainmentAltName = $this->settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
        $attainmentAltNameAbrev = $this->settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
        $effortAltName = $this->settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
        $effortAltNameAbrev = $this->settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');

        $this->settings['enableEffort'] = (!empty($enableEffort)) ? $enableEffort : 'N';
        $this->settings['enableRubrics'] = (!empty($enableRubrics)) ? $enableRubrics : 'N';

        $this->settings['attainmentName'] = (!empty($attainmentAltName)) ? $attainmentAltName : __('Attainment');
        $this->settings['attainmentAbrev'] = (!empty($attainmentAltNameAbrev)) ? $attainmentAltNameAbrev : __('Att');

        $this->settings['effortName'] = (!empty($effortAltName)) ? $effortAltName : __('Effort');
        $this->settings['effortAbrev'] = (!empty($effortAltNameAbrev)) ? $effortAltNameAbrev : __('Eff');
    }

    /**
     * Get Setting
     *
     * @version 11th May 2016
     * @since   11th May 2016
     * @param   string  $key
     * @return  string  Y or N
     */
    public function getSetting($key)
    {
        return (isset($this->settings[$key])) ? $this->settings[$key] : null;
    }

    /**
     * Get Minimum Sequence Number
     *
     * @version  7th May 2016
     * @since    7th May 2016
     * @return   int
     */
    public function getMinimumSequenceNumber()
    {
        return $this->minSequenceNumber;
    }

    /**
     * Get Columns Per Page
     *
     * @version  9th May 2016
     * @since    9th May 2016
     * @return   int
     */
    public function getColumnsPerPage()
    {
        return $this->columnsPerPage;
    }

    /**
     * Get Column Count This Page
     * @version 7th May 2016
     * @since   7th May 2016
     * @return  int
     */
    public function getColumnCountThisPage()
    {
        return $this->columnsThisPage;
    }

    /**
     * Get Column Count Total
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @return  int
     */
    public function getColumnCountTotal()
    {
        if ($this->columnCountTotal > -1) {
            return $this->columnCountTotal;
        }

        // Build the initial column counts for this class
        try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $where = $this->getColumnFilters();
            $sql = 'SELECT count(*) as count FROM gibbonMarkbookColumn WHERE ' . $where;
            $result = $this->pdo->select($sql, $data);
        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            $this->columnCountTotal = (isset($row['count'])) ? $row['count'] : 0;
        }

        return $this->columnCountTotal;
    }

    /**
     * Load Columns
     *
     * @deprecated v17
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   int    $pageNum
     * @return  bool   true if there are columns
     */
    public function loadColumns($pageNum)
    {

        // First ensure the total has been loaded, and cancel out early if there are no columns
        if ($this->getColumnCountTotal() < 1) {
            return false;
        }

        // Grab the minimum sequenceNumber only once for the current page set, to pass to markbook_viewAjax.php
        if ($this->minSequenceNumber == -1) {
            try {
                $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
                $where = $this->getColumnFilters();
                $sql = 'SELECT min(sequenceNumber) as min FROM (SELECT sequenceNumber FROM gibbonMarkbookColumn WHERE ' . $where . ' LIMIT ' . ($pageNum * $this->columnsPerPage) . ', ' . $this->columnsPerPage . ') as mc';
                $resultSequence = $this->pdo->select($sql, $data);
            } catch (\PDOException $e) {
                $this->error($e->getMessage());
            }

            if ($resultSequence->rowCount() > 0) {
                $this->minSequenceNumber = $resultSequence->fetchColumn();
            }
        }

        // Query the markbook columns, applying any filters that have been added
        try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $where = $this->getColumnFilters();

            $sql = 'SELECT * FROM gibbonMarkbookColumn WHERE ' . $where . ' ORDER BY sequenceNumber, date, complete, completeDate LIMIT ' . ($pageNum * $this->columnsPerPage) . ', ' . $this->columnsPerPage;

            $result = $this->pdo->select($sql, $data);
        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

        $this->columnsThisPage = $result->rowCount();
        $this->columns = array();

        // Build a markbookColumn object for each row
        for ($i = 0; $i < $this->columnsThisPage; ++$i) {
            $column = new MarkbookColumn($result->fetch(), $this->settings['enableEffort'], $this->settings['enableRubrics']);

            if ($column != null) {
                $this->columns[$i] = $column;

                //WORK OUT IF THERE IS SUBMISSION
                if (!empty($column->getData('gibbonPlannerEntryID'))) {
                    try {
                        $dataSub = array("gibbonPlannerEntryID" => $column->getData('gibbonPlannerEntryID'));
                        $sqlSub = "SELECT homeworkDueDateTime, date, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID LIMIT 1";
                        $resultSub = $this->pdo->select($sqlSub, $dataSub);
                    } catch (\PDOException $e) {
                        $this->error($e->getMessage());
                    }

                    if ($resultSub && $resultSub->rowCount() > 0) {
                        $column->setSubmissionDetails($resultSub->fetch());
                    }
                }
            }
        }

        if ($this->columnsThisPage != count($this->columns)) {
            $this->error("Column count mismatch. Something went horribly wrong loading column data.");
        }

        return (count($this->columns) > 0);
    }

    /**
     * Load the current markbook columns from a DataSet.
     *
     * @param   DataSet $dataSet
     * @return  bool    true if there are columns
     */
    public function loadColumnsFromDataSet(DataSet $dataSet)
    {
        $this->columns = [];
        $this->columnCountTotal = $dataSet->getResultCount();
        $this->columnsThisPage = count($dataSet);

        // Build a markbookColumn object for each row
        foreach ($dataSet as $i => $columnData) {
            if ($column = new MarkbookColumn($columnData, $this->settings['enableEffort'], $this->settings['enableRubrics'])) {
                $this->columns[$i] = $column;

				// Grab the minimum sequenceNumber for the current page set, to pass to markbook_viewAjax.php
				$this->minSequenceNumber = min($this->minSequenceNumber, $columnData['sequenceNumber']);

				// Attach planner info to help determine if theres homework submissions for this column
                if (!empty($columnData['gibbonPlannerEntry'])) {
                    $column->setSubmissionDetails($columnData['gibbonPlannerEntry']);
                }
            }
        }

        return !empty($this->columns);
    }

    /**
     * Get a single markbookColumn object
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   int     $i Column Index
     * @return  Object  markbookColumn class
     */
    public function getColumn($i)
    {
        return (isset($this->columns[$i])) ? $this->columns[$i] : null;
    }

    /**
     * Get the Primary Assessment Scale info only once & hang onto it
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @return  array
     */
    public function getDefaultAssessmentScale()
    {

        if (!empty($this->defaultAssessmentScale)) {
            return $this->defaultAssessmentScale;
        }

        $DAS = $this->settingGateway->getSettingByScope('System', 'defaultAssessmentScale');
        try {
            $data = array('gibbonScaleID' => $DAS);
            $sql = 'SELECT `name`, `nameShort`, `numeric` FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
            $result = $this->pdo->select($sql, $data);
        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

        if ($result->rowCount() == 1) {
            $DAS = $result->fetch();
            $this->defaultAssessmentScale = $DAS;
            $this->defaultAssessmentScale['percent'] = (stripos($DAS['name'], 'percent') !== false || $DAS['nameShort'] == '%') ? '%' : '';
        }

        return $this->defaultAssessmentScale;
    }

    /**
     * Get Personalized Target from cached values
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $gibbonPersonID
     * @return  int
     */
    public function getTargetForStudent($gibbonPersonID)
    {
        return (isset($this->personalizedTargets[$gibbonPersonID])) ? $this->personalizedTargets[$gibbonPersonID] : '';
    }

    /**
     * Do we have Personalized Targets? Used to hide the Target column
     * @version 7th May 2016
     * @since   7th May 2016
     * @return  bool
     */
    public function hasPersonalizedTargets()
    {
        return (isset($this->personalizedTargets)) ? (count($this->personalizedTargets) > 0) : false;
    }

    /**
     * Cache Personalized Targets
     *
     * @version 7th May 2016
     * @since   7th May 2016
     */
    public function cachePersonalizedTargets()
    {

        $this->personalizedTargets = array();

        try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $sql = 'SELECT gibbonPersonIDStudent, value FROM gibbonMarkbookTarget JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
            $result = $this->pdo->select($sql, $data);
        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

        if ($result->rowCount() > 0) {
            while ($row = $result->fetch()) {
                $this->personalizedTargets[$row['gibbonPersonIDStudent']] = $row['value'];
            }
        }
    }

    /**
     * Get a Formatted Average with titles and maybe a percent sign
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string|int $average
     * @return  string
     */
    public function getFormattedAverage($average)
    {
        if ($average === '') {
            return $average;
        }

        $DAS = $this->getDefaultAssessmentScale();
        return "<span title='" . number_format(round($average, 2), 2) . "'>" . round($average, 0) . $DAS['percent'] . "</span>";
    }

    /**
     * Get the average grade for a given Markbook Type (from pre-calculated values)
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $gibbonPersonID
     * @param   string $gibbonSchoolYearTermID
     * @param   string $type
     * @return  int|string
     */
    public function getTypeAverage($gibbonPersonID, $gibbonSchoolYearTermID, $type)
    {
        if ($gibbonSchoolYearTermID == '0') {
            $gibbonSchoolYearTermID = 'all';
        }

        $gibbonPersonID = str_pad($gibbonPersonID, 10, '0', STR_PAD_LEFT);
        return (isset($this->weightedAverages[$gibbonPersonID]['type'][$gibbonSchoolYearTermID][$type])) ? $this->weightedAverages[$gibbonPersonID]['type'][$gibbonSchoolYearTermID][$type] : '';
    }

    /**
     * Get the average grade for the School Year Term (from pre-calculated values)
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $gibbonPersonID
     * @param   string $gibbonSchoolYearTermID
     * @return  int|string
     */
    public function getTermAverage($gibbonPersonID, $gibbonSchoolYearTermID)
    {
        if ($gibbonSchoolYearTermID == '0') {
            $gibbonSchoolYearTermID = 'all';
        }

        $gibbonPersonID = str_pad($gibbonPersonID, 10, '0', STR_PAD_LEFT);
        return (isset($this->weightedAverages[$gibbonPersonID]['term'][$gibbonSchoolYearTermID])) ? $this->weightedAverages[$gibbonPersonID]['term'][$gibbonSchoolYearTermID] : '';
    }

    /**
     * Get the overall Cumulative Average for all marks (from pre-calculated values)
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $gibbonPersonID
     * @return  int|string
     */
    public function getCumulativeAverage($gibbonPersonID, $gibbonSchoolYearTermID = '')
    {
        $gibbonPersonID = str_pad($gibbonPersonID, 10, '0', STR_PAD_LEFT);
        if ($gibbonSchoolYearTermID == '') {
            return (isset($this->weightedAverages[$gibbonPersonID]['cumulative'])) ? $this->weightedAverages[$gibbonPersonID]['cumulative'] : '';
        } else {
            return (isset($this->weightedAverages[$gibbonPersonID]['term'][$gibbonSchoolYearTermID])) ? $this->weightedAverages[$gibbonPersonID]['term'][$gibbonSchoolYearTermID] : '';
        }
    }

    /**
     * Get the overall Final Grade for all marks (from pre-calculated values)
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $gibbonPersonID
     * @return  int|string
     */
    public function getExamAverage($gibbonPersonID)
    {
        $gibbonPersonID = str_pad($gibbonPersonID, 10, '0', STR_PAD_LEFT);
        return (isset($this->weightedAverages[$gibbonPersonID]['final'])) ? $this->weightedAverages[$gibbonPersonID]['final'] : '';
    }

    /**
     * Get the calculated Final Grade average (from pre-calculated values)
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $gibbonPersonID
     * @return  int|string
     */
    public function getFinalGradeAverage($gibbonPersonID)
    {
        $gibbonPersonID = str_pad($gibbonPersonID, 10, '0', STR_PAD_LEFT);
        return (isset($this->weightedAverages[$gibbonPersonID]['finalGrade'])) ? $this->weightedAverages[$gibbonPersonID]['finalGrade'] : '';
    }

    /**
     * Get a description for a Markbook Type if it has one set in markbookWeights
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $type
     * @return  string
     */
    public function getTypeDescription($type)
    {
        return (isset($this->markbookWeights[$type])) ? $this->markbookWeights[$type]['description'] : $type;
    }

    /**
     * Get the weighting by Markbook Type, from markbookWeights
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $type
     * @return  int
     */
    public function getWeightingByType($type)
    {
        if (isset($this->markbookWeights[$type])) {
            if ($this->markbookWeights[$type]['reportable'] == 'Y') {
                return $this->markbookWeights[$type]['weighting'];
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }

    /**
     * Get if the Markbook Type is reportable
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $type
     * @return  string
     */
    public function getReportableByType($type)
    {
        return (isset($this->markbookWeights[$type])) ? $this->markbookWeights[$type]['reportable'] : 'Y';
    }

    /**
     * Get a grouped set of column types, for different weighting calculations (currently 'term' or 'year')
     * Types will only be grouped into 'term' if enableGroupByTerm is on
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $calculate
     * @return  array
     */
    public function getGroupedMarkbookTypes($calculate = 'year')
    {
        return (isset($this->types[$calculate])) ? $this->types[$calculate] : array();
    }

    /**
     * Get a subset of terms used by the current markbook columns
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @return  array
     */
    public function getCurrentTerms()
    {
        return (isset($this->terms)) ? $this->terms : array();
    }

    /**
     * Calculate and cache all the weighted averages for this Markbook
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @see cacheWeightings
     */
    protected function calculateWeightedAverages()
    {

        if (count($this->rawAverages) == 0) {
            return;
        }

        // Iterate through each student in the markbookEntry set
        foreach ($this->rawAverages as $gibbonPersonID => $averages) {
            if (count($averages) == 0) {
                continue;
            }

            $weightedAverages = array();

            $overallTotal = 0;
            $overallCumulative = 0;

            // Calculate the 'term' averages (Cumulative Average)
            foreach ($averages as $termID => $term) {
                if ($termID == 'final') {
                    continue;
                }

                $termTotal = 0;
                $termCumulative = 0;
                foreach ($term as $type => $weighted) {
                    if ($weighted['total'] <= 0) {
                        continue;
                    }

                    $typeWeight = $this->getWeightingByType($type);
                    $typeAverage = ($weighted['total'] > 0) ? ($weighted['cumulative'] / $weighted['total']) : '';

                    $termTotal += $typeWeight;
                    $termCumulative += ($typeAverage * $typeWeight);

                    $weightedAverages['type'][$termID][$type] = $typeAverage;
                }

                $termAverage = ($termTotal > 0) ? ($termCumulative / $termTotal) : '';

                $weightedAverages['term'][$termID] = $termAverage;
            }

            $terms = array_keys($averages);

            if (!empty($terms) && is_array($terms)) {
                // Get the type names used in all terms (or whole year for now terms)
                $types = array();
                if (isset($this->types['term'])) {
                    $types = array_merge($types, $this->types['term']);
                }

                if (isset($this->types['year'])) {
                    $types = array_merge($types, $this->types['year']);
                }

                if (isset($this->types['all'])) {
                    $types = array_merge($types, $this->types['all']);
                }

                // Calculate the overall cumulative type averages, separate from terms
                foreach ($types as $type) {
                    $typeTotal = null;
                    $typeCumulative = null;

                    $typeWeight = $this->getWeightingByType($type);

                    foreach ($terms as $term) {
                        // Dont include final term marks in the cumulative average
                        if ($term == 'final') {
                            continue;
                        }

                        if (!isset($averages[$term][$type])) {
                            continue;
                        }

                        $weighted = $averages[$term][$type];

                        if ($weighted['total'] <= 0) {
                            continue;
                        }

                        $typeTotal += $weighted['total'];
                        $typeCumulative += $weighted['cumulative'];
                    }

                    // Skip weighting types that have no marks (not marks of zero, but absence of marks)
                    if ($typeTotal === null || $typeCumulative === null) {
                        continue;
                    }

                    $typeAverage = ($typeTotal > 0) ? ($typeCumulative / $typeTotal) : 0;

                    $overallTotal += $typeWeight;
                    $overallCumulative += ($typeAverage * $typeWeight);
                }
            }

            $finalTotal = 0;
            $finalCumulative = 0;

            // Calculate the averages for 'year' (Final Mark) weightings
            if (isset($averages['final'])) {
                foreach ($averages['final'] as $type => $weighted) {
                    if ($weighted['total'] <= 0) {
                        continue;
                    }

                    $typeWeight = $this->getWeightingByType($type);
                    $typeAverage = ($weighted['total'] > 0) ? ($weighted['cumulative'] / $weighted['total']) : 0;

                    $finalTotal += $typeWeight;
                    $finalCumulative += ($typeAverage * $typeWeight);

                    $weightedAverages['type']['final'][$type] = $typeAverage;
                }
            }

            $weightedAverages['final'] = ($finalTotal > 0) ? ($finalCumulative / $finalTotal) : '';

            // The overall weight is 100 minus the sum of Final Grade weights
            $overallWeight = min(100.0, max(0.0, 100.0 - $finalTotal));
            $overallAverage = ($overallTotal > 0) ? ($overallCumulative / $overallTotal) : 0;

            $weightedAverages['cumulative'] = $overallAverage > 0 ? $overallAverage : '';

            $finalTotal += $overallWeight;
            $finalCumulative += ($overallAverage * $overallWeight);

            $weightedAverages['finalGrade'] = ($finalTotal > 0) ? ($finalCumulative / $finalTotal) : '';

            // Save all the weighted averages in a per-student array
            $this->weightedAverages[$gibbonPersonID] = $weightedAverages;
        }
    }

    /**
     * Retrieve all weighting info and weighted markbookEntry rows and collect them in a useful array
     *
     * @version 7th May 2016
     * @since   7th May 2016
     */
    public function cacheWeightings($gibbonPersonIDStudent = null)
    {

        $this->markbookWeights = array();

        // Gather weighted Markbook Type info
        try {
            $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
            $sql = 'SELECT type, description, weighting, reportable, calculate FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY calculate, type';
            $resultWeights = $this->pdo->select($sql, $data);
        } catch (\PDOException $e) {
        }

        if ($resultWeights->rowCount() > 0) {
            $this->settings['enableTypeWeighting'] = 'Y';

            while ($rowWeightings = $resultWeights->fetch()) {
                $this->markbookWeights[$rowWeightings['type']] = $rowWeightings;
            }
        }

        $this->rawAverages = array();

        $typesUsed = array();
        $termsUsed = array();

        // Lookup a single student
        if (!empty($gibbonPersonIDStudent)) {
            $gibbonPersonIDStudent = str_pad($gibbonPersonIDStudent, 10, '0', STR_PAD_LEFT);

            try {
                $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
                $sql = "SELECT attainmentWeighting, attainmentRaw, attainmentRawMax, attainmentValue, attainmentValueRaw, type, gibbonSchoolYearTermID, gibbonPersonIDStudent FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleIDAttainment=gibbonScale.gibbonScaleID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonScale.numeric='Y' AND gibbonScaleID=(SELECT value FROM gibbonSetting WHERE scope='System' AND name='defaultAssessmentScale') AND complete='Y' AND NOT attainmentValue='' AND gibbonPersonIDStudent=:gibbonPersonIDStudent ORDER BY gibbonPersonIDStudent, completeDate";
                $result = $this->pdo->select($sql, $data);
            } catch (\PDOException $e) {
                $this->error($e->getMessage());
            }
        } else {
            try {
                $data = array('gibbonCourseClassID' => $this->gibbonCourseClassID);
                $sql = "SELECT attainmentWeighting, attainmentRaw, attainmentRawMax, attainmentValue, attainmentValueRaw, type, gibbonSchoolYearTermID, gibbonPersonIDStudent FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleIDAttainment=gibbonScale.gibbonScaleID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonScale.numeric='Y' AND gibbonScaleID=(SELECT value FROM gibbonSetting WHERE scope='System' AND name='defaultAssessmentScale') AND complete='Y' AND NOT attainmentValue='' ORDER BY gibbonPersonIDStudent, completeDate";
                $result = $this->pdo->select($sql, $data);
            } catch (\PDOException $e) {
                $this->error($e->getMessage());
            }
        }

        if ($result->rowCount() > 0) {
            while ($entry = $result->fetch()) {
                // Exclude incomplete values -- maybe make this a setting later?
                if (!is_numeric(rtrim($entry['attainmentValue'], "%"))) {
                    continue;
                }

                $gibbonPersonID = $entry['gibbonPersonIDStudent'];

                // floatval these to reduce them to numeric info only
                $weight = floatval($entry['attainmentWeighting']);
                $value = floatval($entry['attainmentValue']);

                // Use the raw percent rather than the rounded values for higher accuracy, if they're available
                if ($this->settings['enableRawAttainment'] == 'Y' && stripos($entry['attainmentValue'], '%') !== false) {
                    if ($entry['attainmentRaw'] == 'Y' && $entry['attainmentValueRaw'] > 0 && $entry['attainmentRawMax'] > 0) {
                        $value = floatval(($entry['attainmentValueRaw'] / $entry['attainmentRawMax']) * 100);
                    }
                }

                if (isset($entry['type'])) {
                    $type = $entry['type'];
                    if ($weight > 0) {
                        $typesUsed[] = $type;
                    }
                } else {
                    $type = 'Unknown';
                }

                if ($this->settings['enableGroupByTerm'] == 'Y' && isset($entry['gibbonSchoolYearTermID'])) {
                    $term = $entry['gibbonSchoolYearTermID'];
                    $termsUsed[] = $term;
                } else {
                    $term = 'all';
                }

                // Group the end-of-course weightings in a specifically named 'term'
                if ($this->settings['enableTypeWeighting'] == 'Y') {
                    if (isset($this->markbookWeights[$type]) && $this->markbookWeights[$type]['calculate'] == 'year') {
                        $term = 'final';
                    }
                }

                // Sum up the raw averages for each entry as we go
                if (isset($this->rawAverages[$gibbonPersonID][$term][$type])) {
                    $this->rawAverages[$gibbonPersonID][$term][$type]['total'] += $weight;
                    $this->rawAverages[$gibbonPersonID][$term][$type]['cumulative'] += ($value * $weight);
                } else {
                    $this->rawAverages[$gibbonPersonID][$term][$type] = array(
                        'total' => $weight,
                        'cumulative' => ($value * $weight),
                    );
                }
            }
        }

        // Group the used Markbook Types together, if nessesary
        if (count($typesUsed) > 0) {
            $typesUsed = array_unique($typesUsed);

            foreach ($typesUsed as $type) {
                if ($this->settings['enableTypeWeighting'] == 'Y') {
                    if (isset($this->markbookWeights[$type])) {
                        $this->types[$this->markbookWeights[$type]['calculate']][] = $type;
                    }
                } else {
                    $this->types['year'][] = $type;
                }
            }
        }

        // Get the proper term order and info for the terms used
        if (count($termsUsed) > 0 && $this->settings['enableGroupByTerm'] == 'Y') {
            $termsUsed = array_unique($termsUsed);
            $this->terms = array();

            try {
                $data = array("gibbonSchoolYearID" => $this->gibbonSchoolYearID);
                $sql = "SELECT gibbonSchoolYearTermID, name, nameShort FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber";
                $resultTerms = $this->pdo->select($sql, $data);
            } catch (\PDOException $e) {
                $this->error($e->getMessage());
            }

            if ($resultTerms->rowCount() > 0) {
                while ($row = $resultTerms->fetch()) {
                    if (in_array($row['gibbonSchoolYearTermID'], $termsUsed)) {
                        $this->terms[$row['gibbonSchoolYearTermID']] = $row;
                    }
                }
            }
        }

        $this->calculateWeightedAverages();
    }

    /**
     * Has External Assessments
     *
     * @version 14th August 2016
     * @since   7th May 2016
     * @return  bool
     */
    public function hasExternalAssessments()
    {
        return (isset($this->externalAssessmentFields)) ? (count($this->externalAssessmentFields) > 0) : false;
    }

    /**
     * Get External Assessments
     *
     * @version 14th August 2016
     * @since   14th August 2016
     * @return  bool
     */
    public function getExternalAssessments()
    {
        return (isset($this->externalAssessmentFields)) ? $this->externalAssessmentFields : false;
    }

    /**
     * Cache External Assessments
     *
     * @version 14th August 2016
     * @since   7th May 2016
     * @param   string $courseName
     * @param   string $gibbonYearGroupIDList
     */
    public function cacheExternalAssessments($courseName, $gibbonYearGroupIDList)
    {

        $gibbonYearGroupIDListArray = (explode(',', $gibbonYearGroupIDList));
        if (count($gibbonYearGroupIDListArray) == 1) {
            $primaryExternalAssessmentByYearGroup = unserialize($this->settingGateway->getSettingByScope('School Admin', 'primaryExternalAssessmentByYearGroup'));

            if (!isset($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]])) {
                return;
            }

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
                            $dataExternalAssessment['token' . $whereCount] = '%' . $courseNameToken . '%';
                            $courseWhere .= "gibbonExternalAssessmentField.name LIKE :token$whereCount OR ";
                            ++$whereCount;
                        }
                    }

                    $courseWhere = ($whereCount < 1) ? '' : substr($courseWhere, 0, -4) . ')';

                    $sqlExternalAssessment = "SELECT gibbonExternalAssessment.name AS assessment, gibbonExternalAssessmentField.name, gibbonExternalAssessmentFieldID, category, gibbonScale.name AS scale
                        FROM gibbonExternalAssessmentField
                            JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID)
                            JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID)
                        WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID
                            AND category=:category $courseWhere
                        ORDER BY name
                        LIMIT 1";
                    $resultExternalAssessment = $this->pdo->select($sqlExternalAssessment, $dataExternalAssessment);
                } catch (\PDOException $e) {
                    $this->error($e->getMessage());
                }

                if ($resultExternalAssessment->rowCount() >= 1) {
                    $rowExternalAssessment = $resultExternalAssessment->fetch();
                    $this->externalAssessmentFields = array();
                    $this->externalAssessmentFields[0] = $rowExternalAssessment['gibbonExternalAssessmentFieldID'];
                    $this->externalAssessmentFields[1] = $rowExternalAssessment['name'];
                    $this->externalAssessmentFields[2] = $rowExternalAssessment['assessment'];
                    $this->externalAssessmentFields[3] = $rowExternalAssessment['category'];
                    $this->externalAssessmentFields[4] = $rowExternalAssessment['scale'];
                }
            }
        }
    }

    /**
     * Creates a date range SQL filter, also checks validity of dates provided
     *
     * @deprecated v17
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $startDate  YYYY-MM-DD Format
     * @param   string $endDate    YYYY-MM-DD Format
     * @return  bool   True if the filter was added
     */
    public function filterByDateRange($startDate, $endDate)
    {

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
        $this->columnFilters['daterange'] = "(date IS NOT NULL AND date BETWEEN '" . $startDate . "' AND '" . $endDate . "' )";
        return true;
    }

    /**
     * Filter By Term
     *
     * @deprecated v17
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   int|string $gibbonSchoolYearTermID
     * @return  bool       True if the filter was added
     */
    public function filterByTerm($gibbonSchoolYearTermID)
    {
        if (empty($gibbonSchoolYearTermID)) {
            return false;
        }

        try {
            $data = array("gibbonSchoolYearTermID" => $gibbonSchoolYearTermID);
            $sql = "SELECT firstDay, lastDay FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID";
            $resultTerms = $this->pdo->select($sql, $data);
        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

        if ($resultTerms->rowCount() > 0) {
            $termRow = $resultTerms->fetch();
            $this->columnFilters['daterange'] = "( gibbonSchoolYearTermID=" . intval($gibbonSchoolYearTermID) . " OR ( date IS NOT NULL AND date BETWEEN '" . $termRow['firstDay'] . "' AND '" . $termRow['lastDay'] . "' ) )";
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a SQL frieldly string of query modifiers
     *
     * @deprecated v17
     * @version 7th May 2016
     * @since   7th May 2016
     * @return  string
     */
    protected function getColumnFilters()
    {

        $where = 'gibbonCourseClassID=:gibbonCourseClassID';
        if (!empty($this->columnFilters)) {
            $where .= ' AND ' . implode(' AND ', $this->columnFilters);
        }

        return $where;
    }

    /**
     * Handle error display. Maybe do something fancier here, eventually.
     *
     * @version 7th May 2016
     * @since   7th May 2016
     * @param   string $message
     */
    protected function error(string $message)
    {
        echo "<div class='error'>" . $message . '</div>';
    }
}

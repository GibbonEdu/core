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

use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonReportingCycleID = $_POST['gibbonReportingCycleID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_cycles_manage_duplicate.php&gibbonReportingCycleID='.$gibbonReportingCycleID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage_duplicate.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);
    $formGroupGateway = $container->get(FormGroupGateway::class);
    $courseGateway = $container->get(CourseGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $_POST['gibbonSchoolYearID'] ?? '',
        'name'                  => $_POST['name'] ?? '',
        'nameShort'             => $_POST['nameShort'] ?? '',
        'dateStart'             => $_POST['dateStart'] ?? '',
        'dateEnd'               => $_POST['dateEnd'] ?? '',
        'cycleNumber'           => $_POST['cycleNumber'] ?? '1',
        'cycleTotal'            => $_POST['cycleTotal'] ?? '1',
    ];

    $data['dateStart'] = Format::dateConvert($data['dateStart']);
    $data['dateEnd'] = Format::dateConvert($data['dateEnd']);
    
    // Validate the required values are present
    if (empty($gibbonReportingCycleID) || empty($data['gibbonSchoolYearID']) || empty($data['name']) || empty($data['nameShort'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate the database relationships exist
    $values = $reportingCycleGateway->getByID($gibbonReportingCycleID);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportingCycleGateway->unique($data, ['name', 'gibbonSchoolYearID'], $gibbonReportingCycleID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update milestone dates
    $milestones = json_decode($values['milestones'], true);
    foreach ($milestones ?? [] as $index => $milestone) {
        $milestones[$index]['milestoneDate'] = $data['dateStart'];
    }

    // Update duplicated values
    $data['milestones'] = json_encode($milestones);
    $data['notes'] = $values['notes'];
    $data['sequenceNumber'] = $values['sequenceNumber'];
    $data['gibbonYearGroupIDList'] = $values['gibbonYearGroupIDList'];

    // Create the record
    $gibbonReportingCycleIDNew = $reportingCycleGateway->insert($data);
    $failedCriteria = 0;

    // Duplicate the reporting scopes and criteria
    if (!empty($gibbonReportingCycleIDNew)) {
        $scopes = $reportingScopeGateway->selectBy(['gibbonReportingCycleID' => $gibbonReportingCycleID])->fetchAll();
        foreach ($scopes as $scopeData) {
            $scopeData['gibbonReportingCycleID'] = $gibbonReportingCycleIDNew;
            $gibbonReportingScopeIDNew = $reportingScopeGateway->insert($scopeData);

            if (!empty($gibbonReportingScopeIDNew)) {
                $criteria = $reportingCriteriaGateway->selectBy([
                    'gibbonReportingCycleID' => $gibbonReportingCycleID,
                    'gibbonReportingScopeID' => $scopeData['gibbonReportingScopeID']]
                )->fetchAll();

                foreach ($criteria as $criteriaData) {
                    // Grab the form group ID by name if it's in a different school year
                    if (!empty($criteriaData['gibbonFormGroupID']) && $data['gibbonSchoolYearID'] != $values['gibbonSchoolYearID']) {
                        $formGroupSource = $formGroupGateway->getByID($criteriaData['gibbonFormGroupID']);
                        $formGroupDestination = $formGroupGateway->selectBy([
                            'gibbonSchoolYearID' => $data['gibbonSchoolYearID'], 
                            'nameShort' => $formGroupSource['nameShort'],
                        ])->fetch();

                        if (!empty($formGroupDestination['gibbonFormGroupID'])) {
                            $criteriaData['gibbonFormGroupID'] = $formGroupDestination['gibbonFormGroupID'];
                        } else {
                            $failedCriteria++;
                            continue;
                        }
                    }
                    // Grab the course ID by name if it's in a different school year
                    if (!empty($criteriaData['gibbonCourseID']) && $data['gibbonSchoolYearID'] != $values['gibbonSchoolYearID']) {
                        $courseSource = $courseGateway->getByID($criteriaData['gibbonCourseID']);
                        $courseDestination = $courseGateway->selectBy([
                            'gibbonSchoolYearID' => $data['gibbonSchoolYearID'], 
                            'nameShort' => $courseSource['nameShort'],
                        ])->fetch();
                        
                        if (!empty($courseDestination['gibbonCourseID'])) {
                            $criteriaData['gibbonCourseID'] = $courseDestination['gibbonCourseID'];
                        } else {
                            $failedCriteria++;
                            continue;
                        }
                    }

                    $criteriaData['gibbonReportingCycleID'] = $gibbonReportingCycleIDNew;
                    $criteriaData['gibbonReportingScopeID'] = $gibbonReportingScopeIDNew;
                    $gibbonReportingCriteriaIDNew = $reportingCriteriaGateway->insert($criteriaData);
                }
            }
        }
    }

    if (!$gibbonReportingCycleIDNew) {
        $URL .= "&return=error2";
        header("Location: {$URL}");
    }

    $URL .= !empty($failedCriteria)
        ? "&return=warning3&failedCriteria=$failedCriteria"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonReportingCycleIDNew");
}

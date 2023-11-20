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
use Gibbon\Domain\Timetable\FacilityChangeGateway;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/spaceChange_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceChange_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0";
        header("Location: {$URL}");
        exit;
    }
    // Proceed!
    $gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
    $gibbonTTDayRowClassID = substr($_POST['gibbonTTDayRowClassID'] ?? '', 0, 12);
    $date = substr($_POST['gibbonTTDayRowClassID'] ?? '', 13);
    $gibbonSpaceID = $_POST['gibbonSpaceID'] ?? '';

    $spaceChangeGateway = $container->get(FacilityChangeGateway::class);

    // Check for access
    if ($highestAction == 'Manage Facility Changes_allClasses') {
        $dataSelect = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
        $sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
    } else if ($highestAction == 'Manage Facility Changes_myDepartment') {
        $dataSelect = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID2' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'gibbonCourseClassID2' => $gibbonCourseClassID);
        $sqlSelect = '(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)
        UNION
        (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND (gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID2 AND role=\'Coordinator\') AND gibbonCourseClassID=:gibbonCourseClassID2)';
    } else {
        $dataSelect = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
        $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
    }
    $resultSelect = $pdo->select($sqlSelect, $dataSelect);

    if ($resultSelect->rowCount() != 1) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Validate Inputs
    if (empty($gibbonTTDayRowClassID) || empty($date)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 

    // Check unique inputs for uniquness
    $data = ['gibbonTTDayRowClassID' => $gibbonTTDayRowClassID, 'date' => $date, 'gibbonSpaceID' => $gibbonSpaceID, 'gibbonPersonID' => $session->get('gibbonPersonID')];

    if (!$spaceChangeGateway->unique($data, ['gibbonTTDayRowClassID', 'date'])) {
        $updated = $spaceChangeGateway->updateWhere(['gibbonTTDayRowClassID' => $gibbonTTDayRowClassID, 'date' => $date], $data);
    } else {
        $updated = $spaceChangeGateway->insert($data);
    }

    if (empty($updated)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Redirect back to View Timetable by Facility if we started there
    if (!empty($_POST['source'])) {
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Timetable/tt_space_view.php&gibbonSpaceID='.$_POST['source'].'&ttDate='.Format::date($date);
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}

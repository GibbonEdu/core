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

use Gibbon\Data\Validator;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['homeworkDetails' => 'HTML']);

//Module includes
include './moduleFunctions.php';

$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
$params = '';
if (isset($_GET['date'])) {
    $params = $params.'&date='.$_GET['date'];
}
if (isset($_GET['viewBy'])) {
    $params = $params.'&viewBy='.$_GET['viewBy'];
}
if (isset($_GET['gibbonCourseClassID'])) {
    $params = $params.'&gibbonCourseClassID='.$_GET['gibbonCourseClassID'];
}
$URL = $session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID$params";

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if planner specified
    if ($gibbonPlannerEntryID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = "SELECT * FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND role='Student'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Get variables
            $homework = $_POST['homework'] ?? '';
            if ($homework == 'Y') {
                $homeworkDetails = $_POST['homeworkDetails'] ?? '';
                if ($_POST['homeworkDueDateTime'] != '') {
                    $homeworkDueDateTime = $_POST['homeworkDueDateTime'].':59';
                } else {
                    $homeworkDueDateTime = '21:00:00';
                }
                if ($_POST['homeworkDueDate'] != '') {
                    $homeworkDueDate = Format::dateConvert($_POST['homeworkDueDate']).' '.$homeworkDueDateTime;
                }
            } else {
                $homework = 'N';
                $homeworkDueDate = null;
                $homeworkDetails = '';
            }

            if ($homework == 'N') { //IF HOMEWORK NO, DELETE ANY RECORDS
                try {
                    $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = 'DELETE FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            } else { //IF HOMEWORK YES, DEAL WITH RECORDS
                //Check for record
                try {
                    $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = 'SELECT * FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() > 1) { //Error!
                            $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }
                if ($result->rowCount() == 1) { //Exists, so update
                    try {
                        $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'homeworkDueDateTime' => $homeworkDueDate, 'homeworkDetails' => $homeworkDetails);
                        $sql = 'UPDATE gibbonPlannerEntryStudentHomework SET homeworkDueDateTime=:homeworkDueDateTime, homeworkDetails=:homeworkDetails WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                } else { //Does not exist, so create
                    //Write to database
                    try {
                        $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'homeworkDueDateTime' => $homeworkDueDate, 'homeworkDetails' => $homeworkDetails);
                        $sql = 'INSERT INTO gibbonPlannerEntryStudentHomework SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID, homeworkDueDateTime=:homeworkDueDateTime, homeworkDetails=:homeworkDetails';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}

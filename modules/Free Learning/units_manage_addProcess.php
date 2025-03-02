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

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_manage_add.php&gibbonDepartmentID='.$_REQUEST['gibbonDepartmentID'].'&difficulty='.$_GET['difficulty'].'&name='.$_GET['name'].'&gibbonYearGroupIDMinimum='.$_GET['gibbonYearGroupIDMinimum'];

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_add.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        //Fail 0
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        if (!(isset($_POST))) {
            //Fail 5
            $URL .= '&return=error5';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Validate Inputs
            $name = $_POST['name'] ?? '';
            $difficulty = $_POST['difficulty'] ?? '';
            $blurb = $_POST['blurb'] ?? '';
            $studentReflectionText = $_POST['studentReflectionText'] ?? '';
            $gibbonDepartmentIDList = (!empty($_POST['gibbonDepartmentIDList']) && is_array($_POST['gibbonDepartmentIDList'])) ? implode(",", $_POST['gibbonDepartmentIDList']) : null;
            $course = $_POST['course'] ?? null;
            $license = $_POST['license'] ?? '';
            $assessable = $_POST['assessable'] ?? null;
            $availableStudents = $_POST['availableStudents'] ?? '';
            $availableStaff = $_POST['availableStaff'] ?? '';
            $availableParents = $_POST['availableParents'] ?? '';
            $availableOther = $_POST['availableOther'] ?? '';
            $sharedPublic = $_POST['sharedPublic'] ?? null;
            $active = $_POST['active'] ?? 'N';
            $editLock = $_POST['editLock'] ?? 'N';
            $gibbonYearGroupIDMinimum = $_POST['gibbonYearGroupIDMinimum'] ?? null;
            $grouping = (!empty($_POST['grouping']) && is_array($_POST['grouping'])) ? implode(",", $_POST['grouping']) : '';
            $freeLearningUnitIDPrerequisiteList = (!empty($_POST['freeLearningUnitIDPrerequisiteList']) && is_array($_POST['freeLearningUnitIDPrerequisiteList'])) ? $_POST['freeLearningUnitIDPrerequisiteList'] : [];
            $outline = $_POST['outline'];
            $schoolMentorCompletors = $_POST['schoolMentorCompletors'] ?? null;
            $schoolMentorCustom = (!empty($_POST['schoolMentorCustom']) && is_array($_POST['schoolMentorCustom'])) ? implode(",", $_POST['schoolMentorCustom']) : null;
            $schoolMentorCustomRole = $_POST['schoolMentorCustomRole'] ?? null;

            if ($name == '' or $difficulty == '' or $active == '' or $editLock == '' or $availableStudents == '' or $availableStaff == '' or $availableParents == '' or $availableOther == '') {
                //Fail 3
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                $partialFail = false;

                //Move attached file, if there is one
                $attachment = null;
                if (!empty($_FILES['file']['tmp_name'])) {
                    $fileUploader = new Gibbon\FileUploader($pdo, $session);
                    $fileUploader->getFileExtensions('Graphics/Design');

                    $file = $_FILES['file'] ?? null;

                    // Upload the file, return the /uploads relative path
                    $attachment = $fileUploader->uploadFromPost($file, $name);

                    if (empty($attachment)) {
                        $partialFail = true;
                    }
                }
                if ($attachment != null) {
                    $attachment = $session->get('absoluteURL').'/'.$attachment;
                }

                // Write to database
                $data = array('name' => $name, 'course' => $course, 'logo' => $attachment, 'difficulty' => $difficulty, 'blurb' => $blurb, 'studentReflectionText' => $studentReflectionText, 'license' => $license, 'assessable' => $assessable, 'availableStudents'=>$availableStudents, 'availableStaff'=>$availableStaff, 'availableParents'=>$availableParents, 'availableOther' => $availableOther, 'sharedPublic' => $sharedPublic, 'active' => $active, 'editLock' => $editLock, 'gibbonYearGroupIDMinimum' => $gibbonYearGroupIDMinimum, 'grouping' => $grouping, 'gibbonDepartmentIDList' => $gibbonDepartmentIDList, 'schoolMentorCompletors' => $schoolMentorCompletors, 'schoolMentorCustom' => $schoolMentorCustom, 'schoolMentorCustomRole' => $schoolMentorCustomRole, 'outline' => $outline, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'timestamp' => date('Y-m-d H:i:s'));
                $sql = 'INSERT INTO freeLearningUnit SET name=:name, course=:course, logo=:logo, difficulty=:difficulty, blurb=:blurb, studentReflectionText=:studentReflectionText, license=:license, assessable=:assessable, availableStudents=:availableStudents, availableStaff=:availableStaff, availableParents=:availableParents, availableOther=:availableOther, sharedPublic=:sharedPublic, active=:active, editLock=:editLock, gibbonYearGroupIDMinimum=:gibbonYearGroupIDMinimum, `grouping`=:grouping, gibbonDepartmentIDList=:gibbonDepartmentIDList, schoolMentorCompletors=:schoolMentorCompletors, schoolMentorCustom=:schoolMentorCustom, schoolMentorCustomRole=:schoolMentorCustomRole, outline=:outline, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestamp=:timestamp';
                $inserted = $pdo->insert($sql, $data);

                if (empty($inserted)) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $AI = str_pad($inserted, 10, '0', STR_PAD_LEFT);

                // Write author to database
                $data = array('freeLearningUnitID' => $AI, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'surname' => $session->get('surname'), 'preferredName' => $session->get('preferredName'), 'website' => $session->get('website') ?? '');
                $sql = 'INSERT INTO freeLearningUnitAuthor SET freeLearningUnitID=:freeLearningUnitID, gibbonPersonID=:gibbonPersonID, surname=:surname, preferredName=:preferredName, website=:website';

                $inserted = $pdo->insert($sql, $data);
                $partialFail &= !$inserted;

                //ADD BLOCKS
                $blockCount = ($_POST['smartCount'] - 1);
                $sequenceNumber = 0;
                if ($blockCount > 0) {
                    $order = $_POST['order'] ?? array();
                    foreach ($order as $i) {
                        $title = '';
                        if ($_POST["title$i"] != "Block $i") {
                            $title = $_POST["title$i"];
                        }
                        $type2 = '';
                        if ($_POST["type$i"] != 'type (e.g. discussion, outcome)') {
                            $type2 = $_POST["type$i"];
                        }
                        $length = isset($_POST["length$i"]) ? intval(trim($_POST["length$i"])) : null;
                        $contents = !empty($_POST["contents$i"]) ? trim($_POST["contents$i"]) : '';

                        // Remove the <![CDATA that TinyMCE adds to script tags
                        $contents = str_replace(["// <![CDATA[", "// ]]>"], ['', ''], $contents);

                        $teachersNotes = $_POST["teachersNotes$i"] ?? '';

                        if ($title != '' or $contents != '') {
                            $dataBlock = array('freeLearningUnitID' => $AI, 'title' => $title, 'type' => $type2, 'length' => $length, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber);
                            $sqlBlock = 'INSERT INTO freeLearningUnitBlock SET freeLearningUnitID=:freeLearningUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber';

                            $inserted = $pdo->insert($sqlBlock, $dataBlock);
                            $partialFail &= !$inserted;
                            ++$sequenceNumber;
                        }
                    }
                }

                // ADD PREREQUISITES
                foreach ($freeLearningUnitIDPrerequisiteList as $prerequisite) {
                    $dataPrerequisite = array('freeLearningUnitID' => $AI, 'freeLearningUnitIDPrerequisite' => $prerequisite);
                    $sqlPrerequisite = 'INSERT INTO freeLearningUnitPrerequisite SET freeLearningUnitID=:freeLearningUnitID, freeLearningUnitIDPrerequisite=:freeLearningUnitIDPrerequisite';
                    $inserted = $pdo->insert($sqlPrerequisite, $dataPrerequisite);
                    $partialFail &= !$inserted;
                }

                if ($partialFail == true) {
                    //Fail 6
                    $URL .= '&return=error6';
                    header("Location: {$URL}");
                } else {
                    //Success 0
                    $URL = $URL.'&return=success0&editID='.$AI;
                    header("Location: {$URL}");
                }
            }
        }
    }
}

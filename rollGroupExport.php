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

include './gibbon.php';

$session = $container->get('session');
$gibbonRollGroupID = $_GET['gibbonRollGroupID'];
$URL = $session->get('absoluteURL').'/index.php';
$connection = $container->get('db');

try {
    $data = array('gibbonPersonIDTutor' => $session->get('gibbonPersonID'), 'gibbonPersonIDTutor2' => $session->get('gibbonPersonID'), 'gibbonPersonIDTutor3' => $session->get('gibbonPersonID'));
    $sql = 'SELECT * FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3)';
    $result = $connection2->prepare($sql);
    $result->execute($data);
} catch (PDOException $e) {
    $URL .= '?return=error2';
    header("Location: {$URL}");
}

if ($result) {
    if ($gibbonRollGroupID == '') {
        $URL .= '?return=error1';
        header("Location: {$URL}");
    } else {
        $rollGroup = $connection->selectOne('SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID = :gibbonRollGroupID', ['gibbonRollGroupID' => $gibbonRollGroupID]);
        if ($result->rowCount() < 1) {
            $URL .= '?return=error3';
            header("Location: {$URL}");
        } else {
            //Proceed!
            $sql = 'SELECT surname, preferredName, email FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID='.$gibbonRollGroupID." AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
            try {
                $studentQuery = $connection2->query($sql);
            }
            catch(PDOException $e) {
                $URL .= '?return=error2';
                header("Location: {$URL}");
            }
            $exp = new Gibbon\Spreadsheet();
            $exp->getActiveSheet()->setTitle('Roll Group '.$rollGroup['name']);
            $exp->exportWithQueryResult($studentQuery, 'Roll Group '.str_replace('.', '_', $rollGroup['name']).' List.xlsx');
        }
    }
}

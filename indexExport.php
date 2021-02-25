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

$gibbonRollGroupID = $_GET['gibbonRollGroupID'];
$URL = $gibbon->session->get('absoluteURL').'/index.php';

try {
    $data = array('gibbonPersonIDTutor' => $gibbon->session->get('gibbonPersonID'), 'gibbonPersonIDTutor2' => $gibbon->session->get('gibbonPersonID'), 'gibbonPersonIDTutor3' => $gibbon->session->get('gibbonPersonID'));
    $sql = 'SELECT * FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3)';
    $result = $connection2->prepare($sql);
    $result->execute($data);
} catch (PDOException $e) {
    $URL .= '?return=error0';
    header("Location: {$URL}");
}

if ($result) {
    if ($gibbonRollGroupID == '') {
        $URL .= '?return=error1';
        header("Location: {$URL}");
    } else {
        if ($result->rowCount() < 1) {
            $URL .= '?return=error3';
            header("Location: {$URL}");
        } else {
            //Proceed!
            $data = ['gibbonRollGroupID' => $gibbonRollGroupID, 'today' => date('Y-m-d')];
            $sql = "SELECT surname, preferredName, email 
                    FROM gibbonStudentEnrolment 
                    JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID 
                    WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' 
                    AND (dateStart IS NULL OR dateStart<=:today) 
                    AND (dateEnd IS NULL  OR dateEnd>=:today) 
                    ORDER BY surname, preferredName";

            $result = $pdo->select($sql, $data);

            $exp = new Gibbon\Excel();
            $exp->exportWithQuery($result, 'classList.xls');
        }
    }
}

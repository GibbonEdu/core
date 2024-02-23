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

use Gibbon\Http\Url;

include './gibbon.php';

$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$URL = Url::fromRoute();

try {
    $data = array('gibbonPersonIDTutor' => $session->get('gibbonPersonID'), 'gibbonPersonIDTutor2' => $session->get('gibbonPersonID'), 'gibbonPersonIDTutor3' => $session->get('gibbonPersonID'));
    $sql = 'SELECT * FROM gibbonFormGroup WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3)';
    $result = $connection2->prepare($sql);
    $result->execute($data);
} catch (PDOException $e) {
    header("Location: {$URL->withReturn('error0')}");
}

if ($result) {
    if ($gibbonFormGroupID == '') {
        header("Location: {$URL->withReturn('error1')}");
    } else {
        if ($result->rowCount() < 1) {
            header("Location: {$URL->withReturn('error3')}");
        } else {
            //Proceed!
            $data = ['gibbonFormGroupID' => $gibbonFormGroupID, 'today' => date('Y-m-d')];
            $sql = "SELECT surname, preferredName, email
                    FROM gibbonStudentEnrolment
                    JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID
                    WHERE gibbonFormGroupID=:gibbonFormGroupID AND status='Full'
                    AND (dateStart IS NULL OR dateStart<=:today)
                    AND (dateEnd IS NULL  OR dateEnd>=:today)
                    ORDER BY surname, preferredName";

            $result = $pdo->select($sql, $data);

            $exp = new Gibbon\Excel();
            $exp->exportWithQuery($result, 'classList.xls');
        }
    }
}

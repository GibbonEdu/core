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
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/billingSchedule_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/billingSchedule_manage_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $name = $_POST['name'] ?? '';
        $active = $_POST['active'] ?? '';
        $description = $_POST['description'] ?? '';
        $invoiceIssueDate = $_POST['invoiceIssueDate'] ?? '';
        $invoiceDueDate = $_POST['invoiceDueDate'] ?? '';

        if ($name == '' or $active == '' or $invoiceIssueDate == '' or $invoiceDueDate == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {

            //Write to database
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'name' => $name, 'active' => $active, 'description' => $description, 'invoiceIssueDate' => Format::dateConvert($invoiceIssueDate), 'invoiceDueDate' => Format::dateConvert($invoiceDueDate), 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'));
                $sql = "INSERT INTO gibbonFinanceBillingSchedule SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, active=:active, description=:description, invoiceIssueDate=:invoiceIssueDate, invoiceDueDate=:invoiceDueDate, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='".date('Y-m-d H:i:s')."'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 6, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}

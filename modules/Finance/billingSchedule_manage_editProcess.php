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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonFinanceBillingScheduleID = $_POST['gibbonFinanceBillingScheduleID'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonFinanceBillingScheduleID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/billingSchedule_manage_edit.php&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/billingSchedule_manage_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonFinanceBillingScheduleID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID);
                $sql = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID';
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
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'name' => $name, 'active' => $active, 'description' => $description, 'invoiceIssueDate' => Format::dateConvert($invoiceIssueDate), 'invoiceDueDate' => Format::dateConvert($invoiceDueDate), 'gibbonPersonIDUpdate' => $session->get('gibbonPersonID'), 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID);
                        $sql = "UPDATE gibbonFinanceBillingSchedule SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, active=:active, description=:description, invoiceIssueDate=:invoiceIssueDate, invoiceDueDate=:invoiceDueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID";
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

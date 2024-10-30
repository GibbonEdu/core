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

include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$action = $_POST['action'] ?? '';
$gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';

if ($gibbonFinanceBudgetCycleID == '' or $action == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $gibbonFinanceExpenseIDs = $_POST['gibbonFinanceExpenseIDs'] ?? [];
        if (count($gibbonFinanceExpenseIDs) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;
            //Export
            if ($action == 'export') {
                $session->set('financeExpenseExportIDs', $gibbonFinanceExpenseIDs);

				include './expenses_manage_processBulkExportContents.php';

                // THIS CODE HAS BEEN COMMENTED OUT, AS THE EXPORT RETURNS WITHOUT IT...NOT SURE WHY!
                    //$URL.="&bulkReturn=success0" ;
                //header("Location: {$URL}");
            } else {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            }
        }
    }
}

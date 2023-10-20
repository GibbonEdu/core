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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStringID = $_GET['gibbonStringID'] ?? '';
$search = $_GET['search'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/stringReplacement_manage_edit.php&gibbonStringID=$gibbonStringID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/System Admin/stringReplacement_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if ($gibbonStringID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonStringID' => $gibbonStringID);
            $sql = 'SELECT * FROM gibbonString WHERE gibbonStringID=:gibbonStringID';
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
            //Validate Inputs
            $original = $_POST['original'] ?? '';
            $replacement = $_POST['replacement'] ?? '';
            $mode = $_POST['mode'] ?? '';
            $caseSensitive = $_POST['caseSensitive'] ?? '';
            $priority = $_POST['priority'] ?? '';

            if ($original == '' or $replacement == '' or $mode == '' or $caseSensitive == '' or $priority == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('original' => $original, 'replacement' => $replacement, 'mode' => $mode, 'caseSensitive' => $caseSensitive, 'priority' => $priority, 'gibbonStringID' => $gibbonStringID);
                    $sql = 'UPDATE gibbonString SET original=:original, replacement=:replacement, mode=:mode, caseSensitive=:caseSensitive, priority=:priority WHERE gibbonStringID=:gibbonStringID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Update string list in session & clear cache to force reload
                $gibbon->locale->setStringReplacementList($session, $pdo, true);
                $session->set('pageLoads', null);

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}

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

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$valueMin = $_POST['valueMin'] ?? '';
$valueMax = $_POST['valueMax'] ?? '';
$descriptorPrefix = $_POST['descriptorPrefix'] ?? '';

$gibbonScaleID = $_POST['gibbonScaleID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/gradeScales_manage_edit_grade_addMulti.php&gibbonScaleID=$gibbonScaleID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit_grade_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    //Proceed!
    //Validate Inputs
    if ($gibbonScaleID == '' or $valueMin == '' or $valueMax == '' or (ctype_alpha($valueMin) && strlen($valueMin) != 1) or (ctype_alpha($valueMax) && strlen($valueMax) != 1)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit();
    } else {
        // Check if valueMin and valueMax are not both numbers or both letters
        if (!(is_numeric($valueMin) && is_numeric($valueMax)) && !(ctype_alpha($valueMin) && ctype_alpha($valueMax)) || $valueMin == $valueMax) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit();
        } else if (ctype_alpha($valueMin) && (ord($valueMin) < ord('A') || ord($valueMin) > ord('z') || ord($valueMax) < ord('A') || ord($valueMax) > ord('z'))) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit();
        } else if (ctype_alpha($valueMin)) {
            $valueMin = strtoupper($valueMin);
            $valueMax = strtoupper($valueMax);
        }

        // Check if the maximum value is less than the minimum value, swap them
        if ((is_numeric($valueMin) && $valueMin > $valueMax) || (ctype_alpha($valueMin) && ord($valueMin) > ord($valueMax))) {
            $temp = $valueMin;
            $valueMin = $valueMax;
            $valueMax = $temp;
        }

        // If the range is numeric
        if (is_numeric($valueMin)) {
            $range = range($valueMin, $valueMax);
        } elseif (ctype_alpha($valueMin)) { // If the range is alphabetic
            // Use ord to get ASCII values and generate the alphabetic range
            $range = range($valueMin, $valueMax);
        } else {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit();
        }

        // Check unique inputs for uniqueness
        try {
            // Process a maximum of 1000 values per query
            $batchSize = 1000;
            $chunks = array_chunk($range, $batchSize);

            // Loop through each chunk of values
            foreach ($chunks as $batch) {
                // Create named placeholders for each value and sequenceNumber
                $valuePlaceholders = [];
                $sequenceNumberPlaceholders = [];
                $params = [];
                $isNumber = is_numeric($value);

                foreach ($batch as $index => $value) {
                    $valuePlaceholders[] = ":value$index";
                    $sequenceNumberPlaceholders[] = ":sequenceNumber$index";
                    $params["value$index"] = $value;
                    $params["sequenceNumber$index"] = $isNumber ? $value : (ord($value) - ord('A') + 1);
                }

                // Construct the SQL query with named parameters
                $sql = 'SELECT * FROM gibbonScaleGrade WHERE (value IN (' . implode(',', $valuePlaceholders) . ') OR sequenceNumber IN (' . implode(',', $sequenceNumberPlaceholders) . ')) AND gibbonScaleID=:gibbonScaleID';

                // Prepare the SQL query
                $result = $connection2->prepare($sql);

                // Merge the gibbonScaleID with the existing parameters
                $params['gibbonScaleID'] = $gibbonScaleID;

                // Execute the query with the parameters
                $result->execute($params);

                // Fetch the result data
                $data = $result->fetchAll(PDO::FETCH_ASSOC);

                // If any duplicates are found (i.e., the query returns data), throw an error
                if (count($data) > 0) {
                    // Redirect to error page with a custom error code for duplicate values
                    $URL .= '&return=error3&count=' . count($data); // You can define different error codes based on your needs
                    header("Location: {$URL}");
                    exit();
                }
            }

            // If no duplicates are found, proceed with further operations
            // Insert data or perform any other necessary logic here

        } catch (PDOException $e) {
            // Catch any database connection or query errors
            // Append the error message to the URL for debugging or reporting
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }
        try {
            // Start the transaction
            $connection2->beginTransaction();
        
            // Batch size: each batch will insert 1,000 records (can be adjusted as needed)
            $batchSize = 1000;
        
            // Split the range into smaller chunks
            $chunks = array_chunk($range, $batchSize);
        
            foreach ($chunks as $batch) {
                // Create arrays for placeholders and parameters
                $placeholders = [];
                $params = [];
                $isNumber = is_numeric($value);
        
                foreach ($batch as $value) {
                    // Generate descriptor, handle the case where descriptorPrefix might be empty
                    $descriptor = ($descriptorPrefix !== '') ? $descriptorPrefix . $value : $value;
        
                    // Prepare the SQL placeholders and parameters
                    $placeholders[] = '(:gibbonScaleID, :value' . $value . ', :descriptor' . $value . ', :sequenceNumber' . $value . ', :isDefault' . $value . ')';
        
                    // Add parameters
                    $index = $isNumber ? $value : (ord($value) - ord('A') + 1);
                    $params['gibbonScaleID'] = $gibbonScaleID;
                    $params['value' . $value] = $value;
                    $params['descriptor' . $value] = $descriptor;
                    $params['sequenceNumber' . $value] = $index;
                    $params['isDefault' . $value] = 'N';
                }
        
                // Create SQL statement with placeholders
                $sql = 'INSERT INTO gibbonScaleGrade (gibbonScaleID, value, descriptor, sequenceNumber, isDefault) VALUES ' . implode(',', $placeholders);
        
                // Prepare the SQL query
                $result = $connection2->prepare($sql);
        
                // Execute the batch insert with the parameters
                $result->execute($params);
            }
        
            // Commit the transaction if all batches are inserted successfully
            $connection2->commit();
        
        } catch (PDOException $e) {
            // If an error occurs, roll back the transaction
            $connection2->rollBack();
            $URL .= '&return=error2' . $e->getMessage();  // Custom error code for DB error
            header("Location: {$URL}");
            exit();
        }
        

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 7, '0', STR_PAD_LEFT);

        $URL .= "&return=success0&editID=$AI";
        header("Location: {$URL}");
    }
}

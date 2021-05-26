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

include '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/thirdPartySettings.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $enablePayments = $_POST['enablePayments'] ?? '';
    $paypalAPIUsername = $_POST['paypalAPIUsername'] ?? '';
    $paypalAPIPassword = $_POST['paypalAPIPassword'] ?? '';
    $paypalAPISignature = $_POST['paypalAPISignature'] ?? '';
    $googleOAuth = $_POST['googleOAuth'] ?? '';
    $googleClientName = $_POST['googleClientName'] ?? '';
    $googleClientID = $_POST['googleClientID'] ?? '';
    $googleClientSecret = $_POST['googleClientSecret'] ?? '';
    $googleRedirectUri = $_POST['googleRedirectUri'] ?? '';
    $googleDeveloperKey = $_POST['googleDeveloperKey'] ?? '';
    $calendarFeed = $_POST['calendarFeed'] ?? '';
    $smsGateway = $_POST['smsGateway'] ?? '';
    $smsSenderID = $_POST['smsSenderID'] ?? '';
    $smsUsername = $_POST['smsUsername'] ?? '';
    $smsPassword = $_POST['smsPassword'] ?? '';
    $smsURL = $_POST['smsURL'] ?? '';
    $smsURLCredit = $_POST['smsURLCredit'] ?? '';

    // SMTP Mail Settings
    $enableMailerSMTP = $_POST['enableMailerSMTP'] ?? '';
    $mailerSMTPHost = $_POST['mailerSMTPHost'] ?? '';
    $mailerSMTPPort = $_POST['mailerSMTPPort'] ?? '';
    $mailerSMTPSecure = $_POST['mailerSMTPSecure'] ?? '';
    $mailerSMTPUsername = $_POST['mailerSMTPUsername'] ?? '';
    $mailerSMTPPassword = $_POST['mailerSMTPPassword'] ?? '';

    //Validate Inputs
    if ($enablePayments == '' or $googleOAuth == '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Write to database
        $fail = false;

        try {
            $data = array('value' => $googleOAuth);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleOAuth'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if ($googleOAuth == 'Y') {
            try {
                $data = array('value' => $googleClientName);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleClientName'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $googleClientID);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleClientID'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $googleClientSecret);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleClientSecret'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $googleRedirectUri);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleRedirectUri'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $googleDeveloperKey);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleDeveloperKey'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('calendarFeed' => $calendarFeed);
                $sql = "UPDATE gibbonSetting SET value=:calendarFeed WHERE scope='System' AND name='calendarFeed'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }
        }

        try {
            $data = array('enablePayments' => $enablePayments);
            $sql = "UPDATE gibbonSetting SET value=:enablePayments WHERE scope='System' AND name='enablePayments'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if ($enablePayments == 'Y') {
            try {
                $data = array('paypalAPIUsername' => $paypalAPIUsername);
                $sql = "UPDATE gibbonSetting SET value=:paypalAPIUsername WHERE scope='System' AND name='paypalAPIUsername'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('paypalAPIPassword' => $paypalAPIPassword);
                $sql = "UPDATE gibbonSetting SET value=:paypalAPIPassword WHERE scope='System' AND name='paypalAPIPassword'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('paypalAPISignature' => $paypalAPISignature);
                $sql = "UPDATE gibbonSetting SET value=:paypalAPISignature WHERE scope='System' AND name='paypalAPISignature'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }
        }

        try {
            $data = array('value' => $smsGateway);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsGateway'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if (!empty($smsGateway)) {
            try {
                $data = array('value' => $smsSenderID);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsSenderID'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $smsUsername);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsUsername'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $smsPassword);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsPassword'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $smsURL);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsURL'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $smsURLCredit);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsURLCredit'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }
        }

        // SMTP Mailer
        try {
            $data = array('value' => $enableMailerSMTP);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='enableMailerSMTP'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if ($enableMailerSMTP == 'Y') {
            try {
                $data = array('value' => $mailerSMTPHost);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='mailerSMTPHost'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $mailerSMTPPort);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='mailerSMTPPort'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $mailerSMTPSecure);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='mailerSMTPSecure'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $mailerSMTPUsername);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='mailerSMTPUsername'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $mailerSMTPPassword);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='mailerSMTPPassword'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }
        }

        if ($fail == true) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            getSystemSettings($guid, $connection2);
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}

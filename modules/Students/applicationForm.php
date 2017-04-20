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

@session_start();

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$proceed = false;
$public = false;

if (isset($_SESSION[$guid]['username']) == false) {
    $public = true;

    //Get public access
    $publicApplications = getSettingByScope($connection2, 'Application Form', 'publicApplications');
    if ($publicApplications == 'Y') {
        $proceed = true;
    }
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm.php') != false) {
        $proceed = true;
    }
}

//Set gibbonPersonID of the person completing the application
$gibbonPersonID = null;
if (isset($_SESSION[$guid]['gibbonPersonID'])) {
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
}

if ($proceed == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    if (isset($_SESSION[$guid]['username'])) {
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Application Form').'</div>';
    } else {
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Application Form').'</div>';
    }
    echo '</div>';

    //Get intro
    $intro = getSettingByScope($connection2, 'Application Form', 'introduction');
    if ($intro != '') {
        echo '<p>';
        echo $intro;
        echo '</p>';
    }

    if (isset($_SESSION[$guid]['username']) == false) {
        echo "<div class='warning' style='font-weight: bold'>".sprintf(__($guid, 'If you already have an account for %1$s %2$s, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under People > Students in the main menu.'), $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['systemName']).' '.sprintf(__($guid, 'If you do not have an account for %1$s %2$s, please use the form below.'), $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['systemName']).'</div>';
    }

    $returnExtra = '';
    $gibbonApplicationFormID = null;

    if (!empty($_GET['id'])) {

        // Use the returned hash to get the actual ID from the database
        $data = array( 'gibbonApplicationFormHash' => $_GET['id'] );
        $sql = "SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormHash=:gibbonApplicationFormHash";
        $resultID = $pdo->executeQuery($data, $sql);

        if ($resultID && $resultID->rowCount() == 1) {
            $row = $resultID->fetch();
            $gibbonApplicationFormID = str_pad( intval($row['gibbonApplicationFormID']), 7, '0', STR_PAD_LEFT);
        } else {
            echo "<div class='error'>";
            echo __($guid, 'The application link does not match an existing record in our system. The record may have been removed or the link is no longer valid.');
            echo '</div>';
        }

        $returnExtra = '<br/><br/>'.__($guid, 'If you need to contact the school in reference to this application, please quote the following number:').' <b><u>'.$gibbonApplicationFormID.'</b></u>.';
    }
    if ($_SESSION[$guid]['organisationAdmissionsName'] != '' and $_SESSION[$guid]['organisationAdmissionsEmail'] != '') {
        $returnExtra .= '<br/><br/>'.sprintf(__($guid, 'Please contact %1$s if you have any questions, comments or complaints.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdmissionsEmail']."'>".$_SESSION[$guid]['organisationAdmissionsName'].'</a>');
    }
    $returns = array();
    $returns['success0'] = __($guid, 'Your application was successfully submitted. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success1'] = __($guid, 'Your application was successfully submitted and payment has been made to your credit card. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success2'] = __($guid, 'Your application was successfully submitted, but payment could not be made to your credit card. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success3'] = __($guid, 'Your application was successfully submitted, payment has been made to your credit card, but there has been an error recording your payment. Please print this screen and contact the school ASAP. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success4'] = __($guid, "Your application was successfully submitted, but payment could not be made as the payment gateway does not support the system's currency. Our admissions team will review your application and be in touch in due course.").$returnExtra;
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //JS success return addition
    if (isset($_GET['return'])) {
        $return = $_GET['return'];
    } else {
        $return = '';
    }
    if ($return == 'success0' or $return == 'success1' or $return == 'success2'  or $return == 'success4') {
        echo "<script type='text/javascript'>";
        echo '$(document).ready(function(){';
        echo "alert('Your application was successfully submitted. Please read the information in the green box above the application form for additional information.') ;";
        echo '});';
        echo '</script>';
    }

    $currency = getSettingByScope($connection2, 'System', 'currency');
    $applicationFee = getSettingByScope($connection2, 'Application Form', 'applicationFee');
    $enablePayments = getSettingByScope($connection2, 'System', 'enablePayments');
    $paypalAPIUsername = getSettingByScope($connection2, 'System', 'paypalAPIUsername');
    $paypalAPIPassword = getSettingByScope($connection2, 'System', 'paypalAPIPassword');
    $paypalAPISignature = getSettingByScope($connection2, 'System', 'paypalAPISignature');

    if ($applicationFee > 0 and is_numeric($applicationFee)) {
        echo "<div class='warning'>";
        echo __($guid, 'Please note that there is an application fee of:').' <b><u>'.$currency.$applicationFee.'</u></b>.';
        if ($enablePayments == 'Y' and $paypalAPIUsername != '' and $paypalAPIPassword != '' and $paypalAPISignature != '') {
            echo ' '.__($guid, 'Payment must be made by credit card, using our secure PayPal payment gateway. When you press Submit at the end of this form, you will be directed to PayPal in order to make payment. During this process we do not see or store your credit card details.');
        }
        echo '</div>';
    }

    $siblingApplicationMode = !empty($gibbonApplicationFormID); // && empty($gibbonPersonID)

    ?>

    <form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/applicationFormProcess.php' ?>" enctype="multipart/form-data">
        <table class='smallIntBorder fullWidth' cellspacing='0'>

            <?php if ($siblingApplicationMode == true) : ?>
                <input type="hidden" name="linkedApplicationFormID" value="<?php echo $gibbonApplicationFormID; ?>">

                <tr class='break'>
                    <td colspan=2>
                        <h3><?php echo __($guid, 'Add Another Application') ?></h3>
                        <p>
                            <?php echo __($guid, 'You may continue submitting applications for siblings with the form below and they will be linked to your family data.').' '; ?>
                            <?php echo __($guid, 'Some information has been pre-filled for you, feel free to change this information as needed.') ?>
                        </p>
                        <div style="text-align:right;">
                            <small class="emphasis small"><a href="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm.php' ?>">
                                <?php echo __($guid, 'Clear Form'); ?>
                            </a></small>
                        </div>

                    </td>
                </tr>
                <tr>
                    <td style='width: 275px'>
                        <b><?php echo __($guid, 'Current Applications') ?></b><br/>
                    </td>
                    <td class="right">
                    <?php
                        $data = array( 'gibbonApplicationFormID' => $gibbonApplicationFormID );
                        $sql = 'SELECT DISTINCT gibbonApplicationFormID, preferredName, surname FROM gibbonApplicationForm
                                LEFT JOIN gibbonApplicationFormLink ON (gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2)
                                WHERE (gibbonApplicationFormID=:gibbonApplicationFormID AND gibbonApplicationFormLinkID IS NULL)
                                OR gibbonApplicationFormID1=:gibbonApplicationFormID
                                OR gibbonApplicationFormID2=:gibbonApplicationFormID
                                ORDER BY gibbonApplicationFormID';
                        $resultLinked = $pdo->executeQuery($data, $sql);

                        if ($resultLinked && $resultLinked->rowCount() > 0) {
                            echo '<ul style="width:302px;display:inline-block">';
                            while ($rowLinked = $resultLinked->fetch()) {
                                echo '<li>'. formatName('', $rowLinked['preferredName'], $rowLinked['surname'], 'Student', true);
                                echo ' ('.str_pad( intval($rowLinked['gibbonApplicationFormID']), 7, '0', STR_PAD_LEFT).')</li>';
                            }
                            echo '</ul>';
                        }
                    ?>
                    </td>
                </tr>
            <?php endif; ?>

            <tr class='break'>
                <td colspan=2>
                    <h3><?php echo __($guid, 'Student') ?></h3>
                </td>
            </tr>

            <tr>
                <td colspan=2>
                    <h4><?php echo __($guid, 'Student Personal Data') ?></h4>
                </td>
            </tr>
            <tr>
                <td style='width: 275px'>
                    <b><?php echo __($guid, 'Surname') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
                </td>
                <td class="right">
                    <input name="surname" id="surname" maxlength=30 type="text" class="standardWidth" value="">
                    <script type="text/javascript">
                        var surname=new LiveValidation('surname');
                        surname.add(Validate.Presence);
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'First Name') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'First name as shown in ID documents.') ?></span>
                </td>
                <td class="right">
                    <input name="firstName" id="firstName" maxlength=30 value="" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var firstName=new LiveValidation('firstName');
                        firstName.add(Validate.Presence);
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Preferred Name') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Most common name, alias, nickname, etc.') ?></span>
                </td>
                <td class="right">
                    <input name="preferredName" id="preferredName" maxlength=30 value="" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var preferredName=new LiveValidation('preferredName');
                        preferredName.add(Validate.Presence);
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Official Name') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Full name as shown in ID documents.') ?></span>
                </td>
                <td class="right">
                    <input title='Please enter full name as shown in ID documents' name="officialName" id="officialName" maxlength=150 value="" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var officialName=new LiveValidation('officialName');
                        officialName.add(Validate.Presence);
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Name In Characters') ?></b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Chinese or other character-based name.') ?></span>
                </td>
                <td class="right">
                    <input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="" type="text" class="standardWidth">
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Gender') ?> *</b><br/>
                </td>
                <td class="right">
                    <select name="gender" id="gender" class="standardWidth">
                        <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                        <option value="F"><?php echo __($guid, 'Female') ?></option>
                        <option value="M"><?php echo __($guid, 'Male') ?></option>
                    </select>
                    <script type="text/javascript">
                        var gender=new LiveValidation('gender');
                        gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Date of Birth') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
                </td>
                <td class="right">
                    <input name="dob" id="dob" maxlength=10 value="" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var dob=new LiveValidation('dob');
                        dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                            echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                        }
                            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                            echo 'dd/mm/yyyy';
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                        }
                        ?>." } );
                        dob.add(Validate.Presence);
                    </script>
                     <script type="text/javascript">
                        $(function() {
                            $( "#dob" ).datepicker();
                        });
                    </script>
                </td>
            </tr>


            <tr>
                <td colspan=2>
                    <h4><?php echo __($guid, 'Student Background') ?></h4>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Home Language - Primary') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'The primary language used in the student\'s home.') ?></span>
                </td>
                <td class="right">
                    <select name="languageHomePrimary" id="languageHomePrimary" class="standardWidth">
                        <?php
                        echo "<option value='Please select...'>Please select...</option>";
                        try {
                            $dataSelect = array();
                            $sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = (isset($row['languageHomePrimary']) && $row['languageHomePrimary'] == $rowSelect['name'])? 'selected' : '';
                            echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
                        }
                        ?>
                    </select>
                    <script type="text/javascript">
                        var languageHomePrimary=new LiveValidation('languageHomePrimary');
                        languageHomePrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Home Language - Secondary') ?></b><br/>
                </td>
                <td class="right">
                    <select name="languageHomeSecondary" id="languageHomeSecondary" class="standardWidth">
                        <?php
                        echo "<option value=''></option>";
                        try {
                            $dataSelect = array();
                            $sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = (isset($row['languageHomeSecondary']) && $row['languageHomeSecondary'] == $rowSelect['name'])? 'selected' : '';
                            echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'First Language') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Student\'s native/first/mother language.') ?></span>
                </td>
                <td class="right">
                    <select name="languageFirst" id="languageFirst" class="standardWidth">
                        <?php
                        echo "<option value='Please select...'>Please select...</option>";
                        try {
                            $dataSelect = array();
                            $sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = (isset($row['languageFirst']) && $row['languageFirst'] == $rowSelect['name'])? 'selected' : '';
                            echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
                        }
                        ?>
                    </select>
                    <script type="text/javascript">
                        var languageFirst=new LiveValidation('languageFirst');
                        languageFirst.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Second Language') ?></b><br/>
                </td>
                <td class="right">
                    <select name="languageSecond" id="languageSecond" class="standardWidth">
                        <?php
                        echo "<option value=''></option>";
                        try {
                            $dataSelect = array();
                            $sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = (isset($row['languageSecond']) && $row['languageSecond'] == $rowSelect['name'])? 'selected' : '';
                            echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Third Language') ?></b><br/>
                </td>
                <td class="right">
                    <select name="languageThird" id="languageThird" class="standardWidth">
                        <?php
                        echo "<option value=''></option>";
                        try {
                            $dataSelect = array();
                            $sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = (isset($row['languageThird']) && $row['languageThird'] == $rowSelect['name'])? 'selected' : '';
                            echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Country of Birth') ?> *</b><br/>
                </td>
                <td class="right">
                    <select name="countryOfBirth" id="countryOfBirth" class="standardWidth">
                        <?php
                        try {
                            $dataSelect = array();
                            $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
                        echo "<option value='Please select...'>"._('Please select...').'</option>';
                        while ($rowSelect = $resultSelect->fetch()) {
                            echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
                        }
                        ?>
                    </select>
                    <script type="text/javascript">
                        var countryOfBirth=new LiveValidation('countryOfBirth');
                        countryOfBirth.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Citizenship') ?> *</b><br/>
                </td>
                <td class="right">
                    <select name="citizenship1" id="citizenship1" class="standardWidth">
                        <?php
                        echo "<option value='Please select...'>"._('Please select...').'</option>';
                        $nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
                        if ($nationalityList == '') {
                            try {
                                $dataSelect = array();
                                $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
                            while ($rowSelect = $resultSelect->fetch()) {
                                echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
                            }
                        } else {
                            $nationalities = explode(',', $nationalityList);
                            foreach ($nationalities as $nationality) {
                                echo "<option value='".trim($nationality)."'>".trim($nationality).'</option>';
                            }
                        }
                        ?>
                    </select>
                    <script type="text/javascript">
                        var citizenship1=new LiveValidation('citizenship1');
                        citizenship1.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Citizenship Passport Number') ?></b><br/>
                </td>
                <td class="right">
                    <input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="" type="text" class="standardWidth">
                </td>
            </tr>
            <tr>
                <td>
                    <?php
                    if ($_SESSION[$guid]['country'] == '') {
                        echo '<b>'.__($guid, 'National ID Card Number').'</b><br/>';
                    } else {
                        echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Number').'</b><br/>';
                    }
                    ?>
                </td>
                <td class="right">
                    <input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="" type="text" class="standardWidth">
                </td>
            </tr>
            <tr>
                <td>
                    <?php
                    if ($_SESSION[$guid]['country'] == '') {
                        echo '<b>'.__($guid, 'Residency/Visa Type').'</b><br/>';
                    } else {
                        echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Residency/Visa Type').'</b><br/>';
                    }
                    ?>
                </td>
                <td class="right">
                    <?php
                    $residencyStatusList = getSettingByScope($connection2, 'User Admin', 'residencyStatus');
                    if ($residencyStatusList == '') {
                        echo "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='' type='text' style='width: 300px'>";
                    } else {
                        echo "<select name='residencyStatus' id='residencyStatus' style='width: 302px'>";
                        echo "<option value=''></option>";
                        $residencyStatuses = explode(',', $residencyStatusList);
                        foreach ($residencyStatuses as $residencyStatus) {
                            echo "<option value='".trim($residencyStatus)."'>".trim($residencyStatus).'</option>';
                        }
                        echo '</select>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php
                    if ($_SESSION[$guid]['country'] == '') {
                        echo '<b>'.__($guid, 'Visa Expiry Date').'</b><br/>';
                    } else {
                        echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Visa Expiry Date').'</b><br/>';
                    }
                    echo "<span style='font-size: 90%'><i>Format: ";
                    if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                        echo 'dd/mm/yyyy';
                    } else {
                        echo $_SESSION[$guid]['i18n']['dateFormat'];
                    }
                    echo '. '.__($guid, 'If relevant.').'</span>';?>
                </td>
                <td class="right">
                    <input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var visaExpiryDate=new LiveValidation('visaExpiryDate');
                        visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                            echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                        }
                            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                            echo 'dd/mm/yyyy';
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                        }
                        ?>." } );
                    </script>
                     <script type="text/javascript">
                        $(function() {
                            $( "#visaExpiryDate" ).datepicker();
                        });
                    </script>
                </td>
            </tr>


            <tr>
                <td colspan=2>
                    <h4><?php echo __($guid, 'Student Contact') ?></h4>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Email') ?></b><br/>
                </td>
                <td class="right">
                    <input name="email" id="email" maxlength=50 value="" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var email=new LiveValidation('email');
                        email.add(Validate.Email);
                    </script>
                </td>
            </tr>
            <?php
            for ($i = 1; $i < 3; ++$i) {
                ?>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Phone') ?> <?php echo $i ?></b><br/>
                        <span class="emphasis small"><?php echo __($guid, 'Type, country code, number.') ?></span>
                    </td>
                    <td class="right">
                        <input name="phone<?php echo $i ?>" id="phone<?php echo $i ?>" maxlength=20 value="" type="text" style="width: 160px">
                        <select name="phone<?php echo $i ?>CountryCode" id="phone<?php echo $i ?>CountryCode" style="width: 60px">
                            <?php
                            echo "<option value=''></option>";
                            try {
                                $dataSelect = array();
                                $sqlSelect = 'SELECT * FROM gibbonCountry ORDER BY printable_name';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
                            while ($rowSelect = $resultSelect->fetch()) {
                                echo "<option value='".$rowSelect['iddCountryCode']."'>".htmlPrep($rowSelect['iddCountryCode']).' - '.htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
                            }
                            ?>
                        </select>
                        <select style="width: 70px" name="phone<?php echo $i ?>Type">
                            <option value=""></option>
                            <option value="Mobile"><?php echo __($guid, 'Mobile') ?></option>
                            <option value="Home"><?php echo __($guid, 'Home') ?></option>
                            <option value="Work"><?php echo __($guid, 'Work') ?></option>
                            <option value="Fax"><?php echo __($guid, 'Fax') ?></option>
                            <option value="Pager"><?php echo __($guid, 'Pager') ?></option>
                            <option value="Other"><?php echo __($guid, 'Other') ?></option>
                        </select>
                    </td>
                </tr>
                <?php

            }
            ?>

            <tr>
                <td colspan=2>
                    <h4><?php echo __($guid, 'Special Educational Needs & Medical') ?></h4>
                    <?php
                    $applicationFormSENText = getSettingByScope($connection2, 'Students', 'applicationFormSENText');
                    if ($applicationFormSENText != '') {
                        echo '<p>';
                        echo $applicationFormSENText;
                        echo '</p>';
                    }
                    ?>
                </td>
            </tr>
            <script type="text/javascript">
                $(document).ready(function(){
                    $(".sen").change(function(){
                        if ($('#sen').val()=="Y" ) {
                            $("#senDetailsRow").slideDown("fast", $("#senDetailsRow").css("display","table-row"));
                        } else {
                            $("#senDetailsRow").css("display","none");
                        }
                     });
                });
            </script>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Special Educational Needs (SEN)') ?></b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Are there any known or suspected SEN concerns, or previous SEN assessments?') ?></span><br/>
                </td>
                <td class="right">
                    <select name="sen" id="sen" class='sen standardWidth'>
                        <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                        <option value="Y" /> <?php echo ynExpander($guid, 'Y') ?>
                        <option value="N" /> <?php echo ynExpander($guid, 'N') ?>
                    </select>
                    <script type="text/javascript">
                        var sen=new LiveValidation('sen');
                        sen.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                    </script>
                </td>
            </tr>
            <tr id='senDetailsRow' style='display: none'>
                <td colspan=2 style='padding-top: 15px'>
                    <b><?php echo __($guid, 'SEN Details') ?></b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Provide any comments or information concerning your child\'s development and SEN history.') ?></span><br/>
                    <textarea name="senDetails" id="senDetails" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
                </td>
            </tr>
            <tr>
                <td colspan=2 style='padding-top: 15px'>
                    <b><?php echo __($guid, 'Medical Information') ?></b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Please indicate any medical conditions.') ?></span><br/>
                    <textarea name="medicalInformation" id="medicalInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
                </td>
            </tr>



            <tr>
                <td colspan=2>
                    <h4><?php echo __($guid, 'Student Education') ?></h4>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Anticipated Year of Entry') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'What school year will the student join in?') ?></span>
                </td>
                <td class="right">
                    <select name="gibbonSchoolYearIDEntry" id="gibbonSchoolYearIDEntry" class="standardWidth">
                        <?php
                        echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                        try {
                            $dataSelect = array();
                            $sqlSelect = "SELECT * FROM gibbonSchoolYear WHERE (status='Current' OR status='Upcoming') ORDER BY sequenceNumber";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            echo "<option value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
                        }
                        ?>
                    </select>
                    <script type="text/javascript">
                        var gibbonSchoolYearIDEntry=new LiveValidation('gibbonSchoolYearIDEntry');
                        gibbonSchoolYearIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Intended Start Date') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Student\'s intended first day at school.') ?><br/><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
                    } else {
                        echo $_SESSION[$guid]['i18n']['dateFormat'];
                    }
                    ?></span>
                </td>
                <td class="right">
                    <input name="dateStart" id="dateStart" maxlength=10 value="" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var dateStart=new LiveValidation('dateStart');
                        dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                            echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                        }
                            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                            echo 'dd/mm/yyyy';
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                        }
                        ?>." } );
                        dateStart.add(Validate.Presence);
                    </script>
                     <script type="text/javascript">
                        $(function() {
                            $( "#dateStart" ).datepicker();
                        });
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Year Group at Entry') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Which year level will student enter.') ?></span>
                </td>
                <td class="right">
                    <select name="gibbonYearGroupIDEntry" id="gibbonYearGroupIDEntry" class="standardWidth">
                        <?php
                        echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                        try {
                            $dataSelect = array();
                            $sqlSelect = 'SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            echo "<option value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
                        }
                        ?>
                    </select>
                    <script type="text/javascript">
                        var gibbonYearGroupIDEntry=new LiveValidation('gibbonYearGroupIDEntry');
                        gibbonYearGroupIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                    </script>
                </td>
            </tr>

            <?php
            $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
            if ($dayTypeOptions != '') {
                ?>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Day Type') ?></b><br/>
                        <span class="emphasis small"><?php echo getSettingByScope($connection2, 'User Admin', 'dayTypeText'); ?></span>
                    </td>
                    <td class="right">
                        <select name="dayType" id="dayType" class="standardWidth">
                            <?php
                            $dayTypes = explode(',', $dayTypeOptions);
                            foreach ($dayTypes as $dayType) {
                                echo "<option value='".trim($dayType)."'>".trim($dayType).'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <?php

            }
            $applicationFormRefereeLink = getSettingByScope($connection2, 'Students', 'applicationFormRefereeLink');
            if ($applicationFormRefereeLink != '') {
                ?>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Current School Reference Email') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'An email address for a referee at the applicant\'s current school.') ?></span>
                    </td>
                    <td class="right">
                        <input name="referenceEmail" id="referenceEmail" maxlength=100 value="" type="text" class="standardWidth">
                        <script type="text/javascript">
                            var referenceEmail=new LiveValidation('referenceEmail');
                            referenceEmail.add(Validate.Presence);
                            referenceEmail.add(Validate.Email);
                        </script>
                    </td>
                </tr>
                <?php
            }
            ?>

            <tr>
                <td colspan=2 style='padding-top: 15px'>
                    <b><?php echo __($guid, 'Previous Schools') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Please give information on the last two schools attended by the applicant.') ?></span>
                </td>
            </tr>
            <tr>
                <td colspan=2>
                    <?php
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'School Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Address');
                    echo '</th>';
                    echo '<th>';
                    echo sprintf(__($guid, 'Grades%1$sAttended'), '<br/>');
                    echo '</th>';
                    echo '<th>';
                    echo sprintf(__($guid, 'Language of%1$sInstruction'), '<br/>');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Joining Date')."<br/><span style='font-size: 80%'>";
                    if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                        echo 'dd/mm/yyyy';
                    } else {
                        echo $_SESSION[$guid]['i18n']['dateFormat'];
                    }
                    echo '</span>';
                    echo '</th>';
                    echo '</tr>';

                    for ($i = 1; $i < 3; ++$i) {
                        if ((($i % 2) - 1) == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }

                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo "<input name='schoolName$i' id='schoolName$i' maxlength=50 value='' type='text' style='width:120px; float: left'>";
                        echo '</td>';
                        echo '<td>';
                        echo "<input name='schoolAddress$i' id='schoolAddress$i' maxlength=255 value='' type='text' style='width:120px; float: left'>";
                        echo '</td>';
                        echo '<td>';
                        echo "<input name='schoolGrades$i' id='schoolGrades$i' maxlength=20 value='' type='text' style='width:70px; float: left'>";
                        echo '</td>';
                        echo '<td>';
                        echo "<input name='schoolLanguage$i' id='schoolLanguage$i' maxlength=50 value='' type='text' style='width:100px; float: left'>";
                        ?>
                        <script type="text/javascript">
                            $(function() {
                                var availableTags=[
                                    <?php
                                    try {
                                        $dataAuto = array();
                                        $sqlAuto = 'SELECT DISTINCT schoolLanguage'.$i.' FROM gibbonApplicationForm ORDER BY schoolLanguage'.$i;
                                        $resultAuto = $connection2->prepare($sqlAuto);
                                        $resultAuto->execute($dataAuto);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowAuto = $resultAuto->fetch()) {
                                        echo '"'.$rowAuto['schoolLanguage'.$i].'", ';
                                    }
                                    ?>
                                ];
                                $( "#schoolLanguage<?php echo $i ?>" ).autocomplete({source: availableTags});
                            });
                        </script>
                        <?php
                    echo '</td>';
                    echo '<td>'; ?>
                        <input name="<?php echo "schoolDate$i" ?>" id="<?php echo "schoolDate$i" ?>" maxlength=10 value="" type="text" style="width:90px; float: left">
                        <script type="text/javascript">
                            $(function() {
                                $( "#<?php echo "schoolDate$i" ?>" ).datepicker();
                            });
                        </script>
                        <?php
                    echo '</td>';
                    echo '</tr>';
                    }
                    echo '</table>';?>
                </td>
            </tr>

            <?php
            //CUSTOM FIELDS FOR STUDENT
            $resultFields = getCustomFields($connection2, $guid, true, false, false, false, true, null);
            if ($resultFields->rowCount() > 0) {
                ?>
                <tr>
                    <td colspan=2>
                        <h4><?php echo __($guid, 'Other Information') ?></h4>
                    </td>
                </tr>
                <?php
                while ($rowFields = $resultFields->fetch()) {
                    echo renderCustomFieldRow($connection2, $guid, $rowFields);
                }
            }

            //FAMILY
            try {
                $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
                $sqlSelect = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID ORDER BY name';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }

            if ($public == true or $resultSelect->rowCount() < 1) {
                ?>
                <input type="hidden" name="gibbonFamily" value="FALSE">

                <?php if ($siblingApplicationMode == true) : ?>
                    <input type="hidden" name="homeAddress" value="<?php echo @$row['homeAddress']; ?>">
                    <input type="hidden" name="homeAddressDistrict" value="<?php echo @$row['homeAddressDistrict']; ?>">
                    <input type="hidden" name="homeAddressCountry" value="<?php echo @$row['homeAddressCountry']; ?>">
                <?php else: ?>
                <tr class='break'>
                    <td colspan=2>
                        <h3>
                            <?php echo __($guid, 'Home Address') ?>
                        </h3>
                        <p>
                            <?php echo __($guid, 'This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.') ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Home Address') ?> *</b><br/>
                        <span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
                    </td>
                    <td class="right">
                        <input name="homeAddress" id="homeAddress" maxlength=255 value="" type="text" class="standardWidth">
                        <script type="text/javascript">
                            var homeAddress=new LiveValidation('homeAddress');
                            homeAddress.add(Validate.Presence);
                        </script>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Home Address (District)') ?> *</b><br/>
                        <span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
                    </td>
                    <td class="right">
                        <input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="" type="text" class="standardWidth">
                    </td>
                    <script type="text/javascript">
                        $(function() {
                            var availableTags=[
                                <?php
                                try {
                                    $dataAuto = array();
                                    $sqlAuto = 'SELECT DISTINCT name FROM gibbonDistrict ORDER BY name';
                                    $resultAuto = $connection2->prepare($sqlAuto);
                                    $resultAuto->execute($dataAuto);
                                } catch (PDOException $e) {
                                }
                                while ($rowAuto = $resultAuto->fetch()) {
                                    echo '"'.$rowAuto['name'].'", ';
                                }
                                ?>
                            ];
                            $( "#homeAddressDistrict" ).autocomplete({source: availableTags});
                        });
                    </script>
                    <script type="text/javascript">
                        var homeAddressDistrict=new LiveValidation('homeAddressDistrict');
                        homeAddressDistrict.add(Validate.Presence);
                    </script>
                </tr>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Home Address (Country)') ?> *</b><br/>
                    </td>
                    <td class="right">
                        <select name="homeAddressCountry" id="homeAddressCountry" class="standardWidth">
                            <?php
                            try {
                                $dataSelect = array();
                                $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                            while ($rowSelect = $resultSelect->fetch()) {
                                echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
                            }
                            ?>
                        </select>
                        <script type="text/javascript">
                            var homeAddressCountry=new LiveValidation('homeAddressCountry');
                            homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                        </script>
                    </td>
                </tr>
                <?php endif; ?>

                <?php

                if (isset($_SESSION[$guid]['username']) || !empty($row['parent1gibbonPersonID']) ) {
                    $start = 2;

                    if (!empty($row['parent1gibbonPersonID']) && !empty($row['parent1username'])) {
                        $parent1username = $row['parent1username'];
                        $parent1email = $row['parent1email'];
                        $parent1surname = $row['parent1surname'];
                        $parent1preferredName = $row['parent1preferredName'];
                        $parent1gibbonPersonID = $row['parent1gibbonPersonID'];
                    } else {
                        $parent1username = $_SESSION[$guid]['username'];
                        $parent1email = $_SESSION[$guid]['email'];
                        $parent1surname = $_SESSION[$guid]['surname'];
                        $parent1preferredName = $_SESSION[$guid]['preferredName'];
                        $parent1gibbonPersonID = $gibbonPersonID;
                    }
                    ?>
                    <tr class='break'>
                        <td colspan=2>
                            <h3>
                                <?php echo __($guid, 'Parent/Guardian 1') ?>
                                <?php
                                if ($i == 1) {
                                    echo "<span style='font-size: 75%'></span>";
                                }
                                ?>
                            </h3>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Username') ?></b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'System login ID.') ?></span>
                        </td>
                        <td class="right">
                            <input readonly name='parent1username' maxlength=30 value="<?php echo $parent1username; ?>" type="text" class="standardWidth">
                            <input type="hidden" name='parent1email' value="<?php echo $parent1email; ?>" >
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Surname') ?></b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
                        </td>
                        <td class="right">
                            <input readonly name='parent1surname' maxlength=30 value="<?php echo $parent1surname; ?>" type="text" class="standardWidth">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Preferred Name') ?></b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Most common name, alias, nickname, etc.') ?></span>
                        </td>
                        <td class="right">
                            <input readonly name='parent1preferredName' maxlength=30 value="<?php echo $parent1preferredName; ?>" type="text" class="standardWidth">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Relationship') ?> *</b><br/>
                        </td>
                        <td class="right">
                            <select name="parent1relationship" id="parent1relationship" class="standardWidth">
                                <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                <option value="Mother"><?php echo __($guid, 'Mother') ?></option>
                                <option value="Father"><?php echo __($guid, 'Father') ?></option>
                                <option value="Step-Mother"><?php echo __($guid, 'Step-Mother') ?></option>
                                <option value="Step-Father"><?php echo __($guid, 'Step-Father') ?></option>
                                <option value="Adoptive Parent"><?php echo __($guid, 'Adoptive Parent') ?></option>
                                <option value="Guardian"><?php echo __($guid, 'Guardian') ?></option>
                                <option value="Grandmother"><?php echo __($guid, 'Grandmother') ?></option>
                                <option value="Grandfather"><?php echo __($guid, 'Grandfather') ?></option>
                                <option value="Aunt"><?php echo __($guid, 'Aunt') ?></option>
                                <option value="Uncle"><?php echo __($guid, 'Uncle') ?></option>
                                <option value="Nanny/Helper"><?php echo __($guid, 'Nanny/Helper') ?></option>
                                <option value="Other"><?php echo __($guid, 'Other') ?></option>
                            </select>
                            <script type="text/javascript">
                                var parent1relationship=new LiveValidation('parent1relationship');
                                parent1relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                            </script>
                        </td>
                    </tr>
                    <?php
                        //CUSTOM FIELDS FOR PARENT 1 WITH FAMILY

                        $existingFields = (isset($row["parent{$i}fields"]))? unserialize($row["parent{$i}fields"]) : null;

                        $resultFields = getCustomFields($connection2, $guid, false, false, true, false, true, null);
                        if ($resultFields->rowCount() > 0) {
                            while ($rowFields = $resultFields->fetch()) {
                                $value = (isset($existingFields[$rowFields['gibbonPersonFieldID']]))? $existingFields[$rowFields['gibbonPersonFieldID']] : '';
                                echo renderCustomFieldRow($connection2, $guid, $rowFields, $value, 'parent1');
                            }
                        }
                    ?>

                    <input name='parent1gibbonPersonID' value="<?php echo $parent1gibbonPersonID ?>" type="hidden">
                    <?php

                } else {
                    $start = 1;
                }
                for ($i = $start;$i < 3;++$i) {
                    ?>
                    <tr class='break'>
                        <td colspan=2>

                            <?php if ($siblingApplicationMode == true) : ?>
                                <small class="emphasis small" style="float:right;margin-top:16px;"><a id="clearParent<?php echo $i; ?>" data-parent="parent<?php echo $i; ?>">
                                    <?php echo __($guid, 'Clear').' '.__($guid, 'Parent/Guardian').' '.$i; ?>
                                </a></small>
                            <?php endif; ?>

                            <h3>
                                <?php echo __($guid, 'Parent/Guardian') ?> <?php echo $i ?>
                                <?php
                                if ($i == 1) {
                                    echo "<span style='font-size: 75%'> ".__($guid, '(e.g. mother)').'</span>';
                                } elseif ($i == 2 and $gibbonPersonID == '') {
                                    echo "<span style='font-size: 75%'> ".__($guid, '(e.g. father)').'</span>';
                                }
                                ?>
                            </h3>
                        </td>
                    </tr>
                    <?php
                    if ($i == 2) {
                        ?>
                        <tr>
                            <td class='right' colspan=2>
                                <script type="text/javascript">
                                    /* Advanced Options Control */
                                    $(document).ready(function(){

                                        /* Enable the Clear parent fields tool */
                                        $('a[id^="clearParent"]').click(function(){
                                            var parent = $(this).data('parent');
                                            $('[name^="'+parent+'"]').val('');
                                        });

                                        $("#secondParent").click(function(){
                                            if ($('input[name=secondParent]:checked').val()=="No" ) {
                                                $(".secondParent").slideUp("fast");
                                                $('[name^="parent2"]').attr("disabled", "disabled");
                                            }
                                            else {
                                                $(".secondParent").slideDown("fast", $(".secondParent").css("display","table-row"));
                                                $('[name^="parent2"]').removeAttr("disabled");
                                            }
                                         });

                                        var parent1Info = "<?php echo @$row['parent1surname']?>";
                                        var parent2Info = "<?php echo @$row['parent2surname']?>";
                                        if (parent1Info != '' && parent2Info == '') {
                                            /* Turn the checkbox on if pre-loading data and no second parent was defined */
                                            $("#secondParent").click();
                                        }
                                    });
                                </script>
                                <span style='font-weight: bold; font-style: italic'><?php echo __($guid, 'Do not include a second parent/guardian') ?> <input id='secondParent' name='secondParent' type='checkbox' value='No'/></span>
                            </td>
                        </tr>
                        <?php

                    }
                    ?>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td colspan=2>
                            <h4><?php echo __($guid, 'Parent/Guardian') ?> <?php echo $i ?> <?php echo __($guid, 'Personal Data') ?></h4>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Title') ?> *</b><br/>
                            <span class="emphasis small"></span>
                        </td>
                        <td class="right">
                            <select class="standardWidth" id="<?php echo "parent$i" ?>title" name="<?php echo "parent$i" ?>title">
                                <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                <option value="Ms."><?php echo __($guid, 'Ms.') ?></option>
                                <option value="Miss"><?php echo __($guid, 'Miss.') ?></option>
                                <option value="Mr."><?php echo __($guid, 'Mr.') ?></option>
                                <option value="Mrs."><?php echo __($guid, 'Mrs.') ?></option>
                                <option value="Dr."><?php echo __($guid, 'Dr.') ?></option>
                            </select>
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>title=new LiveValidation('<?php echo "parent$i" ?>title');
                                <?php echo "parent$i" ?>title.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});

                                $(document).ready(function(){
                                    if ("<?php echo @$row["parent{$i}title"]; ?>" != '') {
                                        $('select#<?php echo "parent{$i}title"; ?> option:contains("<?php echo @$row["parent{$i}title"]; ?>")').attr('selected', 'selected');
                                    }
                                });
                            </script>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Surname') ?> *</b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>surname" id="<?php echo "parent$i" ?>surname" maxlength=30 value="<?php echo @$row["parent{$i}surname"]; ?>" type="text" class="standardWidth">
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>surname=new LiveValidation('<?php echo "parent$i" ?>surname');
                                <?php echo "parent$i" ?>surname.add(Validate.Presence);
                            </script>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'First Name') ?> *</b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'First name as shown in ID documents.') ?></span>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>firstName" id="<?php echo "parent$i" ?>firstName" maxlength=30 value="<?php echo @$row["parent{$i}firstName"]; ?>" type="text" class="standardWidth">
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>firstName=new LiveValidation('<?php echo "parent$i" ?>firstName');
                                <?php echo "parent$i" ?>firstName.add(Validate.Presence);
                            </script>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Preferred Name') ?> *</b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Most common name, alias, nickname, etc.') ?></span>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>preferredName" id="<?php echo "parent$i" ?>preferredName" maxlength=30 value="<?php echo @$row["parent{$i}preferredName"]; ?>" type="text" class="standardWidth">
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>preferredName=new LiveValidation('<?php echo "parent$i" ?>preferredName');
                                <?php echo "parent$i" ?>preferredName.add(Validate.Presence);
                            </script>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Official Name') ?> *</b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Full name as shown in ID documents.') ?></span>
                        </td>
                        <td class="right">
                            <input title='Please enter full name as shown in ID documents' name="<?php echo "parent$i" ?>officialName" id="<?php echo "parent$i" ?>officialName" maxlength=150 value="<?php echo @$row["parent{$i}officialName"]; ?>" type="text" class="standardWidth">
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>officialName=new LiveValidation('<?php echo "parent$i" ?>officialName');
                                <?php echo "parent$i" ?>officialName.add(Validate.Presence);
                            </script>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Name In Characters') ?></b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Chinese or other character-based name.') ?></span>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>nameInCharacters" id="<?php echo "parent$i" ?>nameInCharacters" maxlength=20 value="<?php echo @$row["parent{$i}nameInCharacters"]; ?>" type="text" class="standardWidth">
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Gender') ?> *</b><br/>
                        </td>
                        <td class="right">
                            <select name="<?php echo "parent$i" ?>gender" id="<?php echo "parent$i" ?>gender" class="standardWidth">
                                <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                <option value="F" <?php echo (@$row["parent{$i}gender"] == 'F')? 'selected' : '';?> ><?php echo __($guid, 'Female') ?></option>
                                <option value="M" <?php echo (@$row["parent{$i}gender"] == 'M')? 'selected' : '';?> ><?php echo __($guid, 'Male') ?></option>
                            </select>
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>gender=new LiveValidation('<?php echo "parent$i" ?>gender');
                                <?php echo "parent$i" ?>gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                            </script>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Relationship') ?> *</b><br/>
                        </td>
                        <td class="right">
                            <select name="<?php echo "parent$i" ?>relationship" id="<?php echo "parent$i" ?>relationship" class="standardWidth">
                                <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                <option value="Mother"><?php echo __($guid, 'Mother') ?></option>
                                <option value="Father"><?php echo __($guid, 'Father') ?></option>
                                <option value="Step-Mother"><?php echo __($guid, 'Step-Mother') ?></option>
                                <option value="Step-Father"><?php echo __($guid, 'Step-Father') ?></option>
                                <option value="Adoptive Parent"><?php echo __($guid, 'Adoptive Parent') ?></option>
                                <option value="Guardian"><?php echo __($guid, 'Guardian') ?></option>
                                <option value="Grandmother"><?php echo __($guid, 'Grandmother') ?></option>
                                <option value="Grandfather"><?php echo __($guid, 'Grandfather') ?></option>
                                <option value="Aunt"><?php echo __($guid, 'Aunt') ?></option>
                                <option value="Uncle"><?php echo __($guid, 'Uncle') ?></option>
                                <option value="Nanny/Helper"><?php echo __($guid, 'Nanny/Helper') ?></option>
                                <option value="Other"><?php echo __($guid, 'Other') ?></option>
                            </select>
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>relationship=new LiveValidation('<?php echo "parent$i" ?>relationship');
                                <?php echo "parent$i" ?>relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                            </script>
                        </td>
                    </tr>

                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td colspan=2>
                            <h4><?php echo __($guid, 'Parent/Guardian') ?> <?php echo $i ?> <?php echo __($guid, 'Personal Background') ?></h4>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'First Language') ?></b><br/>
                        </td>
                        <td class="right">
                            <select name="<?php echo "parent$i" ?>languageFirst" id="<?php echo "parent$i" ?>languageFirst" class="standardWidth">
                                <?php
                                echo "<option value=''></option>";
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                while ($rowSelect = $resultSelect->fetch()) {
                                    $selected = (isset($row["parent{$i}languageFirst"]) && $row["parent{$i}languageFirst"] == $rowSelect['name'])? 'selected' : '';
                                    echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Second Language') ?></b><br/>
                        </td>
                        <td class="right">
                            <select name="<?php echo "parent$i" ?>languageSecond" id="<?php echo "parent$i" ?>languageSecond" class="standardWidth">
                                <?php
                                echo "<option value=''></option>";
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                while ($rowSelect = $resultSelect->fetch()) {
                                    $selected = (isset($row["parent{$i}languageSecond"]) && $row["parent{$i}languageSecond"] == $rowSelect['name'])? 'selected' : '';
                                    echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Citizenship') ?></b><br/>
                        </td>
                        <td class="right">
                            <select name="<?php echo "parent$i" ?>citizenship1" id="<?php echo "parent$i" ?>citizenship1" class="standardWidth">
                                <?php
                                echo "<option value=''></option>";
                                $nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
                                if ($nationalityList == '') {
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowSelect = $resultSelect->fetch()) {
                                        $selected = (isset($row["parent{$i}citizenship1"]) && $row["parent{$i}citizenship1"] == $rowSelect['printable_name'])? 'selected' : '';
                                        echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
                                    }
                                } else {
                                    $nationalities = explode(',', $nationalityList);
                                    foreach ($nationalities as $nationality) {
                                        $selected = (isset($row["parent{$i}citizenship1"]) && $row["parent{$i}citizenship1"] == $nationality)? 'selected' : '';
                                        echo "<option $selected value='".trim($nationality)."'>".trim($nationality).'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <?php
                            if ($_SESSION[$guid]['country'] == '') {
                                echo '<b>'.__($guid, 'National ID Card Number').'</b><br/>';
                            } else {
                                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Number').'</b><br/>';
                            }
                            ?>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>nationalIDCardNumber" id="<?php echo "parent$i" ?>nationalIDCardNumber" maxlength=30 value="<?php echo @$row["parent{$i}nationalIDCardNumber"]; ?>" type="text" class="standardWidth">
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <?php
                            if ($_SESSION[$guid]['country'] == '') {
                                echo '<b>'.__($guid, 'Residency/Visa Type').'</b><br/>';
                            } else {
                                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Residency/Visa Type').'</b><br/>';
                            }
                            ?>
                        </td>
                        <td class="right">
                            <?php
                            $residencyStatusList = getSettingByScope($connection2, 'User Admin', 'residencyStatus');
                            if ($residencyStatusList == '') {
                                echo "<input name='parent".$i."residencyStatus' id='parent".$i."residencyStatus' maxlength=30 type='text' style='width: 300px' value='"
                                .@$row["parent{$i}nationalIDCardNumber"]."'>";
                            } else {
                                echo "<select name='parent".$i."residencyStatus' id='parent".$i."residencyStatus' style='width: 302px'>";
                                echo "<option value=''></option>";
                                $residencyStatuses = explode(',', $residencyStatusList);
                                foreach ($residencyStatuses as $residencyStatus) {
                                    echo "<option value='".trim($residencyStatus)."'>".trim($residencyStatus).'</option>';
                                }
                                echo '</select>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <?php
                            if ($_SESSION[$guid]['country'] == '') {
                                echo '<b>'.__($guid, 'Visa Expiry Date').'</b><br/>';
                            } else {
                                echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Visa Expiry Date').'</b><br/>';
                            }
                            echo "<span style='font-size: 90%'><i>".__($guid, 'Format:').' ';
                            if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                                echo 'dd/mm/yyyy';
                            } else {
                                echo $_SESSION[$guid]['i18n']['dateFormat'];
                            }
                            echo '. '.__($guid, 'If relevant.').'</span>'; ?>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>visaExpiryDate" id="<?php echo "parent$i" ?>visaExpiryDate" maxlength=10 value="<?php echo dateConvertBack($guid, @$row["parent{$i}visaExpiryDate"]); ?>" type="text" class="standardWidth">
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>visaExpiryDate=new LiveValidation('<?php echo "parent$i" ?>visaExpiryDate');
                                <?php echo "parent$i" ?>visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                                echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                                } else {
                                    echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                                }
                                            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                                    echo 'dd/mm/yyyy';
                                } else {
                                    echo $_SESSION[$guid]['i18n']['dateFormat'];
                                }
                                ?>." } );
                            </script>
                             <script type="text/javascript">
                                $(function() {
                                    $( "#<?php echo "parent$i" ?>visaExpiryDate" ).datepicker();
                                });
                            </script>
                        </td>
                    </tr>


                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td colspan=2>
                            <h4><?php echo __($guid, 'Parent/Guardian') ?> <?php echo $i ?> <?php echo __($guid, 'Contact') ?></h4>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Email') ?> *</b><br/>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>email" id="<?php echo "parent$i" ?>email" maxlength=50 value="<?php echo @$row["parent{$i}email"]; ?>" type="text" class="standardWidth">
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>email=new LiveValidation('<?php echo "parent$i" ?>email');
                                <?php
                                echo "parent$i".'email.add(Validate.Email);';
                                echo "parent$i".'email.add(Validate.Presence);'; ?>
                            </script>
                        </td>
                    </tr>

                    <?php
                    for ($y = 1; $y < 3; ++$y) {
                        ?>
                        <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                            <td>
                                <b><?php echo __($guid, 'Phone') ?> <?php echo $y;
                                if ($y == 1) {
                                    echo ' *';
                                }
                                ?></b><br/>
                                <span class="emphasis small"><?php echo __($guid, 'Type, country code, number.') ?></span>
                            </td>
                            <td class="right">
                                <input name="<?php echo "parent$i" ?>phone<?php echo $y ?>" id="<?php echo "parent$i" ?>phone<?php echo $y ?>" maxlength=20 value="<?php echo @$row["parent{$i}phone{$y}"]; ?>" type="text" style="width: 160px">
                                <?php
                                if ($y == 1) {
                                    ?>
                                    <script type="text/javascript">
                                        var <?php echo "parent$i" ?>phone<?php echo $y ?>=new LiveValidation('<?php echo "parent$i" ?>phone<?php echo $y ?>');
                                        <?php echo "parent$i" ?>phone<?php echo $y ?>.add(Validate.Presence);
                                    </script>
                                    <?php

                                }
                                ?>
                                <select name="<?php echo "parent$i" ?>phone<?php echo $y ?>CountryCode" id="<?php echo "parent$i" ?>phone<?php echo $y ?>CountryCode" style="width: 60px">
                                    <?php
                                    echo "<option value=''></option>";
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = 'SELECT * FROM gibbonCountry ORDER BY printable_name';
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowSelect = $resultSelect->fetch()) {
                                        echo "<option value='".$rowSelect['iddCountryCode']."'>".htmlPrep($rowSelect['iddCountryCode']).' - '.htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
                                    }
                                    ?>
                                </select>
                                <select style="width: 70px" name="<?php echo "parent$i" ?>phone<?php echo $y ?>Type" id="<?php echo "parent$i" ?>phone<?php echo $y ?>Type">
                                    <option value=""></option>
                                    <option value="Mobile"><?php echo __($guid, 'Mobile') ?></option>
                                    <option value="Home"><?php echo __($guid, 'Home') ?></option>
                                    <option value="Work"><?php echo __($guid, 'Work') ?></option>
                                    <option value="Fax"><?php echo __($guid, 'Fax') ?></option>
                                    <option value="Pager"><?php echo __($guid, 'Pager') ?></option>
                                    <option value="Other"><?php echo __($guid, 'Other') ?></option>
                                </select>

                                <script type="text/javascript">
                                    $(document).ready(function(){

                                        if ("<?php echo @$row["parent{$i}phone{$y}CountryCode"]; ?>" != '') {
                                            $('select#<?php echo "parent{$i}phone{$y}CountryCode"; ?> option:contains("<?php echo @$row["parent{$i}phone{$y}CountryCode"]; ?>")').attr('selected', 'selected');
                                        }

                                        if ("<?php echo @$row["parent{$i}phone{$y}Type"]; ?>" != '') {
                                            $('select#<?php echo "parent{$i}phone{$y}Type"; ?> option:contains("<?php echo @$row["parent{$i}phone{$y}Type"]; ?>")').attr('selected', 'selected');
                                        }
                                    });
                                </script>
                            </td>
                        </tr>
                        <?php

                    }
                    ?>

                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td colspan=2>
                            <h4><?php echo __($guid, 'Parent/Guardian') ?> <?php echo $i ?> <?php echo __($guid, 'Employment') ?></h4>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Profession') ?> *</b><br/>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>profession" id="<?php echo "parent$i" ?>profession" maxlength=30 value="<?php echo @$row["parent{$i}profession"]; ?>" type="text" class="standardWidth">
                            <script type="text/javascript">
                                var <?php echo "parent$i" ?>profession=new LiveValidation('<?php echo "parent$i" ?>profession');
                                <?php echo "parent$i" ?>profession.add(Validate.Presence);
                            </script>
                        </td>
                    </tr>
                    <tr <?php if ($i == 2) { echo "class='secondParent'"; } ?>>
                        <td>
                            <b><?php echo __($guid, 'Employer') ?></b><br/>
                        </td>
                        <td class="right">
                            <input name="<?php echo "parent$i" ?>employer" id="<?php echo "parent$i" ?>employer" maxlength=30 value="<?php echo @$row["parent{$i}employer"]; ?>" type="text" class="standardWidth">
                        </td>
                    </tr>
                    <?php


                    //CUSTOM FIELDS FOR PARENTS, WITH FAMILY
                    $resultFields = getCustomFields($connection2, $guid, false, false, true, false, true, null);
                    if ($resultFields->rowCount() > 0) {
                        ?>
                        <tr <?php if ($i == 2) {
                        echo "class='secondParent'"; } ?>>
                            <td colspan=2>
                                <h4><?php echo __($guid, 'Parent/Guardian') ?> <?php echo $i ?> <?php echo __($guid, 'Other Fields') ?></h4>
                            </td>
                        </tr>
                        <?php

                        // Pull in existing serlialized data if pre-filling form
                        $existingFields = (isset($row["parent{$i}fields"]))? unserialize($row["parent{$i}fields"]) : null;

                        while ($rowFields = $resultFields->fetch()) {
                            $value = (isset($existingFields[$rowFields['gibbonPersonFieldID']]))? $existingFields[$rowFields['gibbonPersonFieldID']] : '';
                            if ($i == 2) {
                                echo renderCustomFieldRow($connection2, $guid, $rowFields, $value, 'parent2', 'secondParent');
                            } else {
                                echo renderCustomFieldRow($connection2, $guid, $rowFields, $value, 'parent1');
                            }
                        }
                    }
                }
            } else {
                ?>
                <input type="hidden" name="gibbonFamily" value="TRUE">
                <tr class='break'>
                    <td colspan=2>
                        <h3><?php echo __($guid, 'Family') ?></h3>
                        <p><?php echo __($guid, 'Choose the family you wish to associate this application with.') ?></p>
                        <?php
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Family Name');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Selected');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Relationships');
                        echo '</th>';
                        echo '</tr>';

                        $rowCount = 1;
                        while ($rowSelect = $resultSelect->fetch()) {
                            if (($rowCount % 2) == 0) {
                                $rowNum = 'odd';
                            } else {
                                $rowNum = 'even';
                            }

                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo '<b>'.$rowSelect['name'].'</b><br/>';
                            echo '</td>';
                            echo '<td>';

                            if (isset($row['gibbonFamilyID'])) {
                                $checked = ($row['gibbonFamilyID'] == $rowSelect['gibbonFamilyID'])? 'checked' : '';
                            } else {
                                $checked = ($rowCount == 1)? 'checked' : '';
                            }

                            echo "<input $checked value='".$rowSelect['gibbonFamilyID']."' name='gibbonFamilyID' type='radio'/>";
                            echo '</td>';
                            echo '<td>';
                            try {
                                $dataRelationships = array('gibbonFamilyID' => $rowSelect['gibbonFamilyID']);
                                $sqlRelationships = 'SELECT surname, preferredName, title, gender, gibbonFamilyAdult.gibbonPersonID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID';
                                $resultRelationships = $connection2->prepare($sqlRelationships);
                                $resultRelationships->execute($dataRelationships);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowRelationships = $resultRelationships->fetch()) {
                                echo "<div style='width: 100%; height: 20px; vertical-align: middle'>";
                                echo formatName($rowRelationships['title'], $rowRelationships['preferredName'], $rowRelationships['surname'], 'Parent'); ?>
                                <select name="<?php echo $rowSelect['gibbonFamilyID'] ?>-relationships[]" id="relationships[]" style="width: 200px">
                                    <option <?php if ($rowRelationships['gender'] == 'F') { echo 'selected'; } ?> value="Mother"><?php echo __($guid, 'Mother') ?></option>
                                    <option <?php if ($rowRelationships['gender'] == 'M') { echo 'selected'; } ?> value="Father"><?php echo __($guid, 'Father') ?></option>
                                    <option value="Step-Mother"><?php echo __($guid, 'Step-Mother') ?></option>
                                    <option value="Step-Father"><?php echo __($guid, 'Step-Father') ?></option>
                                    <option value="Adoptive Parent"><?php echo __($guid, 'Adoptive Parent') ?></option>
                                    <option value="Guardian"><?php echo __($guid, 'Guardian') ?></option>
                                    <option value="Grandmother"><?php echo __($guid, 'Grandmother') ?></option>
                                    <option value="Grandfather"><?php echo __($guid, 'Grandfather') ?></option>
                                    <option value="Aunt"><?php echo __($guid, 'Aunt') ?></option>
                                    <option value="Uncle"><?php echo __($guid, 'Uncle') ?></option>
                                    <option value="Nanny/Helper"><?php echo __($guid, 'Nanny/Helper') ?></option>
                                    <option value="Other"><?php echo __($guid, 'Other') ?></option>
                                </select>
                                <input type="hidden" name="<?php echo $rowSelect['gibbonFamilyID'] ?>-relationshipsGibbonPersonID[]" value="<?php echo $rowRelationships['gibbonPersonID'] ?>">
                                <?php
                            echo '</div>';
                            echo '<br/>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        ++$rowCount;
                    }
                    echo '</table>'; ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            <tr class='break'>
                <td colspan=2>
                    <h3><?php echo __($guid, 'Siblings') ?></h3>
                </td>
            </tr>
            <tr>
                <td colspan=2 style='padding-top: 0px'>
                    <p><?php echo __($guid, 'Please give information on the applicants\'s siblings.') ?></p>
                </td>
            </tr>
            <tr>
                <td colspan=2>
                    <?php
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Sibling Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Date of Birth')."<br/><span style='font-size: 80%'>".$_SESSION[$guid]['i18n']['dateFormat'].'</span>';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'School Attending');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Joining Date')."<br/><span style='font-size: 80%'>".$_SESSION[$guid]['i18n']['dateFormat'].'</span>';
                    echo '</th>';
                    echo '</tr>';

                    $rowCount = 1;

                    //List siblings who have been to or are at the school
                    if (isset($gibbonFamilyID)) {
                        try {
                            $dataSibling = array('gibbonFamilyID' => $gibbonFamilyID);
                            $sqlSibling = 'SELECT surname, preferredName, dob, dateStart FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY dob ASC, surname, preferredName';
                            $resultSibling = $connection2->prepare($sqlSibling);
                            $resultSibling->execute($dataSibling);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        while ($rowSibling = $resultSibling->fetch()) {
                            if (($rowCount % 2) == 0) {
                                $rowNum = 'odd';
                            } else {
                                $rowNum = 'even';
                            }

                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo "<input name='siblingName$rowCount' id='siblingName$rowCount' maxlength=50 value='".formatName('', $rowSibling['preferredName'], $rowSibling['surname'], 'Student')."' type='text' style='width:120px; float: left'>";
                            echo '</td>';
                            echo '<td>';
                            ?>
                                <input name="<?php echo "siblingDOB$rowCount" ?>" id="<?php echo "siblingDOB$rowCount" ?>" maxlength=10 value="<?php echo dateConvertBack($guid, $rowSibling['dob']) ?>" type="text" style="width:90px; float: left"><br/>
                                <script type="text/javascript">
                                    $(function() {
                                        $( "#<?php echo "siblingDOB$rowCount" ?>" ).datepicker();
                                    });
                                </script>
                                <?php
                            echo '</td>';
                            echo '<td>';
                            echo "<input name='siblingSchool$rowCount' id='siblingSchool$rowCount' maxlength=50 value='".$_SESSION[$guid]['organisationName']."' type='text' style='width:200px; float: left'>";
                            echo '</td>';
                            echo '<td>';
                            ?>
                                <input name="<?php echo "siblingSchoolJoiningDate$rowCount" ?>" id="<?php echo "siblingSchoolJoiningDate$rowCount" ?>" maxlength=10 value="<?php echo dateConvertBack($guid, $rowSibling['dateStart']) ?>" type="text" style="width:90px; float: left">
                                <script type="text/javascript">
                                    $(function() {
                                        $( "#<?php echo "siblingSchoolJoiningDate$rowCount" ?>" ).datepicker();
                                    });
                                </script>
                                <?php
                            echo '</td>';
                        echo '</tr>';

                        ++$rowCount;
                    }
                }

                //Space for other siblings
                for ($i = $rowCount; $i < 4; ++$i) {
                    if (($i % 2) == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo "<input name='siblingName$i' id='siblingName$i' maxlength=50 value='' type='text' style='width:120px; float: left'>";
                    echo '</td>';
                    echo '<td>';
                    ?>
                    <input name="<?php echo "siblingDOB$i" ?>" id="<?php echo "siblingDOB$i" ?>" maxlength=10 value="" type="text" style="width:90px; float: left"><br/>
                    <script type="text/javascript">
                        $(function() {
                            $( "#<?php echo "siblingDOB$i" ?>" ).datepicker();
                        });
                    </script>
                    <?php
                    echo '</td>';
                    echo '<td>';
                    echo "<input name='siblingSchool$i' id='siblingSchool$i' maxlength=50 value='' type='text' style='width:200px; float: left'>";
                    echo '</td>';
                    echo '<td>';
                    ?>
                    <input name="<?php echo "siblingSchoolJoiningDate$i" ?>" id="<?php echo "siblingSchoolJoiningDate$i" ?>" maxlength=10 value="" type="text" style="width:120px; float: left">
                    <script type="text/javascript">
                        $(function() {
                            $( "#<?php echo "siblingSchoolJoiningDate$i" ?>" ).datepicker();
                        });
                    </script>
                    <?php
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';?>
                </td>
            </tr>

            <?php
            $languageOptionsActive = getSettingByScope($connection2, 'Application Form', 'languageOptionsActive');
            if ($languageOptionsActive == 'Y') {
                ?>
                <tr class='break'>
                    <td colspan=2>
                        <h3><?php echo __($guid, 'Language Selection') ?></h3>
                        <?php
                        $languageOptionsBlurb = getSettingByScope($connection2, 'Application Form', 'languageOptionsBlurb');
                        if ($languageOptionsBlurb != '') {
                            echo '<p>';
                            echo $languageOptionsBlurb;
                            echo '</p>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Language Choice') ?> *</b><br/>
                        <span class="emphasis small"><?php  echo __($guid, 'Please choose preferred additional language to study.') ?></span>
                    </td>
                    <td class="right">
                        <select name="languageChoice" id="languageChoice" class="standardWidth">
                            <?php
                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                            $languageOptionsLanguageList = getSettingByScope($connection2, 'Application Form', 'languageOptionsLanguageList');
                            $languages = explode(',', $languageOptionsLanguageList);
                            foreach ($languages as $language) {
                                echo "<option value='".trim($language)."'>".trim($language).'</option>';
                            }
                            ?>
                        </select>
                        <script type="text/javascript">
                            var languageChoice=new LiveValidation('languageChoice');
                            languageChoice.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                        </script>
                    </td>
                </tr>
                <tr>
                    <td colspan=2 style='padding-top: 15px'>
                        <b><?php echo __($guid, 'Language Choice Experience') ?> *</b><br/>
                        <span class="emphasis small"><?php echo __($guid, 'Has the applicant studied the selected language before? If so, please describe the level and type of experience.') ?></span><br/>
                        <textarea name="languageChoiceExperience" id="languageChoiceExperience" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
                        <script type="text/javascript">
                            var languageChoiceExperience=new LiveValidation('languageChoiceExperience');
                            languageChoiceExperience.add(Validate.Presence);
                        </script>
                    </td>
                </tr>
                <?php
            }
            ?>

            <tr class='break'>
                <td colspan=2>
                    <h3><?php echo __($guid, 'Scholarships') ?></h3>
                    <?php
                    //Get scholarships info
                    $scholarship = getSettingByScope($connection2, 'Application Form', 'scholarships');
                    if ($scholarship != '') {
                        echo '<p>';
                        echo $scholarship;
                        echo '</p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Interest') ?></b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Indicate if you are interested in a scholarship.') ?></span><br/>
                </td>
                <td class="right">
                    <input type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="Y" /> <?php echo ynExpander($guid, 'Y') ?>
                    <input checked type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="N" /> <?php echo ynExpander($guid, 'N') ?>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Required?') ?></b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Is a scholarship required for you to take up a place at the school?') ?></span><br/>
                </td>
                <td class="right">
                    <input type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="Y" /> <?php echo ynExpander($guid, 'Y') ?>
                    <input checked type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="N" /> <?php echo ynExpander($guid, 'N') ?>
                </td>
            </tr>


            <tr class='break'>
                <td colspan=2>
                    <h3><?php echo __($guid, 'Payment') ?></h3>
                </td>
            </tr>
            <script type="text/javascript">
                /* Resource 1 Option Control */
                $(document).ready(function(){

                    if ("<?php echo @$row["payment"]; ?>" != 'Company') {
                        $("#companyNameRow").css("display","none");
                        $("#companyContactRow").css("display","none");
                        $("#companyAddressRow").css("display","none");
                        $("#companyEmailRow").css("display","none");
                        $("#companyCCFamilyRow").css("display","none");
                        $("#companyPhoneRow").css("display","none");
                        $("#companyAllRow").css("display","none");
                        $("#companyCategoriesRow").css("display","none");
                        companyEmail.disable() ;
                        companyAddress.disable() ;
                        companyContact.disable() ;
                        companyName.disable() ;
                    }

                    $(".payment").click(function(){
                        if ($('input[name=payment]:checked').val()=="Family" ) {
                            $("#companyNameRow").css("display","none");
                            $("#companyContactRow").css("display","none");
                            $("#companyAddressRow").css("display","none");
                            $("#companyEmailRow").css("display","none");
                            $("#companyCCFamilyRow").css("display","none");
                            $("#companyPhoneRow").css("display","none");
                            $("#companyAllRow").css("display","none");
                            $("#companyCategoriesRow").css("display","none");
                            companyEmail.disable() ;
                            companyAddress.disable() ;
                            companyContact.disable() ;
                            companyName.disable() ;
                        } else {
                            $("#companyNameRow").slideDown("fast", $("#companyNameRow").css("display","table-row"));
                            $("#companyContactRow").slideDown("fast", $("#companyContactRow").css("display","table-row"));
                            $("#companyAddressRow").slideDown("fast", $("#companyAddressRow").css("display","table-row"));
                            $("#companyEmailRow").slideDown("fast", $("#companyEmailRow").css("display","table-row"));
                            $("#companyCCFamilyRow").slideDown("fast", $("#companyCCFamilyRow").css("display","table-row"));
                            $("#companyPhoneRow").slideDown("fast", $("#companyPhoneRow").css("display","table-row"));
                            $("#companyAllRow").slideDown("fast", $("#companyAllRow").css("display","table-row"));
                            if ($('input[name=companyAll]:checked').val()=="Y" ) {
                                $("#companyCategoriesRow").css("display","none");
                            } else {
                                $("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row"));
                            }
                            companyEmail.enable() ;
                            companyAddress.enable() ;
                            companyContact.enable() ;
                            companyName.enable() ;
                        }
                     });

                     $(".companyAll").click(function(){
                        if ($('input[name=companyAll]:checked').val()=="Y" ) {
                            $("#companyCategoriesRow").css("display","none");
                        } else {
                            $("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row"));
                        }
                     });
                });
            </script>
            <tr id="familyRow">
                <td colspan=2>
                    <p><?php echo __($guid, 'If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.') ?></p>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Send Future Invoices To') ?></b><br/>
                </td>
                <td class="right">
                    <input type="radio" id="payment" name="payment" value="Family" class="payment" <?php echo (@$row['payment'] != 'Company')? 'checked' : ''; ?> /> <?php echo __($guid, 'Family') ?>
                    <input type="radio" id="payment" name="payment" value="Company" class="payment" <?php echo (@$row['payment'] == 'Company')? 'checked' : ''; ?> /> <?php echo __($guid, 'Company') ?>
                </td>
            </tr>
            <tr id="companyNameRow">
                <td>
                    <b><?php echo __($guid, 'Company Name') ?> *</b><br/>
                </td>
                <td class="right">
                    <input name="companyName" id="companyName" maxlength=100 value="<?php echo @$row['companyName']; ?>" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var companyName=new LiveValidation('companyName');
                        companyName.add(Validate.Presence);
                    </script>
                </td>
            </tr>
            <tr id="companyContactRow">
                <td>
                    <b><?php echo __($guid, 'Company Contact Person') ?> *</b><br/>
                </td>
                <td class="right">
                    <input name="companyContact" id="companyContact" maxlength=100 value="<?php echo @$row['companyContact']; ?>" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var companyContact=new LiveValidation('companyContact');
                        companyContact.add(Validate.Presence);
                    </script>
                </td>
            </tr>
            <tr id="companyAddressRow">
                <td>
                    <b><?php echo __($guid, 'Company Address') ?> *</b><br/>
                </td>
                <td class="right">
                    <input name="companyAddress" id="companyAddress" maxlength=255 value="<?php echo @$row['companyAddress']; ?>" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var companyAddress=new LiveValidation('companyAddress');
                        companyAddress.add(Validate.Presence);
                    </script>
                </td>
            </tr>
            <tr id="companyEmailRow">
                <td>
                    <b><?php echo __($guid, 'Company Emails') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Comma-separated list of email address.') ?></span>
                </td>
                <td class="right">
                    <input name="companyEmail" id="companyEmail" value="<?php echo @$row['companyEmail']; ?>" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var companyEmail=new LiveValidation('companyEmail');
                        companyEmail.add(Validate.Presence);
                    </script>
                </td>
            </tr>
            <tr id="companyCCFamilyRow">
                <td>
                    <b><?php echo __($guid, 'CC Family?') ?></b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Should the family be sent a copy of billing emails?') ?></span>
                </td>
                <td class="right">
                    <select name="companyCCFamily" id="companyCCFamily" class="standardWidth">
                        <option value="N" <?php echo (@$row['companyCCFamily'] == 'N')? 'selected' : ''; ?> /> <?php echo __($guid, 'No') ?>
                        <option value="Y" <?php echo (@$row['companyCCFamily'] == 'Y')? 'selected' : ''; ?> /> <?php echo __($guid, 'Yes') ?>
                    </select>
                </td>
            </tr>
            <tr id="companyPhoneRow">
                <td>
                    <b><?php echo __($guid, 'Company Phone') ?></b><br/>
                </td>
                <td class="right">
                    <input name="companyPhone" id="companyPhone" maxlength=20 value="<?php echo @$row['companyPhone']; ?>" type="text" class="standardWidth">
                </td>
            </tr>
            <?php
            try {
                $dataCat = array();
                $sqlCat = "SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
                $resultCat = $connection2->prepare($sqlCat);
                $resultCat->execute($dataCat);
            } catch (PDOException $e) {
            }
            if ($resultCat->rowCount() < 1) {
                echo '<input type="hidden" name="companyAll" value="Y" class="companyAll"/>';
            } else {
                ?>
                <tr id="companyAllRow">
                    <td>
                        <b><?php echo __($guid, 'Company All?') ?></b><br/>
                        <span class="emphasis small"><?php echo __($guid, 'Should all items be billed to the specified company, or just some?') ?></span>
                    </td>
                    <td class="right">
                        <input type="radio" name="companyAll" value="Y" class="companyAll" <?php echo (@$row['companyAll'] == 'Y')? 'checked' : ''; ?> /> <?php echo __($guid, 'All') ?>
                        <input type="radio" name="companyAll" value="N" class="companyAll" <?php echo (@$row['companyAll'] == 'N')? 'checked' : ''; ?> /> <?php echo __($guid, 'Selected') ?>
                    </td>
                </tr>
                <tr id="companyCategoriesRow">
                    <td>
                        <b><?php echo __($guid, 'Company Fee Categories') ?></b><br/>
                        <span class="emphasis small"><?php echo __($guid, 'If the specified company is not paying all fees, which categories are they paying?') ?></span>
                    </td>
                    <td class="right">
                        <?php

                        $existingFeeCategoryIDList = (isset($row['gibbonFinanceFeeCategoryIDList']) && is_array($row['gibbonFinanceFeeCategoryIDList']))? $row['gibbonFinanceFeeCategoryIDList'] : array();
                        while ($rowCat = $resultCat->fetch()) {
                            $checked = (in_array($rowCat['gibbonFinanceFeeCategoryID'], $existingFeeCategoryIDList, true))? 'checked' : '';
                            echo __($guid, $rowCat['name'])." <input type='checkbox' $checked name='gibbonFinanceFeeCategoryIDList[]' value='".$rowCat['gibbonFinanceFeeCategoryID']."'/><br/>";
                        }
                        $checked = (in_array(0001, $existingFeeCategoryIDList, true))? 'checked' : '';
                        echo __($guid, 'Other')." <input type='checkbox' $checked name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>";
                        ?>
                    </td>
                </tr>
            <?php

            }

            $requiredDocuments = getSettingByScope($connection2, 'Application Form', 'requiredDocuments');
            $requiredDocumentsText = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsText');
            $requiredDocumentsCompulsory = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsCompulsory');
            if ($requiredDocuments != '' and $requiredDocuments != false) {
                ?>
                <tr class='break'>
                    <td colspan=2>
                        <h3><?php echo __($guid, 'Supporting Documents') ?></h3>
                        <?php
                        if ($requiredDocumentsText != '' or $requiredDocumentsCompulsory != '') {
                            echo '<p>';
                            echo $requiredDocumentsText.' ';
                            if ($requiredDocumentsCompulsory == 'Y') {
                                echo __($guid, 'All documents must all be included before the application can be submitted.');
                            } else {
                                echo __($guid, 'These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.');
                            }
                            echo '</p>';
                        }
                        ?>
                    </td>
                </tr>
            <?php

            $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

            $requiredDocumentsList = explode(',', $requiredDocuments);
            $count = 0;
            foreach ($requiredDocumentsList as $document) {
                ?>
                <tr>
                    <td>
                        <b><?php echo $document;
                        if ($requiredDocumentsCompulsory == 'Y') {
                            echo ' *';
                        }
                        ?></b><br/>
                    </td>
                    <td class="right">
                        <?php
                        echo "<input type='file' name='file$count' id='file$count'><br/>";
                        echo "<input type='hidden' name='fileName$count' id='filefileName$count' value='$document'>";
                        echo "<script type='text/javascript'>";
                        echo "var file$count=new LiveValidation('file$count');";
                        echo "file$count.add( Validate.Inclusion, { within: [".$fileUploader->getFileExtensionsCSV()."], failureMessage: 'Illegal file type!', partialMatch: true, caseSensitive: false } );";
                        if ($requiredDocumentsCompulsory == 'Y') {
                            echo "file$count.add(Validate.Presence);";
                        }
                        echo '</script>';
                        ++$count; ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <td colspan=2>
                    <?php echo getMaxUpload($guid);?>
                    <input type="hidden" name="fileCount" value="<?php echo $count ?>">
                </td>
            </tr>
            <?php
            }
            ?>

            <tr class='break'>
                <td colspan=2>
                    <h3><?php echo __($guid, 'Miscellaneous') ?></h3>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'How Did You Hear About Us?') ?> *</b><br/>
                </td>
                <td class="right">
                    <?php
                    $howDidYouHearList = getSettingByScope($connection2, 'Application Form', 'howDidYouHear');
                    if ($howDidYouHearList == '') {
                        echo "<input name='howDidYouHear' id='howDidYouHear' maxlength=30 value='".@$row['howDidYouHear']."' type='text' style='width: 300px'>";
                    } else {
                        echo "<select name='howDidYouHear' id='howDidYouHear' style='width: 302px'>";
                        echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                        $howDidYouHears = explode(',', $howDidYouHearList);
                        foreach ($howDidYouHears as $howDidYouHear) {
                            $selected = (isset($row['howDidYouHear']) && $row['howDidYouHear'] == $howDidYouHear)? 'selected' : '';
                            echo "<option $selected value='".trim($howDidYouHear)."'>".__($guid, trim($howDidYouHear)).'</option>';
                        }
                        echo '</select>'; ?>
                        <script type="text/javascript">
                            var howDidYouHear=new LiveValidation('howDidYouHear');
                            howDidYouHear.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                        </script>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <script type="text/javascript">
                $(document).ready(function(){
                    $("#howDidYouHear").change(function(){
                        if ($('#howDidYouHear').val()=="Please select..." ) {
                            $("#tellUsMoreRow").css("display","none");
                        }
                        else {
                            $("#tellUsMoreRow").slideDown("fast", $("#tellUsMoreRow").css("display","table-row"));
                        }
                     });
                });
            </script>
            <tr id="tellUsMoreRow" style='display: none'>
                <td>
                    <b><?php echo __($guid, 'Tell Us More') ?> </b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'The name of a person or link to a website, etc.') ?></span>
                </td>
                <td class="right">
                    <input name="howDidYouHearMore" id="howDidYouHearMore" maxlength=255 value="<?php echo @$row['howDidYouHearMore']; ?>" type="text" class="standardWidth">
                </td>
            </tr>
            <?php
            $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
            $privacyBlurb = getSettingByScope($connection2, 'User Admin', 'privacyBlurb');
            $privacyOptions = getSettingByScope($connection2, 'User Admin', 'privacyOptions');
            if ($privacySetting == 'Y' and $privacyBlurb != '' and $privacyOptions != '') {
                ?>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Privacy') ?> *</b><br/>
                        <span class="emphasis small"><?php echo htmlPrep($privacyBlurb) ?><br/>
                        </span>
                    </td>
                    <td class="right">
                        <?php
                        $options = explode(',', $privacyOptions);
                        foreach ($options as $option) {
                            echo $option." <input type='checkbox' name='privacyOptions[]' value='".htmlPrep($option)."'/><br/>";
                        }
                        ?>
                    </td>
                </tr>
                <?php

            }

            //Get agreement
            $agreement = getSettingByScope($connection2, 'Application Form', 'agreement');
            if ($agreement != '') {
            echo "<tr class='break'>";
            echo '<td colspan=2>';
            echo '<h3>';
            echo __($guid, 'Agreement');
            echo '</h3>';
            echo '<p>';
            echo $agreement;
            echo '</p>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo '<b>'.__($guid, 'Do you agree to the above?').'</b><br/>';
            echo '</td>';
            echo "<td class='right'>";
            echo "Yes <input type='checkbox' name='agreement' id='agreement'>";
            ?>
            <script type="text/javascript">
                var agreement=new LiveValidation('agreement');
                agreement.add( Validate.Acceptance );
            </script>
             <?php
        echo '</td>';
        echo '</tr>';
        }
        ?>

            <tr>
                <td>
                    <span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
                </td>
                <td class="right">
                    <input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
                    <input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
                </td>
            </tr>
        </table>
    </form>

    <?php
    //Get postscrript
    $postscript = getSettingByScope($connection2, 'Application Form', 'postscript');
    if ($postscript != '') {
        echo '<h2>';
        echo __($guid, 'Further Information');
        echo '</h2>';
        echo "<p style='padding-bottom: 15px'>";
        echo $postscript;
        echo '</p>';
    }
}
?>

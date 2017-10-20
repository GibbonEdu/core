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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

include '../version.php';
include '../functions.php';

//Get and set step
$step = 0;
if (isset($_GET['step'])) {
    $step = $_GET['step'];
}

//Deal with $guid setup
if ($step == 0) {
    $charList = 'abcdefghijkmnopqrstuvwxyz023456789';
    $guid = '';
    for ($i = 0;$i < 36;++$i) {
        if ($i == 9 or $i == 14 or $i == 19 or $i == 24) {
            $guid .= '-';
        } else {
            $guid .= substr($charList, rand(1, strlen($charList)), 1);
        }
    }
} else {
    if (isset($_GET['guid'])) {
        $guid = $_GET['guid'];
    } else {
        $guid = '';
    }
}

//Deal with non-existent stringReplacement session
@session_start();
$_SESSION[$guid]['stringReplacement'] = array();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>
			Gibbon Installer
		</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<meta http-equiv="content-language" content="en"/>
		<meta name="author" content="Ross Parker, International College Hong Kong"/>
		<meta name="robots" content="none"/>

		<link rel="shortcut icon" type="image/x-icon" href="../favicon.ico"/>
		<script type="text/javascript" src="../lib/LiveValidation/livevalidation_standalone.compressed.js"></script>
		<link rel='stylesheet' type='text/css' href='../themes/Default/css/main.css' />
		<script type='text/javascript' src='../themes/Default/js/common.js'></script>
		<script type="text/javascript" src="../lib/jquery/jquery.js"></script>
		<script type="text/javascript" src="../lib/jquery/jquery-migrate.min.js"></script>
	</head>
	<body>
		<div id="wrapOuter">
			<div id="wrap">
				<div id="header">
					<div id="header-left">
						<img height='100px' width='400px' class="logo" alt="Logo" src="../themes/Default/img/logo.png"></a>
					</div>
					<div id="header-right">

					</div>
					<div id="header-menu">

					</div>
				</div>
				<div id="content-wrap">
					<div id='content'>
						<?php
                            //Get and set database variables (not set until step 1)
                            $databaseServer = (isset($_POST['databaseServer']))? $_POST['databaseServer'] : '';
                            $databaseName = (isset($_POST['databaseName']))? $_POST['databaseName'] : '';
                            $databaseUsername = (isset($_POST['databaseUsername']))? $_POST['databaseUsername'] : '';
                            $databasePassword = (isset($_POST['databasePassword']))? $_POST['databasePassword'] : '';
                            $demoData = (isset($_POST['demoData']))? $_POST['demoData'] : '';

                            //Set language
                            $code = (isset($_POST['code']))? $_POST['code'] : 'en_GB';
                            putenv('LC_ALL='.$code);
                            setlocale(LC_ALL, $code);
                            bindtextdomain('gibbon', '../i18n');
                            textdomain('gibbon');

                            echo '<h2>'.sprintf(__($guid, 'Installation - Step %1$s'), ($step + 1)).'</h2>';

                            if ($step == 0) { //Choose language
                                $proceed = true;

                                if (file_exists('../config.php')) { //Make sure system is not already installed
                                    if (filesize('../config.php') > 0 or is_writable('../config.php') == false) {
                                        $proceed = false;
                                    }
                                } else { //No config, so continue installer
                                    if (is_writable('../') == false) { //Ensure that home directory is writable
                                        $proceed = false;
                                    }
                                }

                                if ($proceed == false) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'The directory containing the Gibbon files is not currently writable, or config.php already exists in the root folder and is not empty or is not writable, so the installer cannot proceed.');
                                    echo '</div>';
                                }
                                else {
                                    //PROCEED
                                    echo "<div class='success'>";
                                    echo __($guid, 'The directory containing the Gibbon files is writable, so the installation may proceed.');
                                    echo '</div>';

                                    $trueIcon = "<img title='" . __($guid, 'Yes'). "' src='../themes/Default/img/iconTick.png' style='width:20px;height:20px;margin-right:10px' />";
									$falseIcon = "<img title='" . __($guid, 'No'). "' src='../themes/Default/img/iconCross.png' style='width:20px;height:20px;margin-right:10px' />";

									$versionTitle = __($guid, '%s Version');
									$versionMessage = __($guid, '%s requires %s version %s or higher');

									$phpVersion = phpversion();

									$phpRequirement = $gibbon->getSystemRequirement('php');
									$extensions = $gibbon->getSystemRequirement('extensions');

                                    $form = Form::create('action', "./install.php?step=1&guid=$guid");

                                    $form->addRow()->addHeading(__('System Requirements'));

                                    $row = $form->addRow();
                                        $row->addLabel('phpVersionLabel', sprintf($versionTitle, 'PHP'))->description(sprintf($versionMessage, __($guid, 'Gibbon').' v'.$version, 'PHP', $phpRequirement));
                                        $row->addTextField('phpVersion')->setValue($phpVersion)->readonly();
                                        $row->addContent((version_compare($phpVersion, $phpRequirement, '>='))? $trueIcon : $falseIcon);

                                    $row = $form->addRow();
                                        $row->addLabel('pdoSupportLabel', __($guid, 'MySQL PDO Support'));
                                        $row->addTextField('pdoSupport')->setValue((@extension_loaded('pdo_mysql'))? __($guid, 'Installed') : __($guid, 'Not Installed'))->readonly();
                                        $row->addContent((@extension_loaded('pdo') && extension_loaded('pdo_mysql'))? $trueIcon : $falseIcon);

                                    if (!empty($extensions) && is_array($extensions)) {
                                        foreach ($extensions as $extension) {
                                            $installed = @extension_loaded($extension);
                                            $row = $form->addRow();
                                                $row->addLabel('extensionLabel', __($guid, 'Extension').' '. $extension);
                                                $row->addTextField('extension')->setValue(($installed)? __($guid, 'Installed') : __($guid, 'Not Installed'))->readonly();
                                                $row->addContent(($installed)? $trueIcon : $falseIcon);
                                        }
                                    }

                                    $form->addRow()->addHeading(__('Language Settings'));

                                    $languages = array(
                                        'nl_NL' => 'Dutch - Nederland',
                                        'en_GB' => 'English - United Kingdom',
                                        'en_US' => 'English - United States',
                                        'es_ES' => 'Español',
                                        'fr_FR' => 'Français - France',
                                        'it_IT' => 'Italiano - Italia',
                                        'ro_RO' => 'Română',
                                        'sq_AL' => 'Shqip - Shqipëri',
                                        'vi_VN' => 'Tiếng Việt - Việt Nam',
                                        'ar_SA' => 'العربية - المملكة العربية السعودية',
                                        'th_TH' => 'ภาษาไทย - ราชอาณาจักรไทย',
                                        'zh_HK' => '體字 - 香港');

                                    $row = $form->addRow();
                                        $row->addLabel('code', __('System Language'));
                                        $row->addSelect('code')->fromArray($languages)->selected($code)->isRequired();

                                    $row = $form->addRow();
                                        $row->addFooter();
                                        $row->addSubmit();

                                    echo $form->getOutput();
                                }
                            }
                            if ($step == 1) { //Set database options
                                $form = Form::create('action', "./install.php?step=2&guid=$guid");

                                $form->addHiddenValue('code', $code);

                                $form->addRow()->addHeading(__('Database Settings'));

                                $row = $form->addRow();
                                    $row->addLabel('type', __('Database Type'));
                                    $row->addTextField('type')->setValue('MySQL')->readonly()->isRequired();

                                $row = $form->addRow();
                                    $row->addLabel('databaseServer', __('Database Server'))->description(__('Localhost, IP address or domain.'));
                                    $row->addTextField('databaseServer')->isRequired()->maxLength(255);

                                $row = $form->addRow();
                                    $row->addLabel('databaseName', __('Database Name'))->description(__('This database will be created if it does not already exist. Collation should be utf8_general_ci.'));
                                    $row->addTextField('databaseName')->isRequired()->maxLength(50);

                                $row = $form->addRow();
                                    $row->addLabel('databaseUsername', __('Database Username'));
                                    $row->addTextField('databaseUsername')->isRequired()->maxLength(50);

                                $row = $form->addRow();
                                    $row->addLabel('databasePassword', __('Database Password'));
                                    $row->addPassword('databasePassword')->isRequired()->maxLength(255);

                                $row = $form->addRow();
                                    $row->addLabel('demoData', __('Install Demo Data?'));
                                    $row->addYesNo('demoData')->selected('N');


                                //FINISH & OUTPUT FORM
                                $row = $form->addRow();
                                    $row->addFooter();
                                    $row->addSubmit();

                                echo $form->getOutput();
                            } elseif ($step == 2) {
                                //Check for db values
                                if ($databaseServer == '' or $databaseName == '' or $databaseUsername == '' or $databasePassword == '' or $demoData == '') {
                                    echo "<div class='error'>";
                                    echo sprintf(__($guid, 'A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", '</a>');
                                    echo '</div>';
                                }

                                //Estabish db connection without database name
                                $connected1 = true;
                                $pdo = new Gibbon\sqlConnection(true);
                                $pdo->installBypass($databaseServer, $databaseName, $databaseUsername, $databasePassword);
                                $connected1 = $pdo->getSuccess();
                                $connection2 = $pdo->getConnection();
                                if ($connected1 == false) {
                                    echo "<div class='error'>";
                                    echo sprintf(__($guid, 'A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", '</a>');
                                    echo '</div>';
                                } else {
                                    //Set up config.php
                                    $config = '';
                                    $config .= "<?php\n";
                                    $config .= "/*\n";
                                    $config .= "Gibbon, Flexible & Open School System\n";
                                    $config .= "Copyright (C) 2010, Ross Parker\n";
                                    $config .= "\n";
                                    $config .= "This program is free software: you can redistribute it and/or modify\n";
                                    $config .= "it under the terms of the GNU General Public License as published by\n";
                                    $config .= "the Free Software Foundation, either version 3 of the License, or\n";
                                    $config .= "(at your option) any later version.\n";
                                    $config .= "\n";
                                    $config .= "This program is distributed in the hope that it will be useful,\n";
                                    $config .= "but WITHOUT ANY WARRANTY; without even the implied warranty of\n";
                                    $config .= "MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n";
                                    $config .= "GNU General Public License for more details.\n";
                                    $config .= "\n";
                                    $config .= "You should have received a copy of the GNU General Public License\n";
                                    $config .= "along with this program.  If not, see <http://www.gnu.org/licenses/>.\n";
                                    $config .= "*/\n";
                                    $config .= "\n";
                                    $config .= "//Sets database connection information\n";
                                    $config .= '$databaseServer="'.$databaseServer."\" ;\n";
                                    $config .= '$databaseUsername="'.$databaseUsername."\" ;\n";
                                    $config .= "\$databasePassword='".$databasePassword."' ;\n";
                                    $config .= '$databaseName="'.$databaseName."\" ;\n";
                                    $config .= "\n";
                                    $config .= "//Sets globally unique id, to allow multiple installs on the server server.\n";
                                    $config .= '$guid="'.$guid."\" ;\n";
                                    $config .= "\n";
                                    $config .= "//Sets system-wide caching factor, used to baalance performance and freshness. Value represents number of page loads between cache refresh. Must be posititve integer. 1 means no caching.\n";
                                    $config .= "\$caching=10 ;\n";
                                    $config .= "?>\n";

                                    //Write config
                                    $fp = fopen('../config.php', 'wb');
                                    fwrite($fp, $config);
                                    fclose($fp);

                                    if (file_exists('../config.php') == false) { //Something went wrong, config.php could not be created.
                                        echo "<div class='error'>";
                                        echo __($guid, '../config.php could not be created, and so the installer cannot proceed.');
                                        echo '</div>';
                                    } else { //Config, exists, let's press on
                                        //Let's populate the database
                                        if (file_exists('../gibbon.sql') == false) {
                                            echo "<div class='error'>";
                                            echo __($guid, '../gibbon.sql does not exist, and so the installer cannot proceed.');
                                            echo '</div>';
                                        } else {
                                            include './installerFunctions.php';

                                            $query = @fread(@fopen('../gibbon.sql', 'r'), @filesize('../gibbon.sql')) or die('Encountered a problem.');
                                            $query = remove_remarks($query);
                                            $query = split_sql_file($query, ';');

                                            $i = 1;
                                            $partialFail = false;
                                            foreach ($query as $sql) {
                                                ++$i;
                                                try {
                                                    $connection2->query($sql);
                                                } catch (PDOException $e) {
                                                    $partialFail = true;
                                                }
                                            }

                                            if ($partialFail == true) {
                                                echo "<div class='error'>";
                                                echo __($guid, 'Errors occurred in populating the database; empty your database, remove ../config.php and try again.');
                                                echo '</div>';
                                            } else {
                                                //Try to install the demo data, report error but don't stop if any issues
                                                if ($demoData == 'Y') {
                                                    if (file_exists('../gibbon_demo.sql') == false) {
                                                        echo "<div class='error'>";
                                                        echo __($guid, '../gibbon_demo.sql does not exist, so we will conintue without demo data.');
                                                        echo '</div>';
                                                    } else {
                                                        $query = @fread(@fopen('../gibbon_demo.sql', 'r'), @filesize('../gibbon_demo.sql')) or die('Encountered a problem.');
                                                        $query = remove_remarks($query);
                                                        $query = split_sql_file($query, ';');

                                                        $i = 1;
                                                        $demoFail = false;
                                                        foreach ($query as $sql) {
                                                            ++$i;
                                                            try {
                                                                $connection2->query($sql);
                                                            } catch (PDOException $e) {
                                                                echo $sql.'<br/>';
                                                                echo $e->getMessage().'<br/><br/>';
                                                                $demoFail = true;
                                                            }
                                                        }

                                                        if ($demoFail) {
                                                            echo "<div class='error'>";
                                                            echo __($guid, 'There were some issues installing the demo data, but we will conintue anyway.');
                                                            echo '</div>';
                                                        }
                                                    }
                                                }

                                                //Set default language
                                                try {
                                                    $data = array('code' => $code);
                                                    $sql = "UPDATE gibboni18n SET systemDefault='Y' WHERE code=:code";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                }
                                                try {
                                                    $data = array('code' => $code);
                                                    $sql = "UPDATE gibboni18n SET systemDefault='N' WHERE NOT code=:code";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                }

                                                //Let's gather some more information

                                                $form = Form::create('action', "./install.php?step=3&guid=$guid");

                                                $form->setFactory(DatabaseFormFactory::create($pdo));
                                                $form->addHiddenValue('code', $code);
                                                $form->addHiddenValue('databaseServer', $databaseServer);
                                                $form->addHiddenValue('databaseName', $databaseName);
                                                $form->addHiddenValue('databaseUsername', $databaseUsername);
                                                $form->addHiddenValue('databasePassword', $databasePassword);

                                                $form->addRow()->addHeading(__('User Account'));

                                                $row = $form->addRow();
                                                    $row->addLabel('title', __('Title'));
                                                    $row->addSelectTitle('title');

                                                $row = $form->addRow();
                                                    $row->addLabel('surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                                                    $row->addTextField('surname')->isRequired()->maxLength(30);

                                                $row = $form->addRow();
                                                    $row->addLabel('firstName', __('First Name'))->description(__('First name as shown in ID documents.'));
                                                    $row->addTextField('firstName')->isRequired()->maxLength(30);

                                                $row = $form->addRow();
                                                    $row->addLabel('email', __('Email'));
                                                    $row->addEmail('email')->maxLength(50);

                                                $row = $form->addRow();
                                                    $row->addLabel('support', '<b>'.__('Receive Support?').'</b>')->description(__($guid, 'Join our mailing list and recieve a welcome email from the team.'));
                                                    $row->addCheckbox('support')->fromArray(array('on' => __('Yes')))->checked('on')->setID('support');

                                                $row = $form->addRow();
                                                    $row->addLabel('username', __('Username'))->description(__('Must be unique. System login name. Cannot be changed.'));
                                            		$row->addTextField('username')->isRequired()->maxLength(20);

                                                $policy = getPasswordPolicy($guid, $connection2);
                                            	if ($policy != false) {
                                            		$form->addRow()->addAlert($policy, 'warning');
                                            	}
                                            	$row = $form->addRow();
                                            		$row->addLabel('passwordNew', __('Password'));
                                            		$column = $row->addColumn('passwordNew')->addClass('inline right');
                                            		$column->addButton(__('Generate Password'))->addClass('generatePassword');
                                            		$password = $column->addPassword('passwordNew')
                                            			->isRequired()
                                            			->maxLength(30);

                                            	$alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
                                            	$numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
                                            	$punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
                                            	$minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');

                                            	if ($alpha == 'Y') {
                                            		$password->addValidation('Validate.Format', 'pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: "'.__('Does not meet password policy.').'"');
                                            	}
                                            	if ($numeric == 'Y') {
                                            		$password->addValidation('Validate.Format', 'pattern: /.*[0-9]/, failureMessage: "'.__('Does not meet password policy.').'"');
                                            	}
                                            	if ($punctuation == 'Y') {
                                            		$password->addValidation('Validate.Format', 'pattern: /[^a-zA-Z0-9]/, failureMessage: "'.__('Does not meet password policy.').'"');
                                            	}
                                            	if (!empty($minLength) && is_numeric($minLength)) {
                                            		$password->addValidation('Validate.Length', 'minimum: '.$minLength.', failureMessage: "'.__('Does not meet password policy.').'"');
                                            	}

                                            	$row = $form->addRow();
                                            		$row->addLabel('passwordConfirm', __('Confirm Password'));
                                            		$row->addPassword('passwordConfirm')
                                            			->isRequired()
                                            			->maxLength(30)
                                            			->addValidation('Validate.Confirmation', "match: 'passwordNew'");

                                                $form->addRow()->addHeading(__('System Settings'));

                                                $pageURL = (@$_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
                                                $port = '';
                                                if ($_SERVER['SERVER_PORT'] != '80') {
                                                    $port = ':'.$_SERVER['SERVER_PORT'];
                                                }
                                                $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
                                                $setting = getSettingByScope($connection2, 'System', 'absoluteURL', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addURL($setting['name'])->setValue($pageURL.$_SERVER['SERVER_NAME'].$port.substr($uri_parts[0], 0, -22))->maxLength(100)->isRequired();

                                                $setting = getSettingByScope($connection2, 'System', 'absolutePath', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addTextField($setting['name'])->setValue(substr(__FILE__, 0, -22))->maxLength(100)->isRequired();

                                                $setting = getSettingByScope($connection2, 'System', 'systemName', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addTextField($setting['name'])->maxLength(50)->isRequired();

                                                $setting = getSettingByScope($connection2, 'System', 'installType', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addSelect($setting['name'])->fromString('Production, Testing, Development')->selected('Testing')->isRequired();

                                                $setting = getSettingByScope($connection2, 'System', 'cuttingEdgeCode', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addTextField($setting['name'])->setValue('No')->readonly();

                                                //Check and set cutting edge code based on gibbonedu.org services value
                                                echo '<script type="text/javascript">';
                                                echo '$(document).ready(function(){';
                                                echo '$.ajax({';
                                                echo 'crossDomain: true, type:"GET", contentType: "application/json; charset=utf-8",async:false,';
                                                echo 'url: "https://gibbonedu.org/services/version/devCheck.php?version='.$version.'&callback=?",';
                                                echo "data: \"\",dataType: \"jsonp\", jsonpCallback: 'fnsuccesscallback',jsonpResult: 'jsonpResult',";
                                                echo 'success: function(data) {';
                                                echo '$("#status").attr("class","success");';
                                                echo "if (data['status']==='false') {";
                                                echo "$(\"#status\").html('".__($guid, 'Cutting Edge Code check successful.')."') ;";
                                                echo '}';
                                                echo 'else {';
                                                echo "$(\"#status\").html('".__($guid, 'Cutting Edge Code check successful.')."') ;";
                                                echo "$(\"#cuttingEdgeCode\").val('Y');";
                                                echo "$(\"#cuttingEdgeCodeHidden\").val('Y');";
                                                echo '}';
                                                echo '},';
                                                echo 'error: function (data, textStatus, errorThrown) {';
                                                echo '$("#status").attr("class","error");';
                                                echo "$(\"#status\").html('".__($guid, 'Cutting Edge Code check failed').".') ;";
                                                echo '}';
                                                echo '});';
                                                echo '});';
                                                echo '</script>';

                                                $setting = getSettingByScope($connection2, 'System', 'statsCollection', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addYesNo($setting['name'])->selected('Y')->isRequired();

                                                $form->addRow()->addHeading(__('Organisation Settings'));

                                                $setting = getSettingByScope($connection2, 'System', 'organisationName', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addTextField($setting['name'])->setValue('')->maxLength(50)->isRequired();

                                                $setting = getSettingByScope($connection2, 'System', 'organisationNameShort', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addTextField($setting['name'])->setValue('')->maxLength(50)->isRequired();

                                                $form->addRow()->addHeading(__('gibbonedu.com Value Added Services'));

                                                $setting = getSettingByScope($connection2, 'System', 'gibboneduComOrganisationName', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addTextField($setting['name'])->setValue();

                                                $setting = getSettingByScope($connection2, 'System', 'gibboneduComOrganisationKey', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addTextField($setting['name'])->setValue();

                                                $form->addRow()->addHeading(__('Miscellaneous'));

                                                $setting = getSettingByScope($connection2, 'System', 'country', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addSelectCountry($setting['name']);

                                                $setting = getSettingByScope($connection2, 'System', 'currency', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addSelectCurrency($setting['name'])->isRequired();

                                                $setting = getSettingByScope($connection2, 'System', 'timezone', true);
                                                $row = $form->addRow();
                                                    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                                                    $row->addTextField($setting['name'])->setValue()->isRequired();

                                                $row = $form->addRow();
                                                    $row->addFooter();
                                                    $row->addSubmit();

                                                echo $form->getOutput();
                                            }
                                        }
                                    }
                                }
                            } elseif ($step == 3) {
                                //New PDO DB connection
                                $pdo = new Gibbon\sqlConnection(false, "<div class='error'>\n".sprintf(__($guid, 'A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", '</a>')."\n</div>\n");
                                $connection2 = $pdo->getConnection();
                                $connected3 = $pdo->getSuccess();

                                if ($connected3) {
                                    //Get user account details
                                    $title = $_POST['title'];
                                    $surname = $_POST['surname'];
                                    $firstName = $_POST['firstName'];
                                    $preferredName = $_POST['firstName'];
                                    $username = $_POST['username'];
                                    $password = $_POST['passwordNew'];
                                    $passwordConfirm = $_POST['passwordConfirm'];
                                    $email = $_POST['email'];
                                    $support = false;
                                    if (isset($_POST['support'])) {
                                        if ($_POST['support'] == 'true') {
                                            $support = true;
                                        }
                                    }

                                    //Get system settings
                                    $absoluteURL = $_POST['absoluteURL'];
                                    $absolutePath = $_POST['absolutePath'];
                                    $systemName = $_POST['systemName'];
                                    $organisationName = $_POST['organisationName'];
                                    $organisationNameShort = $_POST['organisationNameShort'];
                                    $currency = $_POST['currency'];
                                    $timezone = $_POST['timezone'];
                                    $country = $_POST['country'];
                                    $installType = $_POST['installType'];
                                    $statsCollection = $_POST['statsCollection'];
                                    $cuttingEdgeCode = $_POST['cuttingEdgeCode'];
                                    $gibboneduComOrganisationName = $_POST['gibboneduComOrganisationName'];
                                    $gibboneduComOrganisationKey = $_POST['gibboneduComOrganisationKey'];

                                    if ($surname == '' or $firstName == '' or $preferredName == '' or $email == '' or $username == '' or $password == '' or $passwordConfirm == '' or $email == '' or $absoluteURL == '' or $absolutePath == '' or $systemName == '' or $organisationName == '' or $organisationNameShort == '' or $timezone == '' or $country == '' or $installType == '' or $statsCollection == '' or $cuttingEdgeCode == '') {
                                        echo "<div class='error'>";
                                        echo __($guid, 'Some required fields have not been set, and so installation cannot proceed.');
                                        echo '</div>';
                                    } else {
                                        //Check passwords for match
                                        if ($password != $passwordConfirm) {
                                            echo "<div class='error'>";
                                            echo __($guid, 'Your request failed because your passwords did not match.');
                                            echo '</div>';
                                        } else {
                                            $salt = getSalt();
                                            $passwordStrong = hash('sha256', $salt.$password);

                                            $userFail = false;
                                            //Write to database
                                            try {
                                                $data = array('title' => $title, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => ($firstName.' '.$surname), 'username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'status' => 'Full', 'canLogin' => 'Y', 'passwordForceReset' => 'N', 'gibbonRoleIDPrimary' => '001', 'gibbonRoleIDAll' => '001', 'email' => $email);
                                                $sql = "INSERT INTO gibbonPerson SET gibbonPersonID=1, title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, status=:status, canLogin=:canLogin, passwordForceReset=:passwordForceReset, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, email=:email";
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $userFail = true;
                                                echo "<div class='error'>";
                                                echo sprintf(__($guid, 'Errors occurred in populating the database; empty your database, remove ../config.php and %1$stry again%2$s.'), "<a href='./install.php'>", '</a>');
                                                echo '</div>';
                                            }

                                            try {
                                                $dataStaff = array('gibbonPersonID' => 1, 'type' => 'Teaching');
                                                $sqlStaff = "INSERT INTO gibbonStaff SET gibbonPersonID=1, type='Teaching', smartWorkflowHelp='Y'";
                                                $resultStaff = $connection2->prepare($sqlStaff);
                                                $resultStaff->execute($dataStaff);
                                            } catch (PDOException $e) {
                                            }

                                            if ($userFail == false) {
                                                $settingsFail = false;
                                                try {
                                                    $data = array('absoluteURL' => $absoluteURL);
                                                    $sql = "UPDATE gibbonSetting SET value=:absoluteURL WHERE scope='System' AND name='absoluteURL'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('absolutePath' => $absolutePath);
                                                    $sql = "UPDATE gibbonSetting SET value=:absolutePath WHERE scope='System' AND name='absolutePath'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('systemName' => $systemName);
                                                    $sql = "UPDATE gibbonSetting SET value=:systemName WHERE scope='System' AND name='systemName'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('organisationName' => $organisationName);
                                                    $sql = "UPDATE gibbonSetting SET value=:organisationName WHERE scope='System' AND name='organisationName'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('organisationNameShort' => $organisationNameShort);
                                                    $sql = "UPDATE gibbonSetting SET value=:organisationNameShort WHERE scope='System' AND name='organisationNameShort'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('organisationEmail' => $email); //Use user email as organisation email, initially
                                                    $sql = "UPDATE gibbonSetting SET value=:organisationEmail WHERE scope='System' AND name='organisationEmail'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('currency' => $currency);
                                                    $sql = "UPDATE gibbonSetting SET value=:currency WHERE scope='System' AND name='currency'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $fail = true;
                                                }

                                                try {
                                                    $data = array('organisationAdministrator' => 1);
                                                    $sql = "UPDATE gibbonSetting SET value=:organisationAdministrator WHERE scope='System' AND name='organisationAdministrator'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('organisationDBA' => 1);
                                                    $sql = "UPDATE gibbonSetting SET value=:organisationDBA WHERE scope='System' AND name='organisationDBA'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('organisationHR' => 1);
                                                    $sql = "UPDATE gibbonSetting SET value=:organisationHR WHERE scope='System' AND name='organisationHR'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('organisationAdmissions' => 1);
                                                    $sql = "UPDATE gibbonSetting SET value=:organisationAdmissions WHERE scope='System' AND name='organisationAdmissions'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('country' => $country);
                                                    $sql = "UPDATE gibbonSetting SET value=:country WHERE scope='System' AND name='country'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('gibboneduComOrganisationName' => $gibboneduComOrganisationName);
                                                    $sql = "UPDATE gibbonSetting SET value=:gibboneduComOrganisationName WHERE scope='System' AND name='gibboneduComOrganisationName'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('gibboneduComOrganisationKey' => $gibboneduComOrganisationKey);
                                                    $sql = "UPDATE gibbonSetting SET value=:gibboneduComOrganisationKey WHERE scope='System' AND name='gibboneduComOrganisationKey'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('timezone' => $timezone);
                                                    $sql = "UPDATE gibbonSetting SET value=:timezone WHERE scope='System' AND name='timezone'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('installType' => $installType);
                                                    $sql = "UPDATE gibbonSetting SET value=:installType WHERE scope='System' AND name='installType'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                try {
                                                    $data = array('statsCollection' => $statsCollection);
                                                    $sql = "UPDATE gibbonSetting SET value=:statsCollection WHERE scope='System' AND name='statsCollection'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }

                                                if ($statsCollection == 'Y') {
                                                    $absolutePathProtocol = '';
                                                    $absolutePath = '';
                                                    if (substr($absoluteURL, 0, 7) == 'http://') {
                                                        $absolutePathProtocol = 'http';
                                                        $absolutePath = substr($absoluteURL, 7);
                                                    } elseif (substr($absoluteURL, 0, 8) == 'https://') {
                                                        $absolutePathProtocol = 'https';
                                                        $absolutePath = substr($absoluteURL, 8);
                                                    }
                                                    echo "<iframe style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/tracker/tracker.php?absolutePathProtocol=".urlencode($absolutePathProtocol).'&absolutePath='.urlencode($absolutePath).'&organisationName='.urlencode($organisationName).'&type='.urlencode($installType).'&version='.urlencode($version).'&country='.$country."&usersTotal=1&usersFull=1'></iframe>";
                                                }

                                                try {
                                                    $data = array('cuttingEdgeCode' => $cuttingEdgeCode);
                                                    $sql = "UPDATE gibbonSetting SET value=:cuttingEdgeCode WHERE scope='System' AND name='cuttingEdgeCode'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $settingsFail = true;
                                                }
                                                if ($cuttingEdgeCode == 'Y') {
                                                    include '../CHANGEDB.php';
                                                    $sqlTokens = explode(';end', $sql[(count($sql))][1]);
                                                    $versionMaxLinesMax = (count($sqlTokens) - 1);
                                                    $tokenCount = 0;
                                                    try {
                                                        $data = array('cuttingEdgeCodeLine' => $versionMaxLinesMax);
                                                        $sql = "UPDATE gibbonSetting SET value=:cuttingEdgeCodeLine WHERE scope='System' AND name='cuttingEdgeCodeLine'";
                                                        $result = $connection2->prepare($sql);
                                                        $result->execute($data);
                                                    } catch (PDOException $e) {
                                                    }

                                                    foreach ($sqlTokens as $sqlToken) {
                                                        if ($tokenCount <= $versionMaxLinesMax) { //Decide whether this has been run or not
                                                            if (trim($sqlToken) != '') {
                                                                try {
                                                                    $result = $connection2->query($sqlToken);
                                                                } catch (PDOException $e) {
                                                                    $partialFail = true;
                                                                }
                                                            }
                                                        }
                                                        ++$tokenCount;
                                                    }
                                                }

                                                //Deal with request to receive welcome email by calling gibbonedu.org iframe
                                                if ($support == true) {
                                                    $absolutePathProtocol = '';
                                                    $absolutePath = '';
                                                    if (substr($absoluteURL, 0, 7) == 'http://') {
                                                        $absolutePathProtocol = 'http';
                                                        $absolutePath = substr($absoluteURL, 7);
                                                    } elseif (substr($absoluteURL, 0, 8) == 'https://') {
                                                        $absolutePathProtocol = 'https';
                                                        $absolutePath = substr($absoluteURL, 8);
                                                    }
                                                    echo "<iframe class='support' style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/support/supportRegistration.php?absolutePathProtocol=".urlencode($absolutePathProtocol).'&absolutePath='.urlencode($absolutePath).'&organisationName='.urlencode($organisationName).'&email='.urlencode($email).'&title='.urlencode($title).'&surname='.urlencode($surname).'&preferredName='.urlencode($preferredName)."'></iframe>";
                                                }

                                                if ($settingsFail == true) {
                                                    echo "<div class='error'>";
                                                    echo sprintf(__($guid, 'Some settings did not save. The system may work, but you may need to remove everything and start again. Try and %1$sgo to your Gibbon homepage%2$s and login as user <u>admin</u> with password <u>gibbon</u>.'), "<a href='$absoluteURL'>", '</a>');
                                                    echo '<br/><br/>';
                                                    echo sprintf(__($guid, 'It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", '</a>');
                                                    echo '</div>';
                                                } else {
                                                    echo "<div class='success'>";
                                                    echo sprintf(__($guid, 'Congratulations, your installation is complete. Feel free to %1$sgo to your Gibbon homepage%2$s and login with the username and password you created.'), "<a href='$absoluteURL'>", '</a>');
                                                    echo '<br/><br/>';
                                                    echo sprintf(__($guid, 'It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", '</a>');
                                                    echo '</div>';
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                        ?>
					</div>
					<div id="sidebar">
						<h2><?php echo __($guid, 'Welcome To Gibbon') ?></h2>
						<p style='padding-top: 7px'>
						<?php echo __($guid, 'Created by teachers, Gibbon is the school platform which solves real problems faced by educators every day.') ?><br/>
						<br/>
						<?php echo __($guid, 'Free, open source and flexible, Gibbon can morph to meet the needs of a huge range of schools.') ?><br/>
						<br/>
						<?php echo sprintf(__($guid, 'For support, please visit %1$sgibbonedu.org%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support'>", '</a>') ?>
						</p>
					</div>
					<br style="clear: both">
				</div>
				<div id="footer">
					<?php echo __($guid, 'Powered by') ?> <a href="https://gibbonedu.org">Gibbon</a> v<?php echo $version ?> &#169; <a href="http://rossparker.org">Ross Parker</a> 2010-<?php echo date('Y') ?><br/>
					<span style='font-size: 90%; '>
						<?php echo __($guid, 'Created under the') ?> <a href="https://www.gnu.org/licenses/gpl.html">GNU GPL</a> at <a href='http://www.ichk.edu.hk'>ICHK</a>
					</span><br/>
					<img style='z-index: 100; margin-bottom: -57px; margin-right: -50px' alt='Logo Small' src='../themes/Default/img/logoFooter.png'/>
				</div>
			</div>
		</div>
	</body>
</html>

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
                            $databaseServer = '';
                            if (isset($_POST['databaseServer'])) {
                                $databaseServer = $_POST['databaseServer'];
                            }
                            $databaseName = '';
                            if (isset($_POST['databaseName'])) {
                                $databaseName = $_POST['databaseName'];
                            }
                            $databaseUsername = '';
                            if (isset($_POST['databaseUsername'])) {
                                $databaseUsername = $_POST['databaseUsername'];
                            }
                            $databasePassword = '';
                            if (isset($_POST['databasePassword'])) {
                                $databasePassword = $_POST['databasePassword'];
                            }
                            $demoData = '';
                            if (isset($_POST['demoData'])) {
                                $demoData = $_POST['demoData'];
                            }

                            //Set language
                            $code = 'en_GB';
                            if (isset($_POST['code'])) {
                                $code = $_POST['code'];
                            }
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

                                    //Set language options
                                    ?>
									<form method="post" action="./install.php?step=1&guid=<?php echo $guid ?>">
										<table class='smallIntBorder fullWidth' cellspacing='0'>
											<tr class='break'>
												<td colspan=3>
													<h3><?php echo __($guid, 'System Requirements') ?></h3>
												</td>
											</tr>
											<tr>
												<td>
													<b><?php printf($versionTitle, 'PHP'); ?></b><br/>
													<span class="emphasis small">
														<?php printf($versionMessage, __($guid, 'Gibbon').' v'.$version, 'PHP', $phpRequirement ); ?>
													</span>
												</td>
												<td style="width:60px;padding-left:10px!important;">
													<b><?php echo $phpVersion; ?></b>
												</td>
												<td class="right" style="width:60px;">
													<?php echo (version_compare($phpVersion, $phpRequirement, '>='))? $trueIcon : $falseIcon; ?>
												</td>
											</tr>
											<tr>
												<td>
													<b><?php echo __($guid, 'MySQL PDO Support'); ?></b><br/>
												</td>
												<td style="padding-left:10px!important;">
													<?php echo (@extension_loaded('pdo_mysql'))? __($guid, 'Installed') : __($guid, 'Not Installed'); ?>
												</td>
												<td class="right">
													<?php echo (@extension_loaded('pdo') && extension_loaded('pdo_mysql'))? $trueIcon : $falseIcon; ?>
												</td>
											</tr>
											<?php
									            if (!empty($extensions) && is_array($extensions)) {
									                foreach ($extensions as $extension) {
									                    $installed = @extension_loaded($extension);
									                    ?>
									                    <tr>
									                        <td>
									                            <b><?php echo __($guid, 'Extension').' '. $extension; ?></b><br/>
									                        </td>
									                        <td style="padding-left:10px!important;">
									                            <?php echo ($installed)? __($guid, 'Installed') : __($guid, 'Not Installed'); ?>
									                        </td>
									                        <td colspan=2 class="right">
									                            <?php echo ($installed)? $trueIcon : $falseIcon; ?>
									                        </td>
									                    </tr>
									                    <?php
									                }
									            }
									        ?>
											<tr class='break'>
												<td colspan=3>
													<h3><?php echo __($guid, 'Language Settings') ?></h3>
												</td>
											</tr>
											<tr>
												<td>
													<b><?php echo __($guid, 'System Language') ?> *</b><br/>
												</td>
												<td colspan=2 class="right" style='width: 275px'>
													<select name="code" id="code" class="standardWidth">
														<option value='nl_NL'>Dutch - Nederland</option>
														<option selected value='en_GB'>English - United Kingdom</option>
														<option value='en_US'>English - United States</option>
														<option value='es_ES'>Español</option>
														<option value='fr_FR'>Français - France</option>
														<option value='it_IT'>Italiano - Italia</option>
														<option value='ro_RO'>Română</option>
                                                        <option value="sq_AL">Shqip - Shqipëri</option>
                                                        <option value="vi_VN">Tiếng Việt - Việt Nam</option>
														<option value='ar_SA'>العربية - المملكة العربية السعودية</option>
                                                        <option value="th_TH">ภาษาไทย - ราชอาณาจักรไทย </option>
                                                        <option value='zh_HK'>體字 - 香港</option>
													</select>
												</td>
											</tr>
											<tr>
												<td>
													<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
												</td>
												<td colspan=2 class="right">
													<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
												</td>
											</tr>
										</table>
									</form>
									<?php

                                }
                            }
                            if ($step == 1) { //Set database options
                                ?>
								<form method="post" action="./install.php?step=2&guid=<?php echo $guid ?>">
									<table class='smallIntBorder fullWidth' cellspacing='0'>
										<tr class='break'>
											<td colspan=2>
												<h3><?php echo __($guid, 'Database Information') ?></h3>
											</td>
										</tr>
										<tr>
											<td style='width: 275px'>
												<b><?php echo __($guid, 'Database Type') ?> *</b><br/>
												<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
											</td>
											<td class="right">
												<input readonly name="type" id="type" value="MySQL" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td style='width: 275px'>
												<b><?php echo __($guid, 'Database Server') ?> *</b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Localhost, IP address or domain.') ?></span>
											</td>
											<td class="right">
												<input name="databaseServer" id="databaseServer" maxlength=255 value="" type="text" class="standardWidth">
												<script type="text/javascript">
													var databaseServer=new LiveValidation('databaseServer');
													databaseServer.add(Validate.Presence);
												</script>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Database Name') ?> *</b><br/>
												<span class="emphasis small"><?php echo __($guid, 'This database will be created if it does not already exist. Collation should be utf8_general_ci.') ?></span>
											</td>
											<td class="right">
												<input name="databaseName" id="databaseName" maxlength=50 value="" type="text" class="standardWidth">
												<script type="text/javascript">
													var databaseName=new LiveValidation('databaseName');
													databaseName.add(Validate.Presence);
												</script>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Database Username') ?>*</b><br/>
											</td>
											<td class="right">
												<input name="databaseUsername" id="databaseUsername" maxlength=50 value="" type="text" class="standardWidth">
												<script type="text/javascript">
													var databaseUsername=new LiveValidation('databaseUsername');
													databaseUsername.add(Validate.Presence);
												</script>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Database Password') ?> *</b><br/>
											</td>
											<td class="right">
												<input name="databasePassword" id="databasePassword" maxlength=255 value="" type="password" class="standardWidth">
												<script type="text/javascript">
													var databasePassword=new LiveValidation('databasePassword');
													databasePassword.add(Validate.Presence);
												</script>
											</td>
										</tr>

										<tr>
											<td>
												<b><?php echo __($guid, 'Install Demo Data?') ?> *</b><br/>
											</td>
											<td class="right">
												<select name="demoData" id="demoData" class="standardWidth">
													<?php
                                                    echo "<option selected value='N'>".ynExpander($guid, 'N').'</option>';echo "<option value='Y'>".ynExpander($guid, 'Y').'</option>';?>
												</select>
											</td>
										</tr>
										<tr>
											<td>
												<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');?></span>
											</td>
											<td class="right">
												<input type="hidden" name="code" value="<?php echo $code ?>">
												<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
											</td>
										</tr>
									</table>
								</form>
								<?php

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
                                                ?>
												<form method="post" action="./install.php?step=3&guid=<?php echo $guid ?>">
													<table class='smallIntBorder fullWidth' cellspacing='0'>
														<tr class='break'>
															<td colspan=2>
																<h3><?php echo __($guid, 'User Account') ?></h3>
															</td>
														</tr>
														<tr>
															<td style='width: 275px'>
																<b><?php echo __($guid, 'Title') ?></b><br/>
															</td>
															<td class="right">
																<select class="standardWidth" name="title">
																	<option value=""></option>
																	<option value="Ms. "><?php echo __($guid, 'Ms.') ?></option>
																	<option value="Miss "><?php echo __($guid, 'Miss') ?></option>
																	<option value="Mr. "><?php echo __($guid, 'Mr.') ?></option>
																	<option value="Mrs. "><?php echo __($guid, 'Mrs.') ?></option>
																	<option value="Dr. "><?php echo __($guid, 'Dr.') ?></option>
																</select>
															</td>
														</tr>
														<tr>
															<td>
																<b><?php echo __($guid, 'Surname') ?> *</b><br/>
																<span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
															</td>
															<td class="right">
																<input name="surname" id="surname" maxlength=30 value="" type="text" class="standardWidth">
																<script type="text/javascript">
																	var surname=new LiveValidation('surname');
																	surname.add(Validate.Presence);
																</script>
															</td>
														</tr>
														<tr>
															<td>
																<b><?php echo __($guid, 'First Name') ?>*</b><br/>
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
																<b><?php echo __($guid, 'Email') ?> *</b><br/>
															</td>
															<td class="right">
																<input name="email" id="email" maxlength=50 value="" type="text" class="standardWidth">
																<script type="text/javascript">
																	var email=new LiveValidation('email');
																	email.add(Validate.Email);
																	email.add(Validate.Presence);
																</script>
															</td>
														</tr>
														<tr>
															<td>
																<b><?php echo __($guid, 'Receive Support?') ?></b><br/>
																<span class="emphasis small"><?php echo __($guid, 'Join our mailing list and recieve a welcome email from the team.') ?></span>
															</td>
															<td class="right">
																<input checked name="support" id="support" value="true" type="checkbox">
															</td>
														</tr>
														<tr>
															<td>
																<b><?php echo __($guid, 'Username') ?> *</b><br/>
																<span class="emphasis small"><?php echo __($guid, 'Must be unique. System login name. Cannot be changed.') ?></span>
															</td>
															<td class="right">
																<input name="username" id="username" maxlength=20 value="" type="text" class="standardWidth">
																<?php
                                                                $idList = '';
																try {
																	$dataSelect = array();
																	$sqlSelect = 'SELECT username FROM gibbonPerson ORDER BY username';
																	$resultSelect = $connection2->prepare($sqlSelect);
																	$resultSelect->execute($dataSelect);
																} catch (PDOException $e) {
																}
																while ($rowSelect = $resultSelect->fetch()) {
																	$idList .= "'".$rowSelect['username']."',";
																}
																?>
																<script type="text/javascript">
																	var username=new LiveValidation('username');
																	username.add(Validate.Presence);
																</script>
															</td>
														</tr>
														<tr>
															<td colspan=2>
																<?php
                                                                $policy = getPasswordPolicy($guid, $connection2);
																if ($policy != false) {
																	echo "<div class='warning'>";
																	echo $policy;
																	echo '</div>';
																}
																?>
															</td>
														</tr>
														<tr>
															<td>
																<b><?php echo __($guid, 'Password') ?> *</b><br/>
																<span class="emphasis small"></span>
															</td>
															<td class="right">
																<input type='button' class="generatePassword" value="<?php echo __($guid, 'Generate Password') ?>"/>
																<input name="passwordNew" id="passwordNew" maxlength=30 value="" type="password" class="standardWidth"><br/>

																<script type="text/javascript">
																	var passwordNew=new LiveValidation('passwordNew');
																	passwordNew.add(Validate.Presence);
																	<?php
                                                                    $alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
																	$numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
																	$punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
																	$minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');
																	if ($alpha == 'Y') {
																		echo 'passwordNew.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
																	}
																	if ($numeric == 'Y') {
																		echo 'passwordNew.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
																	}
																	if ($punctuation == 'Y') {
																		echo 'passwordNew.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
																	}
																	if (is_numeric($minLength)) {
																		echo 'passwordNew.add( Validate.Length, { minimum: '.$minLength.'} );';
																	}
																	?>

																	$(".generatePassword").click(function(){
																		var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^<>~@|';
																		var text = '';
																		for(var i=0; i < <?php echo $minLength + 4 ?>; i++) {
																			if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
																			else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
																			else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
																			else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
																			else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
																		}
																		$('input[name="passwordNew"]').val(text);
																		$('input[name="passwordConfirm"]').val(text);
																		alert('<?php echo __($guid, 'Copy this password if required:') ?>' + '\n\n' + text) ;
																	});
																</script>
															</td>
														</tr>
														<tr>
															<td>
																<b><?php echo __($guid, 'Confirm Password') ?> *</b><br/>
																<span class="emphasis small"></span>
															</td>
															<td class="right">
																<input name="passwordConfirm" id="passwordConfirm" maxlength=30 value="" type="password" class="standardWidth">
																<script type="text/javascript">
																	var passwordConfirm=new LiveValidation('passwordConfirm');
																	passwordConfirm.add(Validate.Presence);
																	passwordConfirm.add(Validate.Confirmation, { match: 'passwordNew' } );
																</script>
															</td>
														</tr>

														<tr class='break'>
															<td colspan=2>
																<h3><?php echo __($guid, 'System Settings') ?></h3>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='absoluteURL'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td style='width: 275px'>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
                                                				?></span>
															</td>
															<td stclass="right">
																<?php
                                                                    $pageURL = (@$_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
																	$port = '';
																	if ($_SERVER['SERVER_PORT'] != '80') {
																		$port = ':'.$_SERVER['SERVER_PORT'];
																	}
																	$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
																	?>
																<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="<?php echo $pageURL.$_SERVER['SERVER_NAME'].$port.substr($uri_parts[0], 0, -22) ?>" type="text" class="standardWidth">
																<script type="text/javascript">
																	var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
																	<?php echo $row['name'] ?>.add(Validate.Presence);
																	<?php echo $row['name'] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
																</script>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='absolutePath'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
                                                				?></span>
															</td>
															<td stclass="right">
																<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="<?php echo substr(__FILE__, 0, -22) ?>" type="text" class="standardWidth">
																<script type="text/javascript">
																	var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
																	<?php echo $row['name'] ?>.add(Validate.Presence);
																</script>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='systemName'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
                                                				?></span>
															</td>
															<td class="right">
																<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="Gibbon" type="text" class="standardWidth">
																<script type="text/javascript">
																	var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
																	<?php echo $row['name'] ?>.add(Validate.Presence);
																</script>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='installType'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																		echo __($guid, $row['description']);
																	}
                                                				?></span>
															</td>
															<td class="right">
																<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
																	<?php
                                                                    echo "<option selected value='Testing'>Testing</option>";
																	echo "<option value='Production'>Production</option>";
																	echo "<option value='Development'>Development</option>";
																	?>
																					</select>
																				</td>
																			</tr>
																			<?php
																			echo '<tr>';
																	echo '<td colspan=2>';
																	echo "<div id='status' class='warning'>";
																	echo "<div style='width: 100%; text-align: center'>";
																	echo "<img style='margin: 10px 0 5px 0' src='../themes/Default/img/loading.gif' alt='Loading'/><br/>";
																	echo __($guid, 'Checking for Cutting Edge Code.');
																	echo '</div>';
																	echo '</div>';
																echo '</td>';
															echo '</tr>'
                                                        ?>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='cuttingEdgeCode'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php echo __($guid, $row['description']) ?>. <?php echo '<b>'.__($guid, 'Not recommended for non-experts!.').'<b>' ?></span>
															</td>
															<td class="right">
																<select disabled name="<?php echo $row['name'] ?>Disabled" id="<?php echo $row['name'] ?>" class="standardWidth">
																	<?php
                                                                    echo "<option selected value='N'>".ynExpander($guid, 'N').'</option>';
                                                					echo "<option value='Y'>".ynExpander($guid, 'Y').'</option>';?>
																</select>
																<input type='hidden' name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>Hidden" value="N">
															</td>
														</tr>
														<?php
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
														?>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='statsCollection'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
                                               				 	?></span>
															</td>
															<td class="right">
																<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
																	<?php
                                                                    echo "<option value='Y'>".ynExpander($guid, 'Y').'</option>';
																	echo "<option value='N'>".ynExpander($guid, 'N').'</option>';
																	?>
																</select>
															</td>
														</tr>

														<tr class='break'>
															<td colspan=2>
																<h3><?php echo __($guid, 'Organisation Settings') ?></h3>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationName'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
                                               				 	?></span>
															</td>
															<td class="right">
																<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="" type="text" class="standardWidth">
																<script type="text/javascript">
																	var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
																	<?php echo $row['name'] ?>.add(Validate.Presence);
																</script>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationNameShort'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
																?></span>
															</td>
															<td class="right">
																<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="" type="text" class="standardWidth">
																<script type="text/javascript">
																	var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
																	<?php echo $row['name'] ?>.add(Validate.Presence);
																</script>
															</td>
														</tr>
														<tr>
														<?php
                                                        try {
                                                            $data = array();
                                                            $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='currency'";
                                                            $result = $connection2->prepare($sql);
                                                            $result->execute($data);
                                                        } catch (PDOException $e) {
                                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                                        }
														$row = $result->fetch();
														?>
														<td>
															<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
															<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
																?></span>
														</td>
														<td class="right">
															<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
																<optgroup label='--<?php echo __($guid, 'PAYPAL SUPPORTED') ?>--'/>
																	<option value='AUD $'>Australian Dollar (A$)</option>
																	<option value='BRL R$'>Brazilian Real</option>
																	<option value='GBP £'>British Pound (£)</option>
																	<option value='CAD $'>Canadian Dollar (C$)</option>
																	<option value='CZK Kč'>Czech Koruna</option>
																	<option value='DKK kr'>Danish Krone</option>
																	<option value='EUR €'>Euro (€)</option>
																	<option value='HKD $'>Hong Kong Dollar ($)</option>
																	<option value='HUF Ft'>Hungarian Forint</option>
																	<option value='ILS ₪'>Israeli New Shekel</option>
																	<option value='JPY ¥'>Japanese Yen (¥)</option>
																	<option value='MYR RM'>Malaysian Ringgit</option>
																	<option value='MXN $'>Mexican Peso</option>
																	<option value='TWD $'>New Taiwan Dollar</option>
																	<option value='NZD $'>New Zealand Dollar ($)</option>
																	<option value='NOK kr'>Norwegian Krone</option>
																	<option value='PHP ₱'>Philippine Peso</option>
																	<option value='PLN zł'>Polish Zloty</option>
																	<option value='SGD $'>Singapore Dollar ($)</option>
																	<option value='CHF'>Swiss Franc</option>
																	<option value='THB ฿'>Thai Baht</option>
																	<option value='TRY'>Turkish Lira</option>
																	<option value='USD $'>U.S. Dollar ($)</option>
																</optgroup>
																<optgroup label='--<?php echo __($guid, 'OTHERS') ?>--'/>
																	<option value='BDT ó'>Bangladeshi Taka (ó)</option>
																	<option value='BTC'>Bitcoin</option>
                                                                    <option value='BGN лв.'>Bulgarian Lev (лв.)</option>
                                        							<option value='XAF FCFA'>Central African Francs (FCFA)</option>
																	<option value='EGP £'>Egyptian Pound (£)</option>
																	<option value='GHS GH₵'>Ghanaian Cedi (GH₵)</option>
																	<option value='INR ₹'>Indian Rupee (₹)</option>
																	<option value='IDR Rp'>Indonesian Rupiah (Rp)</option>
																	<option value='JMD $'>Jamaican Dollar ($)</option>
																	<option value='KES KSh'>Kenyan Shilling (KSh)</option>
																	<option value='MOP MOP$'>Macanese Pataca (MOP$)</option>
                                                                    <option value='MAD'>Moroccan Dirham (MAD)</option>
                                                                    <option value='MMK K'>Myanmar Kyat (K)</option>
                                                                    <option value='NAD N$'>Namibian Dollar (N$)</option>
                                        							<option value='NPR ₨'>Nepalese Rupee (₨)</option>
																	<option value='NGN ₦'>Nigerian Naira (₦)</option>
																	<option value='PKR ₨'>Pakistani Rupee (₨)</option>
																	<option value='ZAR R'>South African Rand (R)</option>
																	<option value='SAR ﷼‎'>Saudi Riyal (﷼‎)</option>
																	<option value='TZS TSh'>Tanzania Shillings (TSh)</option>
																	<option value='VND ₫‎'>Vietnamese Dong (₫‎)</option>
																</optgroup>
															</select>
														</td>
													</tr>

														<tr class='break'>
															<td colspan=2>
																<h3><?php echo __($guid, 'gibbonedu.com Value-Added Services') ?></h3>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='gibboneduComOrganisationName'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
																?></span>
															</td>
															<td class="right">
																<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=255 value="" type="text" class="standardWidth">
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='gibboneduComOrganisationKey'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
																?></span>
															</td>
															<td class="right">
																<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=255 value="" type="text" class="standardWidth">
															</td>
														</tr>

														<tr class='break'>
															<td colspan=2>
																<h3><?php echo __($guid, 'Miscellaneous') ?></h3>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='country'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
																?></span>
															</td>
															<td class="right">
																<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
																	<?php
                                                                    echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
																	try {
																		$dataSelect = array();
																		$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
																		$resultSelect = $connection2->prepare($sqlSelect);
																		$resultSelect->execute($dataSelect);
																	} catch (PDOException $e) {
																		echo "<div class='error'>".$e->getMessage().'</div>';
																	}
																	while ($rowSelect = $resultSelect->fetch()) {
																		echo "<option value='".$rowSelect['printable_name']."'>".__($guid, $rowSelect['printable_name']).'</option>';
																	}
																	?>
																</select>
																<script type="text/javascript">
																	var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
																	<?php echo $row['name'] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
																</script>
															</td>
														</tr>
														<tr>
															<?php
                                                            try {
                                                                $data = array();
                                                                $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='timezone'";
                                                                $result = $connection2->prepare($sql);
                                                                $result->execute($data);
                                                            } catch (PDOException $e) {
                                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                                            }
															$row = $result->fetch();
															?>
															<td>
																<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
																<span class="emphasis small"><?php if ($row['description'] != '') {
																	echo __($guid, $row['description']);
																}
																?></span>
															</td>
															<td class="right">
																<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="Asia/Hong_Kong" type="text" class="standardWidth">
																<script type="text/javascript">
																	var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
																	<?php echo $row['name'] ?>.add(Validate.Presence);
																</script>
															</td>
														</tr>

														<tr>
															<td>
																<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
															</td>
															<td class="right">
																<input type="hidden" name="code" value="<?php echo $code ?>">
																<input type="hidden" name="databaseServer" value="<?php echo $databaseServer ?>">
																<input type="hidden" name="databaseName" value="<?php echo $databaseName ?>">
																<input type="hidden" name="databaseUsername" value="<?php echo $databaseUsername ?>">
																<input type="hidden" name="databasePassword" value="<?php echo $databasePassword ?>">
																<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
															</td>
														</tr>
													</table>
												</form>
												<?php

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

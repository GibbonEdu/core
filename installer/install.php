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

include "../version.php" ;
include "../functions.php" ;
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
		<meta name="ROBOTS" content="none"/>
		
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
							$databaseServer="" ;
							if (isset($_POST["databaseServer"])) {
								$databaseServer=$_POST["databaseServer"] ;
							}
							$databaseName="" ;
							if (isset($_POST["databaseName"])) {
								$databaseName=$_POST["databaseName"] ;
							}
							$databaseUsername="" ;
							if (isset($_POST["databaseUsername"])) {
								$databaseUsername=$_POST["databaseUsername"] ;
							}
							$databasePassword="" ;
							if (isset($_POST["databasePassword"])) {
								$databasePassword=$_POST["databasePassword"] ;
							}
							$demoData="" ;
							if (isset($_POST["demoData"])) {
								$demoData=$_POST["demoData"] ;
							}
							
							//Get and set step
							$step=0 ;
							if (isset($_GET["step"])) {
								$step=$_GET["step"] ;
							}
							print "<h2>" . sprintf(_('Installation - Step %1$s'), ($step+1)) . "</h2>" ;
							
							//Set language
							$code="en_GB" ;
							if (isset($_POST["code"])) {
								$code=$_POST["code"] ;
							}
							putenv("LC_ALL=" . $code);
							setlocale(LC_ALL, $code);
							bindtextdomain("gibbon", "../i18n");
							textdomain("gibbon");
							
							if ($step==0) { //Choose language
								if (file_exists("../config.php")) { //Make sure system is not already installed
									print "<div class='error'>" ;
										print _("../config.php already exists, which suggests this system is already installed. The installer cannot proceed.") ;
									print "</div>" ;
								}
								else { //No config, so continue installer
									if (is_writable("../")==FALSE) { //Ensure that home directory is writable
										print "<div class='error'>" ;
											print _("The directory containing the Gibbon files is not currently writable, so the installer cannot proceed.") ;
										print "</div>" ;
									}
									else {
										print "<div class='success'>" ;
											print _("The directory containing the Gibbon files is writable, so the installation may proceed.") ;
										print "</div>" ;
										
										//Set language options
										?>
										<form method="post" action="./install.php?step=1">
											<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
												<tr class='break'>
													<td colspan=2> 
														<h3><?php print "Language Settings" ?></h3>
													</td>
												</tr>
												<tr>
													<td style='width: 275px'> 
														<b><?php print "System Language" ?> *</b><br/>
													</td>
													<td class="right">
														<select name="code" id="code" style="width: 302px">
															<option value='en_GB'>English - United Kingdom</option>
															<option value='en_US'>English - United States</option>
															<option value='es_ES'>Español</option>
															<option value='fr_FR'>Français - France</option>
															<option value='it_IT'>Italiano - Italia</option>
															<option value='ro_RO'>Română</option>
															<option value='zh_HK'>體字 - 香港</option>
															<option value='ar_SA'>العربية - المملكة العربية السعودية</option>
														</select>
													</td>
												</tr>
												<tr>
													<td>
														<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
													</td>
													<td class="right">
														<input type="submit" value="<?php print _("Submit") ; ?>">
													</td>
												</tr>
											</table>
										</form>
										<?php
									}
								}
							}
							if ($step==1) { //Set database options
								?>
								<form method="post" action="./install.php?step=2">
									<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
										<tr class='break'>
											<td colspan=2> 
												<h3><?php print _('Database Information') ?></h3>
											</td>
										</tr>
										<tr>
											<td style='width: 275px'> 
												<b><?php print _('Database Type') ?> *</b><br/>
												<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
											</td>
											<td class="right">
												<input readonly name="type" id="type" value="MySQL" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td style='width: 275px'> 
												<b><?php print _('Database Server') ?> *</b><br/>
												<span style="font-size: 90%"><i><?php print _('Localhost, IP address or domain.') ?></i></span>
											</td>
											<td class="right">
												<input name="databaseServer" id="databaseServer" maxlength=255 value="" type="text" style="width: 300px">
												<script type="text/javascript">
													var databaseServer=new LiveValidation('databaseServer');
													databaseServer.add(Validate.Presence);
												</script>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Database Name') ?> *</b><br/>
												<span style="font-size: 90%"><i><?php print _('This database will be created if it does not already exist. Collation should be utf8_general_ci.') ?></i></span>
											</td>
											<td class="right">
												<input name="databaseName" id="databaseName" maxlength=50 value="" type="text" style="width: 300px">
												<script type="text/javascript">
													var databaseName=new LiveValidation('databaseName');
													databaseName.add(Validate.Presence);
												</script>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Database Username') ?>*</b><br/>
											</td>
											<td class="right">
												<input name="databaseUsername" id="databaseUsername" maxlength=50 value="" type="text" style="width: 300px">
												<script type="text/javascript">
													var databaseUsername=new LiveValidation('databaseUsername');
													databaseUsername.add(Validate.Presence);
												</script>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Database Password') ?> *</b><br/>
											</td>
											<td class="right">
												<input name="databasePassword" id="databasePassword" maxlength=255 value="" type="password" style="width: 300px">
												<script type="text/javascript">
													var databasePassword=new LiveValidation('databasePassword');
													databasePassword.add(Validate.Presence);
												</script>
											</td>
										</tr>
										
										<tr>
											<td> 
												<b><?php print _('Install Demo Data?') ?> *</b><br/>
											</td>
											<td class="right">
												<select name="demoData" id="demoData" style="width: 302px">
													<?php
													print "<option selected value='N'>" . ynExpander('N') . "</option>" ;
													print "<option value='Y'>" . ynExpander('Y') . "</option>" ;
													?>			
												</select>
											</td>
										</tr>
										<tr>
											<td>
												<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
											</td>
											<td class="right">
												<input type="hidden" name="code" value="<?php print $code ?>">
												<input type="submit" value="<?php print _("Submit") ; ?>">
											</td>
										</tr>
									</table>
								</form>
								<?php
							}
							else if ($step==2) {
								//Check for db values
								if ($databaseServer=="" OR $databaseName=="" OR $databaseUsername=="" OR $databasePassword=="" OR $demoData=="") {
									print "<div class='error'>" ;
										print sprintf(_('A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", "</a>") ;
									print "</div>" ;
								}
								
								//Estabish db connection without database name
								$connected1=TRUE ;
								try {
									@$connection2=new PDO("mysql:host=$databaseServer;charset=utf8", $databaseUsername, $databasePassword);
									$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
									$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
								}
								catch(PDOException $e) {
									$connected1=FALSE ;
								}
								
								if ($connected1==FALSE) {
									print "<div class='error'>" ;
										print sprintf(_('A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", "</a>") ;
									print "</div>" ;
								}
								else {
									//Create database if needed.
									$databaseNameClean="`".str_replace("`","``",$databaseName)."`";
									
									$connected2=TRUE ;
									try {
										$data=array(); 
										$sql="CREATE DATABASE IF NOT EXISTS $databaseNameClean DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci" ;
										$result=@$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$connected2=FALSE ;
									}
								
									//Use database, to make it active.
									try {
										$data=array(); 
										$sql="USE $databaseNameClean" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$connected2=FALSE ;
									}
									
									if ($connected2==FALSE) {
										print "<div class='error'>" ;
											print sprintf(_('A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", "</a>") ;
										print "</div>" ;
									}
									else {
										print "<div class='success'>" ;
											print _("Your database connection was successful, so the installation may proceed.") ;
										print "</div>" ;
									
										//Set up GUID
										$charList="abcdefghijkmnopqrstuvwxyz023456789";
										$guid="" ;
										for ($i=0;$i<36;$i++) {
											if ($i==9 OR $i==14 OR $i==19 OR $i==24) {
												$guid.="-" ;
											}
											else {
												$guid.=substr($charList, rand(1,strlen($charList)),1);
											}
										}
								
										//Set up config.php
										$config="" ;
										$config.="<?php\n" ;
										$config.="/*\n" ;
										$config.="Gibbon, Flexible & Open School System\n" ;
										$config.="Copyright (C) 2010, Ross Parker\n" ;
										$config.="\n" ;
										$config.="This program is free software: you can redistribute it and/or modify\n" ;
										$config.="it under the terms of the GNU General Public License as published by\n" ;
										$config.="the Free Software Foundation, either version 3 of the License, or\n" ;
										$config.="(at your option) any later version.\n" ;
										$config.="\n" ;
										$config.="This program is distributed in the hope that it will be useful,\n" ;
										$config.="but WITHOUT ANY WARRANTY; without even the implied warranty of\n" ;
										$config.="MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n" ;
										$config.="GNU General Public License for more details.\n" ;
										$config.="\n" ;
										$config.="You should have received a copy of the GNU General Public License\n" ;
										$config.="along with this program.  If not, see <http://www.gnu.org/licenses/>.\n" ;
										$config.="*/\n" ;
										$config.="\n" ;
										$config.="//Sets database connection information\n" ;
										$config.="\$databaseServer=\"" . $databaseServer . "\" ;\n" ; 
										$config.="\$databaseUsername=\"" . $databaseUsername . "\" ;\n" ;
										$config.="\$databasePassword='" . $databasePassword . "' ;\n" ;
										$config.="\$databaseName=\"" . $databaseName . "\" ;\n" ; 
										$config.="\n" ;
										$config.="//Sets globally unique id, to allow multiple installs on the server server.\n" ;
										$config.="\$guid=\"" . $guid . "\" ;\n" ; 
										$config.="\n" ;
										$config.="//Sets system-wide caching factor, used to baalance performance and freshness. Value represents number of page loads between cache refresh. Must be posititve integer. 1 means no caching.\n" ;
										$config.="\$caching=10 ;\n" ; 
										$config.="?>\n" ;
								
										//Write config
										$fp = fopen("../config.php","wb");
										fwrite($fp,$config);
										fclose($fp);
								
										if (file_exists("../config.php")==FALSE) { //Something went wrong, config.php could not be created.
											print "<div class='error'>" ;
												print _("../config.php could not be created, and so the installer cannot proceed.") ;
											print "</div>" ;
										}
										else { //Config, exists, let's press on
											//Let's populate the database
											if (file_exists("../gibbon.sql")==FALSE) {
												print "<div class='error'>" ;
													print _("../gibbon.sql does not exist, and so the installer cannot proceed.") ;
												print "</div>" ;
											}
											else {
												include "./installerFunctions.php" ;
										
												$query=@fread(@fopen("../gibbon.sql", 'r'), @filesize("../gibbon.sql")) or die('Encountered a problem.');
												$query=remove_remarks($query);
												$query=split_sql_file($query, ';');
										
												$i=1;
												$partialFail=FALSE ;
												foreach($query as $sql){
													$i++;
													try {
														$connection2->query($sql) ;
													}
													catch(PDOException $e) {
														$partialFail=TRUE ;
													}
												}
												
										
												if ($partialFail==TRUE) {
													print "<div class='error'>" ;
														print _("Errors occurred in populating the database; empty your database, remove ../config.php and try again.") ;
													print "</div>" ;
												}
												else {
													//Try to install the demo data, report error but don't stop if any issues
													if ($demoData=="Y") {
														if (file_exists("../gibbon_demo.sql")==FALSE) {
															print "<div class='error'>" ;
																print _("../gibbon_demo.sql does not exist, so we will conintue without demo data.") ;
															print "</div>" ;
														}
														else {
															$query=@fread(@fopen("../gibbon_demo.sql", 'r'), @filesize("../gibbon_demo.sql")) or die('Encountered a problem.');
															$query=remove_remarks($query);
															$query=split_sql_file($query, ';');
										
															$i=1;
															$demoFail=FALSE ;
															foreach($query as $sql){
																$i++;
																try {
																	$connection2->query($sql) ;
																}
																catch(PDOException $e) {
																	print $sql . "<br/>" ;
																	print $e->getMessage() . "<br/><br/>" ;
																	$demoFail=TRUE ;
																}
															}
														
															if ($demoFail) {
																print "<div class='error'>" ;
																	print _("There were some issues installing the demo data, but we will conintue anyway.") ;
																print "</div>" ;
															}
														}
													}
												
												
													//Set default language
													try {
														$data=array("code"=>$code); 
														$sql="UPDATE gibboni18n SET systemDefault='Y' WHERE code=:code" ;
														$result=$connection2->prepare($sql);
														$result->execute($data);
													}
													catch(PDOException $e) { }
													try {
														$data=array("code"=>$code); 
														$sql="UPDATE gibboni18n SET systemDefault='N' WHERE NOT code=:code" ;
														$result=$connection2->prepare($sql);
														$result->execute($data);
													}
													catch(PDOException $e) { }
										
													//Let's gather some more information
													?>
													<form method="post" action="./install.php?step=3">
														<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
															<tr class='break'>
																<td colspan=2> 
																	<h3><?php print _('User Account') ?></h3>
																</td>
															</tr>
															<tr>
																<td style='width: 275px'> 
																	<b><?php print _('Title') ?></b><br/>
																</td>
																<td class="right">
																	<select style="width: 302px" name="title">
																		<option value=""></option>
																		<option value="Ms. "><?php print _('Ms.') ?></option>
																		<option value="Miss "><?php print _('Miss') ?></option>
																		<option value="Mr. "><?php print _('Mr.') ?></option>
																		<option value="Mrs. "><?php print _('Mrs.') ?></option>
																		<option value="Dr. "><?php print _('Dr.') ?></option>
																	</select>
																</td>
															</tr>
															<tr>
																<td> 
																	<b><?php print _('Surname') ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php print _('Family name as shown in ID documents.') ?></i></span>
																</td>
																<td class="right">
																	<input name="surname" id="surname" maxlength=30 value="" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var surname=new LiveValidation('surname');
																		surname.add(Validate.Presence);
																	</script>
																</td>
															</tr>
															<tr>
																<td> 
																	<b><?php print _('First Name') ?>*</b><br/>
																	<span style="font-size: 90%"><i><?php print _('First name as shown in ID documents.') ?></i></span>
																</td>
																<td class="right">
																	<input name="firstName" id="firstName" maxlength=30 value="" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var firstName=new LiveValidation('firstName');
																		firstName.add(Validate.Presence);
																	</script>
																</td>
															</tr>
															<tr>
																<td> 
																	<b><?php print _('Email') ?> *</b><br/>
																</td>
																<td class="right">
																	<input name="email" id="email" maxlength=50 value="" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var email=new LiveValidation('email');
																		email.add(Validate.Email);
																		email.add(Validate.Presence);
																	</script>
																</td>
															</tr>
															<tr>
																<td> 
																	<b><?php print _('Receive Support?') ?></b><br/>
																	<span style="font-size: 90%"><i><?php print _('Join our mailing list and recieve a welcome email from the team.') ?></i></span>
																</td>
																<td class="right">
																	<input name="support" id="support" value="true" type="checkbox">
																</td>
															</tr>
															<tr>
																<td> 
																	<b><?php print _('Username') ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php print _('Must be unique. System login name. Cannot be changed.') ?></i></span>
																</td>
																<td class="right">
																	<input name="username" id="username" maxlength=20 value="" type="text" style="width: 300px">
																	<?php
																	$idList="" ;
																	try {
																		$dataSelect=array(); 
																		$sqlSelect="SELECT username FROM gibbonPerson ORDER BY username" ;
																		$resultSelect=$connection2->prepare($sqlSelect);
																		$resultSelect->execute($dataSelect);
																	}
																	catch(PDOException $e) { }
																	while ($rowSelect=$resultSelect->fetch()) {
																		$idList.="'" . $rowSelect["username"]  . "'," ;
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
																	$policy=getPasswordPolicy($connection2) ;
																	if ($policy!=FALSE) {
																		print "<div class='warning'>" ;
																			print $policy ;
																		print "</div>" ;
																	}
																	?>
																</td>
															</tr>
															<tr>
																<td> 
																	<b><?php print _('Password') ?> *</b><br/>
																	<span style="font-size: 90%"><i></i></span>
																</td>
																<td class="right">
																	<input name="password" id="password" maxlength=30 value="" type="password" style="width: 300px">
																	<script type="text/javascript">
																		var password=new LiveValidation('password');
																		password.add(Validate.Presence);
																		<?php
																		$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
																		if ($alpha=="Y") {
																			print "password.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
																		}
																		$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
																		if ($numeric=="Y") {
																			print "password.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
																		}
																		$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
																		if ($punctuation=="Y") {
																			print "password.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
																		}
																		$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
																		if (is_numeric($minLength)) {
																			print "password.add( Validate.Length, { minimum: " . $minLength . "} );" ;
																		}
																		?>
																	</script>
																</td>
															</tr>
															<tr>
																<td> 
																	<b><?php print _('Confirm Password') ?> *</b><br/>
																	<span style="font-size: 90%"><i></i></span>
																</td>
																<td class="right">
																	<input name="passwordConfirm" id="passwordConfirm" maxlength=20 value="" type="password" style="width: 300px">
																	<script type="text/javascript">
																		var passwordConfirm=new LiveValidation('passwordConfirm');
																		passwordConfirm.add(Validate.Presence);
																		passwordConfirm.add(Validate.Confirmation, { match: 'password' } );
																	</script>
																</td>
															</tr>
														
															<tr class='break'>
																<td colspan=2> 
																	<h3><?php print _('System Settings') ?></h3>
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='absoluteURL'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td style='width: 275px'> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td stclass="right">
																	<?php $pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://"; ?>
																	<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print substr(($pageURL.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]),0,-29) ?>" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
																		<?php print $row["name"] ?>.add(Validate.Presence);
																		<?php print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
																	</script> 
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='absolutePath'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td stclass="right">
																	<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print substr(__FILE__,0,-22) ?>" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
																		<?php print $row["name"] ?>.add(Validate.Presence);
																	</script> 
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='systemName'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="Gibbon" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
																		<?php print $row["name"] ?>.add(Validate.Presence);
																	</script> 
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='installType'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
																		<?php
																		print "<option selected value='Testing'>Testing</option>" ;
																		print "<option value='Production'>Production</option>" ;
																		print "<option value='Development'>Development</option>" ;
																		?>			
																	</select>
																</td>
															</tr>
															<?php
															print "<tr>" ;
																print "<td colspan=2>" ;
																	print "<div id='status' class='warning'>" ;
																		print "<div style='width: 100%; text-align: center'>" ;
																			print "<img style='margin: 10px 0 5px 0' src='../themes/Default/img/loading.gif' alt='Loading'/><br/>" ;
																			print _("Checking for Cutting Edge Code.") ;
																		print "</div>" ;
																	print "</div>" ;
																print "</td>" ;
															print "</tr>"
															?>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='cuttingEdgeCode'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php print _($row["description"]) ?>. <?php print "<b>" . _('Not recommended for non-experts!.') . "<b>" ?></i></span>
																</td>
																<td class="right">
																	<select disabled name="<?php print $row["name"] ?>Disabled" id="<?php print $row["name"] ?>" style="width: 302px">
																		<?php
																		print "<option selected value='N'>" . ynExpander('N') . "</option>" ;
																		print "<option value='Y'>" . ynExpander('Y') . "</option>" ;
																		?>			
																	</select>
																	<input type='hidden' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>Hidden" value="N">
																</td>
															</tr>
															<?php
															//Check and set cutting edge code based on gibbonedu.org services value
															print "<script type=\"text/javascript\">" ;
																print "$(document).ready(function(){" ;
																	print "$.ajax({" ;
																		print "crossDomain: true, type:\"GET\", contentType: \"application/json; charset=utf-8\",async:false," ;
																		print "url: \"https://gibbonedu.org/services/version/devCheck.php?version=" . $version . "&callback=?\"," ;
																		print "data: \"\",dataType: \"jsonp\", jsonpCallback: 'fnsuccesscallback',jsonpResult: 'jsonpResult'," ;
																		print "success: function(data) {" ;
																			print "$(\"#status\").attr(\"class\",\"success\");" ;
																			print "if (data['status']==='false') {" ;
																				print "$(\"#status\").html('" . _('Cutting Edge Code check successful.') . "') ;" ;
																			print "}" ;
																			print "else {" ;
																				print "$(\"#status\").html('" . _('Cutting Edge Code check successful.') . "') ;" ;
																				print "$(\"#cuttingEdgeCode\").val('Y');" ;
																				print "$(\"#cuttingEdgeCodeHidden\").val('Y');" ;
																			print "}" ;
																		print "}," ;
																		print "error: function (data, textStatus, errorThrown) {" ;
																			print "$(\"#status\").attr(\"class\",\"error\");" ;
																				print "$(\"#status\").html('" . _('Cutting Edge Code check failed') . ".') ;" ;
																		print "}" ;
																	print "});" ;
																print "});" ;
															print "</script>" ;
															?>
														
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='statsCollection'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
																		<?php
																		print "<option value='Y'>" . ynExpander('Y') . "</option>" ;
																		print "<option value='N'>" . ynExpander('N') . "</option>" ;
																		?>			
																	</select>
																</td>
															</tr>
		
															<tr class='break'>
																<td colspan=2> 
																	<h3><?php print _('Organisation Settings') ?></h3>
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationName'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
																		<?php print $row["name"] ?>.add(Validate.Presence);
																	</script> 
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='organisationNameShort'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
																		<?php print $row["name"] ?>.add(Validate.Presence);
																	</script> 
																</td>
															</tr>
															<tr>
															<?php
															try {
																$data=array(); 
																$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='currency'" ;
																$result=$connection2->prepare($sql);
																$result->execute($data);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															$row=$result->fetch() ;
															?>
															<td> 
																<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
															</td>
															<td class="right">
																<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
																	<optgroup label='--<?php print _('PAYPAL SUPPORTED') ?>--'/>
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
																	<optgroup label='--<?php print _('OTHERS') ?>--'/>
																		<option value='BDT ó'>Bangladeshi Taka (ó)</option>
																		<option value='BTC'>Bitcoin</option>
																		<option value='XAF FCFA'>Central African Francs (FCFA)</option>
																		<option value='EGP £'>Egyptian Pound (£)</option>
																		<option value='INR ₹'>Indian Rupee (₹)</option>
																		<option value='IDR Rp'>Indonesian Rupiah (Rp)</option>
																		<option value='KES KSh'>Kenyan Shilling (KSh)</option>
																		<option value='NPR ₨'>Nepalese Rupee (₨)</option>
																		<option value='NGN ₦'>Nigerian Naira (₦)</option>
																		<option value='SAR ﷼‎'>Saudi Riyal (﷼‎)</option>
																		<option value='VND ₫‎'>Vietnamese Dong (₫‎)</option>
																	</optgroup>
																</select>
															</td>
														</tr>
														
															<tr class='break'>
																<td colspan=2> 
																	<h3><?php print _('gibbonedu.com Value-Added Services') ?></h3>
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='gibboneduComOrganisationName'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?></b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="" type="text" style="width: 300px">
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='gibboneduComOrganisationKey'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?></b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="" type="text" style="width: 300px">
																</td>
															</tr>
			
															<tr class='break'>
																<td colspan=2> 
																	<h3><?php print _('Miscellaneous') ?></h3>
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='country'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
																		<?php
																		print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
																		try {
																			$dataSelect=array(); 
																			$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
																			$resultSelect=$connection2->prepare($sqlSelect);
																			$resultSelect->execute($dataSelect);
																		}
																		catch(PDOException $e) { 
																			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																		}
																		while ($rowSelect=$resultSelect->fetch()) {
																			print "<option value='" . $rowSelect["printable_name"] . "'>" . _($rowSelect["printable_name"]) . "</option>" ;
																		}
																		?>
																	</select>
																	<script type="text/javascript">
																		var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
																		<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
																	</script>
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='timezone'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="Asia/Hong_Kong" type="text" style="width: 300px">
																	<script type="text/javascript">
																		var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
																		<?php print $row["name"] ?>.add(Validate.Presence);
																	</script> 
																</td>
															</tr>
															<tr>
																<?php
																try {
																	$data=array(); 
																	$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='primaryAssessmentScale'" ;
																	$result=$connection2->prepare($sql);
																	$result->execute($data);
																}
																catch(PDOException $e) { 
																	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																}
																$row=$result->fetch() ;
																?>
																<td> 
																	<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
																	<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
																</td>
																<td class="right">
																	<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
																		<?php
																		print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
																		try {
																			$dataSelect=array(); 
																			$sqlSelect="SELECT * FROM gibbonScale WHERE active='Y' ORDER BY name" ;
																			$resultSelect=$connection2->prepare($sqlSelect);
																			$resultSelect->execute($dataSelect);
																		}
																		catch(PDOException $e) { 
																			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																		}
																		while ($rowSelect=$resultSelect->fetch()) {
																			print "<option value='" . $rowSelect["gibbonScaleID"] . "'>" . _($rowSelect["name"]) . "</option>" ;
																		}
																		?>			
																	</select>
																	<script type="text/javascript">
																		var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
																		<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
																	</script>
																</td>
															</tr>
			
															<tr>
																<td>
																	<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
																</td>
																<td class="right">
																	<input type="hidden" name="code" value="<?php print $code ?>">
																	<input type="hidden" name="databaseServer" value="<?php print $databaseServer ?>">
																	<input type="hidden" name="databaseName" value="<?php print $databaseName ?>">
																	<input type="hidden" name="databaseUsername" value="<?php print $databaseUsername ?>">
																	<input type="hidden" name="databasePassword" value="<?php print $databasePassword ?>">
																	<input type="submit" value="<?php print _("Submit") ; ?>">
																</td>
															</tr>
														</table>
													</form>
													<?php
												}
											}
										}
									}
								}  
							}
							else if ($step==3) {
								$connected3=TRUE ;
								try {
									$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
									$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
									$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
								}
								catch(PDOException $e) {
									$connected3=FALSE ;
									print "<div class='error'>" ;
										print sprintf(_('A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", "</a>") ;
									print "</div>" ;
								}
								
								if ($connected3) {
									//Get user account details
									$title=$_POST["title"] ; 	
									$surname=$_POST["surname"] ;
									$firstName=$_POST["firstName"] ;
									$preferredName=$_POST["firstName"] ;
									$username=$_POST["username"] ;
									$password=$_POST["password"] ;
									$passwordConfirm=$_POST["passwordConfirm"] ;
									$email=$_POST["email"] ;
									$support=FALSE ;
									if (isset($_POST["support"])) {
										if ($_POST["support"]=="true") {
											$support=TRUE ;
										}
									}
									
									//Get system settings
									$absoluteURL=$_POST["absoluteURL"] ; 	
									$absolutePath=$_POST["absolutePath"] ; 	
									$systemName=$_POST["systemName"] ;
									$organisationName=$_POST["organisationName"] ;
									$organisationNameShort=$_POST["organisationNameShort"] ;
									$currency=$_POST["currency"] ;
									$timezone=$_POST["timezone"] ;
									$country=$_POST["country"] ;
									$primaryAssessmentScale=$_POST["primaryAssessmentScale"] ;
									$installType=$_POST["installType"] ;
									$statsCollection=$_POST["statsCollection"] ;
									$cuttingEdgeCode=$_POST["cuttingEdgeCode"] ;
									$gibboneduComOrganisationName=$_POST["gibboneduComOrganisationName"] ;
									$gibboneduComOrganisationKey=$_POST["gibboneduComOrganisationKey"] ;
								
									if ($surname=="" OR $firstName=="" OR $preferredName=="" OR $email=="" OR $username=="" OR $password=="" OR $passwordConfirm=="" OR $email=="" OR $absoluteURL=="" OR $absolutePath=="" OR $systemName=="" OR $organisationName=="" OR $organisationNameShort=="" OR $timezone=="" OR $country=="" OR $primaryAssessmentScale=="" OR $installType=="" OR $statsCollection=="" OR $cuttingEdgeCode=="") {
										print "<div class='error'>" ;
											print _("Some required fields have not been set, and so installation cannot proceed.") ;
										print "</div>" ;
									}
									else {
										//Check passwords for match
										if ($password!=$passwordConfirm) {
											print "<div class='error'>" ;
												print _("Your request failed because your passwords did not match.") ;
											print "</div>" ;
										}
										else {
											$salt=getSalt() ;
											$passwordStrong=hash("sha256", $salt.$password) ;
											
											$userFail=false ;
											//Write to database
											try {
												$data=array("title"=>$title, "surname"=>$surname, "firstName"=>$firstName, "preferredName"=>$preferredName, "officialName"=>($firstName . " " . $surname), "username"=>$username, "passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "status"=>'Full', "canLogin"=>'Y', "passwordForceReset"=>'N', "gibbonRoleIDPrimary"=>"001", "gibbonRoleIDAll"=>"001", "email"=>$email) ;
												$sql="INSERT INTO gibbonPerson SET gibbonPersonID=1, title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, status=:status, canLogin=:canLogin, passwordForceReset=:passwordForceReset, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, email=:email" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) { 
												$userFail=true ;
												print "<div class='error'>" ;
													print sprintf(_('Errors occurred in populating the database; empty your database, remove ../config.php and %1$stry again%2$s.'), "<a href='./install.php'>", "</a>") ;
												print "</div>" ;
											}
											
											try {
												$dataStaff=array("gibbonPersonID"=>1, "type"=>'Teaching') ;
												$sqlStaff="INSERT INTO gibbonStaff SET gibbonPersonID=1, type='Teaching'" ;
												$resultStaff=$connection2->prepare($sqlStaff);
												$resultStaff->execute($dataStaff);
											}
											catch(PDOException $e) { }
											
											if ($userFail==false) {
												$settingsFail=FALSE ;		
												try {
													$data=array("absoluteURL"=>$absoluteURL); 
													$sql="UPDATE gibbonSetting SET value=:absoluteURL WHERE scope='System' AND name='absoluteURL'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("absolutePath"=>$absolutePath); 
													$sql="UPDATE gibbonSetting SET value=:absolutePath WHERE scope='System' AND name='absolutePath'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("systemName"=>$systemName); 
													$sql="UPDATE gibbonSetting SET value=:systemName WHERE scope='System' AND name='systemName'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("organisationName"=>$organisationName); 
													$sql="UPDATE gibbonSetting SET value=:organisationName WHERE scope='System' AND name='organisationName'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("organisationNameShort"=>$organisationNameShort); 
													$sql="UPDATE gibbonSetting SET value=:organisationNameShort WHERE scope='System' AND name='organisationNameShort'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
												
												try {
													$data=array("currency"=>$currency); 
													$sql="UPDATE gibbonSetting SET value=:currency WHERE scope='System' AND name='currency'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$fail=TRUE ;
												}
	
												try {
													$data=array("organisationAdministrator"=>1); 
													$sql="UPDATE gibbonSetting SET value=:organisationAdministrator WHERE scope='System' AND name='organisationAdministrator'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
												
												try {
													$data=array("organisationDBA"=>1); 
													$sql="UPDATE gibbonSetting SET value=:organisationDBA WHERE scope='System' AND name='organisationDBA'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
												
												try {
													$data=array("organisationAdmissions"=>1); 
													$sql="UPDATE gibbonSetting SET value=:organisationAdmissions WHERE scope='System' AND name='organisationAdmissions'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
												
												try {
													$data=array("country"=>$country); 
													$sql="UPDATE gibbonSetting SET value=:country WHERE scope='System' AND name='country'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("gibboneduComOrganisationName"=>$gibboneduComOrganisationName); 
													$sql="UPDATE gibbonSetting SET value=:gibboneduComOrganisationName WHERE scope='System' AND name='gibboneduComOrganisationName'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("gibboneduComOrganisationKey"=>$gibboneduComOrganisationKey); 
													$sql="UPDATE gibbonSetting SET value=:gibboneduComOrganisationKey WHERE scope='System' AND name='gibboneduComOrganisationKey'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("timezone"=>$timezone); 
													$sql="UPDATE gibbonSetting SET value=:timezone WHERE scope='System' AND name='timezone'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("primaryAssessmentScale"=>$primaryAssessmentScale); 
													$sql="UPDATE gibbonSetting SET value=:primaryAssessmentScale WHERE scope='System' AND name='primaryAssessmentScale'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("installType"=>$installType); 
													$sql="UPDATE gibbonSetting SET value=:installType WHERE scope='System' AND name='installType'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
	
												try {
													$data=array("statsCollection"=>$statsCollection); 
													$sql="UPDATE gibbonSetting SET value=:statsCollection WHERE scope='System' AND name='statsCollection'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
										
												if ($statsCollection=="Y") {
													$absolutePathProtocol="" ;
													$absolutePath="" ;
													if (substr($absoluteURL,0,7)=="http://") {
														$absolutePathProtocol="http" ;
														$absolutePath=substr($absoluteURL,7) ;
													}
													else if (substr($absoluteURL,0,8)=="https://") {
														$absolutePathProtocol="https" ;
														$absolutePath=substr($absoluteURL,8) ;
													}
													print "<iframe style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/tracker/tracker.php?absolutePathProtocol=" . urlencode($absolutePathProtocol) . "&absolutePath=" . urlencode($absolutePath) . "&organisationName=" . urlencode($organisationName) . "&type=" . urlencode($installType) . "&version=" . urlencode($version) . "&country=" . $country . "&usersTotal=1&usersFull=1'></iframe>" ;
												}
									
												try {
													$data=array("cuttingEdgeCode"=>$cuttingEdgeCode); 
													$sql="UPDATE gibbonSetting SET value=:cuttingEdgeCode WHERE scope='System' AND name='cuttingEdgeCode'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$settingsFail=TRUE ;
												}
												if ($cuttingEdgeCode=="Y") {
													include "../CHANGEDB.php" ;
													$sqlTokens=explode(";end", $sql[(count($sql))][1]) ;
													$versionMaxLinesMax=(count($sqlTokens)-1) ;
													$tokenCount=0 ;
													try {
														$data=array("cuttingEdgeCodeLine"=>$versionMaxLinesMax); 
														$sql="UPDATE gibbonSetting SET value=:cuttingEdgeCodeLine WHERE scope='System' AND name='cuttingEdgeCodeLine'" ;
														$result=$connection2->prepare($sql);
														$result->execute($data);
													}
													catch(PDOException $e) { }
													
													foreach ($sqlTokens AS $sqlToken) {
														if ($tokenCount<=$versionMaxLinesMax) { //Decide whether this has been run or not
															if (trim($sqlToken)!="") { 
																try {
																	$result=$connection2->query($sqlToken);
																}
																catch(PDOException $e) { 
																	$partialFail=TRUE;
																}
															}
														}
														$tokenCount++ ;
													}
												}
												
												//Deal with request to receive welcome email by calling gibbonedu.org iframe
												if ($support==TRUE) {
													$absolutePathProtocol="" ;
													$absolutePath="" ;
													if (substr($absoluteURL,0,7)=="http://") {
														$absolutePathProtocol="http" ;
														$absolutePath=substr($absoluteURL,7) ;
													}
													else if (substr($absoluteURL,0,8)=="https://") {
														$absolutePathProtocol="https" ;
														$absolutePath=substr($absoluteURL,8) ;
													}
													print "<iframe class='support' style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/support/supportRegistration.php?absolutePathProtocol=" . urlencode($absolutePathProtocol) . "&absolutePath=" . urlencode($absolutePath) . "&organisationName=" . urlencode($organisationName) . "&email=" . urlencode($email) . "&title=" . urlencode($title) . "&surname=" . urlencode($surname) . "&preferredName=" . urlencode($preferredName) . "'></iframe>" ;
												}
																							
												if ($settingsFail==TRUE) {
													print "<div class='error'>" ;
														print sprintf(_('Some settings did not save. The system may work, but you may need to remove everything and start again. Try and %1$sgo to your Gibbon homepage%2$s and login as user <u>admin</u> with password <u>gibbon</u>.'), "<a href='$absoluteURL'>", "</a>") ;
														print "<br/><br/>" ; 
														print sprintf(_('It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", "</a>") ;
													print "</div>" ;
												}
												else {
													print "<div class='success'>" ;
														print sprintf(_('Congratulations, your installation is complete. Feel free to %1$sgo to your Gibbon homepage%2$s and login with the username and password you created.'), "<a href='$absoluteURL'>", "</a>") ;
														print "<br/><br/>" ; 
														print sprintf(_('It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", "</a>") ;
													print "</div>" ;
												}
											}
										}
									}
								}
							}
						
						?>
					</div>		
					<div id="sidebar">
						<h2><?php print _('Welcome To Gibbon') ?></h2>
						<p style='padding-top: 7px'>
						<?php print _('Created by teachers, Gibbon is the school platform which solves real problems faced by educators every day.') ?><br/>
						<br/>
						<?php print _('Free, open source and flexible, Gibbon can morph to meet the needs of a huge range of schools.') ?><br/>
						<br/>
						<?php print sprintf(_('For support, please visit %1$sgibbonedu.org%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support'>", "</a>") ?>
						</p>
					</div>
					<br style="clear: both">
				</div>
				<div id="footer">
					<?php print _("Powered by") ?> <a href="http://gibbonedu.org">Gibbon</a> v<?php print $version ?> &#169; <a href="http://rossparker.org">Ross Parker</a> 2010-<?php print date("Y") ?><br/>
					<span style='font-size: 90%; '>
						<?php print _("Created under the") ?> <a href="http://www.gnu.org/licenses/gpl.html">GNU GPL</a> at <a href='http://www.ichk.edu.hk'>ICHK</a>
					</span><br/>
					<img style='z-index: 100; margin-bottom: -57px; margin-right: -50px' alt='Logo Small' src='../themes/Default/img/logoFooter.png'/>
				</div>
			</div>
		</div>
	</body>
</html>

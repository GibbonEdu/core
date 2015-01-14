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

//Gibbon system-wide includes
if (file_exists("./config.php")) {
	include "./config.php" ;
}
else { //no config, so go to installer
	$URL="./installer/install.php" ;
	header("Location: {$URL}");
}
include "./functions.php" ;
include "./version.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Deal with caching
if (isset($_SESSION[$guid]["pageLoads"])) {
	$_SESSION[$guid]["pageLoads"]++ ;
}
else {
	$_SESSION[$guid]["pageLoads"]=0 ;
}
$cacheLoad=FALSE ;
if ($caching>0 AND is_numeric($caching)) {
	if ($_SESSION[$guid]["pageLoads"]%$caching==0) {
		$cacheLoad=TRUE ;
	}
}

//Check for cutting edge code
if (isset($_SESSION[$guid]["cuttingEdgeCode"])==FALSE) {
	$_SESSION[$guid]["cuttingEdgeCode"]=getSettingByScope($connection2, "System", "cuttingEdgeCode") ;
}

//Set sidebar values (from the entrySidebar field in gibbonAction and from $_GET variable)
$_SESSION[$guid]["sidebarExtra"]="" ;
$_SESSION[$guid]["sidebarExtraPosition"]="" ;
if (isset($_GET["sidebar"])) {
	$sidebar=$_GET["sidebar"] ;
}
else {
	$sidebar="" ;
}


//Deal with address param q
if (isset($_GET["q"])) {
	$_SESSION[$guid]["address"]=$_GET["q"] ;
}
else {
	$_SESSION[$guid]["address"]="" ;
}
$_SESSION[$guid]["module"]=getModuleName($_SESSION[$guid]["address"]) ;
$_SESSION[$guid]["action"]=getActionName($_SESSION[$guid]["address"]) ;
$q=NULL ;
if (isset($_GET["q"])) {
	$q=$_GET["q"] ;
}


//Check to see if system settings are set from databases
if (@$_SESSION[$guid]["systemSettingsSet"]==FALSE) {
	getSystemSettings($guid, $connection2) ;
}

//Set up for i18n via gettext
if (isset($_SESSION[$guid]["i18n"]["code"])) {
	if ($_SESSION[$guid]["i18n"]["code"]!=NULL) {
		putenv("LC_ALL=" . $_SESSION[$guid]["i18n"]["code"]);
		setlocale(LC_ALL, $_SESSION[$guid]["i18n"]["code"]);
		bindtextdomain("gibbon", "./i18n");
		textdomain("gibbon");
		bind_textdomain_codeset("gibbon", 'UTF-8');
	}
}

//Try to autoset user's calendar feed if not set already
if (isset($_SESSION[$guid]["calendarFeedPersonal"]) AND isset($_SESSION[$guid]['googleAPIAccessToken'])) {
	if ($_SESSION[$guid]["calendarFeedPersonal"]=="" AND $_SESSION[$guid]['googleAPIAccessToken']!=NULL) {
		require_once $_SESSION[$guid]["absolutePath"] . '/lib/google/google-api-php-client/autoload.php';
		$client2=new Google_Client();
		$client2->setAccessToken($_SESSION[$guid]['googleAPIAccessToken']);
		$service=new Google_Service_Calendar($client2);
		$calendar=$service->calendars->get('primary');
	
		if ($calendar["id"]!="") {
			try {
				$dataCalendar=array("calendarFeedPersonal"=>$calendar["id"], "gibbonPersonID"=>$_SESSION[$guid]['gibbonPersonID']); 
				$sqlCalendar="UPDATE gibbonPerson SET calendarFeedPersonal=:calendarFeedPersonal WHERE gibbonPersonID=:gibbonPersonID";
				$resultCalendar=$connection2->prepare($sqlCalendar);
				$resultCalendar->execute($dataCalendar); 
			}
			catch(PDOException $e) { }
			$_SESSION[$guid]["calendarFeedPersonal"]=$calendar["id"] ;
		}
	}
}

//Check for force password reset flag
if (isset($_SESSION[$guid]["passwordForceReset"])) {
	if ($_SESSION[$guid]["passwordForceReset"]=="Y" AND $q!="preferences.php") {
		$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=preferences.php" ;
		$URL=$URL. "&forceReset=Y" ;
		header("Location: {$URL}") ;
		break ;
	}
}

if ($_SESSION[$guid]["address"]!="" AND $sidebar!=true) {
	try {
		$dataSidebar=array("action"=>"%" . $_SESSION[$guid]["action"] . "%", "name"=>$_SESSION[$guid]["module"]); 
		$sqlSidebar="SELECT gibbonAction.name FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonAction.URLList LIKE :action AND entrySidebar='N' AND gibbonModule.name=:name" ;
		$resultSidebar=$connection2->prepare($sqlSidebar);
		$resultSidebar->execute($dataSidebar); 
	}
	catch(PDOException $e) { }
	if ($resultSidebar->rowCount()>0) {
		$sidebar="false" ;
	}
}

//If still false, show warning, otherwise display page
if ($_SESSION[$guid]["systemSettingsSet"]==FALSE) {
	print _("System Settings are not set: the system cannot be displayed") ;
}
else {
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>
				<?php 
				print $_SESSION[$guid]["organisationNameShort"] . " - " . $_SESSION[$guid]["systemName"] ;
				if ($_SESSION[$guid]["address"]!="") {
					if (strstr($_SESSION[$guid]["address"],"..")==FALSE) {
						if (getModuleName($_SESSION[$guid]["address"])!="") {
							print " - " . getModuleName($_SESSION[$guid]["address"]) ;
						}
					}
				}
				?>
			</title>
			<meta charset="utf-8"/>
			<meta name="author" content="Ross Parker, International College Hong Kong"/>
			<meta name="ROBOTS" content="none"/>
			
			<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>
			<script type="text/javascript" src="./lib/LiveValidation/livevalidation_standalone.compressed.js"></script>

			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery/jquery.js"></script>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-ui/js/jquery-ui.min.js"></script>
			<?php 
			if (isset($_SESSION[$guid]["i18n"]["code"])) {
				if (is_file($_SESSION[$guid]["absolutePath"] . "/lib/jquery-ui/i18n/jquery.ui.datepicker-" . substr($_SESSION[$guid]["i18n"]["code"],0,2) . ".js")) {
					print "<script type='text/javascript' src='" . $_SESSION[$guid]["absoluteURL"] . "/lib/jquery-ui/i18n/jquery.ui.datepicker-" .  substr($_SESSION[$guid]["i18n"]["code"],0,2) . ".js'></script>" ;
					print "<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional['" .  substr($_SESSION[$guid]["i18n"]["code"],0,2) . "']);</script>" ;
				}
				else if (is_file($_SESSION[$guid]["absolutePath"] . "/lib/jquery-ui/i18n/jquery.ui.datepicker-" . str_replace("_","-",$_SESSION[$guid]["i18n"]["code"]) . ".js")) {
					print "<script type='text/javascript' src='" . $_SESSION[$guid]["absoluteURL"] . "/lib/jquery-ui/i18n/jquery.ui.datepicker-" .  str_replace("_","-",$_SESSION[$guid]["i18n"]["code"]) . ".js'></script>" ;
					print "<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional['" .  str_replace("_","-",$_SESSION[$guid]["i18n"]["code"]) . "']);</script>" ;
				}
			}
			
			?>
			<script type="text/javascript">$(function() { $( document ).tooltip({  show: 800, hide: false, content: function () { return $(this).prop('title')}, position: { my: "center bottom-20", at: "center top", using: function( position, feedback ) { $( this ).css( position ); $( "<div>" ).addClass( "arrow" ).addClass( feedback.vertical ).addClass( feedback.horizontal ).appendTo( this ); } } }); });</script>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-jslatex/jquery.jslatex.js"></script>
			<script type="text/javascript">$(function () { $(".latex").latex();});</script>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-form/jquery.form.js"></script>
			<link rel="stylesheet" href="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-ui/css/blitzer/jquery-ui.css" type="text/css" media="screen" />
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/chained/jquery.chained.mini.js"></script>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/thickbox/thickbox-compressed.js"></script>
			<script type="text/javascript"> var tb_pathToImage="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/thickbox/loadingAnimation.gif"</script>
			<link rel="stylesheet" href="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/thickbox/thickbox.css" type="text/css" media="screen" />
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-autosize/jquery.autosize.min.js"></script>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-sessionTimeout/jquery.sessionTimeout.min.js"></script>
			<?php
			if (isset($_SESSION[$guid]["username"])) {
				$sessionDuration=getSettingByScope($connection2, "System", "sessionDuration") ;
				if (is_numeric($sessionDuration)==FALSE) {
					$sessionDuration=1200 ;
				}
				if ($sessionDuration<1200) {
					$sessionDuration=1200 ;
				}
				?>
				<script type="text/javascript">
					$(document).ready(function(){
						$.sessionTimeout({
							message: '<?php print _("Your session is about to expire: you will be logged out shortly.") ?>',
							keepAliveUrl: 'keepAlive.php' ,
							redirUrl: 'logout.php?timeout=true', 
							logoutUrl: 'logout.php' , 
							warnAfter: <?php print ($sessionDuration*1000) ?>,
							redirAfter: <?php print ($sessionDuration*1000)+600000 ?>
			 			});
					});
				</script>
			<?php
			}
			//Set theme
			if ($cacheLoad OR $_SESSION[$guid]["themeCSS"]=="" OR isset($_SESSION[$guid]["themeJS"])==FALSE OR $_SESSION[$guid]["gibbonThemeID"]=="" OR $_SESSION[$guid]["gibbonThemeName"]=="") {
				$_SESSION[$guid]["themeCSS"]="<link rel='stylesheet' type='text/css' href='./themes/Default/css/main.css' />" ;
				if ($_SESSION[$guid]["i18n"]["rtl"]=="Y") {
					$_SESSION[$guid]["themeCSS"].="<link rel='stylesheet' type='text/css' href='./themes/Default/css/main_rtl.css' />" ;
				}
				$_SESSION[$guid]["themeJS"]="<script type='text/javascript' src='./themes/Default/js/common.js'></script>" ;
				$_SESSION[$guid]["gibbonThemeID"]="001" ;
				$_SESSION[$guid]["gibbonThemeName"]="Default" ;
				$_SESSION[$guid]["gibbonThemeAuthor"]="" ;
				$_SESSION[$guid]["gibbonThemeURL"]="" ;
				try {
					if (isset($_SESSION[$guid]["gibbonThemeIDPersonal"])) {
						$dataTheme=array("gibbonThemeIDPersonal"=>$_SESSION[$guid]["gibbonThemeIDPersonal"]); 
						$sqlTheme="SELECT * FROM gibbonTheme WHERE gibbonThemeID=:gibbonThemeIDPersonal" ;
					}
					else {
						$dataTheme=array(); 
						$sqlTheme="SELECT * FROM gibbonTheme WHERE active='Y'" ;
					}
					$resultTheme=$connection2->prepare($sqlTheme);
					$resultTheme->execute($dataTheme);
					if ($resultTheme->rowCount()==1) {
						$rowTheme=$resultTheme->fetch() ;
						$_SESSION[$guid]["themeCSS"]="<link rel='stylesheet' type='text/css' href='./themes/" . $rowTheme["name"] . "/css/main.css' />" ;
						if ($_SESSION[$guid]["i18n"]["rtl"]=="Y") {
							$_SESSION[$guid]["themeCSS"].="<link rel='stylesheet' type='text/css' href='./themes/" . $rowTheme["name"] . "/css/main_rtl.css' />" ;
						}
						$_SESSION[$guid]["themeJS"]="<script type='text/javascript' src='./themes/" . $rowTheme["name"] . "/js/common.js'></script>" ;
						$_SESSION[$guid]["gibbonThemeID"]=$rowTheme["gibbonThemeID"] ;
						$_SESSION[$guid]["gibbonThemeName"]=$rowTheme["name"] ;
						$_SESSION[$guid]["gibbonThemeAuthor"]=$rowTheme["author"] ;
						$_SESSION[$guid]["gibbonThemeURL"]=$rowTheme["url"] ;
					}
				}
				catch(PDOException $e) {
					print "<div class='error'>" ;
						print $e->getMessage();
					print "</div>" ;
				}
			}
			
			print $_SESSION[$guid]["themeCSS"] ;
			print $_SESSION[$guid]["themeJS"] ;
			
			//Set module CSS & JS
			if (isset($_GET["q"])) {
				$moduleCSS="<link rel='stylesheet' type='text/css' href='./modules/" . $_SESSION[$guid]["module"] . "/css/module.css' />" ;
				$moduleJS="<script type='text/javascript' src='./modules/" . $_SESSION[$guid]["module"] . "/js/module.js'></script>" ;
				print $moduleCSS ;
				print $moduleJS ;
			}
			
			//Set personalised background, if permitted
			if ($personalBackground=getSettingByScope($connection2, "User Admin", "personalBackground")=="Y" AND isset($_SESSION[$guid]["personalBackground"])) {
				if ($_SESSION[$guid]["personalBackground"]!="") {
					print "<style type=\"text/css\">" ;
						print "body {" ;
							print "background: url(\"" . $_SESSION[$guid]["personalBackground"] . "\") repeat scroll center top #A88EDB!important;" ;
						print "}" ;
					print "</style>" ;
				}
			}
	
			//Set timezone from session variable
			date_default_timezone_set($_SESSION[$guid]["timezone"]);
			
			//Initialise tinymce
			?>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/tinymce/tinymce.min.js"></script>
			<script type="text/javascript">
			tinymce.init({
				selector: "div#editorcontainer textarea",
				width: '738px',
				menubar : false,
				toolbar: 'bold, italic, underline,forecolor,backcolor,|,alignleft, aligncenter, alignright, alignjustify, |, formatselect, fontselect, fontsizeselect, |, table, |, bullist, numlist,outdent, indent, |, link, unlink, image, media, hr, charmap, |, cut, copy, paste, undo, redo, fullscreen',
				plugins: 'table, template, paste, visualchars, image, link, template, textcolor, hr, charmap, fullscreen, media',
			 	statusbar: false,
			 	extended_valid_elements: '<?php print getSettingByScope($connection2, "System", "allowableHTML") ?>',
			 	apply_source_formatting : true,
			 	browser_spellcheck: true,
			 	convert_urls: false,
			 	relative_urls: false
			 });
			</script>
			<style>
				div.mce-listbox button, div.mce-menubtn button { padding-top: 2px!important ; padding-bottom: 2px!important }
			</style>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-tokeninput/src/jquery.tokeninput.js"></script>
			<link rel="stylesheet" href="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-tokeninput/styles/token-input-facebook.css" type="text/css" />
			
			<?php
			//Analytics setting
			if ($_SESSION[$guid]["analytics"]!="") {
				print $_SESSION[$guid]["analytics"] ;
			}
			
			?>
		</head>
		<body>
			<?php
			//Show warning if not in the current school year
			if (isset($_SESSION[$guid]["username"])) {
				if ($_SESSION[$guid]["gibbonSchoolYearID"]!=$_SESSION[$guid]["gibbonSchoolYearIDCurrent"]) {
					print "<div style='margin: 10px auto; width:1101px;' class='warning'>" ;
						print "<b><u>" . sprintf(_('Warning: you are logged into the system in school year %1$s, which is not the current year.'), $_SESSION[$guid]["gibbonSchoolYearName"]) . "</b></u>" . _('Your data may not look quite right (for example, students who have left the school will not appear in previous years), but you should be able to edit information from other years which is not available in the current year.') ;
					print "</div>" ;
				}
			}
			?>
						
			<div id="wrapOuter">
				<?php
				print "<div class='minorLinks'>" ;
					print getMinorLinks($connection2, $guid, $cacheLoad) ;
				print "</div>" ;
				?>
				<div id="wrap">
					<div id="header">
						<div id="header-logo">
							<a href='<?php print $_SESSION[$guid]["absoluteURL"] ?>'><img height='100px' width='400px' class="logo" alt="Logo" src="<?php print $_SESSION[$guid]["absoluteURL"] . "/" . $_SESSION[$guid]["organisationLogo"] ; ?>"/></a>
						</div>
						<div id="header-finder">
							<?php
							//Show student and staff quick finder
							if (isset($_SESSION[$guid]["username"])) {
								if ($cacheLoad) {
									$_SESSION[$guid]["studentFastFinder"]=getStudentFastFinder($connection2, $guid) ;
								}
								print $_SESSION[$guid]["studentFastFinder"] ;
							}
							?>
						</div>
						<div id="header-menu">
							<?php 
								//Get main menu
								if ($cacheLoad) {
									$_SESSION[$guid]["mainMenu"]=mainMenu($connection2, $guid) ;
								}
								print $_SESSION[$guid]["mainMenu"] ;
							?>
						</div>
					</div>
					<div id="content-wrap">
						<?php
						//Allow for wide pages (no sidebar)
						if ($sidebar=="false") {
							print "<div id='content-wide'>" ;
								//Get floating module menu
								if (substr($_SESSION[$guid]["address"],0,8)=="/modules") {
									$moduleID=checkModuleReady($_SESSION[$guid]["address"], $connection2 );
									if ($moduleID!=FALSE) {
										$gibbonRoleIDCurrent=NULL ;
										if (isset($_SESSION[$guid]["gibbonRoleIDCurrent"])) {
											$gibbonRoleIDCurrent=$_SESSION[$guid]["gibbonRoleIDCurrent"] ;
										}
										try {
											$data=array("gibbonModuleID"=>$moduleID, "gibbonRoleID"=>$gibbonRoleIDCurrent); 
											$sql="SELECT gibbonModule.entryURL AS moduleEntry, gibbonModule.name AS moduleName, gibbonAction.name, gibbonAction.precedence, gibbonAction.category, gibbonAction.entryURL, URLList FROM gibbonModule, gibbonAction, gibbonPermission WHERE (gibbonModule.gibbonModuleID=:gibbonModuleID) AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND NOT gibbonAction.entryURL='' ORDER BY gibbonModule.name, category, gibbonAction.name, precedence DESC";
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { }
	
										if ($result->rowCount()>0) {			
											
											$currentCategory="" ;
											$lastCategory="" ;
											$currentName="" ;
											$lastName="" ;
											$count=0;
											$links=0 ;
											$menu="" ;
											while ($row=$result->fetch()) {
												$moduleName=$row["moduleName"] ;
												$moduleEntry=$row["moduleEntry"] ;
			
												$currentCategory=$row["category"] ;
												if (strpos($row["name"],"_")>0) {
													$currentName=_(substr($row["name"],0,strpos($row["name"],"_"))) ;
												}
												else {
													$currentName=_($row["name"]) ;
												}
					
												if ($currentName!=$lastName) {
													if ($currentCategory!=$lastCategory) {
														$menu.="<optgroup label='--" .  _($currentCategory) . "--'/>" ;
													}
													$selected="" ;
													if ($_GET["q"]=="/modules/" . $row["moduleName"] . "/" . $row["entryURL"]) {
														$selected="selected" ;
													}
													$menu.="<option onclick=\"javascript:location.href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'\" $selected>" . _($currentName) . "</option>" ;
													$links++ ;
												}
												$lastCategory=$currentCategory ;
												$lastName=$currentName ;
												$count++ ;
											}
		
											if ($links>1) {
												print "<div class='linkTop'>" ;
													print "<select style='width: 200px'>" ;
														print $menu ;
													print "</select>" ;
													print "<div style='float: right; padding-top: 10px'>" ;
														print _("Module Menu") ;
													print "</div>" ;
												print "</div>" ;
											}
										}
									}
								}
								
							//No closing </div> required here
						}
						else {
							print "<div id='content'>" ;
						}
						
						//Show index page Content
							if ($_SESSION[$guid]["address"]=="") {
								//Welcome message
								if (isset($_SESSION[$guid]["username"])==FALSE) {
									//Create auto timeout message
									if (isset($_GET["timeout"])) {
										if ($_GET["timeout"]=="true") {
											print "<div class='warning'>" ;
												print _('Your session expired, so you were automatically logged out of the system.');
											print "</div>" ;
										}
									}
											
									print "<h2>" ;
									print _("Welcome") ;
									print "</h2>" ;
									print "<p>" ;
									print $_SESSION[$guid]["indexText"] ;
									print "</p>" ;
									
									//Public applications permitted?
									$publicApplications=getSettingByScope($connection2, "Application Form", "publicApplications" ) ; 
									if ($publicApplications=="Y") {
										print "<h2 style='margin-top: 30px'>" ;
											print _("Applications") ;
										print "</h2>" ;
										print "<p>" ;
											print sprintf(_('Parents of students interested in study at %1$s may use our %2$s online form%3$s to initiate the application process.'), $_SESSION[$guid]["organisationName"], "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/?q=/modules/Application Form/applicationForm.php'>", "</a>") ;
										print "</p>" ;
									}
									
									//Public departments permitted?
									$makeDepartmentsPublic=getSettingByScope($connection2, "Departments", "makeDepartmentsPublic" ) ; 
									if ($makeDepartmentsPublic=="Y") {
										print "<h2 style='margin-top: 30px'>" ;
											print _("Departments") ;
										print "</h2>" ;
										print "<p>" ;
											print sprintf(_('Please feel free to %1$sbrowse our departmental information%2$s, to learn more about %3$s.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/?q=/modules/Departments/departments.php'>", "</a>", $_SESSION[$guid]["organisationName"]) ;
										print "</p>" ;
									}
									
									//Public units permitted?
									$makeUnitsPublic=getSettingByScope($connection2, "Planner", "makeUnitsPublic" ) ; 
									if ($makeUnitsPublic=="Y") {
										print "<h2 style='margin-top: 30px'>" ;
											print _("Learn With Us") ;
										print "</h2>" ;
										print "<p>" ;
											print sprintf(_('We are sharing some of our units of study with members of the public, so you can learn with us. Feel free to %1$sbrowse our public units%2$s.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/?q=/modules/Planner/units_public.php&sidebar=false'>", "</a>", $_SESSION[$guid]["organisationName"]) ;
										print "</p>" ;
									}
									
									//Get any elements hooked into public home page, checking if they are turned on
									try {
										$dataHook=array(); 
										$sqlHook="SELECT * FROM gibbonHook WHERE type='Public Home Page' ORDER BY name" ;
										$resultHook=$connection2->prepare($sqlHook);
										$resultHook->execute($dataHook);
									}
									catch(PDOException $e) { }
									while ($rowHook=$resultHook->fetch()) {
										$options=unserialize(str_replace("'", "\'", $rowHook["options"])) ;
										$check=getSettingByScope($connection2, $options["toggleSettingScope"], $options["toggleSettingName"]) ;
										if ($check==$options["toggleSettingValue"]) { //If its turned on, display it
											print "<h2 style='margin-top: 30px'>" ;
												print $options["title"] ;
											print "</h2>" ;
											print "<p>" ;
												print stripslashes($options["text"]) ;
											print "</p>" ;
										}
									}
								}
								else {
									$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
									if ($category==FALSE) {
										print "<div class='error'>" ;
										print _("Your current role type cannot be determined.") ;
										print "</div>" ;
									}
									//Display Parent Dashboard
									else if ($category=="Parent") {
										$count=0 ;
										try {
											$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
											$result=$connection2->prepare($sql);
											$result->execute($data); 
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										
										if ($result->rowCount()>0) {
											//Get child list
											$count=0 ;
											$options="" ;
											$students=array() ;
											while ($row=$result->fetch()) {
												try {
													$dataChild=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"],"gibbonFamilyID"=>$row["gibbonFamilyID"]); 
													$sqlChild="SELECT gibbonPerson.gibbonPersonID, image_75, surname, preferredName, dateStart, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, gibbonRollGroup.website AS rollGroupWebsite, gibbonRollGroup.gibbonRollGroupID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName " ;
													$resultChild=$connection2->prepare($sqlChild);
													$resultChild->execute($dataChild); 
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												while ($rowChild=$resultChild->fetch()) {
													$students[$count][0]=$rowChild["surname"] ;
													$students[$count][1]=$rowChild["preferredName"] ;
													$students[$count][2]=$rowChild["yearGroup"] ;
													$students[$count][3]=$rowChild["rollGroup"] ;
													$students[$count][4]=$rowChild["gibbonPersonID"] ;
													$students[$count][5]=$rowChild["image_75"] ;
													$students[$count][6]=$rowChild["dateStart"] ;
													$students[$count][7]=$rowChild["gibbonRollGroupID"] ;
													$students[$count][8]=$rowChild["rollGroupWebsite"] ;
													$count++ ;
												}
											}
										}
										
										if ($count>0) {
											print "<h2>" ;
												print _("Parental Dashboard") ;
											print "</h2>" ;
											include "./modules/Timetable/moduleFunctions.php" ;
											
											for ($i=0; $i<$count; $i++) {
												print "<h4>" ;
												print $students[$i][1] . " " . $students[$i][0] ;
												print "</h4>" ;
												
												print "<div style='margin-right: 1%; float:left; width: 15%; text-align: center'>" ;
													print getUserPhoto($guid, $students[$i][5], 75) ;
													print "<div style='height: 5px'></div>" ;
													print "<span style='font-size: 70%'>" ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $students[$i][4] . "'>" . _('Student Profile') . "</a><br/>" ;
														if (isActionAccessible($guid, $connection2, "/modules/Roll Groups/rollGroups_details.php")) {
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=" . $students[$i][7] . "'>" . _('Roll Group') . " (" . $students[$i][3] . ")</a><br/>" ;
														}
														if ($students[$i][8]!="") {
															print "<a target='_blank' href='" . $students[$i][8] . "'>" . $students[$i][3] . " " . _('Website') . "</a>" ;
														}
														
													print "</span>" ;
												print "</div>" ;
												print "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 83%'>" ;
													$dashboardContents=getParentalDashboardContents($connection2, $guid, $students[$i][4]) ;
													if ($dashboardContents==FALSE) {
														print "<div class='error'>" ;
															print _("There are no records to display.") ;
														print "</div>" ;
													}
													else {
														print $dashboardContents ;
													}
												print "</div>" ;
											}
										}
									}
									else if ($category=="Student" OR $category=="Staff") {
										//Get Smart Workflow help message
										if ($category=="Staff") {
											$smartWorkflowHelp=getSmartWorkflowHelp($connection2, $guid) ;
											if ($smartWorkflowHelp!=false) {
												print $smartWorkflowHelp ;
											}
										}
										//Display planner
										$date=date("Y-m-d") ;
										if (isSchoolOpen($guid, $date, $connection2)==TRUE AND isActionAccessible($guid, $connection2, "/modules/Planner/planner.php") AND $_SESSION[$guid]["username"]!="") {			
											try {
												$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"],"date"=>$date,"gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"],"gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"],"date2"=>$date,"gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
												$sql="(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess,  role, date, summary, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart, course, class" ; 
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) {
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($result->rowCount()>0) {
												print "<h2>" ;
													print _("Today's Lessons") ;
												print "</h2>" ;
												
												if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
												$updateReturnMessage="" ;
												$class="error" ;
												if (!($updateReturn=="")) {
													if ($updateReturn=="fail0") {
														$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
													}
													else if ($updateReturn=="fail1") {
														$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
													}
													else if ($updateReturn=="fail2") {
														$updateReturnMessage=_("Your request failed due to a database error.") ;	
													}
													else if ($updateReturn=="success0") {
														$updateReturnMessage=_("Your request was completed successfully.") ;	
														$class="success" ;
													}
													print "<div class='$class'>" ;
														print $updateReturnMessage;
													print "</div>" ;
												} 
												
												print "<div class='linkTop'>" ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php'>" . _('View Planner') . "</a>" ;
												print "</div>" ;
												
												print "<table cellspacing='0' style='width: 100%'>" ;
													print "<tr class='head'>" ;
														print "<th>" ;
															print _("Class") . "<br/>" ;
														print "</th>" ;
														print "<th>" ;
															print _("Lesson") . "</br>" ;
															print "<span style='font-size: 85%; font-style: italic'>" . _('Unit') . "</span>" ;
														print "</th>" ;
														print "<th>" ;
															print _("Homework") ;
														print "</th>" ;
														print "<th>" ;
															print _("Summary") ;
														print "</th>" ;
														print "<th>" ;
															print _("Like") ;
														print "</th>" ;
														print "<th>" ;
															print _("Action") ;
														print "</th>" ;
													print "</tr>" ;
													
													$count=0;
													$rowNum="odd" ;
													while ($row=$result->fetch()) {
														if (!($row["role"]=="Student" AND $row["viewableStudents"]=="N")) {
															if ($count%2==0) {
																$rowNum="even" ;
															}
															else {
																$rowNum="odd" ;
															}
															$count++ ;
															
															//Highlight class in progress
															if ((date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"]) AND ($date)==date("Y-m-d")) {
																$rowNum="current" ;
															}
															
															//COLOR ROW BY STATUS!
															print "<tr class=$rowNum>" ;
																print "<td>" ;
																	print $row["course"] . "." . $row["class"] . "<br/>" ;
																	print "<span style='font-style: italic; font-size: 75%'>" . substr($row["timeStart"],0,5) . "-" . substr($row["timeEnd"],0,5) . "</span>" ;
																print "</td>" ;
																print "<td>" ;
																	print "<b>" . $row["name"] . "</b><br/>" ;
																	print "<span style='font-size: 85%; font-style: italic'>" ;
																		$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
																		if (isset($unit[0])) {
																			print $unit[0] ;
																			if ($unit[1]!="") {
																				print "<br/><i>" . $unit[1] . " " . _('Unit') . "</i>" ;
																			}
																		}
																	print "</span>" ;
																print "</td>" ;
																print "<td>" ;
																	if ($row["homework"]=="N" AND $row["myHomeworkDueDateTime"]=="") {
																		print _("No") ;
																	}
																	else {
																		if ($row["homework"]=="Y") {
																			print _("Yes") . ": " . _("Teacher Recorded") . "<br/>" ;
																			if ($row["homeworkSubmission"]=="Y") {
																				print "<span style='font-size: 85%; font-style: italic'>+" . _("Submission") . "</span><br/>" ;
																				if ($row["homeworkCrowdAssess"]=="Y") {
																					print "<span style='font-size: 85%; font-style: italic'>+" . _("Crowd Assessment") . "</span><br/>" ;
																				}
																			}
																		}
																		if ($row["myHomeworkDueDateTime"]!="") {
																			print _("Yes") . ": " . _("Student Recorded") . "</br>" ;
																		}
																	}
																print "</td>" ;
																print "<td>" ;
																	print $row["summary"] ;
																print "</td>" ;
																print "<td>" ;
																	if ($row["role"]=="Teacher") {
																		try {
																			$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]); 
																			$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
																			$resultLike=$connection2->prepare($sqlLike);
																			$resultLike->execute($dataLike); 
																		}
																		catch(PDOException $e) { }
																		print $resultLike->rowCount() ;
																	}
																	else {
																		try {
																			$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"],"gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
																			$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
																			$resultLike=$connection2->prepare($sqlLike);
																			$resultLike->execute($dataLike); 
																		}
																		catch(PDOException $e) { }
																		if ($resultLike->rowCount()!=1) {
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=/modules/Planner/planner.php&viewBy=Class&gibbonCourseClassID=" . $row["gibbonPlannerEntryID"] . "&date=&returnToIndex=Y'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
																		}
																		else {
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=/modules/Planner/planner.php&viewBy=Class&gibbonCourseClassID=" . $row["gibbonPlannerEntryID"] . "&date=&returnToIndex=Y'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
																		}
																	}
																print "</td>" ;
																print "<td>" ;
																	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a>" ;
																print "</td>" ;
															print "</tr>" ;
														}
													}
												print "</table>" ;
											}
										}
										
										//Display TT
										if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt.php") AND $_SESSION[$guid]["username"]!="" AND (getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2)=="Staff" OR getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2)=="Student")) {			
											?>
											<script type="text/javascript">
												$(document).ready(function(){
													$("#tt").load("<?php print $_SESSION[$guid]["absoluteURL"] ?>/index_tt_ajax.php",{"ttDate": "<?php print @$_POST["ttDate"] ?>", "fromTT": "<?php print @$_POST["fromTT"] ?>", "personalCalendar": "<?php print @$_POST["personalCalendar"] ?>", "schoolCalendar": "<?php print @$_POST["schoolCalendar"] ?>", "spaceBookingCalendar": "<?php print @$_POST["spaceBookingCalendar"] ?>"});
												});
											</script>
											<?php
											print "<h2>" . _("My Timetable") . "</h2>" ;
											print "<div id='tt' name='tt' style='width: 100%; min-height: 40px; text-align: center'>" ;
												print "<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='" . _('Loading') . "' onclick='return false;' /><br/><p style='text-align: center'>" . _('Loading') . "</p>" ;
											print "</div>" ;
										}
										
										//Display "My Roll Groups"
										?>
										<script type='text/javascript'>
											$(function() {
												$( "#tabs" ).tabs({
													ajaxOptions: {
														error: function( xhr, status, index, anchor ) {
															$( anchor.hash ).html(
																"<?php
																print _("Couldn't load this tab.") ; 
																?>"
															);
														}
													}
												});
											});
										</script>
	
										<?php
										try {
											$data=array("gibbonPersonIDTutor"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDTutor2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDTutor3"=>$_SESSION[$guid]["gibbonPersonID"],"gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
											$sql="SELECT * FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										
										$h2=_("My Roll Groups") ;
										if ($result->rowCount()==1) {
											$h2=_("My Roll Group") ;
										}
										if ($result->rowCount()>0) {
											print "<h2>" ;
												print $h2 ;
											print "</h2>" ;
											
											?>
											<div id="tabs" style='margin: 10px 0 20px 0'>
												<ul>
													<li><a href="#tabs-1"><?php print _('Students') ?></a></li>
													<li><a href="#tabs-2"><?php print _('Behaviour') ?></a></li>
												</ul>
												<div id="tabs-1">
													<?php
													//Students
													$sqlWhere="" ;
													while ($row=$result->fetch()) {
														$sqlWhere.="gibbonRollGroupID=" . $row["gibbonRollGroupID"] . " OR " ;
														if ($result->fetch()>1) {
															print "<h4>" ;
																print $row["name"] ;
															print "</h4>" ;
														}
														print "<div class='linkTop' style='margin-top: 0px'>" ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "'>" . _('Take Attendance') . "<img style='margin-left: 5px' title='" . _('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a> | " ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/indexExport.php?gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "'>" . _('Export to Excel') . "<img style='margin-left: 5px' title='" . _('Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
														print "</div>" ;
														
														printRollGroupTable($guid, $row["gibbonRollGroupID"],5,$connection2) ;
													}
													$sqlWhere=substr($sqlWhere,0,-4) ;
													?>
												</div>
												<div id="tabs-2">
													<?php
													$plural="s" ;
													if ($result->rowCount()==1) {
														$plural="" ;
													}
													try {
														$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
														$sql="SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 AND ($sqlWhere) ORDER BY timestamp DESC" ; 
														$result=$connection2->prepare($sql);
														$result->execute($data);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													
													if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage_add.php")) {
														print "<div class='linkTop'>" ;
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage_add.php&gibbonPersonID=&gibbonRollGroupID=&gibbonYearGroupID=&type='>" . _('Add') . "<img style='margin: 0 0 -4px 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
															$policyLink=getSettingByScope($connection2, "Behaviour", "policyLink") ;
															if ($policyLink!="") {
																print " | <a target='_blank' href='$policyLink'>" . _('View Behaviour Policy') . "</a>" ;
															}
														print "</div>" ;
													}
													
													if ($result->rowCount()<1) {
														print "<div class='error'>" ;
														print _("There are no records to display.") ;
														print "</div>" ;
													}
													else {
														print "<table cellspacing='0' style='width: 100%'>" ;
															print "<tr class='head'>" ;
																print "<th>" ;
																	print _("Student & Date") ;
																print "</th>" ;
																print "<th>" ;
																	print _("Type") ;
																print "</th>" ;
																print "<th>" ;
																	print _("Descriptor") ;
																print "</th>" ;
																print "<th>" ;
																	print _("Level") ;
																print "</th>" ;
																print "<th>" ;
																	print _("Teacher") ;
																print "</th>" ;
																print "<th>" ;
																	print _("Action") ;
																print "</th>" ;
															print "</tr>" ;
															
															$count=0;
															$rowNum="odd" ;
															while ($row=$result->fetch()) {
																if ($count%2==0) {
																	$rowNum="even" ;
																}
																else {
																	$rowNum="odd" ;
																}
																$count++ ;
																
																//COLOR ROW BY STATUS!
																print "<tr class=$rowNum>" ;
																	print "<td>" ;
																		print "<b>" . formatName("", $row["preferredNameStudent"], $row["surnameStudent"], "Student", false ) . "</b><br/>" ;
																		if (substr($row["timestamp"],0,10)>$row["date"]) {
																			print _("Date Updated") . ": " . dateConvertBack($guid, substr($row["timestamp"],0,10)) . "<br/>" ;
																			print _("Incident Date") . ": " . dateConvertBack($guid, $row["date"]) . "<br/>" ;
																		}
																		else {
																			print dateConvertBack($guid, $row["date"]) . "<br/>" ;
																		}
																	print "</td>" ;
																	print "<td style='text-align: center'>" ;
																		if ($row["type"]=="Negative") {
																			print "<img title='" . _('Negative') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
																		}
																		else if ($row["type"]=="Positive") {
																			print "<img title='" . _('Position') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
																		}
																	print "</td>" ;
																	print "<td>" ;
																		print trim($row["descriptor"]) ;
																	print "</td>" ;
																	print "<td>" ;
																		print trim($row["level"]) ;
																	print "</td>" ;
																	print "<td>" ;
																		print formatName($row["title"], $row["preferredNameCreator"], $row["surnameCreator"], "Staff", false ) . "<br/>" ;
																	print "</td>" ;
																	print "<td>" ;
																		print "<script type='text/javascript'>" ;	
																			print "$(document).ready(function(){" ;
																				print "\$(\".comment-$count\").hide();" ;
																				print "\$(\".show_hide-$count\").fadeIn(1000);" ;
																				print "\$(\".show_hide-$count\").click(function(){" ;
																				print "\$(\".comment-$count\").fadeToggle(1000);" ;
																				print "});" ;
																			print "});" ;
																		print "</script>" ;
																		if ($row["comment"]!="") {
																			print "<a title='" . _('View Description') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . _('Show Comment') . "' onclick='return false;' /></a>" ;
																		}
																	print "</td>" ;
																print "</tr>" ;
																if ($row["comment"]!="") {
																	if ($row["type"]=="Positive") {
																		$bg="background-color: #D4F6DC;" ;
																	}
																	else {
																		$bg="background-color: #F6CECB;" ;
																	}
																	print "<tr class='comment-$count' id='comment-$count'>" ;
																		print "<td style='$bg' colspan=6>" ;
																			print $row["comment"] ;
																		print "</td>" ;
																	print "</tr>" ;
																}
																print "</tr>" ;
																print "</tr>" ;
															}
														print "</table>" ;
													}
													?>
												</div>
											</div>
											<?php
										}
									}
								}
							}
							else {
								if (strstr($_SESSION[$guid]["address"],"..")!=FALSE) {
									print "<div class='error'>" ;
									print _("Illegal address detected: access denied.") ;
									print "</div>" ;
								}
								else {
									if(is_file("./" . $_SESSION[$guid]["address"])) {
										//Include the page
										include ("./" . $_SESSION[$guid]["address"]) ;
									}
									else {
										include "./error.php" ;
									}
								}
							}
							?>
						</div>
						<?php
						if ($sidebar!="false") {
							?>
							<div id="sidebar">
								<?php sidebar($connection2, $guid) ; ?>
							</div>
							<br style="clear: both">
							<?php
						}
						?>
					</div>
					<div id="footer">
						<?php print _("Powered by") ?> <a target='_blank' href="http://gibbonedu.org">Gibbon</a> v<?php print $version ?><?php if ($_SESSION[$guid]["cuttingEdgeCode"]=="Y") { print "dev" ; }?> | &#169; <a target='_blank' href="http://rossparker.org">Ross Parker</a> 2010-<?php print date("Y") ?><br/>
						<span style='font-size: 90%; '>
							<?php print _("Created under the") ?> <a target='_blank' href="http://www.gnu.org/licenses/gpl.html">GNU GPL</a> at <a target='_blank' href='http://www.ichk.edu.hk'>ICHK</a> | <a target='_blank' href='https://www.gibbonedu.org/contribute/'><?php print _("Credits") ; ?></a><br/>
							<?php
								$seperator=FALSE ;
								$thirdLine=FALSE ;
								if ($_SESSION[$guid]["i18n"]["maintainerName"]!="" AND $_SESSION[$guid]["i18n"]["maintainerName"]!="Gibbon") {
									if ($_SESSION[$guid]["i18n"]["maintainerWebsite"]!="") {
										print _("Translation led by") . " <a target='_blank' href='" . $_SESSION[$guid]["i18n"]["maintainerWebsite"] . "'>" . $_SESSION[$guid]["i18n"]["maintainerName"] . "</a>" ;
									}
									else {
										print _("Translation led by") . " " . $_SESSION[$guid]["i18n"]["maintainerName"] ;
									}
									$seperator=TRUE ;
									$thirdLine=TRUE ;
								}
								if ($_SESSION[$guid]["gibbonThemeName"]!="Default" AND $_SESSION[$guid]["gibbonThemeAuthor"]!="") {
									if ($seperator) {
										print " | " ;
									}
									if ($_SESSION[$guid]["gibbonThemeURL"]!="") {
										print _("Theme by") . " <a target='_blank' href='" . $_SESSION[$guid]["gibbonThemeURL"] . "'>" . $_SESSION[$guid]["gibbonThemeAuthor"] . "</a>" ;
									}
									else {
										print _("Theme by") . " " . $_SESSION[$guid]["gibbonThemeAuthor"] ;
									}
									$thirdLine=TRUE ;
								}
								if ($thirdLine==FALSE) {
									print "<br/>" ; 
								}
							?>
						</span>
						<img style='z-index: 9999; margin-top: -82px; margin-left: 850px; opacity: 0.8' alt='Logo Small' src='./themes/Default/img/logoFooter.png'/>
					</div>
				</div>
			</div>
		</body>
	</html>
	<?php
}
?>
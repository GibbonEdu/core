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
include "./functions.php" ;
include "./config.php" ;
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

//Check for force password reset flag
if (isset($_SESSION[$guid]["passwordForceReset"])) {
	if ($_SESSION[$guid]["passwordForceReset"]=="Y" AND $q!="preferences.php") {
		$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=preferences.php" ;
		$URL=$URL. "&forceReset=Y" ;
		header("Location: {$URL}") ;
		break ;
	}
}


if ($_SESSION[$guid]["address"]!="") {
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
			<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
			<meta http-equiv="content-language" content="en"/>
			<meta name="author" content="Ross Parker, International College Hong Kong"/>
			<meta name="ROBOTS" content="none"/>
			
			<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>
			<script type="text/javascript" src="./lib/LiveValidation/livevalidation_standalone.compressed.js"></script>

			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery/jquery.js"></script>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/jquery-ui/js/jquery-ui.min.js"></script>
			<?php 
			if (is_file($_SESSION[$guid]["absolutePath"] . "/lib/jquery-ui/i18n/jquery.ui.datepicker-" . substr($_SESSION[$guid]["i18n"]["code"],0,2) . ".js")) {
				print "<script type='text/javascript' src='" . $_SESSION[$guid]["absoluteURL"] . "/lib/jquery-ui/i18n/jquery.ui.datepicker-" .  substr($_SESSION[$guid]["i18n"]["code"],0,2) . ".js'></script>" ;
				print "<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional['" .  substr($_SESSION[$guid]["i18n"]["code"],0,2) . "']);</script>" ;
			}
			else if (is_file($_SESSION[$guid]["absolutePath"] . "/lib/jquery-ui/i18n/jquery.ui.datepicker-" . str_replace("_","-",$_SESSION[$guid]["i18n"]["code"]) . ".js")) {
				print "<script type='text/javascript' src='" . $_SESSION[$guid]["absoluteURL"] . "/lib/jquery-ui/i18n/jquery.ui.datepicker-" .  str_replace("_","-",$_SESSION[$guid]["i18n"]["code"]) . ".js'></script>" ;
				print "<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional['" .  str_replace("_","-",$_SESSION[$guid]["i18n"]["code"]) . "']);</script>" ;
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
		
			<?php
			//Set up for i18n via gettext
			if ($_SESSION[$guid]["i18n"]["code"]!=NULL) {
				putenv("LC_ALL=" . $_SESSION[$guid]["i18n"]["code"]);
				setlocale(LC_ALL, $_SESSION[$guid]["i18n"]["code"]);
				bindtextdomain("gibbon", "./i18n");
				textdomain("gibbon");
			}
			
			//Set theme
			if ($cacheLoad OR $_SESSION[$guid]["themeCSS"]=="" OR isset($_SESSION[$guid]["themeJS"])==FALSE OR $_SESSION[$guid]["gibbonThemeID"]=="" OR $_SESSION[$guid]["gibbonThemeName"]=="") {
				$_SESSION[$guid]["themeCSS"]="<link rel='stylesheet' type='text/css' href='./themes/Default/css/main.css' />" ;
				$_SESSION[$guid]["themeJS"]="<script type='text/javascript' src='./themes/Default/js/common.js'></script>" ;
				$_SESSION[$guid]["gibbonThemeID"]="001" ;
				$_SESSION[$guid]["gibbonThemeName"]="Default" ;
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
						$_SESSION[$guid]["themeJS"]="<script type='text/javascript' src='./themes/" . $rowTheme["name"] . "/js/common.js'></script>" ;
						$_SESSION[$guid]["gibbonThemeID"]=$rowTheme["gibbonThemeID"] ;
						$_SESSION[$guid]["gibbonThemeName"]=$rowTheme["name"] ;
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
							print "background: url(\"" . $_SESSION[$guid]["personalBackground"] . "\") repeat scroll center top #fff!important;" ;
						print "}" ;
					print "</style>" ;
				}
			}
	
			//Set timezone from session variable
			date_default_timezone_set($_SESSION[$guid]["timezone"]);
			
			//Initialise tinymce
			$initArray=array (
				'script_url'=> $_SESSION[$guid]["absoluteURL"] . "/lib/tiny_mce/tiny_mce.js",
				'mode'=> 'textareas',
				'editor_selector'=> 'tinymce',
				'width'=> '738px',
				'theme'=> 'advanced',
				'skin'=> 'wp_theme',
				'theme_advanced_buttons1'=> "formatselect, bold, italic, underline,forecolor,backcolor,separator,justifyleft, justifycenter, justifyright, justifyfull, separator, bullist, numlist,outdent, indent, separator, link, unlink, separator, hr,removeformat, charmap, table,image",
				'theme_advanced_buttons2'=> "",
				'theme_advanced_buttons3'=> "",
				'theme_advanced_buttons4'=> "",
				'theme_advanced_toolbar_location'=> 'top',
				'theme_advanced_toolbar_align'=> 'left',
				'theme_advanced_statusbar_location'=> 'bottom',
				'theme_advanced_resizing'=> true,
				'theme_advanced_resize_horizontal'=> false,
				'dialog_type'=> 'modal',
				'formats'=> "{
					alignleft : [
						{selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles : {textAlign : 'left'}},
						{selector : 'img,table', classes : 'alignleft'}
					],
					aligncenter : [
						{selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles : {textAlign : 'center'}},
						{selector : 'img,table', classes : 'aligncenter'}
					],
					alignright : [
						{selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles : {textAlign : 'right'}},
						{selector : 'img,table', classes : 'alignright'}
					],
					strikethrough : {inline : 'del'}
				}",
				'relative_urls'=> false,
				'remove_script_host'=> false,
				'convert_urls'=> false,
				'apply_source_formatting'=> false,
				'remove_linebreaks'=> true,
				'gecko_spellcheck'=> true,
				'keep_styles'=> false,
				'entities'=> '38,amp,60,lt,62,gt',
				'accessibility_focus'=> true,
				'tabfocus_elements'=> 'major-publishing-actions',
				'media_strict'=> false,
				'paste_remove_styles'=> true,
				'paste_remove_spans'=> true,
				'paste_strip_class_attributes'=> 'all',
				'paste_text_use_dialog'=> true,
				'extended_valid_elements'=> getSettingByScope($connection2, "System", "allowableHTML"),
				'wp_fullscreen_content_css'=> "/plugins/wpfullscreen/css/wp-fullscreen.css",
				'plugins'=> "table,advlink,contextmenu,paste,visualchars,template,advimage"
			);
			
			$mce_options='';
			foreach ( $initArray as $k=> $v ) {
				if ( is_bool($v) ) {
					$val=$v ? 'true' : 'false';
					$mce_options .=$k . ':' . $val . ', ';
					continue;
				} elseif ( !empty($v) && is_string($v) && ( ('{'==$v{0} && '}'==$v{strlen($v) - 1}) || ('['==$v{0} && ']'==$v{strlen($v) - 1}) || preg_match('/^\(?function ?\(/', $v) ) ) {
					$mce_options .=$k . ':' . $v . ', ';
					continue;
				}
				$mce_options .=$k . ':"' . $v . '", ';
			}
			$mce_options=rtrim( trim($mce_options), '\n\r,' );
			?>
			<script type="text/javascript" src="<?php print $_SESSION[$guid]["absoluteURL"] ?>/lib/tinymce/tiny_mce.js"></script>
			<script type="text/javascript">
				tinymce.init({
					<?php print $mce_options; ?>
				});
			</script>	
			
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
				<div id="wrap">
					<div id="header">
						<div id="header-left">
							<a href='<?php print $_SESSION[$guid]["absoluteURL"] ?>'><img height='107px' width='250px' class="logo" alt="Logo" src="<?php print $_SESSION[$guid]["absoluteURL"] . "/" . $_SESSION[$guid]["organisationLogo"] ; ?>"/></a>
						</div>
						<div id="header-right">
							<?php 
								if (isset($_SESSION[$guid]["username"]) && $_SESSION[$guid]["username"]!="") {
									print "<div class='minorLinks'>" ;
										print $_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"] . " . " ;
										print "<a href='./logout.php'>Logout</a> . <a href='./index.php?q=preferences.php'>" . _('Preferences') . "</a>" ;
										if ($_SESSION[$guid]["emailLink"]!="") {
											print " . <a target='_blank' href='" . $_SESSION[$guid]["emailLink"] . "'>" . _('Email') . "</a>" ;
										}
										if ($_SESSION[$guid]["webLink"]!="") {
											print " . <a target='_blank' href='" . $_SESSION[$guid]["webLink"] . "'>" . $_SESSION[$guid]["organisationNameShort"] . " " . _('Website') . "</a>" ;
										}
										if ($_SESSION[$guid]["website"]!="") {
											print " . <a target='_blank' href='" . $_SESSION[$guid]["website"] . "'>" . _('My Website') . "</a>" ;
										}
										
										//STARS!
										if ($cacheLoad) {
											$_SESSION[$guid]["likeCount"]=0 ;
											$_SESSION[$guid]["likeCountTitle"]="" ;
											//Count crowd assessment likes
											try {
												$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
												$sqlLike="SELECT * FROM gibbonCrowdAssessLike JOIN gibbonPlannerEntryHomework ON (gibbonCrowdAssessLike.gibbonPlannerEntryHomeworkID=gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID) JOIN gibbonPlannerEntry ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
												$resultLike=$connection2->prepare($sqlLike);
												$resultLike->execute($dataLike); 
												if ($resultLike->rowCount()>0) {
													$_SESSION[$guid]["likeCount"]+=$resultLike->rowCount() ;
													$_SESSION[$guid]["likeCountTitle"].=_('Crowd Assessment') . ": " . count($resultLike) . ", " ;
												}
											}
											catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
										
											//Count planner likes
											try {
												$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
												$sqlLike="SELECT * FROM gibbonPlannerEntryLike JOIN gibbonPlannerEntry ON (gibbonPlannerEntryLike.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
												$resultLike=$connection2->prepare($sqlLike);
												$resultLike->execute($dataLike); 
												if ($resultLike->rowCount()>0) {
													$_SESSION[$guid]["likeCount"]+=$resultLike->rowCount() ;
													$_SESSION[$guid]["likeCountTitle"].=_('Planner') . ": " . count($resultLike) . ", " ;
												}
											}
											catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
										
											//Count positive haviour
											try {
												$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
												$sqlLike="SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND type='Positive' AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
												$resultLike=$connection2->prepare($sqlLike);
												$resultLike->execute($dataLike); 
												if ($resultLike->rowCount()>0) {
													$_SESSION[$guid]["likeCount"]+=$resultLike->rowCount() ;
													$_SESSION[$guid]["likeCountTitle"].=_('Behaviour') . ": " . count($resultLike) . ", " ;
												}
											}
											catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
										}
										
										//Spit out likes
										if (isset($_SESSION[$guid]["likeCount"])) {
											if ($_SESSION[$guid]["likeCount"]>0) {
												print " . <a title='" . substr($_SESSION[$guid]["likeCountTitle"],0,-2) . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=stars.php'>" . $_SESSION[$guid]["likeCount"] . " x </a><a title='" . substr($_SESSION[$guid]["likeCountTitle"],0,-2) . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=stars.php'><img style='opacity: 0.8; vertical-align: -60%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
											}
											else {
												print " . " . $_SESSION[$guid]["likeCount"] . " x <img title='" . substr($_SESSION[$guid]["likeCountTitle"],0,-2) . "' style='opacity: 0.8; vertical-align: -60%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'>" ;
											}
										}
										
										//MESSAGE WALL!
										if (isActionAccessible($guid, $connection2, "/modules/Messenger/messageWall_view.php")) {
											include "./modules/Messenger/moduleFunctions.php" ; 
											
											$addReturn=NULL ;
											if (isset($_GET["addReturn"])) {
												$addReturn=$_GET["addReturn"] ;
											}
											$updateReturn=NULL ;
											if (isset($_GET["updateReturn"])) {
												$updateReturn=$_GET["updateReturn"] ;
											}
											$deleteReturn=NULL ;
											if (isset($_GET["deleteReturn"])) {
												$deleteReturn=$_GET["deleteReturn"] ;
											}
											if ($cacheLoad OR ($q=="/modules/Messenger/messenger_post.php" AND $addReturn=="success0") OR ($q=="/modules/Messenger/messenger_manage_edit.php" AND $updateReturn=="success0") OR ($q=="/modules/Messenger/messenger_manage.php" AND $deleteReturn=="success0")) {
												$messages=getMessages($guid, $connection2, "result") ;					
												$messages=unserialize($messages) ;
												try {
													$resultPosts=$connection2->prepare($messages[1]);
													$resultPosts->execute($messages[0]);  
												}
												catch(PDOException $e) { }	
										
												$_SESSION[$guid]["messageWallCount"]=0 ;
												if ($resultPosts->rowCount()>0) {
													$output=array() ;
													$last="" ;
													while ($rowPosts=$resultPosts->fetch()) {
														if ($last==$rowPosts["gibbonMessengerID"]) {
															$output[($count-1)]["source"]=$output[($count-1)]["source"] . "<br/>" .$rowPosts["source"] ;
														}
														else {
															$output[$_SESSION[$guid]["messageWallCount"]]["photo"]=$rowPosts["image_75"] ;
															$output[$_SESSION[$guid]["messageWallCount"]]["subject"]=$rowPosts["subject"] ;
															$output[$_SESSION[$guid]["messageWallCount"]]["details"]=$rowPosts["body"] ;
															$output[$_SESSION[$guid]["messageWallCount"]]["author"]=formatName($rowPosts["title"], $rowPosts["preferredName"], $rowPosts["surname"], $rowPosts["category"]) ;
															$output[$_SESSION[$guid]["messageWallCount"]]["source"]=$rowPosts["source"] ;
		
															$_SESSION[$guid]["messageWallCount"]++ ;
															$last=$rowPosts["gibbonMessengerID"] ;
														}	
													}
												}
											}
											
											$URL=$_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Messenger/messageWall_view_full.php&width=1000&height=550" ;
											if (isset($_SESSION[$guid]["messageWallCount"])==FALSE) {
												print " . 0 x <img style='opacity: 0.8; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/messageWall_none.png'>" ;
											}
											else {
												if ($_SESSION[$guid]["messageWallCount"]<1) {
													print " . 0 x <img style='opacity: 0.8; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/messageWall_none.png'>" ;
												}
												else {
													print " . <a class='thickbox' href='$URL'>" . $_SESSION[$guid]["messageWallCount"] . " x </a><a class='thickbox' href='$URL'><img style='opacity: 0.8; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/messageWall.png'></a>" ;
													if ($_SESSION[$guid]["pageLoads"]==0 AND ($_SESSION[$guid]["messengerLastBubble"]==NULL OR $_SESSION[$guid]["messengerLastBubble"]<date("Y-m-d"))) {
														?>
														<div id='messageBubbleArrow' style="left: 650px; top: 42px" class='arrow top'></div>
														<div id='messageBubble' style="left: 420px; top: 58px; width: 300px; min-width: 300px; max-width: 300px; min-height: 100px; text-align: center; padding-bottom: 10px" class="ui-tooltip ui-widget ui-corner-all ui-widget-content" role="tooltip"">
															<div class="ui-tooltip-content">
																<div style='font-weight: bold; font-style: italic; font-size: 120%; margin-top: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dotted rgba(255,255,255,0.5); display: block'><?php print _('New Messages') ?></div>
																<?php
																$test=count($output) ;
																if ($test>3) {
																	$test=3 ;
																}
																for ($i=0; $i<$test; $i++) {
																	print "<span style='font-size: 120%; font-weight: bold'>" ;
																	if (strlen($output[$i]["subject"])<=30) {
																		print $output[$i]["subject"] ;
																	}
																	else {
																		print substr($output[$i]["subject"],0,30) . "..." ;
																	}
															 
																	 print "</span><br/>" ;
																	print "<i>" . $output[$i]["author"] . "</i><br/><br/>" ;
																}
																?>
																<?php
																if (count($output)>3) {
																	print "<i>" . _('Plus more') . "...</i>" ;
																}
																?>
														
															</div>
															<div style='text-align: right; margin-top: 20px; color: #666'>
																<a onclick='$("#messageBubble").hide("fade", {}, 1); $("#messageBubbleArrow").hide("fade", {}, 1)' style='text-decoration: none; color: #666' class='thickbox' href='<?php print $URL ?>'><?php print _('Read All') ?></a> . 
																<a style='text-decoration: none; color: #666' onclick='$("#messageBubble").hide("fade", {}, 1000); $("#messageBubbleArrow").hide("fade", {}, 1000)' href='#'><?php print _('Dismiss') ?></a>
															</div>
														</div>
												
														<script type="text/javascript">
															$(function() {
																setTimeout(function() {
																	$("#messageBubble").hide('fade', {}, 3000)
																}, 10000);
															});
															$(function() {
																setTimeout(function() {
																	$("#messageBubbleArrow").hide('fade', {}, 3000)
																}, 10000);
															});
														</script>
														<?php
													
														try {
															$data=array("messengerLastBubble"=>date("Y-m-d"), "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"] ); 
															$sql="UPDATE gibbonPerson SET messengerLastBubble=:messengerLastBubble WHERE gibbonPersonID=:gibbonPersonID" ;
															$result=$connection2->prepare($sql);
															$result->execute($data); 
														}
														catch(PDOException $e) { }
													}
												}
											}
										}
										
									print "</div>" ;
									
									if ($cacheLoad) {
										$_SESSION[$guid]["mainMenu"]=mainMenu($connection2, $guid) ;
									}
									print $_SESSION[$guid]["mainMenu"] ;
								}
							
							?>
						</div>
					</div>
					<div id="content-wrap">
						<?php
						//Allow for wide pages (no sidebar)
						if ($sidebar=="false") {
							print "<div id='content-wide'>" ;
						}
						else {
							print "<div id='content'>" ;
						}
						
						//Show index page Content
							if ($_SESSION[$guid]["address"]=="") {
								//Welcome message
								if (isset($_SESSION[$guid]["username"])==FALSE) {
									print "<div class='trail'>" ;
									print "<div class='trailEnd'>" . _('Home') . "</div>" ;
									print "</div>" ;
									
									print "<h2>" ;
									print _("Welcome") ;
									print "</h2>" ;
									print "<p>" ;
									print $_SESSION[$guid]["indexText"] ;
									print "</p>" ;
									
									//Public applications permitted?
									try {
										$sqlIntro="SELECT * FROM gibbonSetting WHERE scope='Application Form' AND name='publicApplications'" ;
										$resultIntro=$connection2->query($sqlIntro);  
										if (count($resultIntro)==1) {
											$rowIntro=$resultIntro->fetch() ;
											if ($rowIntro["value"]=="Y") {
												print "<h2 style='margin-top: 30px'>" ;
												print _("Applications") ;
												print "</h2>" ;
												print "<p>" ;
												print sprintf(_('Parents of students interested in study at %1$s may use our %2$s online form%3$s to initiate the application process.'), $_SESSION[$guid]["organisationName"], "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/?q=/modules/Application Form/applicationForm.php'>", "</a>") ;
												print "</p>" ;
											}
										}
									}
									catch(PDOException $e) {
										print "<div class='error'>" ;
											print $e->getMessage();
										print "</div>" ;
									}
								}
								else {
									print "<div class='trail'>" ;
									print "<div class='trailEnd'>" . _('Home') . "</div>" ;
									print "</div>" ;
									
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
													$sqlChild="SELECT gibbonPerson.gibbonPersonID, image_75, surname, preferredName, dateStart, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, gibbonRollGroup.gibbonRollGroupID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName " ;
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
													$count++ ;
												}
											}
										}
										
										if ($count>0) {
											print "<h2>" ;
												print _("Parental Dashboard") ;
											print "</h2>" ;
											$alert=getAlert($connection2, 002) ;
											$entryCount=0 ;
											
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
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=" . $students[$i][7] . "'>" . _('Roll Group') . " (" . $students[$i][3] . ")</a>" ;
														}
													print "</span>" ;
												print "</div>" ;
												print "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 83%'>" ;
													//Display planner
													print "<span style='font-size: 85%; font-weight: bold'>" . _('Today\'s Classes') . "</span> . <span style='font-size: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&search=" . $students[$i][4] . "'>" . _('View Planner') . "</a></span>" ;
													
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
													
													$classes=FALSE ;
													$date=date("Y-m-d") ;
													if (isSchoolOpen($guid, $date, $connection2)==TRUE AND isActionAccessible($guid, $connection2, "/modules/Planner/planner.php") AND $_SESSION[$guid]["username"]!="") {			
														try {
															$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "date"=>$date, "gibbonPersonID"=>$students[$i][4], "date2"=>$date, "gibbonPersonID2"=>$students[$i][4]); 
															$sql="(SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, date, summary FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, date, summary FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart" ; 
															$result=$connection2->prepare($sql);
															$result->execute($data); 
														}
														catch(PDOException $e) { 
															print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														}
														if ($result->rowCount()>0) {
															$classes=TRUE ;
															print "<table cellspacing='0' style='margin: 3px 0px; width: 100%'>" ;
																print "<tr class='head'>" ;
																	print "<th>" ;
																		print _("Class") ;
																	print "</th>" ;
																	print "<th>" ;
																		print _("Lesson") ;
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
																
																$count2=0;
																$rowNum="odd" ;
																while ($row=$result->fetch()) {
																	if ($count2%2==0) {
																		$rowNum="even" ;
																	}
																	else {
																		$rowNum="odd" ;
																	}
																	$count2++ ;
																	
																	//Highlight class in progress
																	if ((date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"]) AND ($date)==date("Y-m-d")) {
																		$rowNum="current" ;
																	}
																	
																	//COLOR ROW BY STATUS!
																	print "<tr class=$rowNum>" ;
																		print "<td>" ;
																			print $row["course"] . "." . $row["class"] ;
																		print "</td>" ;
																		print "<td>" ;
																			print "<b>" . $row["name"] . "</b><br/>" ;
																			$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
																			if (isset($unit[0])) {
																				print $unit[0] ;
																				if ($unit[1]!="") {
																					print "<br/><i>" . $unit[1] . " " . _('Unit') . "</i>" ;
																				}
																			}
																		print "</td>" ;
																		print "<td>" ;
																			print $row["homework"] ;
																		print "</td>" ;
																		print "<td>" ;
																			print $row["summary"] ;
																		print "</td>" ;
																		print "<td>" ;
																			try {
																				$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"],"gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
																				$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
																				$resultLike=$connection2->prepare($sqlLike);
																				$resultLike->execute($dataLike); 
																			}
																			catch(PDOException $e) { 
																				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																			}
																			if ($resultLike->rowCount()!=1) {
																				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=/modules/Planner/planner.php&viewBy=date&date=$date&gibbonPersonID=" . $students[$i][4] . "&returnToIndex=Y'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
																			}
																			else {
																				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=/modules/Planner/planner.php&viewBy=date&date=$date&gibbonPersonID=" . $students[$i][4] . "&returnToIndex=Y'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
																			}
																		print "</td>" ;
																		print "<td>" ;
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=" . $students[$i][4] . "&viewBy=date&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&date=$date&width=1000&height=550'><img title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
																		print "</td>" ;
																	print "</tr>" ;
																}
															print "</table>" ;
														}
													}
													if ($classes==FALSE) {
														print "<div style='margin-top: 2px' class='warning'>" ;
														print _("There are no records to display.") ;
														print "</div>" ;
													}
													
													//Display recent grades
													print "<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>" . _('Recent Grades') . "</span> . <span style='font-size: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php&search=" . $students[$i][4] . "'>" . _('View Markbook') . "</a></span></div>" ;
													$grades=FALSE ;
													
													try {
														$dataEntry=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"],"gibbonPersonID"=>$students[$i][4]); 
														$sqlEntry="SELECT *, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND viewableParents='Y' ORDER BY completeDate DESC LIMIT 0, 3" ;
														$resultEntry=$connection2->prepare($sqlEntry);
														$resultEntry->execute($dataEntry); 
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													if ($resultEntry->rowCount()>0) {
														$showParentAttainmentWarning=getSettingByScope($connection2, "Markbook", "showParentAttainmentWarning" ) ; 
														$showParentEffortWarning=getSettingByScope($connection2, "Markbook", "showParentEffortWarning" ) ; 
														$grades=TRUE ;
														print "<table cellspacing='0' style='margin: 3px 0px; width: 100%'>" ;
															print "<tr class='head'>" ;
															print "<th style='width: 120px'>" ;
																print _("Assessment") ;
															print "</th>" ;
															print "<th style='width: 75px'>" ;
																print _("Attainment") ;
															print "</th>" ;
															print "<th style='width: 75px'>" ;
																print _("Effort") ;
															print "</th>" ;
															print "<th>" ;
																print _("Comment") ;
															print "</th>" ;
															print "<th style='width: 75px'>" ;
																print _("Submission") ;
															print "</th>" ;
														print "</tr>" ;
														
														$count3=0 ;
														while ($rowEntry=$resultEntry->fetch()) {
															if ($count3%2==0) {
																$rowNum="even" ;
															}
															else {
																$rowNum="odd" ;
															}
															$count3++ ;
															
															print "<a name='" . $rowEntry["gibbonMarkbookEntryID"] . "'></a>" ;
										
															print "<tr class=$rowNum>" ;
																print "<td>" ;
																	print "<span title='" . htmlPrep($rowEntry["description"]) . "'>" . $rowEntry["name"] . "</span><br/>" ;
																	print "<span style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
																	print _("Marked on") . " " . dateConvertBack($guid, $rowEntry["completeDate"]) . "<br/>" ;
																	print "</span>" ;
																print "</td>" ;
																print "<td style='text-align: center'>" ;
																	$attainmentExtra="" ;
																	try {
																		$dataAttainment=array("gibbonScaleID"=>$rowEntry["gibbonScaleIDAttainment"]); 
																		$sqlAttainment="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
																		$resultAttainment=$connection2->prepare($sqlAttainment);
																		$resultAttainment->execute($dataAttainment);
																	}
																	catch(PDOException $e) { }
																	if ($resultAttainment->rowCount()==1) {
																		$rowAttainment=$resultAttainment->fetch() ;
																		$attainmentExtra="<br/>" . $rowAttainment["usage"] ;
																	}
																	$styleAttainment="style='font-weight: bold'" ;
																	if ($rowEntry["attainmentConcern"]=="Y" AND $showParentAttainmentWarning=="Y") {
																		$styleAttainment="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
																	}	
																	else if ($rowEntry["attainmentConcern"]=="P" AND $showParentAttainmentWarning=="Y") {
																		$styleAttainment="style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'" ;
																	}
																	print "<div $styleAttainment>" . $rowEntry["attainmentValue"] ;
																		if ($rowEntry["gibbonRubricIDAttainment"]!="") {
																			print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDAttainment"] . "&gibbonCourseClassID=" . $rowEntry["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=" . $students[$i][4] . "&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
																		}
																	print "</div>" ;
																	if ($rowEntry["attainmentValue"]!="") {
																		print "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep($rowEntry["attainmentDescriptor"]) . "</b>" . $attainmentExtra . "</div>" ;
																	}
																print "</td>" ;
																print "<td style='text-align: center'>" ;
																	$effortExtra="" ;
																	try {
																		$dataEffort=array("gibbonScaleID"=>$rowEntry["gibbonScaleIDEffort"]); 
																		$sqlEffort="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
																		$resultEffort=$connection2->prepare($sqlEffort);
																		$resultEffort->execute($dataEffort); 
																	}
																	catch(PDOException $e) { }
																	if ($resultEffort->rowCount()==1) {
																		$rowEffort=$resultEffort->fetch() ;
																		$effortExtra="<br/>" . $rowEffort["usage"] ;
																	}
																	$styleEffort="style='font-weight: bold'" ;
																	if ($rowEntry["effortConcern"]=="Y" AND $showParentEffortWarning=="Y") {
																		$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
																	}
																	print "<div $styleEffort>" . $rowEntry["effortValue"] ;
																		if ($rowEntry["gibbonRubricIDEffort"]!="") {
																			print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDEffort"] . "&gibbonCourseClassID=" . $rowEntry["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=" . $students[$i][4] . "&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
																		}
																	print "</div>" ;
																	if ($rowEntry["effortValue"]!="") {
																		print "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep($rowEntry["effortDescriptor"]) . "</b>" . $effortExtra . "</div>" ;
																	}
																print "</td>" ;
																print "<td>" ;
																	if ($rowEntry["comment"]!="") {
																		if (strlen($rowEntry["comment"])>50) {
																			print "<script type='text/javascript'>" ;	
																				print "$(document).ready(function(){" ;
																					print "\$(\".comment-$entryCount\").hide();" ;
																					print "\$(\".show_hide-$entryCount\").fadeIn(1000);" ;
																					print "\$(\".show_hide-$entryCount\").click(function(){" ;
																					print "\$(\".comment-$entryCount\").fadeToggle(1000);" ;
																					print "});" ;
																				print "});" ;
																			print "</script>" ;
																			print "<span>" . substr($rowEntry["comment"], 0, 50) . "...<br/>" ;
																			print "<a title='" . _('View Description') . "' class='show_hide-$entryCount' onclick='return false;' href='#'>" . _('Read more') . "</a></span><br/>" ;
																		}
																		else {
																			print $rowEntry["comment"] ;
																		}
																		if ($rowEntry["response"]!="") {
																			print "<a title='" . _('Uploaded Response') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>" . _('Uploaded Response') . "</a><br/>" ;
																		}
																	}
																print "</td>" ;
																print "<td>" ;
																	if ($rowEntry["gibbonPlannerEntryID"]!="") {
																		try {
																			$dataSub=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"]); 
																			$sqlSub="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'" ;
																			$resultSub=$connection2->prepare($sqlSub);
																			$resultSub->execute($dataSub); 
																		}
																		catch(PDOException $e) { }
																		if ($resultSub->rowCount()==1) {
																			$rowSub=$resultSub->fetch() ;
																			try {
																				$dataWork=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"],"gibbonPersonID"=>$students[$i][4]); 
																				$sqlWork="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
																				$resultWork=$connection2->prepare($sqlWork);
																				$resultWork->execute($dataWork);
																			}
																			catch(PDOException $e) { }
																			if ($resultWork->rowCount()>0) {
																				$rowWork=$resultWork->fetch() ;
																				
																				if ($rowWork["status"]=="Exemption" OR $rowWork["version"]=="Final") {
																					$linkText=substr(_($rowWork["version"]),0,2) ;
																				}
																				else {
																					$linkText=substr(_("Draft"),0,2) ;
																				}
																				
																				$style="" ;
																				$status=_("On Time") ;
																				if ($rowWork["status"]=="Exemption") {
																					$status=_("Exemption") ;
																				}
																				else if ($rowWork["status"]=="Late") {
																					$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
																					$status=_("Late") ;
																				}
																				
																				if ($rowWork["type"]=="File") {
																					print "<span title='" . $rowWork["version"] . ". $status. " . sprintf(_('Submitted at %1$s on %2$s'),substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["location"] ."'>$linkText</a></span>" ;
																				}
																				else if ($rowWork["type"]=="Link") {
																					print "<span title='" . $rowWork["version"] . ". $status. " . sprintf(_('Submitted at %1$s on %2$s'),substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style><a target='_blank' href='" . $rowWork["location"] ."'>$linkText</a></span>" ;
																				}
																				else {
																					print "<span title='$status. " . sprintf(_('Recorded at %1$s on %2$s'),substr($rowWork["timestamp"],11,5), dateConvertBack($guid, substr($rowWork["timestamp"],0,10))) . "' $style>$linkText</span>" ;
																				}
																			}
																			else {
																				if (date("Y-m-d H:i:s")<$homeworkDueDateTime[$i]) {
																					print "<span title='" . _('Pending') . "'>" . _('Pending') . "</span>" ;
																				}
																				else {
																					if ($students[$i][6]>$rowSub["date"]) {
																						print "<span title='" . _('Student joined school after assessment was given.') . "' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>" . _("NA") . "</span>" ;
																					}
																					else {
																						if ($rowSub["homeworkSubmissionRequired"]=="Compulsory") {
																							print "<span title='" . _('Incomplete') . "' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>" . _('Incomplete') . "</span>" ;
																						}
																						else {
																							print _("Not submitted online") ;
																						}
																					}
																				}	
																			}
																		}
																	}
																print "</td>" ;
															print "</tr>" ;
															if (strlen($rowEntry["comment"])>50) {
																print "<tr class='comment-$entryCount' id='comment-$entryCount'>" ;
																	print "<td colspan=6>" ;
																		print $rowEntry["comment"] ;
																	print "</td>" ;
																print "</tr>" ;
															}
															$entryCount++ ;
														}
														
														print "</table>" ;
													}
													if ($grades==FALSE) {
														print "<div style='margin-top: 2px' class='warning'>" ;
														print _("There are no records to display.") ;
														print "</div>" ;
													}
													
													//Display upcoming deadlines
													print "<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>" . _('Upcoming Deadlines') . "</span> . <span style='font-size: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php&search=" . $students[$i][4] . "'>". _('View All Deadlines') . "</a></span></div>" ;
													$deadlines=FALSE ;
													
													try {
														$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"],"gibbonPersonID"=>$students[$i][4],"dateTime"=>date("Y-m-d H:i:s"),"date"=>date("Y-m-d"),"date2"=>date("Y-m-d"),"time"=>date("H:i:s")); 
														$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND role='Student' AND viewableParents='Y' AND homeworkDueDateTime>:dateTime AND ((date<:date) OR (date=:date2 AND timeEnd<=:time)) ORDER BY homeworkDueDateTime LIMIT 0, 3" ;
														$result=$connection2->prepare($sql);
														$result->execute($data);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
	
													if ($result->rowCount()>0) {
														$deadlines=TRUE ;
														print "<ol style='margin-left: 15px'>" ;
														while ($row=$result->fetch()) {
															$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
															$style="style='padding-right: 3px;'" ;
															if ($diff<2) {
																$style="style='padding-right: 3px; border-right: 10px solid #cc0000'" ;	
															}
															else if ($diff<4) {
																$style="style='padding-right: 3px; border-right: 10px solid #D87718'" ;	
															}
															print "<li $style>" ;
															if ($viewBy=="class") {
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=" . $students[$i][4] . "&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550'>" . $row["course"] . "." . $row["class"] . "</a> " ;
															}
															else {
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=" . $students[$i][4] . "&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=date&date=$date&width=1000&height=550'>" . $row["course"] . "." . $row["class"] . "</a> " ;
															}
															print "<span style='font-style: italic'>" . sprintf(_('Due at %1$s on %2$s'), substr($row["homeworkDueDateTime"],11,5), dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10))) ;
															print "</li>" ;
														}
														print "</ol>" ;
													}
													
													if ($deadlines==FALSE) {
														print "<div style='margin-top: 2px' class='warning'>" ;
														print _("There are no records to display.") ;
														print "</div>" ;
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
												$sql="(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess,  role, date, summary FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart, course, class" ; 
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
																	if ($row["homework"]=="Y") {
																		print _("Yes") ;
																	}
																	else {
																		print _("No") ;
																	}
																	if ($row["homeworkSubmission"]=="Y") {
																		print "<br/>+" . _("Submission") ;
																		if ($row["homeworkCrowdAssess"]=="Y") {
																			print "<br/>+" . _("Crowd Assessment") ;
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
																	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "'><img title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a>" ;
																print "</td>" ;
															print "</tr>" ;
														}
													}
												print "</table>" ;
											}
										}
										
										//Display TT
										if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt.php") AND $_SESSION[$guid]["username"]!="") {			
											?>
											<script type="text/javascript">
												$(document).ready(function(){
													$("#tt").load("<?php print $_SESSION[$guid]["absoluteURL"] ?>/index_tt_ajax.php",{"ttDate": "<?php print @$_POST["ttDate"] ?>", "fromTT": "<?php print @$_POST["fromTT"] ?>", "personalCalendar": "<?php print @$_POST["personalCalendar"] ?>", "schoolCalendar": "<?php print @$_POST["schoolCalendar"] ?>"});
												});
											</script>
											<?php
											try {
												$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
												$sql="SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' " ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) { }
											if ($result->rowCount()>0) {
												print "<h2>" . _("My Timetable") . "</h2>" ;
												print "<div id='tt' name='tt' style='width: 100%; min-height: 40px; text-align: center'>" ;
													print "<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='" . _('Loading') . "' onclick='return false;' /><br/><p style='text-align: center'>" . _('Loading') . "</p>" ;
												print "</div>" ;
											}
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
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "'><img title='" . _('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.gif'/></a> " ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/indexExport.php?gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "'><img title='" . _('Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
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
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage_add.php&gibbonPersonID=&gibbonRollGroupID=&gibbonYearGroupID=&type='><img style='margin: 0 0 -4px 3px' title='" . _('Add New Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
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
						<?php print _("Powered by") ?> <a href="http://gibbonedu.org">Gibbon</a> v<?php print $version ?> &#169; <a href="http://rossparker.org">Ross Parker</a> 2010-<?php print date("Y") ?><br/>
						<span style='font-size: 90%; '><?php print _("Created under the") ?> <a href="http://www.gnu.org/licenses/gpl.html">GNU GPL</a> at <a href='http://www.ichk.edu.hk'>ICHK</a></span><br/>
						<img style='z-index: 100; margin-bottom: -57px; margin-right: -50px' alt='Logo Small' src='./themes/Default/img/logoFooter.png'/>
					</div>
				</div>
			</div>
		</body>
	</html>
	<?php
}
?>